<?php

namespace simialbi\bexio\models;

use simialbi\bexio\Module;
use simialbi\yii2\rest\Connection;

class ActiveRecord extends \simialbi\yii2\rest\ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function getDb(): ?Connection
    {
        return Module::getInstance()->connection;
    }
}
