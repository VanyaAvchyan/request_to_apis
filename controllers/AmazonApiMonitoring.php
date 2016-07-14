<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class AmazonApiMonitoring
{
    /**
     * @var array $config array for configurations
     */
    private $config = [];

    /**
     * @var string $message contains  error messages 
     */
    private $message = '';
    
    /**
     * @var array $postData contains  all post data wich snded before success or error
     */
    private $postData = [];

    /**
     * Initialaizing custom properties
     * 
     * @return void
     */
    public function __construct($config)
    {
        set_time_limit(0);
        $this->config = $config;
    }

    /**
     * This method is main which run index.php 
     * Creating amazon instances with CURL 
     * Get speed with CURL 
     * Getting instances with CURL 
     * Getting speed by location id
     * Getting speed Details
     * If one method returns code = 0 Sending Message to Email with notice
     *
     * @return void
     */
    public function run()
    {
        /**
         * Call getUrl private method and check returned data
         * If returned data column code == 0, 
         * code stops working and calling sendMail private method.
         * Else output success message         * 
         */
        $getUrl = $this->getUrl();
        if(!$getUrl['code'])
        {
            $this->message = $getUrl['message'];
            dp($this->sendMail('getUrl',$this->postData),false);
        }
        else{
            $this->sendMail('getUrl',  $this->postData);
            echo $getUrl['message'].PHP_EOL;
        }

        /**
         * Call createInstance private method and check returned data
         * If returned data column code == 0, 
         * code stops working and calling sendMail private method.
         * Else output success message         * 
         */
        $createdInstance = $this->createInstance();
        if(!$createdInstance['code'])
        {
            $this->message = $createdInstance['message'];
            dp($this->sendMail('createInstance',$this->postData),false);
        }
        else{
            $this->sendMail('createInstance',$this->postData);
            echo $createdInstance['message'].PHP_EOL;
        }
        
        /**
         * Call getSpeed private method and check returned data
         * If returned data column code == 0, 
         * code stops working and calling sendMail private method.
         * Else output success message         * 
         */
        $speed = $this->getSpeed();
        if(!$speed['code'])
        {
            $this->message = $speed['message'];
            dp($this->sendMail('getSpeed',$this->postData),false);
        }
        else{
            $this->sendMail('getSpeed',$this->postData);
            echo $speed['message'].PHP_EOL;
        }
        
        /**
         * Call getSpeed private method and check returned data
         * If returned data column code == 0, 
         * code stops working and calling sendMail private method.
         * Else output success message         * 
         */
        $getInst = $this->getInstance($createdInstance['instanceId']);
        if(!$getInst['code'])
        {
            $this->message = $getInst['message'];
            dp($this->sendMail('getInstance', $this->postData),false);
        }
        else
        {
            $this->sendMail('getInstance', $this->postData);
            echo $getInst['message'].PHP_EOL;
        }

        /**
         * Call getSpeedDetails private method and check returned data
         * If returned data column code == 0, 
         * code stops working and calling sendMail private method.
         * Else output success message         * 
         */
        $speedDetails = $this->getSpeedDetails($speed);
        if(!$speedDetails['code'])
        {
            $this->message = $speedDetails['message'] ;
            dp($this->sendMail('getSpeedDetails', $this->postData),false);
        }
        else{
            $this->sendMail('getSpeedDetails', $this->postData);
            echo $speedDetails['message'].PHP_EOL;
        }
 
        /**
         * Call displayReport private method and check returned data
         * If returned data column code == 0, 
         * code stops working and calling sendMail private method.
         * Else output success message         * 
         */
        $displayReport = $this->displayReport($speedDetails);
        if(!$displayReport['code'])
        {
            $this->message = $displayReport['message'];
            dp($this->sendMail('displayReport', $this->postData),false);
        }
        else{
            $this->sendMail('displayReport', $this->postData);
            echo $displayReport['message'].PHP_EOL;
        }

        /**
         * After this all displaing success message
         */
        $report = $this->config['success_message'].$displayReport['report_file_name'];
        dp($report);
    }

    /**
     * Sending CURL for checking url
     * 
     * @return array
     */
    private function getUrl()
    {
        $postFields  = 'url='.$this->config['url'];
        $curl_out = $this->curlPostRequst( $this->config['live_actions']['getUrl'], $postFields);
        $response = $this->isValidResponse($curl_out, __FUNCTION__);
        $this->postData['action']     = 'getUrl';
        $this->postData['postFields'] = $postFields;
        $this->postData['response']   = $response;
        return $response;
    }

    /**
     * Sending CURL to create instances
     * 
     * @return array
     */
    private function createInstance()
    {
        $postFields  = 'region_code='.$this->config['region_code'].'&';
        $postFields .= 'init_url='.$this->config['init_url'].'&';
        $postFields .= 'url='.$this->config['domain'].'&';
        $postFields .= 'create_config_file='.$this->config['create_config_file'];
        $curl_out = $this->curlPostRequst($this->config['live_actions']['createInstance'], $postFields);
        $response = $this->isValidResponse($curl_out, __FUNCTION__);
        $this->postData['action']     = 'createInstance';
        $this->postData['postFields'] = $postFields;
        $this->postData['response']   = $response;
        return $response;
    }

    /**
     * Sending CURL to get instance
     * 
     * @return array
     */
    private function getInstance($instanceId)
    {
        $postFields  = 'region_code='.   $this->config['region_code'].'&';
        $postFields .= 'instanceId='.    $instanceId.'&';
        $postFields .= 'init_url='.      $this->config['init_url'].'&';
        $postFields .= 'url='.           $this->config['domain'].'&';
        $postFields .= 'protocol='.      $this->config['protocol'].'&';
        $postFields .= 'instance_count='.$this->config['instance_count'];
        $curl_out   = $this->curlPostRequst($this->config['live_actions']['getInstance'], $postFields);
        $response = $this->isValidResponse($curl_out,__FUNCTION__);
        $this->postData['action']     = 'getInstance';
        $this->postData['postFields'] = $postFields;
        $this->postData['response']   = $response;
        return $response;
    }

    /**
     * Sending CURL to check speed
     * 
     * @return array
     */
    private function getSpeed()
    {
        $start_time = time();
        $arr = [];
        $postFields  = 'website='.$this->config['domain'].'&';
        $postFields .= 'region_code=origine_website&';
        $postFields .= 'init_url='.$this->config['init_url'].'&';
        $postFields .= 'url='.$this->config['domain'].'&';
        $postFields .= 'public_dns=';
        $url = $this->config['live_actions']['getSpeed'];

        for( $i = 1; $i <= 2; $i++ )
        {
            $curl_out       = $this->curlPostRequst($url, $postFields);
            $valid_response = $this->isValidResponse($curl_out,__FUNCTION__);
            $this->postData['action']     = 'getSpeed';
            $this->postData['postFields'] = $postFields;
            $this->postData['response']   = $valid_response;
            if($valid_response['code'])
            {
                $arr [] = $valid_response['neustar_id'].'/'.$valid_response['sanfrancisco_id'];
            }  else {
                $i--;
            }
            $duration = time() - $start_time;
            if($duration >= $this->config['duration'])
                return [
                    'code'    => 0,
                    'message' => $this->config['wrong_duration'].$this->config['duration'].' seconds.Action '.__FUNCTION__
                ];
        }
        if( count($arr) > 1 )
        {
            $getSpeedByLoc = $this->getSpeedByLocation($valid_response['neustar_id']);
            
            if (!$getSpeedByLoc['code'])
            {
                $this->message = $getSpeedByLoc['message'];
                dp($this->sendMail('getSpeedByLocation',  $this->postData),false);
            }else{
                $this->sendMail('getSpeedByLocation',  $this->postData);
                echo $getSpeedByLoc['message'];
            }

            $valid_response['speed_origine']                = $getSpeedByLoc['speed_origine'];
            $valid_response['speed']                        = $getSpeedByLoc['speed'];
            $valid_response['random_faster']                = $getSpeedByLoc['random_faster'];
            $valid_response['neustar_location_id_origine']  = $arr[0];
            $valid_response['neustar_location_id']          = $arr[1];
        }
        return $valid_response;
    }

    /**
     * Sending CURL to get speed by location
     * 
     * @param sting $neuStarId
     * @return array
     */
    private function getSpeedByLocation($neuStarId)
    {
        $location_start_time = time();
        while ($neuStarId)
        {
            $postFields = "neustar_id=".$neuStarId;
            $curl_out   = $this->curlPostRequst($this->config['live_actions']['getSpeedByLocation'], $postFields);
            $validRequst = $this->isValidResponse($curl_out,'getSpeedByLocation');
            $this->postData['action']     = 'getSpeedByLocation';
            $this->postData['postFields'] = $postFields;
            $this->postData['response']   = $validRequst;
            if($validRequst['code'])
            {
                $no_valid_speed = $this->config['no_valid_speed'];
                if(!isset($validRequst['speed_sanfrancisco']) && !isset($validRequst['speed_singapore']) && !isset($validRequst['speed_dublin']) && !isset($validRequst['speed_washingtondc']) ){
                    return [
                        'code'    => 0,
                        'message' => $this->config['wrong_code'].__LINE__
                    ];
                }
                if($validRequst['speed_sanfrancisco'] > $no_valid_speed && $validRequst['speed_singapore'] > $no_valid_speed && $validRequst['speed_dublin'] > $no_valid_speed && $validRequst['speed_washingtondc'] > $no_valid_speed){
                    $validRequst['speed_origine'] = $validRequst['speed_sanfrancisco'];
                    $validRequst['speed'] = $this->config['avarage_speed'];
                    $validRequst['random_faster'] = $this->config['random_faster'];
                    return $validRequst;
                }
                $location_duration = time() - $location_start_time;
                if( $location_duration >= $this->config['duration'] )
                    return [
                        'code'    => 0,
                        'message' => $this->config['wrong_duration'].$this->config['duration'].' seconds.Action getSpeedByLocation'
                    ];
            }
            if($validRequst['code'])
                return [
                    'code'    => 0,
                    'message' => $this->config['sero_speed_msg'].__LINE__. ' ['.json_encode($validRequst).' ]'
                ];
            return [
                'code'    => 0,
                'message' => $validRequst['message'].'.Line '.__LINE__. ' ['.json_encode($validRequst).' ]'
            ];
            
        }
        return [
            'code'    => 0,
            'message' => $this->config['neeustar_error_msg'].__LINE__. ' ['.json_encode($validRequst).' ]'
        ];
    }

    /**
     * Sending CURL to get speed details
     *
     * @param array $speedData
     * @return array
     */
    private function getSpeedDetails($speedData)
    {
        $postFields  = 'url='.                           $this->config['domain'].'&';
        $postFields .= 'region_code='.                   $this->config['region_code'].'&';
        $postFields .= 'location='.                      $this->config['location'][0].'&';
        $postFields .= 'speed_origine='.                 $speedData['speed_origine'].'&';
        $postFields .= 'speed='.                         $speedData['speed'].'&';
        $postFields .= 'random_faster='.                 $speedData['random_faster'].'&';
        $postFields .= 'neustar_location_id='.           $speedData['neustar_location_id'].'&';
        $postFields .= 'neustar_location_id_origine='.   $speedData['neustar_location_id_origine'];
        
        $curl_out   = $this->curlPostRequst($this->config['live_actions']['getSpeedDetails'], $postFields);
        $response = $this->isValidResponse($curl_out,__FUNCTION__);
        $this->postData['action']     = 'getSpeedDetails';
        $this->postData['postFields'] = $postFields;
        $this->postData['response']   = $response;
        return $response;
    }

    /**
     * Display report action generating pdf file
     *
     * @param array $reportData
     * @return array
     */
    private function displayReport($reportData)
    {
        $postFields  = 'table_html='.                       $reportData['waterfall_html'].'&';
        $postFields .= 'get_perc_by='.                      $this->config['get_perc_by'].'&';
        $postFields .= 'recommanded_region[0][fullname]='.  $this->config['rec_region_fullname'].'&';
        $postFields .= 'recommanded_region[0][name]='.      $this->config['rec_region_name'].'&';
        $postFields .= 'recommanded_region[0][class_name]='.$this->config['rec_region_class_name'].'&';
        $postFields .= 'recommanded_region[0][prec]='.      $this->config['rec_region_prec'].'&';
        $postFields .= 'url='.                              $this->config['domain'].'&';
        
        $curl_out   = $this->curlPostRequst($this->config['live_actions']['displayReport'], $postFields);
        $response =  $this->isValidResponse($curl_out,__FUNCTION__);
        $this->postData['action']     = 'displayReport';
        $this->postData['postFields'] = $postFields;
        $this->postData['response']   = $response;
        return $response;
    }

    /**
     * Validation response data
     * 
     * @param string $response
     * @return array|boolean
     */
    private function isValidResponse($response, $type)
    {
        if(is_array($response)){
            return [
                'code'=>0,
                'message' => $type.$this->config['not_valid_respons_msg'].' ['.json_encode($response).']'
            ];
        }
        $output = (array)json_decode($response);
        if(!isset($output['code']) && !isset($output['message']))
            return 
            [
                'code'    => 0,
                'message' => $this->config['wrong_code'].'Action '.$type.'. Look at line '.__LINE__.' ['.$response.']'
            ];
        return $output;
    }

    /**
     * Sending Curl
     * 
     * @param string $url
     * @param array $postFields
     * @return array
     */
    private function curlPostRequst( $url, $postFields )
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);
        $info = curl_getinfo($ch);
        curl_close ($ch);

        if($info['http_code'] && $info['http_code']  >= 400 )
        {
            return [
                'code'    => 0,
                'message' => $url. $this->config['incorrect_url'].$info['http_code']
            ];
        }
        return $server_output;
    }
    
    /**
     * Send mail
     * 
     * @return boolean
     */
    private function sendMail($name, array $postData = [])
    {
        $this->postData = [];
        $logPath = $this->config['wornings_log_path'];
        $log = new Logger($name);
        if(empty($postData))
        {
            $log->pushHandler(new StreamHandler($logPath, Logger::NOTICE));
            $notice = $this->config['code_error'].__LINE__;
            $log->notice($notice);
            dp($notice);
        }

        

        if(isset($postData['response']) && isset($postData['response']['code']))
        {
            if($postData['response']['code'])
            {
                $log->pushHandler(new StreamHandler($logPath, Logger::INFO));
                $log->info('Success',$postData);
                return true;
            }
        }else{
            $log->pushHandler(new StreamHandler($logPath, Logger::NOTICE));
            $notice = $this->config['code_error'].__LINE__;
            $log->notice($notice);
            dp($notice);
        }
        
        if(!$this->message && empty($this->message)){
            $log->pushHandler(new StreamHandler($logPath, Logger::NOTICE));
            $notice = $this->config['missing_message_error'].__LINE__;
            $log->notice($notice);
            dp($notice);
        }
        
        // create a log channel
        $log = new Logger($name);
        $log->pushHandler(new StreamHandler($logPath, Logger::WARNING));

        // add records to the log
        $log->warning($this->message, $postData);

        $to      = $this->config['to_email'];
        $subject = $this->config['test_email_subjct'];
        $headers = 'From: '. $this->config['from_email'];

        if( !mail( $to, $subject, $this->message, $headers ) ){
            $mail = new Logger('E-Mail');
            $mail->pushHandler(new StreamHandler($logPath, Logger::WARNING));

            // add records to the log
            $mail->warning(error_get_last());
            echo 'E-mail not sent :'.error_get_last().PHP_EOL;
        }
        echo 'Error into '.$name.',Log fil is '.realpath($logPath).PHP_EOL;
    }
}