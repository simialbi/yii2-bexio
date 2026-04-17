<?php

namespace simialbi\bexio\models;

use simialbi\bexio\BexioException;
use simialbi\bexio\Module;
use Yii;
use yii\base\ModelEvent;
use yii\helpers\StringHelper;

class Model extends \yii\base\Model
{
    /**
     * @event ModelEvent an event that is triggered before deleting a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the deletion.
     */
    public const EVENT_BEFORE_DELETE = 'beforeDelete';
    /**
     * @event Event an event that is triggered after a record is deleted.
     */
    public const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * Saves the current record.
     *
     * This method will call [[create()]] when [[id]] is empty, or [[update()]]
     * when [[id]] is set.
     *
     * For example, to save a contact record:
     *
     * ```
     * $contact = new Contact;
     * $contact->name_1 = $name;
     * $contact->mail = $email;
     * $contact->save();
     * ```
     *
     * @return bool whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $method = (!empty($this->id))
            ? 'update' . StringHelper::basename(static::class)
            : 'create' . StringHelper::basename(static::class);
        $module = Module::getInstance();
        try {
            $model = $module->$method($this);
        } catch (BexioException $e) {
            Yii::error($e->getMessage());
            return false;
        }
        $this->setAttributes($model->getAttributes());
        return true;
    }

    /**
     * Deletes the model corresponding to this record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns `false`, it will skip the
     * rest of the steps;
     * 2. delete the record ;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return bool `true` if the model was succesfully deleted, or `false` if the deletion is unsuccessful for some reason.
     */
    public function delete(): bool
    {
        if (!$this->beforeDelete()) {
            return false;
        }
        $reflection = new \ReflectionClass(static::class);
        $method = 'delete' . $reflection->getShortName();
        $module = Module::getInstance();
        try {
            $module->$method($this);
        } catch (BexioException $e) {
            Yii::error($e->getMessage());
            return false;
        }
        $this->afterDelete();
        return true;
    }

    /**
     * This method is invoked before deleting a record.
     *
     * The default implementation raises the [[EVENT_BEFORE_DELETE]] event.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```
     * public function beforeDelete()
     * {
     *     if (!parent::beforeDelete()) {
     *         return false;
     *     }
     *
     *     // ...custom code here...
     *     return true;
     * }
     * ```
     *
     * @return bool whether the record should be deleted. Defaults to `true`.
     */
    public function beforeDelete(): bool
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after deleting a record.
     * The default implementation raises the [[EVENT_AFTER_DELETE]] event.
     * You may override this method to do postprocessing after the record is deleted.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    public function afterDelete(): void
    {
        $this->trigger(self::EVENT_AFTER_DELETE);
    }
}
