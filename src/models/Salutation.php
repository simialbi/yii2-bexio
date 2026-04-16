<?php

namespace simialbi\bexio\models;

/**
 * @property int $id
 * @property string $name
 */
class Salutation extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function primaryKey(): array
    {
        return ['id'];
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],

            ['name', 'required']
        ];
    }
}
