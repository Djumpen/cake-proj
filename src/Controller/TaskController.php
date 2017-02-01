<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\Validation\Validator;
use Cake\View\Exception\MissingTemplateException;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;

class TaskController extends AppController
{

    public function initialize() {
        parent::initialize();

        $this->loadModel('Tasks');
        $this->loadModel('Projects');
    }

    public function index() {
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $taskId = (int)$this->request->query('task_id');
        if(!$taskId){
            return $this->respondWithBadRequest(['message' => "'task_id' expected"]);
        }

        return $this->respondTask($taskId);
    }

    public function add(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $requestData = $this->request->input('json_decode');
        $errors = $this->validateSchema($requestData, 'Task.add');

        if($errors) {
            return $this->respondWithBadRequest($errors);
        }

        if(!property_exists($requestData, 'Project')){
            // Update
            if(!property_exists($requestData->Task, 'id')){
                return $this->respondWithBadRequest(['message' => "'Task.id' expected"]);
            }

            // TODO: need to do it by single request
            if(!$this->Tasks->getTask($requestData->Task->id, $this->userId)){
                return $this->respondWithNotFound(['message' => 'Task not found']);
            }
            $task = $this->Tasks->get($requestData->Task->id);

            $task->title        = $this->esc($requestData->Task->title);
            $task->description  = $this->esc($requestData->Task->description);

            if(!$this->Tasks->save($task)){
                // TODO: throw exception
            }

            return $this->respondTask($task->id);
        } else {
            // Create
            try {
                $this->Projects->get($requestData->Project->id, [
                    'conditions' => ['user_id' => $this->userId]
                ]);
            } catch (RecordNotFoundException $e){
                return $this->respondWithNotFound(['message' => 'Project not found']);
            }

            $task = $this->Tasks->newEntity([
                'title'         => $this->esc($requestData->Task->title),
                'description'   => $this->esc($requestData->Task->description),
                'project_id'    => $requestData->Project->id
            ]);

            if(!$this->Tasks->save($task)){
                // TODO: throw exception
            }

            return $this->respondWithCreated([
                'Task' => [
                    'id' => $task->id
                ]
            ]);
        }
    }

    public function delete(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $taskId = (int)$this->request->query('task_id');
        if(!$taskId){
            return $this->respondWithBadRequest(['message' => "'task_id' expected"]);
        }

        if(!$this->Tasks->getTask($taskId, $this->userId)){
            return $this->respondWithNotFound(['message' => 'Task not found']);
        }

        $task = $this->Tasks->get($taskId);
        if(!$this->Tasks->delete($task)){
            // TODO: throw exception
        }

        return $this->respondWithOK([]);
    }

    // TODO: complEte :)
    public function complite(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $requestData = $this->request->input('json_decode');
        $errors = $this->validateSchema($requestData, 'Task.complete');

        if($errors) {
            return $this->respondWithBadRequest($errors);
        }

        // TODO: need to do it by single request
        if(!$this->Tasks->getTask($requestData->Task->id, $this->userId)){
            return $this->respondWithNotFound(['message' => 'Task not found']);
        }

        $task = $this->Tasks->get($requestData->Task->id);
        $task->completed = true;

        if(!$this->Tasks->save($task)){
            // TODO: throw exception
        }

        return $this->respondWithOK([]);
    }

    private function respondTask($taskId){
        $task = $this->Tasks->getTask($taskId, $this->userId);
        if(!$task){
            return $this->respondWithNotFound(['message' => 'Task not found']);
        }
        return $this->respondWithOK([
            'Task' => $task
        ]);
    }
}

