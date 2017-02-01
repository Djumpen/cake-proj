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

use Cake\Controller\Controller;
use Cake\Event\Event;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use voku\helper\AntiXSS;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    protected $userId = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize() {
        parent::initialize();

        $this->loadComponent('RequestHandler');

        $this->viewBuilder()->className('Json');
        $this->render(false);

        $this->response->cors($this->request)
            ->allowOrigin(['*'])
            ->allowMethods(['GET', 'POST', 'DELETE'])
            ->allowCredentials()
            ->maxAge(1500)
            ->build();
    }

    protected function respondWith($statusCode, array $data = []) {
        $this->response->statusCode($statusCode);
        if(!empty($data)){
            $this->set('data', $data);
            $this->set('_serialize', 'data');
        }

        $this->render();
    }

    public function respondWithOK(array $data = []) {
        return $this->respondWith('200', $data);
    }

    public function respondWithBadRequest(array $data = []) {
        return $this->respondWith('400', $data);
    }

    public function respondWithCreated(array $data = []) {
        return $this->respondWith('201', $data);
    }

    public function respondWithNotFound(array $data = []) {
        return $this->respondWith('404', $data);
    }

    public function checkSession()
    {
        $session = null;
        if ($this->request->query('session')) {
            $session = $this->request->query('session');
        } else {
            $requestData = $this->request->input('json_decode');
            if (is_object($requestData) && property_exists($requestData, 'session')) {
                $session = $requestData->session;
            }
        }

        if (!$session)
            return false;

        $this->loadModel('Apikeys');
        $apikeys = $this->Apikeys->find()->where([
            'value' => $session
        ]);

        $apikey = $apikeys->first();

        if ($apikey) {
            $this->userId = $apikey->user_id;
            return true;
        } else {
            return false;
        }
    }

    public function validateSchema($data, $jsonSchemaName){

        $retriever = new UriRetriever();
        $jsonSchemaObject = $retriever->retrieve('file://' . JSON_SCHEMES . $jsonSchemaName . '.json');
        $jsonValidator = new Validator();
        $jsonValidator->check($data, $jsonSchemaObject);

        if ($jsonValidator->isValid()) {
            return false;
        }
        $errors = [];
        foreach ($jsonValidator->getErrors() as $error) {
            $errors[$error['property']] = $error['message'];
        }
        return $errors;
    }

    public function esc($value){
        return (new AntiXSS())->xss_clean($value);
    }

    protected function getPagination($defaultOffset = null, $defaultLimit = null){
        return [
            abs($this->request->query('offset')) ?: $defaultOffset,
            abs($this->request->query('limit')) ?: $defaultLimit
        ];
    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeRender(Event $event) {
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }
}
