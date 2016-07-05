<?php
    
return [
    'url'                   => 'https://www.google.com',
    'domain'                => 'www.google.com',
    'init_url'              => 'google.com',
    'key'                   => 'AKIAIL4CY74GOZXWZWLQ',
    'secret'                => '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',
    'to_email'              => 'admin@test.com',
    'from_email'            => 'cron@test.com',
    'email_subjct'          => 'Cron returns an error',
    'create_config_file'    => true,
    'duration'              => 100,
    'avarage_speed'         => 1.78,
    'random_faster'         => 16,
    'no_valid_speed'        => 0,
    'get_perc_by'           => '2-18 %',
    'protocol'              => 'http',
    'instance_count'        => 1,
    'validation_error'      => 'actionn get not a valid response',
    'wrong_code'            => 'Something wet wrong. Code is changed on the original site.',
    'wrong_duration'        => 'Alowable limit of script work exceeded the allowable limit of ',
    'incorrect_url'         => 'does not respond to a request. Hader code ',
    'sero_speed_msg'        => 'All indicators zero speed. Line ',
    'neeustar_error_msg'    => 'Something wet wrong, neuStarId is not defined. Line ',
    'live_actions'          => [
        'getUrl'             => 'http://deploy.aiscaler.com/geturl',
        'createInstance'      => 'http://deploy.aiscaler.com/createinstance',
        'getSpeed'            => 'http://deploy.aiscaler.com/getspeed',
        'getInstance'         => 'http://deploy.aiscaler.com/getinstance',
        'getSpeedByLocation'  => 'http://deploy.aiscaler.com/getspeedbylocation',
        'getSpeedDetails'     =>'http://deploy.aiscaler.com/getspeeddetails',
        'displayReport'       =>'http://deploy.aiscaler.com/displayreport'
    ]
];
