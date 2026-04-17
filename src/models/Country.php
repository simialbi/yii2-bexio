<?php

namespace simialbi\bexio\models;

/**
 */
class Country extends Model
{
    public ?int $id;
    public string $name;
    public string $name_short;
    public string $iso3166_alpha2;

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
