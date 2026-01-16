<?php

namespace Bendersay\Entityadmin\Handler;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Grid\Export\ExcelExporter;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\Web\Uri;

/**
 * Обработчик списка элементов сущности
 */
class EntityListHandler extends AbstractEntityHandler
{
    /**
     * Обработка GET запроса
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function processGet(): self
    {
        $idDelete = $this->request->get('id');
        $excelExporter = new ExcelExporter();

        try {
            // удаление элемента
            if (!empty($idDelete) || $this->request->get('delete') === 'Y') {
                $result = $this->entityClass::delete($this->preparedId($idDelete));
                if (!$result->isSuccess()) {
                    $this->errorList = $result->getErrorMessages();
                } else {
                    LocalRedirect(EntityHelper::getListUrl(['entity' => $this->entityClass]));
                }
            }

            // экспорт Excel
            if ($excelExporter->isExportRequest()) {
                $excelExporter->process($this->getDinamicClass());
            }
        } catch (\Exception $e) {
            Application::getInstance()->getExceptionHandler()->writeToLog(
                $e,
                ExceptionHandlerLog::CAUGHT_EXCEPTION
            );
            $this->errorList = [$e->getMessage()];
        }

        return $this;
    }

    /**
     * Обработка POST запроса
     *
     * @return $this
     *
     * @throws NotSupportedException
     */
    public function processPost(): self
    {
        if (!$this->checkPost()) {
            return $this;
        }

        if ($this->request->getPost('action_button_' . $this->manager->getGridId()) === 'delete') {
            $this->postActionDelete();
        }
        if ($this->request->getPost('action_button_' . $this->manager->getGridId()) === 'edit') {
            $this->postActionEdit();
        }

        return $this;
    }

    /**
     * Проверка наличия ошибок.
     *  Отправка JSON ответа для POST.
     *  Сохранение ошибки в кеш для GET.
     *
     * @return void
     *
     */
    public function processFinish(): void
    {
        $this->processFinishCommon();

        if (!empty($this->errorList)) {
            if ($this->request->isPost()) {
                (new Json(['messages' => $this->errorList]))->send();
            } else {
                $this->localSession->set('error', implode("\n", $this->errorList));

                $uri = new Uri($this->request->getRequestUri());
                $uri->deleteParams(self::GET_DELETE_PARAM_NAME);
                LocalRedirect($uri->getUri());
            }
        }
    }

    /**
     * Удаляем элементы, через POST
     *
     * @return void
     */
    protected function postActionDelete(): void
    {
        foreach ($this->request->getPost('ID') as $id) {
            try {
                $result = $this->entityClass::delete($this->preparedId($id));
                if (!$result->isSuccess()) {
                    foreach ($result->getErrors() as $error) {
                        $this->errorList[] = [
                            'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_TITLE_DELETE', [
                                '#primaryCode#' => implode(',', $this->primaryFieldList),
                                '#id#' => $id,
                            ]),
                            'TEXT' => $error->getMessage(),
                            'TYPE' => 'ERROR',
                        ];
                    }
                }
            } catch (\Exception $e) {
                Application::getInstance()->getExceptionHandler()->writeToLog(
                    $e,
                    ExceptionHandlerLog::CAUGHT_EXCEPTION
                );
                $this->errorList[] = [
                    'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_TITLE_DELETE', [
                        '#primaryCode#' => implode(',', $this->primaryFieldList),
                        '#id#' => $id,
                    ]),
                    'TEXT' => $e->getMessage(),
                    'TYPE' => 'ERROR',
                ];
            }
        }
    }

    /**
     * Обработка POST запроса при редактировании
     *
     * @return void
     */
    protected function postActionEdit(): void
    {
        $postFieldList = $this->request->getPost('FIELDS');
        if (empty($postFieldList) || !is_array($postFieldList)) {
            return;
        }

        $scalarFieldList = [];
        foreach ($this->manager->getFieldList() as $field) {
            if ($field instanceof ScalarField) {
                $scalarFieldList[$field->getName()] = $field;
            }
        }

        foreach ($postFieldList as $id => $elementField) {
            try {
                $preparedUpdateFieldList = $this->getPreparedUpdateFieldList($elementField, $scalarFieldList);
                $result = $this->entityClass::update($this->preparedId($id), $preparedUpdateFieldList);
                if (!$result->isSuccess()) {
                    foreach ($result->getErrors() as $error) {
                        $this->errorList[] = [
                            'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_TITLE_EDIT', [
                                '#primaryCode#' => implode(',', $this->primaryFieldList),
                                '#id#' => $id,
                            ]),
                            'TEXT' => $error->getMessage(),
                            'TYPE' => 'ERROR',
                        ];
                    }
                }
                unset($result);
            } catch (\Exception $e) {
                Application::getInstance()->getExceptionHandler()->writeToLog(
                    $e,
                    ExceptionHandlerLog::CAUGHT_EXCEPTION
                );
                $this->errorList[] = [
                    'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_TITLE_EDIT', [
                        '#primaryCode#' => implode(',', $this->primaryFieldList),
                        '#id#' => $id,
                    ]),
                    'TEXT' => $e->getMessage(),
                    'TYPE' => 'ERROR',
                ];
            }
        }
    }

    /**
     * Подготавливаем id для использования
     *
     * @param string $id
     *
     * @return array
     */
    protected function preparedId(string $id): array
    {
        $idList = [];
        parse_str($id, $idList);

        return $idList;
    }

    /**
     * Создаем динамический класс грида для выгрузки в Excel
     *
     * @return Grid
     *
     * @throws ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getDinamicClass(): Grid
    {
        $settings = new Settings([
            'ID' => $this->manager->getGridId(),
        ]);
        $shortClassName = basename(str_replace('\\', '/', $this->entityClass));
        $classGrid = $shortClassName . 'Grid';

        $classDefinition = '
            final class ' . $classGrid . ' extends \Bitrix\Main\Grid\TabletGrid {
                protected function getTabletClass(): string
	            {
    	            return ' . $this->entityClass . '::class;
	            }
            }
        ';

        eval($classDefinition);

        $objectGrid = new $classGrid($settings);
        /* @var $objectGrid Grid */
        $objectGrid->setRawRows($this->manager->getElementList());

        return $objectGrid;
    }
}
