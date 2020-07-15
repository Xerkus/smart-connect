<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

class VlsmController extends AbstractRestfulController
{
    public function create($params) {
        $service = $this->getServiceLocator()->get('SampleService');
        $response =$service->saveFileFromVlsmAPI();
        return new JsonModel($response);
    }
}