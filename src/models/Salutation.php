<?php

namespace simialbi\bexio\models;

/**
 */
class Salutation extends Model
{
    public ?int $id;
    public string $name;

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
