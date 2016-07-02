<?php

include 'vendor/autoload.php';

function autoLoader($name)
{
    if(file_exists('controllers/'.$name.'.php')){
        include 'controllers/'.$name.'.php';
        $object = new $name();
    }

}
spl_autoload_register('autoLoader');
$newObj = new AmazonApiMonitoring();
$newObj->run();
