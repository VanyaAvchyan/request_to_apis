<?php

function dp($data, $type = true){
    echo '<pre>';
    if($type){
        print_r($data);
    }
    else{
        var_dump($data);
    }
    exit();
}

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
     * Initialaizing custom properties
     * 
     * @return void
     */
    public function __construct()
    {
        $this->config = include __DIR__ . "/../config/config.php";
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
        set_time_limit(0);

        $getUrl = $this->getUrl();
        if(!$getUrl['code'])
        {
            $this->message = $getUrl['message'];
            dp($this->sendMail(),false);
        }

        $createdInstance = $this->createInstance();
        if(!$createdInstance['code'])
        {
            $this->message = $createdInstance['message'];
            dp($this->sendMail(),false);
        }

        $speed = $this->getSpeed();
        if(!$speed['code'])
        {
            $this->message = $speed['message'];
            dp($this->sendMail(),false);
        }

        $getInst = $this->getInstance($createdInstance['instanceId']);
        if(!$getInst['code'])
        {
            $this->message = $getInst['message'];
            dp($this->sendMail(),false);
        }
//
//        $getSpeedByLoc = $this->getSpeedByLocation($speed['neustar_id']);
//        if (!$getSpeedByLoc['code'])
//        {
//            $this->message = $getSpeedByLoc['message'];
//            dp($this->sendMail(),false);
//        }

        $speedDetails = $this->getSpeedDetails($speed);
        if(!$speedDetails['code'])
        {
            $this->message = $speedDetails['message'] ;
        }
        dp('Everithing is ok');
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
        return $this->isValidResponse($curl_out, __FUNCTION__);
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
        return $this->isValidResponse($curl_out, __FUNCTION__);
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
        return $this->isValidResponse($curl_out,__FUNCTION__);
    }

    /**
     * Sending CURL to check speed
     * 
     * @return array
     */
    private function getSpeed()
    {
        $cnt = 0;
        $arr = [];
        
        $postFields  = 'website='.$this->config['domain'].'&';
        $postFields .= 'region_code=origine_website&';
        $postFields .= 'init_url='.$this->config['init_url'].'&';
        $postFields .= 'url='.$this->config['domain'].'&';
        $postFields .= 'public_dns=';
        $url = $this->config['live_actions']['getSpeed'];

        for( $i = 1; $i <= 2; $i++ )
        {
            $cnt++;
            $curl_out       = $this->curlPostRequst($url, $postFields);
            $valid_response = $this->isValidResponse($curl_out,__FUNCTION__);
            if($valid_response['code'])
            {
                $arr [] = $valid_response['neustar_id'].'/'.$valid_response['sanfrancisco_id'];
            }  else {
                $i--;
            }
            if($cnt == $this->config['duration'])
                return [
                    'code'    => 0,
                    'message' => $this->config['wrong_duration'].$cnt
                ];
        }
        if( count($arr) > 1 )
        {
            $getSpeedByLoc = $this->getSpeedByLocation($valid_response['neustar_id']);
            if (!$getSpeedByLoc['code'])
            {
                $this->message = $getSpeedByLoc['message'];
                dp($this->sendMail(),false);
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
        $cntSBL = 0;
        while ($neuStarId && $cntSBL < $this->config['duration'])
        {
            $cntSBL++;
            $postFields = "neustar_id=".$neuStarId;
            $curl_out   = $this->curlPostRequst($this->config['live_actions']['getSpeedByLocation'], $postFields);
            $validRequst = $this->isValidResponse($curl_out,__FUNCTION__);
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
                    $validRequst['speed'] = 1.78;
                    $validRequst['random_faster'] = 16;
                    return $validRequst;
                }

                if( $cntSBL == $this->config['duration'] )
                    return [
                        'code'    => 0,
                        'message' => $this->config['wrong_duration'].$cntSBL
                    ];
            }
            return [
                'code'    => 0,
                'message' => $this->config['wrong_duration'].$cntSBL
            ];
        }
        return [
            'code'    => 0,
            'message' => 'Something wet wrong,line '.__LINE__
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
        return $this->isValidResponse($curl_out,__FUNCTION__);
    }
    
    /**
     * Send mail
     * 
     * @return boolean
     */
    private function sendMail()
    {
        return $this->message;
        if(!$this->message && empty($this->message))
            dp('Somthing wet wrong , message is missing');
        $to      = $this->config['to_email'];
        $subject = $this->config['email_subjct'];
        $headers = 'From: '. $this->config['from_email'];

        if( mail( $to, $subject, $this->message, $headers ) )
            return true;
        return false;
    }
    
    /**
     * Validation response data
     * 
     * @param string $response
     * @return array|boolean
     */
    private function isValidResponse($response, $type)
    {
        $output = (array)json_decode($response);
        if(!isset($output['code']) && !isset($output['message']))
            return [
                'code'    => 0,
                'message' => $this->config['wrong_code'].'Action '.$type.',  Look at line '.__LINE__
            ];
        if(!empty($output))
            return $output;
        return
        [
            'code'    => 0,
            'message' => $type.' '.$this->config['validation_error']
        ];
    }

    /**
     * Validating response data
     * 
     * @param string $response
     * @return array|boolean
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
        
        if($info['http_code'] && $info['http_code']  >= 400 ){
            return [
                'code'    => 0,
                'message' => $url. $this->config['incorrect_url'].$info['http_code']
            ];
        }
        return $server_output;
    }
}