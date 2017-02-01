<?php

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use RandomLib\Factory;
use SecurityLib\Strength;

class ApikeysTable extends BaseTable {

    public function beforeSave(Event $event, EntityInterface $entity, \ArrayObject $options) {
        parent::beforeMarshal($event, $entity, $options);

        if($entity->isNew()) {
            $factory = new Factory;
            $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));
            $key = $generator->generateString(32, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
            while (true) {
                $apikeys = $this->find()->where(['value' => $key]);
                if (!$apikeys->count()) {
                    $entity->set('value', $key);
                    break;
                }
            }
        }
    }

}