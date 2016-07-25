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
    private $input = [];
    
    /**
     * @var array $postData contains  all post data wich snded before success or error
     */
    private $speedData = [];

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
     * Default action
     * Call getUrl private mthod
     * 
     * @return void
     */
    public function run()
    {
        $getUrlData = $this->getUrl();
        if(!$this->checkResponse('getUrl', $getUrlData))
            exit;
        
        $createdInstanceData = $this->createInstance();
        if(!($createdInstanceData = $this->checkResponse('createInstance', $createdInstanceData)))
            exit;
        
        $getSpeedData = $this->getSpeed();
        if(!($getSpeedOriginalData = $this->checkResponse('getSpeed', $getSpeedData)))
            exit;
        $speed['neustar_location_id_origine'] = $getSpeedOriginalData['neustar_id'].'/'.$getSpeedOriginalData['sanfrancisco_id'];
        
        $getInstanceData = $this->getInstance($createdInstanceData['instanceId']);
        if(!($getInstanceData = $this->checkResponse('getInstance', $getInstanceData)))
            exit;

        $getSpeedData = $this->getSpeed($getInstanceData['PublicDnsName']);
        if(!($getSpeedData = $this->checkResponse('getSpeed', $getSpeedData)))
            exit;
        $speed['neustar_location_id'] = $getSpeedData['neustar_id'].'/'.$getSpeedData['sanfrancisco_id'];
        
        $getSpeedByLocationData = $this->getSpeedByLocation($getSpeedData['neustar_id']);
        if(!$this->checkResponse('getSpeedByLocation', json_encode($getSpeedByLocationData)))
            exit;

        $getSpeedDetailsData = $this->getSpeedDetails($speed);
        if(!($getSpeedDetailsData = $this->checkResponse('getSpeedDetails', $getSpeedDetailsData)))
            exit;

        $displayReport = $this->displayReport($getSpeedDetailsData['waterfall_html']);
        if(!$this->checkResponse('displayReport', $displayReport))
            exit;

        $this->callSuccess();
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
        return $curl_out;
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
        return $curl_out;
    }
    /**
     * Sending CURL to check speed
     * 
     * @return array
     */
    private function getSpeed($public_dns = false)
    {
        $postFields  = 'website='.((!$public_dns)?$this->config['domain']:$public_dns).'&';
        $postFields .= 'region_code=origine_website&';
        $postFields .= 'init_url='.$this->config['init_url'].'&';
        $postFields .= 'url='.$this->config['domain'].'&';
        $postFields .= 'public_dns='.((!$public_dns)?'':$public_dns);
        $url = $this->config['live_actions']['getSpeed'];

        $curl_out = $this->curlPostRequst($url, $postFields);
        return $curl_out;
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
        return $curl_out;
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
        $message = '';
        $delimiter = ' {{{|}}} ';
        while ($neuStarId)
        {
            $postFields = "neustar_id=".$neuStarId;
            $curl_out   = $this->curlPostRequst($this->config['live_actions']['getSpeedByLocation'], $postFields);
            $validResponse = $this->isValidResponse($curl_out,'getSpeedByLocation');
            if($validResponse['code'])
            {
                $no_valid_speed = $this->config['no_valid_speed'];
                if(!isset($validResponse['speed_sanfrancisco']) && !isset($validResponse['speed_singapore']) && !isset($validResponse['speed_dublin']) && !isset($validResponse['speed_washingtondc']) ){
                    $message = $curl_out.$delimiter;
                    $message .= $this->config['wrong_code'].__LINE__;
                    return [
                        'code'    => 0,
                        'message' => $message
                    ];
                }
                if($validResponse['speed_sanfrancisco'] > $no_valid_speed && $validResponse['speed_singapore'] > $no_valid_speed && $validResponse['speed_dublin'] > $no_valid_speed && $validResponse['speed_washingtondc'] > $no_valid_speed)
                {
                    return $validResponse;
                }
                $location_duration = time() - $location_start_time;
                if( $location_duration >= $this->config['duration'] )
                {
                    $message .= $this->config['wrong_duration'].$delimiter;
                    return [
                        'code'    => 0,
                        'message' => $message
                    ];
                }
            }else{
                return $validResponse;
            }
        }
        return [
            'code'    => 0,
            'message' => json_encode($validResponse).$delimiter.$this->config['neeustar_error_msg'].__LINE__
        ];
    }
    
    /**
     * Sending CURL to get speed details
     *
     * @param array $speedData
     * @return array
     */
    private function getSpeedDetails($speed)
    {
        $postFields  = 'url='.                           $this->config['domain'].'&';
        $postFields .= 'region_code='.                   $this->config['region_code'].'&';
        $postFields .= 'location='.                      $this->config['location'][0].'&';
        $postFields .= 'speed_origine='.                 $this->config['speed_origine'].'&';
        $postFields .= 'speed='.                         $this->config['speed'].'&';
        $postFields .= 'random_faster='.                 $this->config['random_faster'].'&';
        $postFields .= 'neustar_location_id='.           $speed['neustar_location_id'].'&';
        $postFields .= 'neustar_location_id_origine='.   $speed['neustar_location_id_origine'];
        
        $curl_out   = $this->curlPostRequst($this->config['live_actions']['getSpeedDetails'], $postFields);
        return $curl_out;
    }

    /**
     * Display report action generating pdf file
     *
     * @param array $reportData
     * @return array
     */
    private function displayReport( $table_html )
    {
        $postFields  = 'table_html='.                       $table_html.'&';
        $postFields .= 'get_perc_by='.                      $this->config['get_perc_by'].'&';
        $postFields .= 'recommanded_region[0][fullname]='.  $this->config['rec_region_fullname'].'&';
        $postFields .= 'recommanded_region[0][name]='.      $this->config['rec_region_name'].'&';
        $postFields .= 'recommanded_region[0][class_name]='.$this->config['rec_region_class_name'].'&';
        $postFields .= 'recommanded_region[0][prec]='.      $this->config['rec_region_prec'].'&';
        $postFields .= 'url='.                              $this->config['domain'].'&';
        
        $curl_out   = $this->curlPostRequst($this->config['live_actions']['displayReport'], $postFields);
        return $curl_out;
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
        $errorMessage = '';
        sleep($this->config['sleep_time']);
        $this->input = $postFields;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['curl_duration_time']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);
        $info = curl_getinfo($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close ($ch);

        if($curl_errno > 0)
        {
            return [
                'code'    => 0,
                'message' => $curl_error
            ];
        }
        if($info['http_code'] && $info['http_code']  >= 400 )
        {
            $errorMessage = $server_output.' {{{|}}} ';
            $errorMessage .= $url.' --> ';
            return [
                'code'    => 0,
                'message' => $errorMessage. $this->config['incorrect_url'].$info['http_code']
            ];
        }
        return $server_output;
    }
    
    /**
     * Validation response data
     * 
     * @param string $response
     * @return array|boolean
     */
    private function isValidResponse($response, $type)
    {
        $errorMessage = '';
        if(is_array($response))
        {
            $response = json_encode($response);
            $errorMessage = $response.' {{{|}}} ';
            return [
                'code'=>0,
                'message' => $errorMessage.$this->config['not_valid_respons_msg']
            ];
        }
        $output = (array)json_decode($response);
        if(!isset($output['code']) && !isset($output['message'])){
            $response = (string)$response;
            $errorMessage = $response.' {{{|}}} ';
            return 
            [
                'code'    => 0,
                'message' => $errorMessage.$this->config['wrong_code'].'. Look at line '.__LINE__
            ];
        }
        return $output;
    }

    /**
     * Check response data
     */
    private function checkResponse( $method, $response )
    {
        $delimiter = '{{{|}}}';
        $response = $this->isValidResponse( $response, $method );
        echo $response['message'].PHP_EOL;
        $this->message .= '{'.date('Y-m-d H:i:s').'} api Function '.$method.' executed with following input output'.PHP_EOL;
        $this->message .= 'INPUT:'.PHP_EOL;
        $this->message .= '--->Action:'.$method.PHP_EOL;
        $this->message .= '--->Post fields:'.$this->input.PHP_EOL;
        if(!$response['code'])
            $this->message .= '[ERROR] ';
        else
            $this->message .= '[SUCCESS] ';
        $this->message .= 'OUTPUT:'.PHP_EOL;
        if(strpos($response['message'],$delimiter)){
            foreach (explode($delimiter, $response['message']) as $msg)
            {
                $this->message .= '--->'.  trim($msg).PHP_EOL;
            }
        }else{
            $this->message .= '--->'.json_encode($response).PHP_EOL;
        }
        $this->message .= '---------------------------------'.PHP_EOL;
        $this->message .= PHP_EOL;
        if(!$response['code'])
        {
            $this->callError ();
            return false;
        }
        return $response;
    }

    /**
     * Calling when response return success
     * Write success message into log file 
     * 
     * @return void
     */
    private function callSuccess()
    {
        $message = '['.date('Y-m-d H:i:s').'] Test.SUCCESS'.PHP_EOL;
        $message .= PHP_EOL;
        $message .= $this->message;
        Helper::safefilerewrite($this->config['wornings_log_path'], $message,'a+');
    }

    /**
     * Calling when response return error 
     * Write error message into log file
     * 
     * @return void
     */
    private function callError()
    {
        $message = '['.date('Y-m-d H:i:s').'] Test.ERROR'.PHP_EOL;
        $message .= PHP_EOL;
        $message .= $this->message;
        
        Helper::safefilerewrite($this->config['wornings_log_path'], $message,'a+');

        $to      = $this->config['to_email'];
        $subject = $this->config['monitor_email_subjct'];
        $headers = 'From: '. $this->config['from_email'];

        if(!mail( $to, $subject, $message, $headers ) ){
            $mail = new Logger('E-Mail');
            $mail->pushHandler(new StreamHandler($logPath, Logger::WARNING));

            // add records to the log
            $mail->warning(error_get_last());
            echo 'E-mail not sent :'.error_get_last().PHP_EOL;
        }
    }
}