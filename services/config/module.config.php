<?php
return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'geturl' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/geturl',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'geturl',
                    ),
                ),
            ),

            'createinstance' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/createinstance',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'createinstance',
                    ),
                ),
            ),
            'getinstance' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/getinstance',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'getinstance',
                    ),
                ),
            ),
            'getspeed' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/getspeed',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'getspeed',
                    ),
                ),
            ),
            'getspeedbylocation' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/getspeedbylocation',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'getspeedbylocation',
                    ),
                ),
            ),
            'highrise' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/highrise',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'highrise',
                    ),
                ),
            ),
            'displayreport' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/displayreport',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'displayreport',
                    ),
                ),
            ),
            'senderror' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/senderror',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'senderror',
                    ),
                ),
            ),
            'sendpdf' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/sendpdf',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'sendpdf',
                    ),
                ),
            ),
            'getspeeddetails' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/getspeeddetails',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'getspeeddetails',
                    ),
                ),
            ),
            'killinstance' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/killinstance',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ajax',
                        'action'     => 'killinstance',
                    ),
                ),
            ),
            'doublecheck' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/cron/doublecheck',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Cron',
                        'action'     => 'doublecheck',
                    ),
                ),
            ),
            'cronindex' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/cron/index',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Cron',
                        'action'     => 'index',
                    ),
                ),
            ),
            'cronreport' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/cron/dailyreport',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Cron',
                        'action'     => 'dailyreport',
                    ),
                ),
            ),
            'describe' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/cron/describe',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Cron',
                        'action'     => 'describe',
                    ),
                ),
            ),
            'showinstances' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/cron/showinstances',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Cron',
                        'action'     => 'showinstances',
                    ),
                ),
            ),
            'showinstances' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/cron/check-apis',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Cron',
                        'action'     => 'checkApis',
                    ),
                ),
            ),
            'download' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/download',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'download',
                    ),
                ),
            ),
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'application' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/application',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
            'Application\Controller\Ajax' => 'Application\Controller\AjaxController',
            'Application\Controller\Cron' => 'Application\Controller\CronController'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
);
