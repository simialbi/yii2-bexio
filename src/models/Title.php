<?php

namespace simialbi\bexio\models;

/**
 */
class Title extends Model
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
