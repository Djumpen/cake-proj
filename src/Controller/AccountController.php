<?php

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use \Faker\Factory as Faker;

class AccountController extends AppController
{

    private $avatarPath = 'https://s3-ap-northeast-1.amazonaws.com/testtask/avatars/';

    public function initialize() {
        parent::initialize();

        $this->loadModel('Users');
        $this->loadModel('Apikeys');
        $this->loadModel('Projects');
    }


    public function index(){
        if(!$this->checkSession()){
            return $this->respondWithBadRequest(['message' => "'session'expected"]);
        }
        try {
            $user = $this->Users->get($this->userId, [
                'fields' => ['name', 'image_url']
            ]);
        } catch (RecordNotFoundException $e){
            return $this->respondWithNotFound(['message' => 'Account not found']);
        }
        return $this->respondWithOK($user->toArray());
    }

    protected function signup() {
        $faker = Faker::create();

        $user = $this->Users->newEntity([
            'name'      => $faker->name,
            'image_url' => $this->avatarPath . 'user-' . rand(1,22) . '.png'
        ]);

        if(!$this->Users->save($user)) {
            // TODO: throw exception
        }

        $this->Projects->createSampleProjects(3, $user->id);

        $apikey = $this->createSession($user->id);

        return $this->respondWithOK([
            'session' => $apikey->value
        ]);
    }

    private function createSession($userId){
        $apikey = $this->Apikeys->newEntity([
            'user_id'   => $userId
        ]);
        $this->Apikeys->save($apikey);
        return $apikey;
    }

}
