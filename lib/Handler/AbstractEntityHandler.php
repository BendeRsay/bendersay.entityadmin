<?php

namespace Bendersay\Entityadmin\Handler;

use Bendersay\Entityadmin\EntityEditManager;
use Bendersay\Entityadmin\EntityListManager;
use Bendersay\Entityadmin\Install\Config;
use Bitrix\Main\Application;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorage;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\Type\DateTime;

class AbstractEntityHandler
{
    /** @var array Имена GET параметров, которые нужно удалить из урла */
    protected const array GET_DELETE_PARAM_NAME = ['delete', 'id', 'sessid'];

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

    /** @var string Код primary поля */
    protected string $primaryCode;

    /**
     * Сессионный кеш
     *
     * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=14018&LESSON_PATH=3913.3435.4816.14028.14018
     *
     * @var SessionLocalStorage
     */
    protected SessionLocalStorage $localSession;

    public function __construct(EntityListManager|EntityEditManager $manager)
    {
        $this->manager = $manager;
        $this->request = $this->manager->getRequest();
        $this->entityClass = $this->manager->getEntityClass();
        $this->primaryCode = $this->entityClass::getEntity()->getPrimary();
        $this->modRight = \CMain::GetGroupRight(Config::MODULE_CODE);
        $this->localSession = Application::getInstance()->getLocalSession(self::class);

        if ($this->modRight < 'W') {
            $this->errorList[] = 'access denied';
        }
    }

    /**
     * Проверка POST запроса
     *
     * @return bool
     */
    protected function checkPost(): bool
    {
        if (!$this->request->isPost()) {
            return false;
        }

        if (!check_bitrix_sessid()) {
            $this->errorList[] = 'access denied';

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

            // TODO поддержка поля ArrayField
            //            if ($field instanceof ArrayField) {
            //                try {
            //                    if (!isset($elementField[$fieldCode])) {
            //                        continue;
            //                    }
            //
            //                    $elementField[$fieldCode] = !empty($elementField[$fieldCode])
            //                        ? $field->decode($elementField[$fieldCode])
            //                        : ($field->isNullable() ? null : $field->getDefaultValue());
            //                } catch (\Throwable $ex) {
            //                    $errorMessage = "Ошибка \"{$ex->getMessage()}\" в поле {$fieldCode}";
            //                    $result = new AddResult();
            //                    $result->setId($this->request->get('id'));
            //                    $result->addError(new Error($errorMessage, $ex->getCode()));
            //                }
            //            }
        }

        return $elementField;
    }
}
