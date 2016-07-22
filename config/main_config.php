<?php
    
return [
    'url'                   => 'https://www.google.com',
    'domain'                => 'www.google.com',
    'init_url'              => 'google.com',
    'key'                   => 'AKIAIL4CY74GOZXWZWLQ',
    'secret'                => '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',
    'to_email'              => 'admin@test.com',
    'from_email'            => 'cron@test.com',
    'get_perc_by'           => '2-18 %',
    'protocol'              => 'http',
    'create_config_file'    => true,
    'duration'              => 2*60,//seconds
    'speed_origine'         => 1.45,
    'speed'                 => 1.3,
    'random_faster'         => 12,
    'no_valid_speed'        => 0,
    'instance_count'        => 1,
    'sleep_time'            => 2,
    'wornings_log_path'     => __DIR__.'/../log.txt',
    'monitor_json_file'     => __DIR__.'/../config/monitor.json',
    'monitor_log'           => __DIR__.'/../monitorLog.txt',
    'wrong_code'            => 'Something wet wrong. Code is changed on the original site.',
    'wrong_duration'        => " doesn't returned expected result after {configured} amount of time",
    'incorrect_url'         => 'does not respond to a request. Hader code ',
    'sero_speed_msg'        => 'All indicators zero speed. Line ',
    'neeustar_error_msg'    => 'Something wet wrong, neuStarId is not defined. Line ',
    'code_error'            => 'The code does not work correctly.Line ',
    'success_message'       => 'Everything is ok.File name is ',
    'missing_message_error' => 'Somthing wet wrong , message is missing. Line ',
    'not_valid_respons_msg' => ' return Not valid response',
    'test_email_subjct'     => 'Cron <<test>> returns an error',
    'monitor_email_subjct'  => 'Cron <<monitor>> returns an error',
    'live_actions'          => [
        'getUrl'              => 'http://deploy.aiscaler.com/geturl',
        'createInstance'      => 'http://deploy.aiscaler.com/createinstance',
        'getSpeed'            => 'http://deploy.aiscaler.com/getspeed',
        'getInstance'         => 'http://deploy.aiscaler.com/getinstance',
        'getSpeedByLocation'  => 'http://deploy.aiscaler.com/getspeedbylocation',
        'getSpeedDetails'     =>'http://deploy.aiscaler.com/getspeeddetails',
        'displayReport'       =>'http://deploy.aiscaler.com/displayreport'
    ],
    'regions'=>[
        'us-east-1',
        'us-west-1',
        'us-west-2',
        'sa-east-1',
        'eu-west-1',
        'eu-central-1',
        'ap-southeast-1',
        'ap-northeast-1',
        'ap-southeast-2',
    ],
    'instance_key_name'      => 'deployment-',
    'runned_duration'        => 10,
    'monitor_success_msg'    => "Success: Don't exist running instances",
    'monitor_notice_msg'     => "Notice: Runned Instances saved",
    'monitor_error_msg'      => " Running instances.ERROR: ",
];
