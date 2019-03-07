<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class UserController extends AbstractRestfulController
{
    public function create($params) {
        $plugin = $this->HasParams();
        if($plugin->checkParams($params,array('userName','password'))){
            $userService = $this->getServiceLocator()->get('UserService');
            $response =$userService->userLoginApi($params);
            return new JsonModel($response);
        }else{
            $response['status'] = '403';
            $response['result'] = 'Invalid or Missing Query Params';
          return new JsonModel($response);
        }
    }
}   

