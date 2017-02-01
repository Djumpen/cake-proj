<?php

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;

class TasksController extends AppController
{

    private $defaultLimit = 10;

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Projects');
        $this->loadModel('Tasks');
    }

    public function index(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $projectId = (int)$this->request->query('project_id');
        if(!$projectId){
            return $this->respondWithBadRequest(['message' => "'project_id'expected"]);
        }

        $keywords = $this->request->query('condition_keywords');
        list($offset, $limit) = $this->getPagination(0, $this->defaultLimit);

        try {
            $this->Projects->get($projectId, [
                'conditions' => ['user_id' => $this->userId]
            ]);
        } catch (RecordNotFoundException $e){
            return $this->respondWithNotFound(['message' => 'Project not found']);
        }

        $query = $this->Tasks->find();
        $query->select([
            'Tasks.id',
            'Tasks.title',
            'Tasks.description',
            'Tasks.completed',
            'Tasks.created'])
            ->leftJoinWith('Projects')
            ->group(['Tasks.id'])
            ->where([
                'Tasks.project_id' => $projectId,
                'Projects.user_id' => $this->userId
            ])
            ->offset($offset)
            ->limit($limit);
        if($keywords)
            $query->andWhere(['Tasks.title LIKE' => '%' . $keywords . '%']);

        $tasks = array_map(function($row) {
            return ['Task' => $row->toArray()];
        }, $query->toArray());

        $quryCount = $this->Tasks->find('all', ['conditions' => ['project_id' => $projectId]]);
        if($keywords)
            $quryCount->andWhere(['Tasks.title LIKE' => '%' . $keywords . '%']);

        return $this->respondWithOK([
            'tasks' => $tasks,
            'total_count' => $quryCount->count()
        ]);
    }
}

