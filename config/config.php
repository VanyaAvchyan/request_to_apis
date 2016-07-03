<?php
    
return [
    'url'                   => 'https://www.google.com',
    'domain'                => 'www.google.com',
    'init_url'              => 'google.com',
    'key'                   => 'AKIAIL4CY74GOZXWZWLQ',
    'secret'                => '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',
    'region_code'           => 'us-east-1',
    'amis_region_code'      => 'ami-6089d208',
    'subnets'               => 'subnet-b6a061c1',
    'region'                => 'ec2.us-east-1.amazonaws.com',
    'to_email'              => 'admin@test.com',
    'from_email'            => 'cron@test.com',
    'email_subjct'          => 'Cron returns an error',
    'location'              => ['sanfrancisco'],
    'create_config_file'    => true,
    'duration'              => 3,
    'valid_speed'           => 0,
    'protocol'              => 'http',
    'instance_count'        => 1,
    'validation_error'      => 'actionn get not a valid response',
    'wrong_code'            => 'Something wet wrong. Code is changed on the original site.',
    'wrong_duration'        => 'Get Speed By Location limit is reached. Attempts have been made ',
    'live_actions'          => [
        'getUrl'             => 'http://deploy.aiscaler.com/geturl',
        'createInstance'      => 'http://deploy.aiscaler.com/createinstance',
        'getSpeed'            => 'http://deploy.aiscaler.com/getspeed',
        'getInstance'         => 'http://deploy.aiscaler.com/getinstance',
        'getSpeedByLocation'  => 'http://deploy.aiscaler.com/getspeedbylocation',
        'getSpeedDetails'     =>'http://deploy.aiscaler.com/getspeeddetails'
    ]
];
