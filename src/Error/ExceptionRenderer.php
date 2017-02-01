<?php

namespace App\Error;

use Cake\Network\Exception\NotFoundException;

class ExceptionRenderer extends \Cake\Error\ExceptionRenderer {

    public function render() {
        return parent::render();
        /*if($this->error instanceof NotFoundException){
            $this->controller->response->statusCode(404);
        } else {
            $this->controller->response->statusCode(500);
        }

        $this->controller->render(false);
        return $this->_shutdown();*/
    }

}