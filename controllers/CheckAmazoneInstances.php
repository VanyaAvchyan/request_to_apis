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
    public function __construct($config) {
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
        $json = (array)json_decode($json);

        $notice = [];
        $runnedInstances = [];

        foreach ($this->config['regions'] as $region)
        {
            $runnedInstances[] = $this->getRunnedInstances($region);
        }
        foreach ($json as $key => $rInstances)
        {
            if(is_array($rInstances) && isset($runnedInstances[$key]))
            {
                foreach ($rInstances as $rKey=>$nstance)
                {
                    $nstance = (array)$nstance;
                    if(isset($runnedInstances[$key][$rKey]))
                    {
                        $newRunnedInstance = $runnedInstances[$key][$rKey];
                        if($nstance['instanceId'] == $newRunnedInstance['instanceId'])
                        {
                            if(($duration = $newRunnedInstance['time'] - $nstance['time']) >= $this->config['runned_duration'])
                            {
                                $newRunnedInstance['duration'] = $duration;
                                $notice[$newRunnedInstance['instanceId']] = $newRunnedInstance;
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
            echo '1. Runned Instances saved'.PHP_EOL;
        }
        $this->notify($notice);
    }
    
    /**
     * Get runned Instances by region
     * 
     * @param string $region
     * @return array
     */
    private function getRunnedInstances($region)
    {
        $arr = [];
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
                foreach ($instances as $instance) {
                    if($instance['State']['Name'] == 'running'){
                        $this->runnedInstances['state']           = $instance['State']['Name'];
                        $this->runnedInstances['instanceId']      = $instance['InstanceId'];
                        $this->runnedInstances['region']          = 'us-east-1';
                        $this->runnedInstances['time']            = time();
                        $this->runnedInstances['imageId']         = $instance['ImageId'];
                        $this->runnedInstances['privateDnsName']  = $instance['PrivateDnsName'];
                        $this->runnedInstances['instanceType']    = $instance['InstanceType'];
                        $this->runnedInstances['securityGroups']  = $instance['SecurityGroups'];
                        $arr[] = $this->runnedInstances;
                    }
                    echo '============================'. PHP_EOL;
                    echo '<br>';
                    echo '---> State: ' . $instance['State']['Name'] . PHP_EOL;
                    echo '<br>';
                    echo '---> Instance ID: ' . $instance['InstanceId'] . PHP_EOL;
                    echo '<br>';
                    echo '---> Image ID: ' . $instance['ImageId'] . PHP_EOL;
                    echo '<br>';
                    echo '---> Private Dns Name: ' . $instance['PrivateDnsName'] . PHP_EOL;
                    echo '<br>';
                    echo '---> Instance Type: ' . $instance['InstanceType'] . PHP_EOL;
                    echo '<br>';
                    if(isset($instance['SecurityGroups'][0]))
                        echo '---> Security Group: ' . $instance['SecurityGroups'][0]['GroupName'] . PHP_EOL;
                    else
                        echo '---> Security Group: Undefined'. PHP_EOL;
                    echo '<br>';
                    echo '-----------------------------------------------------------------------------------------------------';
                    echo '<br>';
                    echo '<br>'. PHP_EOL;
                }
            }
        }  catch (Exception $e)
        {
            return $arr['error'] = $e->getMessage();
        }
        return $arr;
    }
    /**
     * Generate a message based on an array of $notices to send email and logging
     * Send mail with notice mssage
     * Writ notice mssage into monitorLog.txt file
     * 
     * @param array $notices
     * @return void
     */
    private function notify(array $notices)
    {
        if(empty($notices))
            return;
        $message = '';
        $name = 'Runned instances';
        $duration = $this->config['runned_duration'];
        foreach ($notices as $notice)
        {
            $message .= 'Permitted connection '.$duration.' seconds'.PHP_EOL;
            $message .= '['. PHP_EOL;
            $message .= 'It is exceeded for more than '.($notice['duration']-$duration).' seconds'.PHP_EOL;
            $message .= '---> Instance ID: '.$notice['instanceId'].PHP_EOL;
            $message .= '---> State: '.$notice['state'].PHP_EOL;
            $message .= '---> Image ID: '.$notice['imageId'].PHP_EOL;
            $message .= '---> Private Dns Name: '.$notice['privateDnsName'].PHP_EOL;
            $message .= '---> Instance Type: '.$notice['instanceType'].PHP_EOL;
            if(isset($notice['securityGroups'][0]))
                $message .= '---> Security Group: ' . $notice['securityGroups'][0]['GroupName'] . PHP_EOL;
            else
                $message .= '---> Security Group: Undefined'. PHP_EOL;
            $message .= ']'. PHP_EOL;
            
        }
        $logPath = $this->config['monitor_log'];
        // create a log channel
        $log = new Logger($name);
        $log->pushHandler(new StreamHandler($logPath, Logger::WARNING));

        // add records to the log
        $log->warning($message);

        $to      = $this->config['to_email'];
        $subject = $this->config['monitor_email_subjct'];
        $headers = 'From: '. $this->config['from_email'];

        if( !mail( $to, $subject, $message, $headers ) ){
            $mail = new Logger('E-Mail');
            $mail->pushHandler(new StreamHandler($logPath, Logger::WARNING));

            // add records to the log
            $mail->warning(error_get_last());
            echo 'E-mail not sent :'.error_get_last().PHP_EOL;
        }
        echo '2. ['.$name.'] The message was formed. Log fil is '.realpath($logPath). PHP_EOL;
    }
}