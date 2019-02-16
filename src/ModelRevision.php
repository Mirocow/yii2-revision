<?php
namespace mirocow\revision;

use Codeception\Exception\ConfigurationException;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\helpers\Json;

class ModelRevision extends Behavior
{
    const EVENT_BEFORE_REVISION_SAVE = 'beforeRevisionSave';

    const EVENT_AFTER_REVISION_SAVE = 'afterRevisionSave';

    public $revisionModelId = 'model_id';

    public $revisionUserId = 'user_id';

    public $revisionAttributeData = 'data';

    public $revisionClassNamespace = 'class_namespace';

    public $revisionHash = 'hash';

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

        $event = new RevisionEvent(
            [
                'model' => $owner,
                'attributes' => $newAttributes,
                'doSave' => !$existsRecord || $newAttributes != $oldAttributes,
            ]
        );
        $owner->trigger(self::EVENT_BEFORE_REVISION_SAVE, $event);

        if(method_exists($owner, self::EVENT_BEFORE_REVISION_SAVE)){
            $owner->{self::EVENT_BEFORE_REVISION_SAVE}($event);
        }

        if($event->doSave) {

            $fields = [
                $this->revisionModelId => $owner->primaryKey,
                $this->revisionAttributeData => $event->attributes,
                $this->revisionUserId => (($user = \Yii::$app->get('user', FALSE)) && !$user->isGuest)? $user->id: null,
                $this->revisionClassNamespace => get_class($owner),
                $this->revisionHash => md5(Json::encode($event->attributes, JSON_OBJECT_AS_ARRAY)),
            ];

            /** @var BaseActiveRecord $model */
            $model = new $this->classModel;
            $model->load($fields, '');
            $model->save();

            $event = new RevisionEvent(
                [
                    'model' => $owner,
                    'attributes' => $event->attributes,
                ]
            );
            $owner->trigger(self::EVENT_AFTER_REVISION_SAVE, $event);

            if(method_exists($owner, self::EVENT_AFTER_REVISION_SAVE)){
                $owner->{self::EVENT_AFTER_REVISION_SAVE}($event);
            }

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