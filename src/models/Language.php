<?php

namespace simialbi\bexio\models;

/**
 */
class Language extends Model
{
    public ?int $id;
    public string $name;
    public string $decimal_point;
    public string $thousands_separator;
    public int $date_format_id;
    public string $date_format;
    public string $iso_639_1;

    public function rules(): array
    {
        return [
            [['id', 'date_format_id'], 'integer'],
            ['name', 'string', 'max' => 255],
            [['decimal_point', 'thousands_separator'], 'string', 'max' => 1],
            ['date_format', 'string', 'max' => 100],
            ['iso_639_1', 'string', 'length' => 2],

            [['name', 'decimal_point', 'thousands_separator', 'date_format', 'iso_639_1'], 'required']
        ];
    }
}
