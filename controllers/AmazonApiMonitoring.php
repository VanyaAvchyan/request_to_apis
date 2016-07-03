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
    private $config =[];
    /**
     * @var array $message contains  error messages 
     */
    private $message = [];

    /**
     * Initialaizing custom properties
     * 
     * @return void
     */
    public function __construct()
    {
        $this->config = include_once __DIR__ . "/../config/config.php";

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
        $instance = $this->createInstance();

        if(!$instance['code'])
        {
            $this->message[] = $instance['message'];
        }

        $speed = $this->getSpeed();

        if(!$speed['code'])
        {
            $this->message[] = $speed['message'];
        }

        $getInst = $this->getInstance($instance['instanceId']);
        
        if(!$getInst['code'])
        {
            $this->message[] = $getInst['message'];
        }
        if($speed['code'] && isset($speed['neustar_id'])) {
            $getSpeedByLoc = $this->getSpeedByLocation($speed['neustar_id']);
            if (!$getSpeedByLoc['code'])
            {
                $this->message[] = $getSpeedByLoc['message'];
            }

            if(false)
            {
                $speedData['speed_origine'] = $getSpeedByLoc['sanfrancisco'] ;
                $speedData['speed'] = '' ;
                $speedData['neustar_location_id'] = '' ;
                $speedData['neustar_location_id_origine'] = '' ;

                $speedDetails = $this->getSpeedDetails($speedData);
                if(!$speedDetails['code']){
                    $this->message[] = $speedDetails['message'] ;
                }
            }
        }

        if(!empty($this->message))
        {
            $messageBody = '';
            foreach( $this->message as $error)
            {
                $messageBody .= $error.'<br>';
            }
            $to      = $this->config['to_email'];
            $subject = 'the subject';
            $headers = 'From: '. $this->config['from_email'];

            mail($to, $subject, $messageBody, $headers);
        }
    }

    /**
     * Sending CURL to create instances
     * 
     * @return array
     */
    private function createInstance()
    {   
        $postFields  = 'region_code=us-east-1&';
        $postFields .= 'init_url=google.com&';
        $postFields .= 'url=www.google.com&';
        $postFields .= 'create_config_file=true';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://deploy.aiscaler.com/createinstance');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);
        return (array)json_decode($server_output);
    }

    /**
     * Sending CURL to check speed
     * 
     * @return array
     */
    private function getSpeed()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://deploy.aiscaler.com/getspeed');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "website=www.google.com&region_code=origine_website&init_url=google.com&url=www.google.com&public_dns=");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        return (array)json_decode($server_output);
    }

    /**
     * Sending CURL to get instance
     * 
     * @return array
     */
    private function getInstance($instanceId)
    {
        $postField  = 'region_code=us-east-1&';
        $postField .= 'instanceId='.$instanceId.'&';
        $postField .= 'init_url=google.com&';
        $postField .= 'url=www.google.com&';
        $postField .= 'protocol=http&';
        $postField .= 'instance_count=1';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://deploy.aiscaler.com/getinstance');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postField);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        return (array)json_decode($server_output);
    }

    /**
     * Sending CURL to get speed by location
     * 
     * @param sting $neuStarId
     * @return array
     */
    private function getSpeedByLocation($neuStarId)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://deploy.aiscaler.com/getspeedbylocation');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "neustar_id=".$neuStarId);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        return (array)json_decode($server_output);
    }

    /**
     * Sending CURL to get speed details
     *
     * @param array $speedData
     * @return array
     */
    private function getSpeedDetails($speedData)
    {
        $postField  = 'url='.                           $this->config['domain'].'&';
        $postField .= 'region_code='.                   $this->config['region_code'].'&';
        $postField .= 'location='.                      $this->config['location'].'&';
        $postField .= 'speed_origine='.                 $speedData['speed_origine'].'&';
        $postField .= 'speed='.                         $speedData['speed'].'&';
        $postField .= 'neustar_location_id='.           $speedData['neustar_location_id'].'&';
        $postField .= 'neustar_location_id_origine='.   $speedData['neustar_location_id_origine'].'&';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://deploy.aiscaler.com/getspeeddetails');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postField);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        return (array)json_decode($server_output);
    }
}