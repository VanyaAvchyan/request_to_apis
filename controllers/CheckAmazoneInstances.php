<?php
use Aws\Ec2\Ec2Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CheckAmazoneInstances
{
    private $config = [],
            $runnedInstances = [];
    /**
     * Initialaizing private configuration  properties

     * @param type $config
     * $return void
     */
    public function __construct($config)
    {
        $this->config = $config;
        if(!file_exists($this->config['monitor_json_file'])){
            $fp = fopen($this->config['monitor_json_file'], 'w');
            fclose($fp);
        }
        if(!file_exists($this->config['monitor_log'])){
            $fp = fopen($this->config['monitor_log'], 'w');
            fclose($fp);
        }
    }

    /**
     * This is a main action
     * Iterating by regions
     * After each iteration call getRunnedInstances private mthod
     * 
     * @return void
     */
    public function run()
    {
        $json = file_get_contents($this->config['monitor_json_file']);
        $oldrunnedInstances = (array)json_decode($json);

        $notice = [];
        $runnedInstances = [];
        $sendMail = false;

        foreach ($this->config['regions'] as $region)
        {
            if(!empty($this->getRunnedInstances($region)))
                $runnedInstances[] = $this->getRunnedInstances($region);
        }

        if(!empty($runnedInstances))
        {
            foreach ($oldrunnedInstances as $key => $oldrunnedInstance)
            {
                if(is_array($oldrunnedInstance) && isset($runnedInstances[$key]))
                {
                    foreach ( $oldrunnedInstance as $oKey => $instance )
                    {
                        $instance = (array)$instance;
                        if(isset($runnedInstances[$key][$oKey]))
                        {
                            $newRunnedInstance = $runnedInstances[$key][$oKey];
                            $runnedInstances[$key][$oKey] = $instance;
                            if($instance['instanceId'] == $newRunnedInstance['instanceId'])
                            {
                                if(($duration = $newRunnedInstance['time'] - $instance['time']) >= $this->config['runned_duration'])
                                {
                                    $newRunnedInstance['duration'] = $duration;
                                    $notice[$newRunnedInstance['instanceId']] = $newRunnedInstance;
                                    $sendMail = true;
                                }
                            }
                        }
                    }
                }
            }
        }
        if(!empty($runnedInstances))
        {
            $runnedInstancesJson = json_encode($runnedInstances);
            file_put_contents($this->config['monitor_json_file'], $runnedInstancesJson);
            echo $this->config['monitor_notice_msg'].PHP_EOL;
            $this->notify($notice, $sendMail);
        }else
            echo $this->config['monitor_success_msg'].PHP_EOL;
    }
    
    /**
     * Get runned Instances by region
     * 
     * @param string $region
     * @return array
     */
    private function getRunnedInstances($region)
    {
        $runnedInstances = [];
        try{
            $aws = Ec2Client::factory(array(
                'key'    => $this->config['key'],
                'secret' => $this->config['secret'],
                'region' => $region
            ));
            $result = $aws->DescribeInstances();
            $reservations = $result['Reservations'];

            foreach ($reservations as $reservation) {
                $instances = $reservation['Instances'];
                foreach ($instances as $instance)
                {
                    if(strpos( $instance['KeyName'], $this->config['instance_key_name']) === false){
                        continue;
                    }
                    if($instance['State']['Name'] === 'running')
                    {
                        $this->runnedInstances['state']           = $instance['State']['Name'];
                        $this->runnedInstances['instanceId']      = $instance['InstanceId'];
                        $this->runnedInstances['keyName']         = $instance['KeyName'];
                        $this->runnedInstances['region']          = $region;
                        $this->runnedInstances['time']            = time();
                        $this->runnedInstances['imageId']         = $instance['ImageId'];
                        $this->runnedInstances['privateDnsName']  = $instance['PrivateDnsName'];
                        $this->runnedInstances['instanceType']    = $instance['InstanceType'];
                        $this->runnedInstances['securityGroups']  = $instance['SecurityGroups'];
                        $runnedInstances[] = $this->runnedInstances;

                        echo '============================'. PHP_EOL;
                        echo '---> Region: ' . $region . PHP_EOL;
                        echo '---> Key Name: ' . $instance['KeyName'] . PHP_EOL;
                        echo '---> State: ' . $instance['State']['Name'] . PHP_EOL;
                        echo '---> Instance ID: ' . $instance['InstanceId'] . PHP_EOL;
                        echo '---> Image ID: ' . $instance['ImageId'] . PHP_EOL;
                        echo '---> Private Dns Name: ' . $instance['PrivateDnsName'] . PHP_EOL;
                        echo '---> Instance Type: ' . $instance['InstanceType'] . PHP_EOL;
                        if(isset($instance['SecurityGroups'][0]))
                            echo '---> Security Group: ' . $instance['SecurityGroups'][0]['GroupName'] . PHP_EOL;
                        else
                            echo '---> Security Group: Undefined'. PHP_EOL;
                        echo '---------------------------------------------'.PHP_EOL;
                        echo  PHP_EOL;
                    }
                }
            }
        }catch (\Aws\Ec2\Exception\Ec2Exception $e)
        {
            $server = new Logger('Amazone server');
            $server->pushHandler(new StreamHandler($this->config['monitor_log'], Logger::ERROR));
            $server->error($e->getMessage());
            dp('Amazone server: '.$e->getMessage());
        }
        return $runnedInstances;
    }
    /**
     * Generate a message based on an array of $notices to send email and logging
     * Send mail with notice mssage
     * Writ notice mssage into monitorLog.txt file
     * 
     * @param array $notices
     * @return void
     */
    private function notify(array $notices, $sendMail = false)
    {
        if(empty($notices))
            return false;
        $message = '';
        $name = 'Runned instances';
        $duration = $this->config['runned_duration'];
        $message .= '['.date("Y-m-d H:i:s").' ]'.$this->config['monitor_error_msg'].PHP_EOL;
        $message .= 'Permissible delay '.$duration.' seconds'.PHP_EOL;
        foreach ($notices as $notice)
        {
            $message .=  '======================================'.PHP_EOL;
            $message .= 'Exceeded for more than '.($notice['duration']-$duration).' seconds'.PHP_EOL;
            $message .= '---> Instance ID: '.$notice['instanceId'].PHP_EOL;
            $message .= '---> Key Name: ' . $notice['keyName'] . PHP_EOL;
            $message .= '---> Instance ID: '.$notice['instanceId'].PHP_EOL;
            $message .= '---> Region: '.$notice['region'].PHP_EOL;
            $message .= '---> State: '.$notice['state'].PHP_EOL;
            $message .= '---> Image ID: '.$notice['imageId'].PHP_EOL;
            $message .= '---> Private Dns Name: '.$notice['privateDnsName'].PHP_EOL;
            $message .= '---> Instance Type: '.$notice['instanceType'].PHP_EOL;
            if(isset($notice['securityGroups'][0]))
                $message .= '---> Security Group: ' . $notice['securityGroups'][0]['GroupName'] . PHP_EOL;
            else
                $message .= '---> Security Group: Undefined'. PHP_EOL;
            $message .= '------------------------------'. PHP_EOL;
            $message .=  PHP_EOL;
            $message .=  PHP_EOL;
            
        }
        $logPath = $this->config['monitor_log'];
        Helper::safefilerewrite($logPath, $message,'a+');

        $to      = $this->config['to_email'];
        $subject = $this->config['monitor_email_subjct'];
        $headers = 'From: '. $this->config['from_email'];

        if($sendMail && !mail( $to, $subject, $message, $headers ) ){
            $mail = new Logger('E-Mail');
            $mail->pushHandler(new StreamHandler($logPath, Logger::WARNING));

            // add records to the log
            $mail->warning(error_get_last());
            echo 'E-mail not sent :'.error_get_last().PHP_EOL;
        }
        echo '['.$name.'] The message was formed. Log fil is '.realpath($logPath). PHP_EOL;
    }
}