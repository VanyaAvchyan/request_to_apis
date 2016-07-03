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
        $instance = $this->createInstance();
        if(!$instance['code'])
        {
            $this->message = $instance['message'];
            dp($this->sendMail(),false);
        }

        $speed = $this->getSpeed();
        if(!$speed['code'])
        {
            $this->message = $speed['message'];
            dp($this->sendMail(),false);
        }

        $getInst = $this->getInstance($instance['instanceId']);
        if(!$getInst['code'])
        {
            $this->message = $getInst['message'];
            dp($this->sendMail(),false);
        }
        
        $getSpeedByLoc = $this->getSpeedByLocation($speed['neustar_id']);
        if (!$getSpeedByLoc['code'])
        {
            $this->message = $getSpeedByLoc['message'];
            dp($this->sendMail(),false);
        }

        if(false)
        {
            $speedData['speed_origine'] = $getSpeedByLoc['sanfrancisco'] ;
            $speedData['speed'] = '' ;
            $speedData['neustar_location_id'] = '' ;
            $speedData['neustar_location_id_origine'] = '' ;

            $speedDetails = $this->getSpeedDetails($speedData);
            if(!$speedDetails['code'])
            {
                $this->message = $speedDetails['message'] ;
            }
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
     * Sending CURL to check speed
     * 
     * @return array
     */
    private function getSpeed()
    {
        $postFields  = 'website='.$this->config['domain'].'&';
        $postFields .= 'region_code=origine_website&';
        $postFields .= 'init_url='.$this->config['init_url'].'&';
        $postFields .= 'url='.$this->config['domain'].'&';
        $postFields .= 'public_dns=';
        $curl_out    = $this->curlPostRequst($this->config['live_actions']['getSpeed'], $postFields);
        return $this->isValidResponse($curl_out,__FUNCTION__);
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
     * Sending CURL to get speed by location
     * 
     * @param sting $neuStarId
     * @return array
     */
    private function getSpeedByLocation($neuStarId)
    {
        $cnt = 0;
        while ($neuStarId && $cnt < $this->config['duration']){
            $cnt++;
            $postFields .= "neustar_id=".$neuStarId;
            $curl_out   = $this->curlPostRequst($this->config['live_actions']['getSpeedByLocation'], $postFields);
            $validRequst = $this->isValidResponse($curl_out,__FUNCTION__);
            if($validRequst['code'])
            {
                $valid_sped = $this->config['valid_speed'];
                if(!isset($validRequst['speed_sanfrancisco']) && !isset($validRequst['speed_singapore']) && !isset($validRequst['speed_dublin']) && !isset($validRequst['speed_washingtondc']) ){
                    return [
                        'code'    => 0,
                        'message' => $this->config['wrong_code'].__LINE__
                    ];
                }
                if($validRequst['speed_sanfrancisco'] > $valid_sped && $validRequst['speed_singapore'] > $valid_sped && $validRequst['speed_dublin'] > $valid_sped && $validRequst['speed_washingtondc'] > $valid_sped)
                    return $validRequst;
                if( $cnt > $this->config['duration'] )
                    return [
                        'code'    => 0,
                        'message' => $this->config['wrong_duration'].$cnt
                    ];
            }
            return [
                'code'    => 0,
                'message' => $this->config['wrong_duration'].$cnt
            ];
        }
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
        $postFields .= 'location='.                      $this->config['location'].'&';
        $postFields .= 'speed_origine='.                 $speedData['speed_origine'].'&';
        $postFields .= 'speed='.                         $speedData['speed'].'&';
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
     * Validation response data
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