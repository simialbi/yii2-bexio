<?php

namespace simialbi\bexio\models;

use yii\base\Model;

/**
 * @property int $id
 * @property string $name
 */
class Title extends Model
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
