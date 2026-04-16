<?php

namespace simialbi\bexio\models;

/**
 * @property int $id
 * @property string $name
 * @property string $name_short
 * @property string $iso3166_alpha2
 */
class Country extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function primaryKey(): array
    {
        return['id'];
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['id', 'integer'],
            ['name', 'string', 'max' => 255],
            [['name_short', 'iso3166_alpha2'], 'string', 'max' => 2],

            [['name', 'name_short', 'iso3166_alpha2'], 'required']
        ];
    }
}
