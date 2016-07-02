<?php

namespace Application\Model;

class AwsInstance
{
    public $id;
    public $instanceId;
    public $region;
    public $createdDate;
    public $is_empty_instance;

    function exchangeArray($data)
    {
        $this->id		= (isset($data['id'])) ? $data['id'] : null;
        $this->instanceId		= (isset($data['instanceId'])) ? $data['instanceId'] : null;
        $this->region		= (isset($data['region'])) ? $data['region'] : null;
        $this->createdDate	= (isset($data['createdDate'])) ? $data['createdDate'] : null;
        $this->is_empty_instance	= (isset($data['is_empty_instance'])) ? $data['is_empty_instance'] : null;

    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public static function runInstance($ec2Client, $configs, $keyPairName, $regionCode) {
        $result = $ec2Client->runInstances(array(
            'ImageId' => $configs['amis'][$regionCode],
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => 't2.micro',
            'SubnetId' => $configs['subnets'][$regionCode],
            'KeyName' => $keyPairName
        ));
        $instanceIds = $result->getPath('Instances/*/InstanceId');
        return $instanceIds[0];
    }

    public static function killInstance( $region,$instancesInDatabase, $ec2Client){
        $instancesToDelete = array();

        $result = $ec2Client->describeInstances(array(
            'Filters' => array(
                array(
                    'Name' => 'key-name',
                    'Values' => array('deployment-'.$region),
                ),
                array(
                    'Name' => 'instance-state-name',
                    'Values' => array('running'),
                )
            )
        ));
        $instanceIds = $result->getPath('Reservations/*/Instances/*/InstanceId');

        foreach ($instanceIds as $instanceId) {
            if (!in_array($instanceId,$instancesInDatabase)) {
                array_push($instancesToDelete,$instanceId);
            }
        }
        if (!empty($instancesToDelete)) {
            $result = $ec2Client->terminateInstances(array(
                'InstanceIds' => $instancesToDelete
            ));
        }

        $response = "<b>/******************************<br>
                    ******** $region ************<br>
                    ****************************/<br></b>";

        $response .= "<b>running instances :". count($instanceIds).' --> </b>'.implode(', ',$instanceIds)."<br>";
        $response .= "<b>instance removed :". count($instancesToDelete).' --> </b>'.implode(', ',$instancesToDelete)."<br><br>";
        return $response;
    }

    public static function killInstancesByIds( $region,$instancesInDatabase, $ec2Client){
        $instancesToDelete = array();

        if (!empty($instancesToDelete)) {
            $result = $ec2Client->terminateInstances(array(
                'InstanceIds' => $instancesInDatabase
            ));
        }

        $response = "<b>/******************************<br>
                    ******** $region ************<br>
                    ****************************/<br></b>";

        $response .= "<b>instance removed :". count($instancesToDelete).' --> </b>'.implode(', ',$instancesToDelete)."<br><br>";
        return $response;
    }

}
