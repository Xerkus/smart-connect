<?php

namespace Eid;

use Laminas\Session\Container;
use Laminas\Cache\Pattern\ObjectCache;
use Laminas\Cache\Pattern\PatternOptions;


class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Eid\Controller\SummaryController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $summaryService = $diContainer->get('EidSummaryService');
                        return new \Eid\Controller\SummaryController($summaryService, $commonService);
                    }
                },
                'Eid\Controller\LabsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $facilityService = $diContainer->get('FacilityService');
                        $sampleService = $diContainer->get('EidSampleService');
                        $commonService = $diContainer->get('CommonService');
                        return new \Eid\Controller\LabsController($sampleService, $facilityService, $commonService);
                    }
                },
                'Eid\Controller\ClinicsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        $sampleService = $diContainer->get('EidSampleService');
                        return new \Eid\Controller\ClinicsController($sampleService, $commonService);
                    }
                },
            )
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(

                'EidSampleTable' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $session = new Container('credo');
                        $mappedFacilities = (isset($session->mappedFacilities) && count($session->mappedFacilities) > 0) ? $session->mappedFacilities : array();
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $eidSampleTable = isset($session->eidSampleTable) ? $session->eidSampleTable :  null;
                        $commonService = $diContainer->get('CommonService');
                        $tableObj = new \Eid\Model\EidSampleTable($dbAdapter, $diContainer, $mappedFacilities, $eidSampleTable, $commonService);


                        $storage = $diContainer->get('Cache\Persistent');
                        return new ObjectCache(
                            $storage,
                            new PatternOptions([
                                'object' => $tableObj,
                                'object_key' => $eidSampleTable // this makes sure we have different caches for both current and archive
                            ])
                        );
                    }
                },
                'EidSampleTableWithoutCache' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $session = new Container('credo');
                        $mappedFacilities = (isset($session->mappedFacilities) && count($session->mappedFacilities) > 0) ? $session->mappedFacilities : array();
                        $eidSampleTable = isset($session->eidSampleTable) ? $session->eidSampleTable :  null;
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        $commonService = $diContainer->get('CommonService');
                        return new \Eid\Model\EidSampleTable($dbAdapter, $diContainer, $mappedFacilities, $eidSampleTable, $commonService);
                    }
                },


                'EidSampleService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Eid\Service\EidSampleService($diContainer);
                    }
                },
                'EidSummaryService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new \Eid\Service\EidSummaryService($diContainer);
                    }
                },
            ),
        );
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
