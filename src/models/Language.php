<?php

namespace simialbi\bexio\models;

use yii\base\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $decimal_point
 * @property string $thousands_separator
 * @property int $date_format_id
 * @property string $date_format
 * @property string $iso_639_1
 */
class Language extends Model
{
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
