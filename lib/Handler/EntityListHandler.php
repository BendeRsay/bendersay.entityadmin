<?php

namespace Bendersay\Entityadmin\Handler;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Localization\Loc;
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
        $elemId = $this->request->get('id');
        if (empty($elemId) || $this->request->get('delete') === null) {
            return $this;
        }

        if (!check_bitrix_sessid()) {
            $this->errorList[] = 'access denied';

            return $this;
        }

        try {
            $result = $this->entityClass::delete($elemId);
            if (!$result->isSuccess()) {
                $this->errorList = $result->getErrorMessages();
            }
            LocalRedirect(EntityHelper::getListUrl(['entity' => $this->entityClass]));
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
     * Есть ли ошибка в кеше?
     *
     * @return bool
     */
    public function isError(): bool
    {
        return (bool)$this->localSession->get('error');
    }

    /**
     * Возвращаем ошибку, удаляем из кеша
     *
     * @return string
     */
    public function getError(): string
    {
        $error = $this->localSession->get('error');
        $this->localSession->clear();

        return $error;
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
                $result = $this->entityClass::delete($id);
                if (!$result->isSuccess()) {
                    foreach ($result->getErrors() as $error) {
                        $this->errorList[] = [
                            'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_TITLE_DELETE', [
                                '#primaryCode#' => $this->primaryCode,
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
                        '#primaryCode#' => $this->primaryCode,
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
                $result = $this->entityClass::update($id, $preparedUpdateFieldList);
                if (!$result->isSuccess()) {
                    foreach ($result->getErrors() as $error) {
                        $this->errorList[] = [
                            'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_TITLE_EDIT', [
                                '#primaryCode#' => $this->primaryCode,
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
                        '#primaryCode#' => $this->primaryCode,
                        '#id#' => $id,
                    ]),
                    'TEXT' => $e->getMessage(),
                    'TYPE' => 'ERROR',
                ];
            }
        }
    }
}
