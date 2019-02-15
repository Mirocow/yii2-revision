<?php
namespace mirocow\revision;

use Codeception\Exception\ConfigurationException;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\db\Expression;

class ModelRevision extends Behavior
{
    const EVENT_BEFORE_REVISION_SAVE = 'beforeRevisionSave';

    const EVENT_AFTER_REVISION_SAVE = 'afterRevisionSave';

    public $revisionModelId = 'model_id';

    public $revisionUserId = 'user_id';

    public $revisionAttributeData = 'data';

    public $revisionClassNamespace = 'class_namespace';

    /** @var  */
    public $classModel;

    /** @var array  */
    public $fields = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'makeRevision',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'makeRevision',
        ];
    }

    /**
     * @throws ConfigurationException
     * @throws \yii\base\InvalidConfigException
     */
    public function makeRevision()
    {
        if(!class_exists($this->classModel)){
            throw new ConfigurationException(\Yii::t('app','Model class {class} not found', ['class' => $this->classModel]));
        }

        /* @var BaseActiveRecord $owner */
        $owner = $this->owner;

        $newAttributes = $owner->getAttributes();
        $oldAttributes = $owner->getOldAttributes();

        if($this->fields) {
            $newAttributes = $this->getRevisionAttributes($newAttributes);
            $oldAttributes = $this->getRevisionAttributes($oldAttributes);
        }

        $existsRecord = ($this->classModel)::find()
            ->where([$this->revisionModelId => $owner->primaryKey])
            ->exists();

        if(!$existsRecord || $newAttributes != $oldAttributes) {

            $event = new RevisionEvent(
                [
                    'model' => $owner,
                    'attributes' => $newAttributes,
                ]
            );
            $owner->trigger(self::EVENT_BEFORE_REVISION_SAVE, $event);

            $fields[ $this->revisionModelId ] = $owner->primaryKey;
            $fields[ $this->revisionAttributeData ] = $newAttributes;
            $fields[ $this->revisionUserId ] = (($user = \Yii::$app->get('user', FALSE)) && !$user->isGuest)? $user->id: null;
            $fields[ $this->revisionClassNamespace ] = get_class($owner);

            /** @var BaseActiveRecord $model */
            $model = new $this->classModel;
            $model->load($fields, '');
            $model->save();

            $event = new RevisionEvent(
                [
                    'model' => $owner,
                    'attributes' => $newAttributes,
                ]
            );
            $owner->trigger(self::EVENT_AFTER_REVISION_SAVE, $event);

        }

    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function getRevisionAttributes($attributes = [])
    {
        $data = [];

        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, $this->fields)) {
                if (!empty($value)) {
                    $data[ $attribute ] = $value;
                }
            }
        }

        return $data;
    }

}