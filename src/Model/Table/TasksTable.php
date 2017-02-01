<?php

namespace App\Model\Table;

use Cake\Datasource\ConnectionManager;
use \Cake\ORM\Table;

class TasksTable extends Table {

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Projects', [
            'className' => 'Projects',
        ]);
    }

    public function getTask($taskId, $userId){
        $connection = ConnectionManager::get('default');
        $result = $connection->execute(
            'SELECT t.id, t.title, t.description, t.created, t.completed FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE t.id = :task_id AND p.user_id = :user_id',
            ['task_id' => $taskId, 'user_id' => $userId]
        )->fetchAll('assoc');
        if(count($result)){
            return $result[0];
        }
        return null;
    }

}