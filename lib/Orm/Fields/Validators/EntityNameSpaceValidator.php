<?php

namespace Bendersay\Entityadmin\Orm\Fields\Validators;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\ORM;

/**
 * Валидатор namespace сущности.
 */
class EntityNameSpaceValidator extends ORM\Fields\Validators\Validator
{
    public function validate($value, $primary, array $row, ORM\Fields\Field $field)
    {
        if (EntityHelper::checkEntityExistence($value)) {
            return true;
        }

        return $this->getErrorMessage($value, $field);
    }
}
