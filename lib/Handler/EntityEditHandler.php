<?php

namespace Bendersay\Entityadmin\Handler;

use Bendersay\Entityadmin\EntityEditManager;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\Web\Uri;

/**
 * Обработчик элемента сущности
 */
class EntityEditHandler extends AbstractEntityHandler
{
    /** @var null|string id элемента с которым работаем, может быть строкой или числом */
    protected ?string $elementId;

    /**
     * Обработка GET запроса
     *
     * @return $this
     *
     */
    public function processGet(): self
    {
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

        $this->elementId = $this->request->get('id');
        $actionAdd = $this->request->get('add') === 'Y';

        if (empty($this->elementId) && !$actionAdd) {
            $this->errorList[] = Loc::getMessage('BENDERSAY_ENTITYADMIN_ERROR_DELETE_ID_TEXT', [
                '#primaryCode#' => $this->primaryCode,
                '#id#' => $this->elementId,
            ]);

            return $this;
        }

        if ($this->request->getPost('delete') === 'Y') {
            $this->postActionDelete();
        }
        if ($this->request->getPost('save') === Loc::getMessage('BENDERSAY_ENTITYADMIN_ACTION_SAVE')
            || $this->request->getPost('apply') === Loc::getMessage('BENDERSAY_ENTITYADMIN_ACTION_APPLY')) {
            $this->postActionEdit($actionAdd);
        }

        return $this;
    }

    /**
     * Проверка наличия ошибок.
     * Сохранение ошибки в кеш.
     * Сохранение POST данных в кеш.
     * Перенаправление на текущий урл.
     *
     * Если ошибок нет, редирект на список при add, на деталку при edit.
     *
     * @return void
     *
     */
    public function processFinish(): void
    {
        $uri = new Uri($this->request->getRequestUri());

        if (!empty($this->errorList)) {
            $this->localSession->set('error', $this->errorList);
            $localSessionEntityEditManager = Application::getInstance()->getLocalSession(EntityEditManager::class);
            $postFieldList = $this->request->getPost('FIELDS');
            $localSessionEntityEditManager->set('postFieldList', $postFieldList);

            LocalRedirect($uri->getUri());
        }

        if ($this->request->getPost('save') === Loc::getMessage('BENDERSAY_ENTITYADMIN_ACTION_SAVE')) {
            LocalRedirect(EntityHelper::getListUrl(['entity' => $this->entityClass]));
        }
        if ($this->request->getPost('apply') === Loc::getMessage('BENDERSAY_ENTITYADMIN_ACTION_APPLY')) {
            $uri->deleteParams(['add']);
            $uri->addParams(['id' => $this->elementId]);
            LocalRedirect($uri->getUri());
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
     * Возвращаем ошибки, удаляем из кеша
     *
     * @return array
     */
    public function getError(): array
    {
        $error = $this->localSession->get('error');
        $this->localSession->clear();

        return $error;
    }

    /**
     * Удаляем элемент, через POST
     *
     * @return void
     */
    protected function postActionDelete(): void
    {
        try {
            $result = $this->entityClass::delete($this->elementId);
            if (!$result->isSuccess()) {
                $this->errorList[] = array_merge($this->errorList, $result->getErrorMessages());
            } else {
                LocalRedirect(EntityHelper::getListUrl(['entity' => $this->entityClass]));
            }
        } catch (\Exception $e) {
            Application::getInstance()->getExceptionHandler()->writeToLog(
                $e,
                ExceptionHandlerLog::CAUGHT_EXCEPTION
            );
            $this->errorList[] = $e->getMessage();
        }
    }

    /**
     * Обработка POST запроса при редактировании/создании элемента
     *
     * @param bool $actionAdd Создание элемента?
     *
     * @return void
     */
    protected function postActionEdit(bool $actionAdd): void
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

        $preparedUpdateFieldList = $this->getPreparedUpdateFieldList($postFieldList, $scalarFieldList);

        try {
            if ($actionAdd) {
                $result = $this->entityClass::add($preparedUpdateFieldList);
            } else {
                $result = $this->entityClass::update($this->elementId, $preparedUpdateFieldList);
            }

            if (!$result->isSuccess()) {
                foreach ($result->getErrors() as $error) {
                    $this->errorList[] = $error->getMessage();
                }
            } else {
                $this->elementId = $result->getId();
            }
            unset($result);
        } catch (\Exception $e) {
            Application::getInstance()->getExceptionHandler()->writeToLog(
                $e,
                ExceptionHandlerLog::CAUGHT_EXCEPTION
            );
            $this->errorList[] = $e->getMessage();
        }
    }

}