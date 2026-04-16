<?php

namespace simialbi\bexio;

use simialbi\yii2\rest\Connection;
use yii\base\InvalidConfigException;
use yii\di\Instance;

class Module extends \yii\base\Module
{
    /**
     * @var Connection|array|string The Bexio API connection configuration.
     */
    public Connection|array|string $connection;

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        if (!isset($this->connection)) {
            throw new InvalidConfigException('Connection property must be set');
        }

        $this->connection = Instance::ensure($this->connection, Connection::class);

        parent::init();
    }
}
