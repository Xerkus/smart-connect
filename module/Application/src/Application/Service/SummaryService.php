<?php

namespace Application\Service;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;
use PHPExcel;

class SummaryService
{

    public $sm = null;
    protected $translate = null;

    public function __construct($sm)
    {
        $this->sm = $sm;
        $this->translate = $this->sm->get('translator');
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public function fetchSummaryTabDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->getSummaryTabDetails($params);
    }

    public function getKeySummaryIndicatorsDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchKeySummaryIndicatorsDetails($params);
    }

    public function getSamplesReceivedBarChartDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchSamplesReceivedBarChartDetails($params);
    }

    public function getAllSamplesReceivedByDistrict($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSamplesReceivedByDistrict($parameters);
    }
    public function getAllSamplesReceivedByProvince($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSamplesReceivedByProvince($parameters);
    }

    public function getAllSamplesReceivedByFacility($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSamplesReceivedByFacility($parameters);
    }

    public function getSuppressionRateBarChartDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchSuppressionRateBarChartDetails($params);
    }

    public function getAllSuppressionRateByDistrict($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSuppressionRateByDistrict($parameters);
    }

    public function getAllSuppressionRateByProvince($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSuppressionRateByProvince($parameters);
    }

    public function getAllSuppressionRateByFacility($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSuppressionRateByFacility($parameters);
    }

    public function getSamplesRejectedBarChartDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchSamplesRejectedBarChartDetails($params);
    }

    public function getAllSamplesRejectedByDistrict($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllSamplesRejectedByDistrict($parameters);
    }

    public function getAllSamplesRejectedByFacility($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fecthAllSamplesRejectedByFacility($parameters);
    }
    public function getAllSamplesRejectedByProvince($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fecthAllSamplesRejectedByProvince($parameters);
    }

    public function getRegimenGroupBarChartDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchRegimenGroupBarChartDetails($params);
    }

    public function getRegimenGroupSamplesDetails($parameters)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchRegimenGroupSamplesDetails($parameters);
    }

    public function getAllLineOfTreatmentDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllLineOfTreatmentDetails($params);
    }

    public function getAllCollapsibleLineOfTreatmentDetails($params)
    {
        $sampleDb = $this->sm->get('SampleTable');
        return $sampleDb->fetchAllCollapsibleLineOfTreatmentDetails($params);
    }

    public function exportIndicatorResultExcel($params)
    {
        $queryContainer = new Container('query');
        $translator = $this->sm->get('translator');
        $common = new CommonService();
        if (isset($queryContainer->indicatorSummaryQuery)) {
            try {
                $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
                $sql = new Sql($dbAdapter);
                $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->indicatorSummaryQuery);
                $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if (isset($sResult) && count($sResult) > 0) {
                    $excel = new PHPExcel();
                    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                    $cacheSettings = array('memoryCacheSize' => '80MB');
                    \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                    $sheet = $excel->getActiveSheet();
                    $output = array();
                    $keySummaryIndicators = array();
                    $j = 0;
                    foreach ($sResult as $row) {
                        $keySummaryIndicators['sample'][$this->translate('Samples Received')]['month'][$j] = (isset($row["total_samples_received"])) ? $row["total_samples_received"] : 0;
                        $keySummaryIndicators['sample'][$this->translate('Samples Tested')]['month'][$j] = (isset($row["total_samples_tested"])) ? $row["total_samples_tested"] : 0;
                        $keySummaryIndicators['sample'][$this->translate('Samples Rejected')]['month'][$j] = (isset($row["total_samples_rejected"])) ? $row["total_samples_rejected"] : 0;
                        $keySummaryIndicators['sample'][$this->translate('Valid Tested')]['month'][$j]  = $valid = (isset($row["total_samples_tested"])) ? $row["total_samples_tested"] - $row["total_samples_rejected"] : 0;;
                        $keySummaryIndicators['sample'][$this->translate('Samples Suppressed')]['month'][$j] = (isset($row["total_suppressed_samples"])) ? $row["total_suppressed_samples"] : 0;
                        $keySummaryIndicators['sample'][$this->translate('Suppression Rate')]['month'][$j] = ($valid > 0) ? round((($row["total_suppressed_samples"] / $valid) * 100), 2) . ' %' : '0';
                        $keySummaryIndicators['sample'][$this->translate('Rejection Rate')]['month'][$j] = (isset($row["total_samples_rejected"]) && $row["total_samples_rejected"] > 0 && $row["total_samples_received"] > 0) ? round((($row["total_samples_rejected"] / ($row["total_samples_tested"] + $row["total_samples_rejected"])) * 100), 2) . ' %' : '0';
                        $keySummaryIndicators['month'][$j] = $row['monthyear'];
                        $j++;
                    }

                    $styleArray = array(
                        'font' => array(
                            'bold' => true,
                        ),
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    $borderStyle = array(
                        'alignment' => array(
                            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                        ),
                        'borders' => array(
                            'outline' => array(
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        )
                    );
                    $eRow = 0;
                    $sheet->setCellValue('A1', html_entity_decode($translator->translate('Months'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    foreach ($keySummaryIndicators['month'] as $key => $month) {
                        $colNo = $key + 1;
                        $currentRow = 1;
                        $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($month, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                        $sheet->getStyle($cellName . $currentRow)->applyFromArray($styleArray);
                    }


                    foreach ($keySummaryIndicators['sample'] as $key => $indicators) {
                        $row = array();
                        $row[] = $translator->translate($key);
                        foreach ($indicators['month'] as $months) {
                            $row[] = $months;
                        }
                        $output[] = $row;
                    }


                    $currentRow = 2;
                    foreach ($output as $rowData) {
                        $colNo = 0;
                        foreach ($rowData as $field => $value) {
                            if (!isset($value)) {
                                $value = "";
                            }

                            if (is_numeric($value)) {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            } else {
                                $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                            }
                            $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                            $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                            $sheet->getDefaultRowDimension()->setRowHeight(20);
                            $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                            $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                            $colNo++;
                        }
                        $currentRow++;
                    }
                    $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                    $filename = 'SUMMARY-INDICATORS-RESULT-REPORT--' . date('d-M-Y-H-i-s') . '.xls';
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                    return $filename;
                } else {
                    return "";
                }
            } catch (Exception $exc) {
                error_log("SUMMARY-INDICATORS-RESULT-REPORT--" . $exc->getMessage());
                error_log($exc->getTraceAsString());
                return "";
            }
        } else {
            return "";
        }
    }

    public function exportSuppressionRateByFacility($params, $dashTable = 'dash_vl_request_form')
    {
        $queryContainer = new Container('query');
        $translator = $this->sm->get('translator');
        // To set te session table
        $logincontainer = new Container('credo');
        if(isset($logincontainer->sampleTable) && $logincontainer->sampleTable != ""){
            $dashTable = $logincontainer->sampleTable;
        }
        $common = new CommonService();

        if (!isset($queryContainer->fetchAllSuppressionRateByFacility)) {

            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->fetchAllSuppressionRateByFacility = $sql->select()->from(array('vl' => $dashTable))
                ->columns(
                    array(
                        'vl_sample_id',
                        'facility_id',
                        'sampleCollectionDate' => new Expression('DATE(sample_collection_date)'),
                        'result',
                        "total_samples_received" => new Expression("(COUNT(*))"),
                        "total_samples_valid" => new Expression("(SUM(CASE WHEN (((vl.DashVL_AnalysisResult IS NOT NULL AND vl.DashVL_AnalysisResult != '' AND vl.DashVL_AnalysisResult != 'NULL'))) THEN 1 ELSE 0 END))"),
                        "total_samples_rejected" => new Expression("(SUM(CASE WHEN (reason_for_sample_rejection !='' AND reason_for_sample_rejection !='0' AND reason_for_sample_rejection IS NOT NULL) THEN 1 ELSE 0 END))"),
                        "total_suppressed_samples" => new Expression("SUM(CASE WHEN ((vl.DashVL_AnalysisResult like 'suppressed%' OR vl.DashVL_AnalysisResult like 'Suppressed%' )) THEN 1 ELSE 0 END)"),
                        "total_not_suppressed_samples" => new Expression("SUM(CASE WHEN ((vl.DashVL_AnalysisResult like 'not%' OR vl.DashVL_AnalysisResult like 'Not%' or vl.DashVL_Abs >= 1000)) THEN 1 ELSE 0 END)"),
                        //"total_suppressed_samples_percentage" => new Expression("TRUNCATE(((SUM(CASE WHEN (vl.result < 1000 or vl.result='Target Not Detected') THEN 1 ELSE 0 END)/COUNT(*))*100),2)")
                        "suppression_rate" => new Expression("ROUND(((SUM(CASE WHEN ((vl.DashVL_AnalysisResult like 'suppressed%' OR vl.DashVL_AnalysisResult like 'Suppressed%' )) THEN 1 ELSE 0 END))/(SUM(CASE WHEN (((vl.DashVL_AnalysisResult IS NOT NULL AND vl.DashVL_AnalysisResult != '' AND vl.DashVL_AnalysisResult != 'NULL'))) THEN 1 ELSE 0 END)))*100,2)")
                    )
                )
                ->join(array('f' => 'facility_details'), 'f.facility_id=vl.facility_id', array('facility_name'))
                ->join(array('f_d_l_dp' => 'location_details'), 'f_d_l_dp.location_id=f.facility_state', array('province' => 'location_name'))
                ->join(array('f_d_l_d' => 'location_details'), 'f_d_l_d.location_id=f.facility_district', array('district' => 'location_name'))
                ->where("(vl.sample_collection_date is not null AND vl.sample_collection_date != '' AND DATE(vl.sample_collection_date) !='1970-01-01' AND DATE(vl.sample_collection_date) !='0000-00-00')")
                ->group('vl.facility_id');
        }

        try {
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->fetchAllSuppressionRateByFacility);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (isset($sResult) && count($sResult) > 0) {
                $excel = new PHPExcel();
                $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                $cacheSettings = array('memoryCacheSize' => '80MB');
                \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
                $sheet = $excel->getActiveSheet();
                $output = array();
                foreach ($sResult as $aRow) {

                    $row = array();
                    $row[] = ucwords($aRow['facility_name']);
                    $row[] = ucwords($aRow['province']);
                    $row[] = ucwords($aRow['district']);
                    $row[] = $aRow['total_samples_valid'];
                    $row[] = $aRow['total_suppressed_samples'];
                    $row[] = $aRow['total_not_suppressed_samples'];
                    $row[] = ($aRow['total_samples_rejected'] > 0 && $aRow['total_samples_received'] > 0) ? round((($aRow['total_samples_rejected'] / $aRow['total_samples_received']) * 100), 2) : '';
                    $row[] = ($aRow['total_samples_valid'] > 0 && $aRow['total_suppressed_samples'] > 0) ? round((($aRow['total_suppressed_samples'] / $aRow['total_samples_valid']) * 100), 2) : '';
                    $output[] = $row;
                }
                $styleArray = array(
                    'font' => array(
                        'bold' => true,
                    ),
                    'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    ),
                    'borders' => array(
                        'outline' => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN,
                        ),
                    )
                );
                $borderStyle = array(
                    'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    ),
                    'borders' => array(
                        'outline' => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN,
                        ),
                    )
                );

                $sheet->setCellValue('A1', html_entity_decode($translator->translate('Facility'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('B1', html_entity_decode($translator->translate('Province'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('C1', html_entity_decode($translator->translate('District/County'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D1', html_entity_decode($translator->translate('Valid Results'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E1', html_entity_decode($translator->translate('Suppressed Results'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('F1', html_entity_decode($translator->translate('Non Suppressed Results'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('G1', html_entity_decode($translator->translate('Samples Rejected in %'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('H1', html_entity_decode($translator->translate('Suppression Rate in %'), ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);

                $sheet->getStyle('A1')->applyFromArray($styleArray);
                $sheet->getStyle('B1')->applyFromArray($styleArray);
                $sheet->getStyle('C1')->applyFromArray($styleArray);
                $sheet->getStyle('D1')->applyFromArray($styleArray);
                $sheet->getStyle('E1')->applyFromArray($styleArray);
                $sheet->getStyle('F1')->applyFromArray($styleArray);
                $sheet->getStyle('G1')->applyFromArray($styleArray);
                $sheet->getStyle('H1')->applyFromArray($styleArray);

                $currentRow = 2;
                foreach ($output as $rowData) {
                    $colNo = 0;
                    foreach ($rowData as $field => $value) {
                        if (!isset($value)) {
                            $value = "";
                        }
                        // if ($colNo > 5) {
                        //     break;
                        // }
                        if (is_numeric($value)) {
                            $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        } else {
                            $sheet->getCellByColumnAndRow($colNo, $currentRow)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                        $cellName = $sheet->getCellByColumnAndRow($colNo, $currentRow)->getColumn();
                        $sheet->getStyle($cellName . $currentRow)->applyFromArray($borderStyle);
                        $sheet->getDefaultRowDimension()->setRowHeight(20);
                        $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                        $sheet->getStyleByColumnAndRow($colNo, $currentRow)->getAlignment()->setWrapText(true);
                        $colNo++;
                    }
                    $currentRow++;
                }
                $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
                $filename = 'Facility-Wise-Suppression-Rate-' . date('d-M-Y-H-i-s') . '.xls';
                $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                return $filename;
            } else {
                return "";
            }
        } catch (Exception $exc) {
            error_log("Facility-Wise-Suppression-Rate-" . $exc->getMessage());
            error_log($exc->getTraceAsString());
            return "";
        }
    }
}
