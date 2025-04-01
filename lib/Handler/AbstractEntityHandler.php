<?php

namespace Bendersay\Entityadmin\Handler;

use Bendersay\Entityadmin\EntityEditManager;
use Bendersay\Entityadmin\EntityListManager;
use Bendersay\Entityadmin\Enum\AccessLevelEnum;
use Bendersay\Entityadmin\Enum\CodeExceptionEnum;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorage;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class AbstractEntityHandler
{
    /** @var array Имена GET параметров, которые нужно удалить из урла */
    protected const array GET_DELETE_PARAM_NAME = ['delete', 'id'];

    /** @var EntityListManager|EntityEditManager Менеджер для работы со списком элементов в компоненте main.ui.grid или для работы с деталкой */
    protected EntityListManager|EntityEditManager $manager;

    /** @var HttpRequest */
    protected HttpRequest $request;

    /** @var DataManager|string Класс сущности с которой работаем */
    protected DataManager|string $entityClass;

    /** @var array Массив ошибок */
    protected array $errorList = [];

    /** @var mixed|false|string|null Права пользователя на модуль */
    protected mixed $modRight;

    /** @var array Primary поля */
    protected array $primaryFieldList;

    /**
     * Сессионный кеш
     *
     * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=14018&LESSON_PATH=3913.3435.4816.14028.14018
     *
     * @var SessionLocalStorage
     */
    protected SessionLocalStorage $localSession;

    /**
     * @param EntityListManager|EntityEditManager $manager
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function __construct(EntityListManager|EntityEditManager $manager)
    {
        $this->manager = $manager;
        $this->request = $this->manager->getRequest();
        $this->entityClass = $this->manager->getEntityClass();
        $this->primaryFieldList = $this->entityClass::getEntity()->getPrimaryArray();
        $this->modRight = EntityHelper::getGroupRight($this->entityClass);
        $this->localSession = Application::getInstance()->getLocalSession(self::class);

        if (!check_bitrix_sessid()) {
            $this->errorList[CodeExceptionEnum::ACCESS_DENIED->name] = CodeExceptionEnum::getMessage(
                CodeExceptionEnum::ACCESS_DENIED->name
            );
        }
        if ($this->modRight === AccessLevelEnum::DENIED->value) {
            throw new SecurityException(
                CodeExceptionEnum::getMessage(
                    CodeExceptionEnum::ACCESS_DENIED->name
                )
            );
        }
    }

    /**
     * Проверка POST запроса
     * Права при POST у модуля должны быть W - запись
     * При запросе из фильтра и правах !== 'D' пропускаем
     *
     * @return bool
     *
     * @throws NotSupportedException
     */
    protected function checkPost(): bool
    {
        if (!$this->request->isPost()) {
            return false;
        }

        if ($this->modRight < AccessLevelEnum::WRITE->value) {
            if ($this->request->isAjaxRequest()) {
                $applyFilter = $this->request->get('apply_filter');
                if ($applyFilter === 'Y' && $this->modRight !== AccessLevelEnum::DENIED->value) {
                    return true;
                }
                $this->errorList[] = [
                    'TEXT' => CodeExceptionEnum::getMessage(
                        CodeExceptionEnum::ACCESS_DENIED->name
                    ),
                    'TYPE' => 'ERROR',
                ];
            } else {
                $this->errorList[] = CodeExceptionEnum::getMessage(CodeExceptionEnum::ACCESS_DENIED->name);
            }

            return false;
        }

        return true;
    }

    /**
     * Подготавливаем элемент к обновлению
     * Если поле Autocomplete или (пустое и принимает null) - пропускаем
     * Если пустое значение и у пля есть DefaultValue - сохраняем его
     * Для BooleanField в списке меняем Y, N на bool
     * Для DatetimeField приводим к DateTime
     * Для ArrayField из Json приводим к массиву
     *
     * @param array $elementField поля элемента
     * @param ScalarField[] $scalarFieldList список скалярных полей элемента
     *
     * @return array
     */
    protected function getPreparedUpdateFieldList(array $elementField, array $scalarFieldList): array
    {
        foreach ($elementField as $fieldCode => $fieldValue) {
            if (!isset($scalarFieldList[$fieldCode])) {
                continue;
            }
            $field = $scalarFieldList[$fieldCode];

            if ($field->isAutocomplete() === true) {
                unset($elementField[$fieldCode]);
            }
            if ($field->isNullable() && empty($fieldValue)) {
                $elementField[$fieldCode] = null;
            }

            $defaultValue = $field->getDefaultValue();
            if (empty($fieldValue) && $defaultValue !== null) {
                $elementField[$fieldCode] = $defaultValue;
            }

            if ($field instanceof BooleanField) {
                if (in_array(
                    mb_strtolower($elementField[$fieldCode]),
                    ['y', 'n'],
                    true
                )) {
                    $elementField[$fieldCode] = mb_strtolower($elementField[$fieldCode]) === 'y';
                } else {
                    $elementField[$fieldCode] = $field->booleanizeValue($fieldValue);
                }

                continue;
            }

            if ($field instanceof DatetimeField && !empty($fieldValue)) {
                try {
                    $elementField[$fieldCode] = new DateTime($fieldValue);
                } catch (ObjectException $e) {
                    // Пропускаем ошибку дальше в update(). Там она обработается
                }
            }

            if ($field instanceof ArrayField) {
                if (!isset($elementField[$fieldCode])) {
                    continue;
                }

                // Т.к. в виджете всегда показываем в Json, тут тоже преобразуем из него
                $reflection = new \ReflectionClass($field);
                $serializationType = $reflection->getProperty('serializationType')->getValue($field);
                $encodeFunction = $reflection->getProperty('encodeFunction')->getValue($field);
                $field->configureSerializationJson();

                $elementField[$fieldCode] = !empty($elementField[$fieldCode])
                    ? $field->decode($elementField[$fieldCode])
                    : ($field->isNullable() ? null : $field->getDefaultValue());

                switch ($serializationType) {
                    case 'json':
                        break;
                    case 'php':
                        $field->configureSerializationPhp();

                        break;
                    case 'custom':
                        $field->configureSerializeCallback($encodeFunction);

                        break;
                }
            }
        }

        return $elementField;
    }

    /**
     * Общая логика финальных операций
     *
     * @return void
     */
    protected function processFinishCommon(): void
    {
        global $APPLICATION;

        if (empty($this->errorList)) {
            return;
        }

        if (isset($this->errorList[CodeExceptionEnum::ACCESS_DENIED->name])
            && $this->request->getRequestMethod() === 'GET') {
            $APPLICATION->RestartBuffer();
            $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
        }
    }
}
