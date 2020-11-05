<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Zend\Debug\Debug;
use \Covid19\Service\Covid19FormService;

class VlsmCovidController extends AbstractRestfulController
{
    private $covid19SampleService = null;

    public function __construct($covid19SampleService)
    {
        $this->covid19SampleService = $covid19SampleService;
    }

    public function getList()
    {
        exit('Nothing to see here');
    }
    public function create($params)
    {
        if (!isset($params['api-version'])) {
            $params['api-version'] = 'v1';
        }
        // print_r($params);die;
        if ($params['api-version'] == 'v1') {
            $response = $this->covid19SampleService->saveFileFromVlsmAPIV1();
        } else if ($params['api-version'] == 'v2') {
            $response = $this->covid19SampleService->saveFileFromVlsmAPIV2();
        }


        return new JsonModel($response); 
        
    }
}
