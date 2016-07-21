<?php
include __DIR__.'/vendor/autoload.php';
/**
 * Dumping result and exiting kode
 * 
 * @param mix $data
 * @param boolan $type if true xecute print_r else var_dump
 * @return var_dump | print_r
 */
function dp($data, $type = true)
{
    if($type){
        print_r($data);
    }
    else{
        var_dump($data);
    }
    exit();
}

$type = false;

if(isset($argv[1]))
    $type = $argv[1];

/**
     * This is a callback like __aoutoload for spl_autoload_register
 * 
 * @param string $name
 */
function autoLoader($name)
{
    if(file_exists('controllers/'.$name.'.php')){
        include 'controllers/'.$name.'.php';
    }
}

/**
 * Register given function as __autoload() implementation
 */
spl_autoload_register('autoLoader');

if($type)
{
    switch ($type){
        case 'test' :
            $newObj = (new AmazonApiMonitoring(Helper::dataConfiguration()))->run();break;
        case 'monitor' :
            $mainConfig = include __DIR__.'/config/main_config.php';
            $newObj = (new CheckAmazoneInstances($mainConfig))->run();break;
        default : echo ('Please select from these two (test or monitor)');
    }
}else
    echo ('Please select checking type (test or monitor)');
