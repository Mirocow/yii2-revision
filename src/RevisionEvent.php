<?php
namespace mirocow\revision;

use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Class RevisionEvent
 * @package mirocow\revision
 */
class RevisionEvent extends Event
{
    /** @var ActiveRecord|null  */
    public $model = null;

    /** @var array  */
    public $attributes = [];
}