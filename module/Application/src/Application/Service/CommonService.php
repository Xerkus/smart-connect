<?php

namespace Application\Service;

use Laminas\Session\Container;
use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use Laminas\Mail;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;


class CommonService
{

     public $sm = null;
     public $cache = null;

     public function __construct($sm = null, $cache = null)
     {
          $this->sm = $sm;
          $this->cache = $cache;
     }

     public function getServiceManager()
     {
          return $this->sm;
     }

     public function startsWith($string, $startString)
     {
          $len = strlen($startString);
          return (substr($string, 0, $len) === $startString);
     }

     public static function generateRandomString($length = 8, $seeds = 'alphanum')
     {
          // Possible seeds
          $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
          $seedings['numeric'] = '0123456789';
          $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
          $seedings['hexidec'] = '0123456789abcdef';

          // Choose seed
          if (isset($seedings[$seeds])) {
               $seeds = $seedings[$seeds];
          }

          // Seed generator
          list($usec, $sec) = explode(' ', microtime());
          $seed = (float) $sec + ((float) $usec * 100000);
          mt_srand($seed);

          // Generate
          $str = '';
          $seeds_count = strlen($seeds);

          for ($i = 0; $length > $i; $i++) {
               $str .= $seeds[mt_rand(0, $seeds_count - 1)];
          }

          return $str;
     }

     public function checkMultipleFieldValidations($params)
     {
          $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
          $jsonData = $params['json_data'];
          $tableName = $jsonData['tableName'];
          $sql = new Sql($adapter);
          $select = $sql->select()->from($tableName);
          foreach ($jsonData['columns'] as $val) {
               if ($val['column_value'] != "") {
                    $select->where($val['column_name'] . "=" . "'" . $val['column_value'] . "'");
               }
          }

          //edit
          if (isset($jsonData['tablePrimaryKeyValue']) && $jsonData['tablePrimaryKeyValue'] != null && $jsonData['tablePrimaryKeyValue'] != "null") {
               $select->where($jsonData['tablePrimaryKeyId'] . "!=" . $jsonData['tablePrimaryKeyValue']);
          }
          //error_log($sql);
          $statement = $sql->prepareStatementForSqlObject($select);
          $result = $statement->execute();
          $data = count($result);
          return $data;
     }


     public function checkFieldValidations($params)
     {
          $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
          $tableName = $params['tableName'];
          $fieldName = $params['fieldName'];
          $value = trim($params['value']);
          $fnct = $params['fnct'];
          try {
               $sql = new Sql($adapter);
               if ($fnct == '' || $fnct == 'null') {
                    $select = $sql->select()->from($tableName)->where(array($fieldName => $value));
                    //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."'");
                    $statement = $sql->prepareStatementForSqlObject($select);
                    $result = $statement->execute();
                    $data = count($result);
               } else {
                    $table = explode("##", $fnct);
                    if ($fieldName == 'password') {
                         //Password encrypted
                         $config = new \Laminas\Config\Reader\Ini();
                         $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                         $password = sha1($value . $configResult["password"]["salt"]);
                         //$password = $value;
                         $select = $sql->select()->from($tableName)->where(array($fieldName => $password, $table[0] => $table[1]));
                         $statement = $sql->prepareStatementForSqlObject($select);
                         $result = $statement->execute();
                         $data = count($result);
                    } else {
                         // first trying $table[1] without quotes. If this does not work, then in catch we try with single quotes
                         //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."' and ".$table[0]."!=".$table[1] );
                         $select = $sql->select()->from($tableName)->where(array("$fieldName='$value'", $table[0] . "!=" . "'$table[1]'"));
                         $statement = $sql->prepareStatementForSqlObject($select);
                         $result = $statement->execute();
                         $data = count($result);
                    }
               }
               return $data;
          } catch (Exception $exc) {
               error_log($exc->getMessage());
               error_log($exc->getTraceAsString());
          }
     }

     public function dateFormat($date)
     {
          if (!isset($date) || $date == null || $date == "" || $date == "0000-00-00") {
               return "0000-00-00";
          } else {
               $dateArray = explode('-', $date);
               if (sizeof($dateArray) == 0) {
                    return;
               }
               $newDate = $dateArray[2] . "-";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = 1;
               $mon += array_search(ucfirst($dateArray[1]), $monthsArray);

               if (strlen($mon) == 1) {
                    $mon = "0" . $mon;
               }
               return $newDate .= $mon . "-" . $dateArray[0];
          }
     }

     public function humanDateFormat($date)
     {
          if ($date == null || $date == "" || $date == "0000-00-00" || $date == "0000-00-00 00:00:00") {
               return "";
          } else {
               $dateArray = explode('-', $date);
               $newDate = $dateArray[2] . "-";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = $monthsArray[$dateArray[1] - 1];
               return $newDate .= $mon . "-" . $dateArray[0];
          }
     }

     public function viewDateFormat($date)
     {
          if ($date == null || $date == "" || $date == "0000-00-00") {
               return "";
          } else {
               $dateArray = explode('-', $date);
               $newDate = $dateArray[2] . "-";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = $monthsArray[$dateArray[1] - 1];

               return $newDate .= $mon . "-" . $dateArray[0];
          }
     }

     public function insertTempMail($to, $subject, $message, $fromMail, $fromName, $cc, $bcc)
     {
          $tempmailDb = $this->sm->get('TempMailTable');
          return $tempmailDb->insertTempMailDetails($to, $subject, $message, $fromMail, $fromName, $cc, $bcc);
     }

     public function sendTempMail()
     {
          try {
               $tempDb = $this->sm->get('TempMailTable');
               $config = new \Laminas\Config\Reader\Ini();
               $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
               $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
               $sql = new Sql($dbAdapter);

               // Setup SMTP transport using LOGIN authentication
               $transport = new SmtpTransport();
               $options = new SmtpOptions(array(
                    'host' => $configResult["email"]["host"],
                    'port' => $configResult["email"]["config"]["port"],
                    'connection_class' => $configResult["email"]["config"]["auth"],
                    'connection_config' => array(
                         'username' => $configResult["email"]["config"]["username"],
                         'password' => $configResult["email"]["config"]["password"],
                         'ssl' => $configResult["email"]["config"]["ssl"],
                    ),
               ));
               $transport->setOptions($options);
               $limit = '10';
               $mailQuery = $sql->select()->from(array('tm' => 'temp_mail'))
                    ->where("status='pending'")
                    ->limit($limit);
               $mailQueryStr = $sql->buildSqlString($mailQuery);
               $mailResult = $dbAdapter->query($mailQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
               if (count($mailResult) > 0) {
                    foreach ($mailResult as $result) {
                         $alertMail = new Mail\Message();
                         $id = $result['temp_id'];
                         $tempDb->updateTempMailStatus($id);

                         $fromEmail = $result['from_mail'];
                         $fromFullName = $result['from_full_name'];
                         $subject = $result['subject'];

                         $html = new MimePart($result['message']);
                         $html->type = "text/html";

                         $body = new MimeMessage();
                         $body->setParts(array($html));

                         $alertMail->setBody($body);
                         $alertMail->addFrom($fromEmail, $fromFullName);
                         $alertMail->addReplyTo($fromEmail, $fromFullName);

                         $toArray = explode(",", $result['to_email']);
                         foreach ($toArray as $toId) {
                              if ($toId != '') {
                                   $alertMail->addTo($toId);
                              }
                         }
                         if (isset($result['cc']) && trim($result['cc']) != "") {
                              $ccArray = explode(",", $result['cc']);
                              foreach ($ccArray as $ccId) {
                                   if ($ccId != '') {
                                        $alertMail->addCc($ccId);
                                   }
                              }
                         }

                         if (isset($result['bcc']) && trim($result['bcc']) != "") {
                              $bccArray = explode(",", $result['bcc']);
                              foreach ($bccArray as $bccId) {
                                   if ($bccId != '') {
                                        $alertMail->addBcc($bccId);
                                   }
                              }
                         }

                         $alertMail->setSubject($subject);
                         $transport->send($alertMail);
                         $tempDb->deleteTempMail($id);
                    }
               }
          } catch (Exception $e) {
               error_log($e->getMessage());
               error_log($e->getTraceAsString());
               error_log('whoops! Something went wrong in cron/SendMailAlerts.php');
          }
     }

     function removeDirectory($dirname)
     {
          // Sanity check
          if (!file_exists($dirname)) {
               return false;
          }

          // Simple delete for a file
          if (is_file($dirname) || is_link($dirname)) {
               return unlink($dirname);
          }

          // Loop through the folder
          $dir = dir($dirname);
          while (false !== $entry = $dir->read()) {
               // Skip pointers
               if ($entry == '.' || $entry == '..') {
                    continue;
               }

               // Recurse
               $this->removeDirectory($dirname . DIRECTORY_SEPARATOR . $entry);
          }

          // Clean up
          $dir->close();
          return rmdir($dirname);
     }

     public function removespecials($url)
     {
          $url = str_replace(" ", "-", $url);

          $url = preg_replace('/[^a-zA-Z0-9\-]/', '', $url);
          $url = preg_replace('/^[\-]+/', '', $url);
          $url = preg_replace('/[\-]+$/', '', $url);
          $url = preg_replace('/[\-]{2,}/', '', $url);

          return strtolower($url);
     }

     public static function getDateTime($timezone = 'Asia/Calcutta')
     {
          $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone($timezone));
          return $date->format('Y-m-d H:i:s');
     }

     public static function getDate($timezone = 'Asia/Calcutta')
     {
          $date = new \DateTime(date('Y-m-d'), new \DateTimeZone($timezone));
          return $date->format('Y-m-d');
     }

     public function humanMonthlyDateFormat($date)
     {
          if ($date == null || $date == "" || $date == "0000-00-00" || $date == "0000-00-00 00:00:00") {
               return "";
          } else {
               $dateArray = explode('-', $date);
               $newDate =  "";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = $monthsArray[$dateArray[1] * 1];

               return $newDate .= $mon . " " . $dateArray[0];
          }
     }



     public function cacheQuery($queryString, $dbAdapter, $fetchCurrent = false)
     {
          // in case fetchCurrent is true, we want to ensure it is treated as a
          // separate query compared to fetchCurrent = false
          $cacheId = hash("sha512", ($fetchCurrent) ? 'current-' : '' . $queryString);
          $res = null;

          try {
               if (!$this->cache->hasItem($cacheId)) {
                    if (!$fetchCurrent) {
                         $res = $dbAdapter->query($queryString, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                    } else {
                         $res = $dbAdapter->query($queryString, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                    }
                    $this->cache->addItem($cacheId, ($res));
               } else {
                    $res = ($this->cache->getItem($cacheId));
               }
               return $res;
          } catch (Exception $e) {
               error_log($e->getMessage());
          }
     }

     public function clearAllCache()
     {
          return $this->cache->flush();
     }

     public function getRoleFacilities($params)
     {
          $facilityDb = $this->sm->get('FacilityTable');
          return $facilityDb->fetchRoleFacilities($params);
     }

     public function getSampleTestedFacilityInfo($params)
     {
          $facilityDb = $this->sm->get('FacilityTable');
          return $facilityDb->fetchSampleTestedFacilityInfo($params);
     }

     public function getSampleTestedLocationInfo($params)
     {
          $facilityDb = $this->sm->get('FacilityTable');
          return $facilityDb->fetchSampleTestedLocationInfo($params);
     }

     public function addBackupGeneration($params)
     {
          $facilityDb = $this->sm->get('GenerateBackupTable');
          return $facilityDb->addBackupGeneration($params);
     }

     public function translate($text)
     {
          $translateObj = $this->sm->get('translator');
          return $translateObj->translate($text);
     }

     public function crypto($action, $inputString, $secretIv)
     {

          // return $inputString;
          if (empty($inputString)) return "";

          $output = false;
          $encrypt_method = "AES-256-CBC";
          $secret_key = 'rXBCNkAzkHXGBKEReqrTfPhGDqhzxgDRQ7Q0XqN6BVvuJjh1OBVvuHXGBKEReqrTfPhGDqhzxgDJjh1OB4QcIGAGaml';

          // hash
          $key = hash('sha256', $secret_key);

          if (empty($secretIv)) {
               $secretIv = 'sd893urijsdf8w9eurj';
          }
          // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
          $iv = substr(hash('sha256', $secretIv), 0, 16);

          if ($action == 'encrypt') {
               $output = openssl_encrypt($inputString, $encrypt_method, $key, 0, $iv);
               $output = base64_encode($output);
          } else if ($action == 'decrypt') {

               $output = openssl_decrypt(base64_decode($inputString), $encrypt_method, $key, 0, $iv);
          }
          return $output;
     }

     //get all sample types
     public function getSampleType()
     {
          $sampleDb = $this->sm->get('SampleTypeTable');
          return $sampleDb->fetchAllSampleType();
     }



     //get all Lab Name
     public function getAllLabName()
     {
          $logincontainer = new Container('credo');
          $mappedFacilities = null;
          if ($logincontainer->role != 1) {
               $mappedFacilities = (isset($logincontainer->mappedFacilities) && count($logincontainer->mappedFacilities) > 0) ? $logincontainer->mappedFacilities : null;
          }
          $facilityDb = $this->sm->get('FacilityTable');
          return $facilityDb->fetchAllLabName($mappedFacilities);
     }
     //get all Lab Name
     public function getAllClinicName()
     {

          $logincontainer = new Container('credo');
          $mappedFacilities = null;
          if ($logincontainer->role != 1) {
               $mappedFacilities = (isset($logincontainer->mappedFacilities) && count($logincontainer->mappedFacilities) > 0) ? $logincontainer->mappedFacilities : null;
          }

          $facilityDb = $this->sm->get('FacilityTable');
          return $facilityDb->fetchAllClinicName($mappedFacilities);
     }
     //get all province name
     public function getAllProvinceList()
     {

          $logincontainer = new Container('credo');
          $mappedFacilities = null;
          if ($logincontainer->role != 1) {
               $mappedFacilities = (isset($logincontainer->mappedFacilities) && count($logincontainer->mappedFacilities) > 0) ? $logincontainer->mappedFacilities : null;
          }

          $locationDb = $this->sm->get('LocationDetailsTable');
          return $locationDb->fetchLocationDetails($mappedFacilities);
     }
     public function getAllDistrictList()
     {

          $logincontainer = new Container('credo');
          $mappedFacilities = null;
          if ($logincontainer->role != 1) {
               $mappedFacilities = (isset($logincontainer->mappedFacilities) && count($logincontainer->mappedFacilities) > 0) ? $logincontainer->mappedFacilities : null;
          }
          $locationDb = $this->sm->get('LocationDetailsTable');
          return $locationDb->fetchAllDistrictsList();
     }

     public function getDistrictList($locationId)
     {
          $locationDb = $this->sm->get('LocationDetailsTable');
          return $locationDb->fetchDistrictListByIds($locationId);
     }

     public function getFacilityList($districtId, $facilityType = 1)
     {
          $facilityDb = $this->sm->get('FacilityTable');
          return $facilityDb->fetchFacilityListByDistrict($districtId, $facilityType);
     }

     public function getLastModifiedDateTime($tableName, $modifiedDateTimeColName = 'updated_datetime', $condition = "")
     {
          $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
          $sql = new Sql($dbAdapter);
          $Query = $sql->select()->from($tableName)->columns(array($modifiedDateTimeColName))->order($modifiedDateTimeColName . ' DESC')->where(array($modifiedDateTimeColName . ' IS NOT NULL'))->limit(1);
          if (!empty($condition)) {
               $Query = $Query->where(array($condition));
          }
          $QueryStr = $sql->buildSqlString($Query);
          $result = $dbAdapter->query($QueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          if (isset($result[$modifiedDateTimeColName]) && $result[$modifiedDateTimeColName] != '' && $result[$modifiedDateTimeColName] != NULL && !$this->startsWith($result[$modifiedDateTimeColName], '0000-00-00')) {
               return $result[$modifiedDateTimeColName];
          } else {
               return null;
          }
     }


     public function saveVlsmReferenceTablesFromAPI($params)
     {
          /* if(empty($params['api-version'])){
               return array('status' => 'fail', 'message' => 'Please specify API version');
          } */
          $testReasonDb = $this->sm->get('TestReasonTable');
          $covid19TestReasonDb = $this->sm->get('Covid19TestReasonsTable');
          $artCodeDb = $this->sm->get('ArtCodeTable');
          $sampleRejectionReasonDb = $this->sm->get('SampleRejectionReasonTable');
          $eidSampleRejectionReasonDb = $this->sm->get('EidSampleRejectionReasonTable');
          $covid19SampleRejectionDb = $this->sm->get('Covid19SampleRejectionReasonsTable');
          $eidSampleTypeDb = $this->sm->get('EidSampleTypeTable');
          $covid19SampleTypeDb = $this->sm->get('Covid19SampleTypeTable');
          $covid19ComorbiditiesDb = $this->sm->get('Covid19ComorbiditiesTable');
          $covid19SymptomsDb = $this->sm->get('Covid19SymptomsTable');
          $facilityDb = $this->sm->get('FacilityTableWithoutCache');
          $locationDb = $this->sm->get('LocationDetailsTable');
          $importConfigDb = $this->sm->get('ImportConfigMachineTable');
          $hepatitisSampleTypeDb = $this->sm->get('HepatitisSampleTypeTable');
          $hepatitisSampleRejectionDb = $this->sm->get('HepatitisSampleRejectionReasonTable');
          $hepatitisResultsDb = $this->sm->get('HepatitisResultsTable');
          $hepatitisRiskFactorDb = $this->sm->get('HepatitisRiskFactorTable');
          $hepatitisTestReasonsDb = $this->sm->get('HepatitisTestReasonsTable');

          $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
          $sql = new Sql($dbAdapter);

          $apiData = array();
          $fileName = $_FILES['referenceFile']['name'];
          $ranNumber = str_pad(rand(0, pow(10, 6) - 1), 6, '0', STR_PAD_LEFT);
          $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
          $fileName = $ranNumber . "." . $extension;
          // $fileName = 'ref.json';   // Testing
          if (!file_exists(TEMP_UPLOAD_PATH) && !is_dir(TEMP_UPLOAD_PATH)) {
               mkdir(APPLICATION_PATH . DIRECTORY_SEPARATOR . "temporary", 0777);
          }
          if (!file_exists(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "vlsm-reference") && !is_dir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "vlsm-reference")) {
               mkdir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "vlsm-reference", 0777);
          }

          $pathname = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "vlsm-reference" . DIRECTORY_SEPARATOR . $fileName;
          if (!file_exists($pathname)) {
               if (move_uploaded_file($_FILES['referenceFile']['tmp_name'], $pathname)) {
                    // $apiData = \JsonMachine\JsonMachine::fromFile($pathname);
                    $apiData = json_decode(file_get_contents($pathname));
               }
          }

          /* echo "<pre>";
          print_r($apiData->geographical_divisions);
          die; */
          if ($apiData !== FALSE) {
               /* For update the location details */
               if (isset($apiData->geographical_divisions) && !empty($apiData->geographical_divisions)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->geographical_divisions->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->geographical_divisions->lastModifiedTime) && !empty($apiData->geographical_divisions->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->geographical_divisions->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('location_details', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         $rQueryStr = 'SET FOREIGN_KEY_CHECKS=0; ALTER TABLE `location_details` DISABLE KEYS';
                         $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                         $dbAdapter->query('TRUNCATE TABLE `location_details`', $dbAdapter::QUERY_MODE_EXECUTE);

                         foreach ((array)$apiData->geographical_divisions->tableData as $row) {
                              $lData = (array)$row;
                              $locationData = array(
                                   'location_id'       => $lData['geo_id'],
                                   'parent_location'   => $lData['geo_parent'],
                                   'location_name'     => $lData['geo_name'],
                                   'location_code'     => $lData['geo_code'],
                                   'updated_datetime'  => $lData['updated_datetime']
                              );
                              $locationDb->insert($locationData);
                         }
                    }
               }

               /* For update the Facility Details */
               if (isset($apiData->facility_details) && !empty($apiData->facility_details)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->facility_details->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->facility_details->lastModifiedTime) && !empty($apiData->facility_details->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->facility_details->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('facility_details', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->facility_details->tableData as $row) {
                              $facilityData = (array)$row;
                              unset($facilityData['data_sync']);
                              unset($facilityData['facility_state_id']);
                              unset($facilityData['facility_district_id']);
                              if (trim($facilityData['facility_state']) != '' || $facilityData['facility_state_id'] != '') {
                                   if ($facilityData['facility_state_id'] != "") {
                                        $facilityData['facility_state'] = $facilityData['facility_state_id'];
                                   }
                                   $sQueryResult = $this->checkFacilityStateDistrictDetails(trim($facilityData['facility_state']), 0);
                                   if ($sQueryResult) {
                                        $facilityData['facility_state'] = $sQueryResult['location_id'];
                                   } else {
                                        $locationDb->insert(array('parent_location' => 0, 'location_name' => trim($facilityData['facility_state'])));
                                        $facilityData['facility_state'] = $locationDb->lastInsertValue;
                                   }
                              }
                              if (trim($facilityData['facility_district']) != '' || $facilityData['facility_district_id'] != '') {
                                   if ($facilityData['facility_district_id'] != "") {
                                        $facilityData['facility_district'] = $facilityData['facility_district_id'];
                                   }
                                   $sQueryResult = $this->checkFacilityStateDistrictDetails(trim($facilityData['facility_district']), $facilityData['facility_state']);
                                   if ($sQueryResult) {
                                        $facilityData['facility_district'] = $sQueryResult['location_id'];
                                   } else {
                                        $locationDb->insert(array('parent_location' => $facilityData['facility_state'], 'location_name' => trim($facilityData['facility_district'])));
                                        $facilityData['facility_district'] = $locationDb->lastInsertValue;
                                   }
                              }

                              $facilityDb->insertOrUpdate($facilityData);
                         }
                    }
               }

               /* For update the Test Reasons */
               if (isset($apiData->r_vl_test_reasons) && !empty($apiData->r_vl_test_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_vl_test_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_vl_test_reasons->lastModifiedTime) && !empty($apiData->r_vl_test_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_vl_test_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_vl_test_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_vl_test_reasons->tableData as $row) {
                              $testReasonData = (array)$row;
                              $testReasonDb->insertOrUpdate($testReasonData);
                         }
                    }
               }

               /* For update the Covid19 Test Reasons */
               if (isset($apiData->r_covid19_test_reasons) && !empty($apiData->r_covid19_test_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_covid19_test_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_covid19_test_reasons->lastModifiedTime) && !empty($apiData->r_covid19_test_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_covid19_test_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_covid19_test_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_covid19_test_reasons->tableData as $row) {
                              $covid19TestReasonData = (array)$row;
                              $rQuery = $sql->select()->from('r_covid19_test_reasons')->where(array('test_reason_name LIKE "%' . $covid19TestReasonData['test_reason_name'] . '%" OR test_reason_id = ' . $covid19TestReasonData['test_reason_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $covid19TestReasonDb->update($covid19TestReasonData, array('test_reason_id' => $covid19TestReasonData['test_reason_id']));
                              } else {
                                   $covid19TestReasonDb->insert($covid19TestReasonData);
                              }
                         }
                    }
               }

               /* For update the Art Code Details */
               if (isset($apiData->r_vl_art_regimen) && !empty($apiData->r_vl_art_regimen)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_vl_art_regimen->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_vl_art_regimen->lastModifiedTime) && !empty($apiData->r_vl_art_regimen->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_vl_art_regimen->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_vl_art_regimen', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_vl_art_regimen->tableData as $row) {
                              $artCodeData = (array)$row;
                              unset($artCodeData['data_sync']);
                              $artCodeDb->insertOrUpdate($artCodeData);
                         }
                    }
               }

               /* For update the Sample Rejection Reason Details */
               if (isset($apiData->r_vl_sample_rejection_reasons) && !empty($apiData->r_vl_sample_rejection_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_vl_sample_rejection_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_vl_sample_rejection_reasons->lastModifiedTime) && !empty($apiData->r_vl_sample_rejection_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_vl_sample_rejection_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_vl_sample_rejection_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_vl_sample_rejection_reasons->tableData as $row) {
                              $sampleRejectionReasonData = (array)$row;
                              unset($sampleRejectionReasonData['data_sync']);
                              $sampleRejectionReasonDb->insertOrUpdate($sampleRejectionReasonData);
                         }
                    }
               }

               /* For update the EID Sample Rejection Reason Details */
               if (isset($apiData->r_eid_sample_rejection_reasons) && !empty($apiData->r_eid_sample_rejection_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_eid_sample_rejection_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_eid_sample_rejection_reasons->lastModifiedTime) && !empty($apiData->r_eid_sample_rejection_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_eid_sample_rejection_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_eid_sample_rejection_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_eid_sample_rejection_reasons->tableData as $row) {
                              $eidSampleRejectionReasonData = (array)$row;
                              $rQuery = $sql->select()->from('r_eid_sample_rejection_reasons')->where(array('rejection_reason_name LIKE "%' . $eidSampleRejectionReasonData['rejection_reason_name'] . '%" OR rejection_reason_id = ' . $eidSampleRejectionReasonData['rejection_reason_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $eidSampleRejectionReasonDb->update($eidSampleRejectionReasonData, array('rejection_reason_id' => $eidSampleRejectionReasonData['rejection_reason_id']));
                              } else {
                                   $eidSampleRejectionReasonDb->insert($eidSampleRejectionReasonData);
                              }
                         }
                    }
               }

               /* For update the Covid19 Sample Rejection Reason Details */
               if (isset($apiData->r_covid19_sample_rejection_reasons) && !empty($apiData->r_covid19_sample_rejection_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_covid19_sample_rejection_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_covid19_sample_rejection_reasons->lastModifiedTime) && !empty($apiData->r_covid19_sample_rejection_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_covid19_sample_rejection_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_covid19_sample_rejection_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_covid19_sample_rejection_reasons->tableData as $row) {
                              $covid19SampleRejectionData = (array)$row;
                              $rQuery = $sql->select()->from('r_covid19_sample_rejection_reasons')->where(array('rejection_reason_name LIKE "%' . $covid19SampleRejectionData['rejection_reason_name'] . '%" OR rejection_reason_id = ' . $covid19SampleRejectionData['rejection_reason_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $covid19SampleRejectionDb->update($covid19SampleRejectionData, array('rejection_reason_id' => $covid19SampleRejectionData['rejection_reason_id']));
                              } else {
                                   $covid19SampleRejectionDb->insert($covid19SampleRejectionData);
                              }
                         }
                    }
               }

               /* For update the  Import Config Machine */
               if (isset($apiData->import_config_machines) && !empty($apiData->import_config_machines)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_covid19_symptoms->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->import_config_machines->lastModifiedTime) && !empty($apiData->import_config_machines->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->import_config_machines->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('import_config_machines', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->import_config_machines->tableData as $row) {
                              $importConfigMachData = (array)$row;
                              // \Zend\Debug\Debug::dump($importConfigMachData);die;
                              $rQuery = $sql->select()->from('import_config_machines')->where(array('config_machine_name LIKE "%' . $importConfigMachData['config_machine_name'] . '%" OR config_machine_id = ' . $importConfigMachData['config_machine_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $importConfigDb->update($importConfigMachData, array('config_machine_id' => $importConfigMachData['config_machine_id']));
                              } else {
                                   $importConfigDb->insert($importConfigMachData);
                              }
                         }
                    }
               }

               /* For update the EID Sample Type Details */
               if (isset($apiData->r_eid_sample_type) && !empty($apiData->r_eid_sample_type)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_eid_sample_type->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_eid_sample_type->lastModifiedTime) && !empty($apiData->r_eid_sample_type->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_eid_sample_type->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_eid_sample_type', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_eid_sample_type->tableData as $row) {
                              $eidSampleTypeData = (array)$row;
                              $rQuery = $sql->select()->from('r_eid_sample_type')->where(array('sample_name LIKE "%' . $eidSampleTypeData['sample_name'] . '%" OR sample_id = ' . $eidSampleTypeData['sample_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $eidSampleTypeDb->update($eidSampleTypeData, array('sample_id' => $eidSampleTypeData['sample_id']));
                              } else {
                                   $eidSampleTypeDb->insert($eidSampleTypeData);
                              }
                         }
                    }
               }

               /* For update the Covid19 Sample Type Details */
               if (isset($apiData->r_covid19_sample_type) && !empty($apiData->r_covid19_sample_type)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_covid19_sample_type->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_covid19_sample_type->lastModifiedTime) && !empty($apiData->r_covid19_sample_type->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_covid19_sample_type->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_covid19_sample_type', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_covid19_sample_type->tableData as $row) {
                              $covid19SampleTypeData = (array)$row;
                              $rQuery = $sql->select()->from('r_covid19_sample_type')->where(array('sample_name LIKE "%' . $covid19SampleTypeData['sample_name'] . '%" OR sample_id = ' . $covid19SampleTypeData['sample_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $covid19SampleTypeDb->update($covid19SampleTypeData, array('sample_id' => $covid19SampleTypeData['sample_id']));
                              } else {
                                   $covid19SampleTypeDb->insert($covid19SampleTypeData);
                              }
                         }
                    }
               }

               /* For update the  Covid19 Comorbidities */
               if (isset($apiData->r_covid19_comorbidities) && !empty($apiData->r_covid19_comorbidities)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_covid19_comorbidities->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_covid19_comorbidities->lastModifiedTime) && !empty($apiData->r_covid19_comorbidities->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_covid19_comorbidities->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_covid19_comorbidities', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_covid19_comorbidities->tableData as $row) {
                              $covid19ComorbiditiesData = (array)$row;
                              $rQuery = $sql->select()->from('r_covid19_comorbidities')->where(array('comorbidity_name LIKE "%' . $covid19ComorbiditiesData['comorbidity_name'] . '%" OR comorbidity_id = ' . $covid19ComorbiditiesData['comorbidity_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $covid19ComorbiditiesDb->update($covid19ComorbiditiesData, array('comorbidity_id' => $covid19ComorbiditiesData['comorbidity_id']));
                              } else {
                                   $covid19ComorbiditiesDb->insert($covid19ComorbiditiesData);
                              }
                         }
                    }
               }

               /* For update the  Covid19 Symptoms */
               if (isset($apiData->r_covid19_symptoms) && !empty($apiData->r_covid19_symptoms)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_covid19_symptoms->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_covid19_symptoms->lastModifiedTime) && !empty($apiData->r_covid19_symptoms->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_covid19_symptoms->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_covid19_symptoms', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_covid19_symptoms->tableData as $row) {
                              $covid19SymptomsData = (array)$row;
                              $rQuery = $sql->select()->from('r_covid19_symptoms')->where(array('symptom_name LIKE "%' . $covid19SymptomsData['symptom_name'] . '%" OR symptom_id = ' . $covid19SymptomsData['symptom_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $covid19SymptomsDb->update($covid19SymptomsData, array('symptom_id' => $covid19SymptomsData['symptom_id']));
                              } else {
                                   $covid19SymptomsDb->insert($covid19SymptomsData);
                              }
                         }
                    }
               }

               /* For update the Hepatitis Sample Rejection Reasons Details */
               if (isset($apiData->r_hepatitis_sample_rejection_reasons) && !empty($apiData->r_hepatitis_sample_rejection_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_hepatitis_sample_rejection_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_hepatitis_sample_rejection_reasons->lastModifiedTime) && !empty($apiData->r_hepatitis_sample_rejection_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_hepatitis_sample_rejection_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_hepatitis_sample_rejection_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_hepatitis_sample_rejection_reasons->tableData as $row) {
                              $hepatitisSampleRejectionData = (array)$row;
                              $rQuery = $sql->select()->from('r_hepatitis_sample_rejection_reasons')->where(array('rejection_reason_name LIKE "%' . $hepatitisSampleRejectionData['rejection_reason_name'] . '%" OR rejection_reason_id = ' . $hepatitisSampleRejectionData['rejection_reason_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $hepatitisSampleRejectionDb->update($hepatitisSampleRejectionData, array('rejection_reason_id' => $hepatitisSampleRejectionData['rejection_reason_id']));
                              } else {
                                   $hepatitisSampleRejectionDb->insert($hepatitisSampleRejectionData);
                              }
                         }
                    }
               }

               /* For update the Hepatitis Risk Factor Details */
               if (isset($apiData->r_hepatitis_rick_factors) && !empty($apiData->r_hepatitis_rick_factors)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_hepatitis_rick_factors->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_hepatitis_rick_factors->lastModifiedTime) && !empty($apiData->r_hepatitis_rick_factors->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_hepatitis_rick_factors->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_hepatitis_rick_factors', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_hepatitis_rick_factors->tableData as $row) {
                              $hepatitisRiskData = (array)$row;
                              $rQuery = $sql->select()->from('r_hepatitis_rick_factors')->where(array('riskfactor_name LIKE "%' . $hepatitisRiskData['riskfactor_name'] . '%" OR riskfactor_id = ' . $hepatitisRiskData['riskfactor_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $hepatitisRiskFactorDb->update($hepatitisRiskData, array('riskfactor_id' => $hepatitisRiskData['riskfactor_id']));
                              } else {
                                   $hepatitisRiskFactorDb->insert($hepatitisRiskData);
                              }
                         }
                    }
               }

               /* For update the Hepatitis Results Details */
               if (isset($apiData->r_hepatitis_test_reasons) && !empty($apiData->r_hepatitis_test_reasons)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_hepatitis_test_reasons->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_hepatitis_test_reasons->lastModifiedTime) && !empty($apiData->r_hepatitis_test_reasons->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_hepatitis_test_reasons->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_hepatitis_test_reasons', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_hepatitis_test_reasons->tableData as $row) {
                              $hepatitisTestReasonData = (array)$row;
                              $rQuery = $sql->select()->from('r_hepatitis_test_reasons')->where(array('test_reason_name LIKE "%' . $hepatitisTestReasonData['test_reason_name'] . '%" OR test_reason_id = ' . $hepatitisTestReasonData['test_reason_id']));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $hepatitisTestReasonsDb->update($hepatitisTestReasonData, array('test_reason_id' => $hepatitisTestReasonData['test_reason_id']));
                              } else {
                                   $hepatitisTestReasonsDb->insert($hepatitisTestReasonData);
                              }
                         }
                    }
               }

               /* For update the Hepatitis Results Details */
               if (isset($apiData->r_hepatitis_results) && !empty($apiData->r_hepatitis_results)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_hepatitis_results->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_hepatitis_results->lastModifiedTime) && !empty($apiData->r_hepatitis_results->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_hepatitis_results->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_hepatitis_results', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_hepatitis_results->tableData as $row) {
                              $hepatitisResultData = (array)$row;
                              $rQuery = $sql->select()->from('r_hepatitis_results')->where(array('result LIKE "%' . $hepatitisResultData['result'] . '%" OR result_id = "' . $hepatitisResultData['result_id'] . '" '));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $hepatitisResultsDb->update($hepatitisResultData, array('result_id' => $hepatitisResultData['result_id']));
                              } else {
                                   $hepatitisResultsDb->insert($hepatitisResultData);
                              }
                         }
                    }
               }

               /* For update the Hepatitis Sample Type Details */
               if (isset($apiData->r_hepatitis_sample_type) && !empty($apiData->r_hepatitis_sample_type)) {
                    /* if($apiData->forceSync){
                         $rQueryStr = $apiData->r_hepatitis_sample_type->tableStructure;
                         $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    } */
                    $condition = "";
                    if (isset($apiData->r_hepatitis_sample_type->lastModifiedTime) && !empty($apiData->r_hepatitis_sample_type->lastModifiedTime)) {
                         $condition = "updated_datetime > '" . $apiData->r_hepatitis_sample_type->lastModifiedTime . "'";
                    }
                    $notUpdated = $this->getLastModifiedDateTime('r_hepatitis_sample_type', 'updated_datetime', $condition);
                    if (empty($notUpdated) || !isset($notUpdated)) {
                         foreach ((array)$apiData->r_hepatitis_sample_type->tableData as $row) {
                              $hepatitisSampleTypeData = (array)$row;
                              $rQuery = $sql->select()->from('r_hepatitis_sample_type')->where(array('sample_name LIKE "%' . $hepatitisSampleTypeData['sample_name'] . '%" OR sample_id = "' . $hepatitisSampleTypeData['sample_id'] . '" '));
                              $rQueryStr = $sql->buildSqlString($rQuery);
                              $rowData = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                              if ($rowData) {
                                   $hepatitisSampleTypeDb->update($hepatitisSampleTypeData, array('sample_id' => $hepatitisSampleTypeData['sample_id']));
                              } else {
                                   $hepatitisSampleTypeDb->insert($hepatitisSampleTypeData);
                              }
                         }
                    }
               }
               return array(
                    'status' => 'success',
                    'message' => 'All reference tables synced'
               );
          } else {
               return array(
                    'status' => 'fail',
                    'message' => "File doesn't have data to update"
               );
          }
     }

     public function checkFacilityStateDistrictDetails($location, $parent)
     {
          $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
          $sql = new Sql($dbAdapter);
          if (is_numeric($location)) {
               $where = array('l.parent_location' => $parent, 'l.location_id' => trim($location));
          } else {
               $where = array('l.parent_location' => $parent, 'l.location_name' => trim($location));
          }
          $sQuery = $sql->select()->from(array('l' => 'location_details'))
               ->where($where);
          $sQuery = $sql->buildSqlString($sQuery);
          $sQueryResult = $dbAdapter->query($sQuery, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          return $sQueryResult;
     }

     function getMonthsInRange($startDate, $endDate)
     {
          $months = array();
          while (strtotime($startDate) <= strtotime($endDate)) {
               $monthYear = date('M', strtotime($startDate)) . "-" . date('Y', strtotime($startDate));
               $monthYearDBForamt = date('Y', strtotime($startDate)) . "-" . date('m', strtotime($startDate));
               $months[$monthYear] = $monthYearDBForamt;
               $startDate = date('d M Y', strtotime($startDate . '+ 1 month'));
          }
          return $months;
     }

     public function getAllDashApiReceiverStatsByGrid($parameters)
     {
          $statsDb = $this->sm->get('DashApiReceiverStatsTable');
          return $statsDb->fetchAllDashApiReceiverStatsByGrid($parameters);
     }

     public function getStatusDetails($statusId)
     {
          $statsDb = $this->sm->get('DashApiReceiverStatsTable');
          return $statsDb->fetchStatusDetails($statusId);
     }

     public function getLabSyncStatus($params)
     {
          $statsDb = $this->sm->get('DashApiReceiverStatsTable');
          return $statsDb->fetchLabSyncStatus($params);
     }

     public function generateSyncStatusExcel($params)
     {
          $queryContainer = new Container('query');
          $translator = $this->sm->get('translator');
          if (isset($queryContainer->syncStatus)) {
               try {
                    $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
                    $sql = new Sql($dbAdapter);
                    $sQueryStr = $sql->buildSqlString($queryContainer->syncStatus);
                    $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                    if (isset($sResult) && count($sResult) > 0) {
                         $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                         $sheet = $excel->getActiveSheet();
                         $output = array();

                         $today = new \DateTimeImmutable();
                         $twoWeekExpiry = $today->sub(\DateInterval::createFromDateString('2 weeks'));
                         //$twoWeekExpiry = date("Y-m-d", strtotime(date("Y-m-d") . '-2 weeks'));
                         $threeWeekExpiry = $today->sub(\DateInterval::createFromDateString('4 weeks'));

                         foreach ($sResult as $aRow) {
                              $row = array();

                              $_color = "f08080";

                              $aRow['latest'] = $aRow['latest'] ?: $aRow['requested_on'];
                              $latest = new \DateTimeImmutable($aRow['latest']);

                              $latest = (!empty($aRow['latest'])) ? new \DateTimeImmutable($aRow['latest']) : null;
                              // $twoWeekExpiry = new DateTimeImmutable($twoWeekExpiry);
                              // $threeWeekExpiry = new DateTimeImmutable($threeWeekExpiry);

                              if (empty($latest)) {
                                   $_color = "f08080";
                              } elseif ($latest >= $twoWeekExpiry) {
                                   $_color = "90ee90";
                              } elseif ($latest > $threeWeekExpiry && $latest < $twoWeekExpiry) {
                                   $_color = "ffff00";
                              } elseif ($latest >= $threeWeekExpiry) {
                                   $_color = "f08080";
                              }
                              $color[]['color'] = $_color;
                              
                              $row[] = (isset($aRow['labName']) && !empty($aRow['labName'])) ? ucwords($aRow['labName']) : "";
                              $row[] = (isset($aRow['latest']) && !empty($aRow['latest'])) ? $this->humanDateFormat($aRow['latest']) : "";
                              $row[] = (isset($aRow['dashLastResultsSync']) && !empty($aRow['dashLastResultsSync'])) ? $this->humanDateFormat($aRow['dashLastResultsSync']) : "";
                              $row[] = (isset($aRow['dashLastRequestsSync']) && !empty($aRow['dashLastRequestsSync'])) ? $this->humanDateFormat($aRow['dashLastRequestsSync']) : "";
                              $output[] = $row;
                         }
                         $styleArray = array(
                              'font' => array(
                                   'bold' => true,
                              ),
                              'alignment' => array(
                                   'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                   'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                              ),
                              'borders' => array(
                                   'outline' => array(
                                        'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                   ),
                              )
                         );
                         $borderStyle = array(
                              'alignment' => array(
                                   'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                              ),
                              'borders' => array(
                                   'outline' => array(
                                        'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                   ),
                              )
                         );

                         $sheet->setCellValue('A1', html_entity_decode($translator->translate('Lab Name'), ENT_QUOTES, 'UTF-8'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                         $sheet->setCellValue('B1', html_entity_decode($translator->translate('Last Synced on'), ENT_QUOTES, 'UTF-8'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                         $sheet->setCellValue('C1', html_entity_decode($translator->translate('Last Results Sync from Lab'), ENT_QUOTES, 'UTF-8'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                         $sheet->setCellValue('D1', html_entity_decode($translator->translate('Last Requests Sync from VLSTS'), ENT_QUOTES, 'UTF-8'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                         $sheet->getStyle('A1')->applyFromArray($styleArray);
                         $sheet->getStyle('B1')->applyFromArray($styleArray);
                         $sheet->getStyle('C1')->applyFromArray($styleArray);
                         $sheet->getStyle('D1')->applyFromArray($styleArray);

                         $colorNo = 0;
                         foreach ($output as $rowNo => $rowData) {
                              $colNo = 1;
                              foreach ($rowData as $field => $value) {
                                   $rRowCount = ($rowNo + 2);
                                   $sheet->getCellByColumnAndRow($colNo, $rRowCount)->setValueExplicit(html_entity_decode($value), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                                   // echo "Col : ".$colNo ." => Row : " . $rRowCount . " => Color : " .$color[$colorNo]['color'];
                                   // echo "<br>";
                                   $cellName = $sheet->getCellByColumnAndRow($colNo, $rRowCount)->getColumn();
                                   $sheet->getStyle($cellName . $rRowCount)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($color[$colorNo]['color']);
                                   $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                                   $sheet->getDefaultRowDimension($colNo)->setRowHeight(18);
                                   $sheet->getColumnDimensionByColumn($colNo)->setWidth(30);
                                   $colNo++;
                              }
                              $colorNo++;
                         }
                         $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($excel, 'Xlsx');
                         $filename = 'SAMPLE-SYNC-STATUS-REPORT--' . date('d-M-Y-H-i-s') . '.xlsx';
                         $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
                         return $filename;
                    } else {
                         return "";
                    }
               } catch (Exception $exc) {
                    error_log("SAMPLE-SYNC-STATUS-REPORT--" . $exc->getMessage());
                    error_log($exc->getTraceAsString());
                    return "";
               }
          } else {
               return "";
          }
     }
}
