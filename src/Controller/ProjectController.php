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

class ProjectController extends AppController
{

    public function initialize() {
        parent::initialize();

        $this->loadModel('Projects');
    }

    public function index() {
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        // TODO: validator should be here
        $projectId = (int)$this->request->query('project_id');
        if(!$projectId){
            return $this->respondWithBadRequest(['message' => "'project_id' expected"]);
        }

        return $this->respondProject($projectId);
    }

    public function add(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $requestData = $this->request->input('json_decode');
        $errors = $this->validateSchema($requestData, 'Project.add');

        if($errors) {
            return $this->respondWithBadRequest($errors);
        }

        if(property_exists($requestData->Project, 'id')){
            // Update
            try {
                $project = $this->Projects->get($requestData->Project->id, [
                    'conditions' => ['user_id' => $this->userId]
                ]);
            } catch (RecordNotFoundException $e){
                return $this->respondWithNotFound(['message' => 'Project not found']);
            }

            $project->title = $this->esc($requestData->Project->title);

            if(!$this->Projects->save($project)){
                // TODO: throw exception
            }

            return $this->respondProject($project->id);
        } else {
            // Create
            $project = $this->Projects->newEntity([
                'title' => $this->esc($requestData->Project->title),
                'user_id' => $this->userId
            ]);

            if(!$this->Projects->save($project)){
                // TODO: throw exception
            }

            return $this->respondWithCreated([
                'Project' => [
                    'id' => $project->id
                ]
            ]);
        }
    }

    public function delete(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]); // TODO: throw exception
        }

        $projectId = (int)$this->request->query('project_id');
        if(!$projectId){
            return $this->respondWithBadRequest(['message' => "'project_id' expected"]);
        }

        try {
            $project = $this->Projects->get($projectId, [
                'conditions' => ['user_id' => $this->userId]
            ]);
        } catch (RecordNotFoundException $e){
            return $this->respondWithNotFound(['message' => 'Project not found']);
        }

        if(!$this->Projects->delete($project)){
            // TODO: throw exception
        }

        return $this->respondWithOK([]);
    }

    private function respondProject($projectId){
        $projectQuery = $this->Projects->getProject($projectId, $this->userId);

        if(!$projectQuery->count()){
            return $this->respondWith(404, ['message' => 'Project not found']);
        }

        return $this->respondWithOK([
            'Project' => $projectQuery->first()->toArray()
        ]);
    }
}

