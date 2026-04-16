<?php

namespace simialbi\bexio\models;

use yii\base\Model;

/**
 * @property int $id
 * @property string $name
 */
class Salutation extends Model
{
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
