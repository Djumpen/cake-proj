<?php

namespace App\Model\Table;

use \Faker\Factory as Faker;

class ProjectsTable extends BaseTable  {

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('Tasks', [
            'className' => 'Tasks',
            'dependent' => true
        ]);
    }

    public function getProject($projectId, $userId){
        $query = $this->find();
        $query->select(['Projects.id', 'Projects.title', 'task_count' => $query->func()->count('Tasks.id')])
            ->where(['Projects.id' => $projectId,'Projects.user_id' => $userId])
            ->leftJoinWith('Tasks')
            ->group(['Projects.id']);

        return $query;
    }

    public function getProjects($userId){
        $query = $this->find();
        $query->select(['Projects.id', 'Projects.title', 'task_count' => $query->func()->count('Tasks.id')])
            ->where(['Projects.user_id' => $userId])
            ->leftJoinWith('Tasks')
            ->group(['Projects.id']);

        return $query;
    }

    /**
     * Generate sample projects
     *
     * @param int $projectsNumber
     * @param int $userId
     */
    public function createSampleProjects($projectsNumber, $userId){
        $faker = Faker::create();
        for($i = 0; $i < $projectsNumber; $i++){
            $project = $this->newEntity([
                'title' => $faker->sentence(2),
                'user_id' => $userId
            ]);
            $this->save($project);
        }
    }

}