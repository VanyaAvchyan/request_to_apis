<?php
include __DIR__.'/vendor/autoload.php';

/**
 * Dumping result and exiting kode
 * 
 * @param mix $data
 * @param boolan $type if true xecute print_r else var_dump
 * @return var_dump | print_r
 */
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

$newObj = new AmazonApiMonitoring(Helper::dataConfiguration());
$newObj->run();
