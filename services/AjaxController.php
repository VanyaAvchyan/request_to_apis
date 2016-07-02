<?php

namespace Application\Controller;

use Application\Model\AwsInstance;
use Application\Model\Mailer;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Http\Request;
use Application\Model\UrlChecker;
use Application\Model\Helper;
use Aws\Sdk as Aws;

class AjaxController extends AbstractActionController
{

    public function geturlAction()
    {
        if ($this->getRequest()->isPost()) {
            $url = $this->getRequest()->getPost('url');
        } else {
            $url = $this->getRequest()->getQuery('url');;
        }
        if (!empty($url)) {
            $urlChecker = new UrlChecker();
            $response = $urlChecker->getFinalUrl($url);
            return new JsonModel($response);
        }
        return new JsonModel(array('message' => 'Error Occured', 'code' => 0));
    }

    public function createinstanceAction()
    {
        $regionCode = $this->getRequest()->getPost('region_code');
        $url = $this->getRequest()->getPost('url');
        $initUrl = $this->getRequest()->getPost('init_url');
        $createConfigFile = $this->getRequest()->getPost('create_config_file');

        if ($createConfigFile) {
            $config_file_name = Helper::createConfigFile($url);
            $return['config_file_name'] = $config_file_name;
        }

        $instanceTable = $this->getServiceLocator()->get('InstanceTable');
        $config = $this->getServiceLocator()->get('config');
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        $view = new ViewModel(array(
            'regionCode' => $regionCode,
            'regionArray' => $config['regionArray'],
            'regionCodesArray' => $config['regionCodesArray']
        ));
        $view->setTemplate('application/partial/tableRow.phtml');
        $html = $viewRender->render($view);
        $return['table_row_html'] = $html;

        $response = array();

        $keyPairName = 'deployment-' . $regionCode; // support@aiscaler.com

        $date = date('Y-m-d H:i:s');

        $ec2Client = \Aws\Ec2\Ec2Client::factory(array(
            'key' => $config['aws']['key'],
            'secret' => $config['aws']['secret'],
            'region' => $regionCode
        ));
        $instancesData = $instanceTable->fetchAll(array('is_empty_instance' => 1, 'region' => $regionCode))->current();
        try {
            /* End Create Empty instance */
            if ($instancesData) {
                $instancesData->is_empty_instance = 0;
                $instancesData->createdDate = $date;
                $instanceTable->saveInstance($instancesData);
                $instanceId = $instancesData->instanceId;
            } else {
                $instanceId = AwsInstance::runInstance($ec2Client, $config, $keyPairName, $regionCode);
                $instanceObject = new AwsInstance();
                $instanceObject->instanceId = $instanceId;
                $instanceObject->region = $regionCode;
                $instanceObject->is_empty_instance = 0;
                $instanceObject->createdDate = $date;
                $instanceTable->saveInstance($instanceObject);
            }

            $tempInstanceId = AwsInstance::runInstance($ec2Client, $config, $keyPairName, $regionCode);
            $instanceObject = new AwsInstance();
            $instanceObject->instanceId = $tempInstanceId;
            $instanceObject->region = $regionCode;
            $instanceObject->is_empty_instance = 1;
            $instanceObject->createdDate = $date;
            $instanceTable->saveInstance($instanceObject);
            if (!$instancesData) {
                $instanceId = $tempInstanceId;
            }
            $code = 1;
            $message = "INSTANCE_FOUND";

        } catch (Exception $e) {
            $message = "Can't create instance";
            $output = $e->getMessage();
            $code = 0;
            $body = "CREATE_INSTANCE_ERROR";
            $body .= $message . "<br>";
            $body .= "<b>Output:</b><br>";
            $body .= $output;
            Mailer::send($body, $regionCode, $initUrl, $url, '', $config);

        }

        $return['code'] = $code;
        $return['message'] = $message;
        $return['output'] = @$output;
        $return['url'] = $url;
        $return['ip_address'] = @$ip_address;
        $return['instanceId'] = @$instanceId;
        $return['table_row_html'] = '';
        if ($regionCode != 'us-east-1') {
            $viewRender = $this->getServiceLocator()->get('ViewRenderer');
            $view = new ViewModel(array(
                'regionCode' => $regionCode,
                'regionArray' => $config['regionArray'],
                'regionCodesArray' => $config['regionCodesArray']
            ));
            $view->setTemplate('application/partial/tableRow.phtml');
            $html = $viewRender->render($view);
            $return['table_row_html'] = $html;
        }
        return new JsonModel($return);
    }

    public function getinstanceAction()
    {
        $output = false;
        $regionCode = $this->getRequest()->getPost('region_code');
        $instanceId = $this->getRequest()->getPost('instanceId');
        $initUrl = $this->getRequest()->getPost('init_url');
        $url = $this->getRequest()->getPost('url');
        $protocol = $this->getRequest()->getPost('protocol');
        $instanceCount = $this->getRequest()->getPost('instance_count');


        $config = $this->getServiceLocator()->get('config');

        //this needs to ensure that we trying process running instance
        $retries = 100;
        do {
            if ($retries != 100) {
                sleep(3);
            }
            $ec2Client = \Aws\Ec2\Ec2Client::factory(array(
                'key' => $config['aws']['key'],
                'secret' => $config['aws']['secret'],
                'region' => $regionCode
            ));

            $result = $ec2Client->describeInstances(array(
                'InstanceIds' => array($instanceId)
            ));

            $state = $result->getPath('Reservations/*/Instances/*/State');
            $stateName = $state['Name'];
            $retries--;
//            file_put_contents(APP_ROOT . '/data/error.log', 'Create instance: ' . $stateName. ', retry: '.$retries . PHP_EOL, FILE_APPEND);
        } while ($stateName == 'pending' && $retries != 0);

        $PublicDnsName_arr = $result->getPath('Reservations/*/Instances/*/PublicDnsName');
        $PublicDnsName = $PublicDnsName_arr[0];

        if ($stateName == 'running') {

            $keyPairName = APP_ROOT . "/data/key/deployment-" . $regionCode . ".pem"; // support@aiscaler.com

            $command_1 = "curl -Ls -o /dev/null -w %{url_effective} " . $protocol . '://' . $url;
            $iterationsCounter = 1;
            $iterationsStatus = false;
            do {
                exec("ssh ubuntu@" . $PublicDnsName . " -i " . $keyPairName . " -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -tt '" . $command_1 . "' 2>&1", $output_1, $result_1);
                if ((strpos(json_encode($output_1), 'Connection timed out') === false && strpos(json_encode($output_1), 'Connection refused') === false && strpos(json_encode($output_1), 'another process could be using this port') === false) || $iterationsCounter++ == 20) {
                    $iterationsStatus = true;
                } else {
                    sleep(15);
                }
            } while ($iterationsStatus == false);

            $full_url = @end($output_1);
            if (preg_match("/^(http:\/\/)/", $full_url)) {
                $url = preg_replace('/http:\/\//', '', $full_url, 1);
                $protocol = "http";
            } else if (preg_match("/^(https:\/\/)/", $full_url)) {
                $url = preg_replace('/https:\/\//', '', $full_url, 1);
                $protocol = "https";
            } else {
                $url = $full_url;
                $protocol = "http";
            }
            if (strpos($url, "Connection to")) {
                $url = substr($url, 0, strpos($url, "Connection to"));
            }
            if (strpos($url, " ")) {
                $url = substr($url, 0, strpos($url, " "));
            }
            if (strpos($url, "?")) {
                $url = substr($url, 0, strpos($url, "?"));
            }
            if (strpos($url, "/")) {
                $url_arr = explode("/", $url);
                $url = $url_arr[0];
                $uri = $url_arr[1];
            }
            if (preg_match("/^(www.)/", $url)) {
                $url_with_www = $url;
                $url_without_www = preg_replace('/www./', '', $url, 1);
            } else {
                if (preg_match("/^(.*)\.(.*)\.(.*)$/", $url)) { // Example : sites.sonypicturestelevision.com
                    $url_with_www = $url;
                    $url_without_www = $url;
                } else {
                    $url_with_www = "www." . $url;
                    $url_without_www = $url;
                }
            }
            $command = "sudo mkdir -p /usr/local/aicache_installer/downloads/ &&";
            $command .= "sudo curl -L -s -o /usr/local/aicache_installer/downloads/aicache.tar https://aiscaler.com/aicache.tar &&";
            $command .= "sudo tar --directory=/usr/local/aicache_installer/downloads -xf \/usr/local/aicache_installer/downloads/aicache.tar &&";
            $command .= "sudo chmod 755 /usr/local/aicache_installer/downloads/aicache/ &&";
            $command .= "cd /usr/local/aicache_installer/downloads/aicache && sudo ./install.sh &&";
            $command .= "sudo touch /usr/local/aicache/RELOAD_FILE &&";
            $command .= "sudo touch /usr/local/aicache/RELOAD_SUCCESS &&";
            $command .= "sudo touch /usr/local/aicache/RELOAD_FAIL &&";
            $command .= "sudo sed -i \"/server #/aignore_hostname\" /usr/local/aicache/example.cfg &&";
            $command .= "sudo sed -i \"/listen http 192.168.168.8 80/aserver_port 80\" /usr/local/aicache/example.cfg &&";
            $command .= "sudo sed -i \"s/listen http 192.168.168.8 80/server_ip */\" /usr/local/aicache/example.cfg &&";
            $command .= "sudo sed -i \"/hostname aaa.bbb.com/asub_hostname " . $url_with_www . "\" /usr/local/aicache/example.cfg &&";
            $command .= "sudo sed -i \"s/aaa.bbb.com/" . $url_without_www . "/\" /usr/local/aicache/example.cfg &&";
            $command .= "sudo sed -i \"s/healthcheck \//#healthcheck \//\" /usr/local/aicache/example.cfg &&";

            $command .= "sudo wget -q --no-check-certificate https://aiscaler.com/deploy/config/useragentfile -O /usr/local/aicache/useragentfile && ";
            $command .= "sudo chown aicache:aicache /usr/local/aicache/useragentfile && ";

//            $command .= "sudo sed -i \"/logstats                        # Will/aua_tag_file useragentfile\" /usr/local/aicache/example.cfg &&";
//            $command .= "sudo sed -i \"/      regexp 1m       # cache/aua_tag_process\" /usr/local/aicache/example.cfg &&";
            if ($url == "www.microsoft.com") {
                $command .= "sudo sed -i \"/logstats                        # Will/aforward_ua\" /usr/local/aicache/example.cfg &&";
            }
            if ($protocol == 'https') {
                $command .= "sudo sed -i \"s/#origin 1.2.3.4 80/origin 127.0.0.1 8080/\" /usr/local/aicache/example.cfg &&";
                $command .= "sudo apt-get update &&";
                $command .= "sudo apt-get -y install nginx &&";
                $command .= "sudo rm -f /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'server {' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sh -c \"echo 'listen 8080 default_server' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/default_server$/default_server;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'resolver 8.8.8.8' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/8.8.8.8$/8.8.8.8;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'location / {' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sh -c \"echo 'proxy_redirect off' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/off$/off;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'proxy_set_header X-Forwarded-For $ proxy_add_x_forwarded_for' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/ proxy_add_x_forwarded_for$/proxy_add_x_forwarded_for;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'proxy_set_header Host $ http_host' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sh -c \"echo 'proxy_set_header HTTPS on' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/on$/on;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'proxy_http_version 1.1' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/1.1$/1.1;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo 'proxy_pass https://$ http_host' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sed -i s\"/ http_host$/http_host;/\" /etc/nginx/conf.d/virtual.conf &&";
                $command .= "sudo sh -c \"echo '}' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo sh -c \"echo '}' >> /etc/nginx/conf.d/virtual.conf\" &&";
                $command .= "sudo rm -f /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo 'user www-data' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/www-data$/www-data;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo 'worker_processes 1' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/1$/1;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo 'pid /run/nginx.pid' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/pid$/pid;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo 'events {' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sh -c \"echo 'worker_connections 1024' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/1024$/1024;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo '}' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sh -c \"echo 'http {' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sh -c \"echo 'include /etc/nginx/mime.types' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/types$/types;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo 'default_type application/octet-stream' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/stream$/stream;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo 'include /etc/nginx/conf.d/*.conf' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo sed -i s\"/conf$/conf;/\" /etc/nginx/nginx.conf &&";
                $command .= "sudo sh -c \"echo '}' >> /etc/nginx/nginx.conf\" &&";
                $command .= "sudo service nginx restart &&";
            } else {
                $command .= "sudo sed -i \"s/#origin 1.2.3.4/origin " . $url . "/\" /usr/local/aicache/example.cfg &&";
            }
            $command .= "cd /usr/local/aicache && sudo ./aicache -l /usr/local/aicache/*.demo -f example.cfg; echo \"Aicache Working\";";

            $command .= "sudo netstat -netulp";

            if ($instanceCount == 1) {
//                $command .= "cd /usr/local/aicache && sudo ./aicache -l /usr/local/aicache/*.demo -f example.cfg; echo \"Aicache Working\";";
                exec("ssh ubuntu@" . $PublicDnsName . " -i " . $keyPairName . " -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -tt '" . $command . "
		' 2>&1", $output, $result);
            } else {
//                $command .= "cd /usr/local/aicache && sudo ./aicache -l /usr/local/aicache/*.demo -f example.cfg; echo \"Aicache Working\";";
                exec("ssh ubuntu@" . $PublicDnsName . " -i " . $keyPairName . " -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -tt '" . $command . "
			' 2>&1", $output, $result);
            }

        } else {
            Mailer::send('tryed to configure non-running instance: ' . $stateName, $regionCode, $initUrl, $protocol . '://' . $url, $PublicDnsName, $config);
        }
        $output = @json_encode($output, JSON_PRETTY_PRINT);
        $output = str_replace(array("[FATAL]: Could not bind() on listening socket IP", "\"bash: $'\\\\r': command not found\","), '', $output);
        $aicache_success = false;
        $code = 1;
        $message = "instance found !!";
        if (isset($uri) && $uri) {
            $PublicDnsName .= '/' . $uri;
        }
        $return['cached_url'] = 'http://' . $PublicDnsName;
        $return['url'] = $protocol . '://' . $url;

        if (strpos($output, '[FATAL]') !== false) {
            $code = 0;
            $message = "FATAL Error";

            $body = "FATAL_ERROR <br> Testing failed for url:" . $_POST['url'] . "<br>";
            if (strpos($output, 'License has expired') !== false) {
                $message .= ": License has expired !!!";
                $body .= "<b>Error: </b> License has expired<br>";
            }
            $body .= "<b>SSH Output:</b><br>";
            $body .= $output;

            Mailer::send($body, $regionCode, $initUrl, $protocol . '://' . $url, $PublicDnsName, $config);
        } else if (strpos($output, 'Aicache Working') !== false) {
            $aicache_success = true;

            $url_PublicDnsName = urldecode($PublicDnsName);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_PublicDnsName);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch);

            $iterationsCounter = 1;
            $iterationsStatus = false;
            do {
                $urlStatus = UrlChecker::checkUrlExist('http://' . $PublicDnsName);
                if ($urlStatus || $iterationsCounter++ == 30) {
                    $iterationsStatus = true;
                } else {
                    sleep(15);
                }
            } while ($iterationsStatus == false);
            if (!$urlStatus) {
                $array = @get_headers('http://' . $PublicDnsName);
                $result_page = $array[0];
                $body = 'PUBLIC_DNS_NOT_WORKING' . '<br>';
                $body .= "PublicDnsName: " . $PublicDnsName . "<br>";
                $body .= "<b>Test Number </b>" . $instanceCount . "<br>";
                $body .= "The public DNS is not working<br>";
                $body .= "Public DNS return:" . $result_page;
                $body .= "<br><b>command: </b><br>" . $command;
                $body .= "<br><b>output: </b><br>" . $output;
                Mailer::send($body, $regionCode, $initUrl, $protocol . '://' . $url, $PublicDnsName, $config);
                $code = 2;
                $message = "The public DNS is not working";
            } else if ($instanceCount > 1) {

                $body = "PublicDnsName: " . $PublicDnsName . "<br>";
                $body .= "<b>Test Number </b>" . $instanceCount . "<br>";
                $body .= "The public DNS is currently working ";

                Mailer::send($body, $regionCode, $initUrl, $protocol . '://' . $url, $PublicDnsName, $config);
            }
        }

        $return['output'] = $output;
        $return['aicache_success'] = $aicache_success;
        $return['code'] = $code;
        $return['stateName'] = $stateName;
        $return['PublicDnsName'] = $PublicDnsName;
        $return['message'] = $message;

        return new JsonModel($return);
    }

        public function getspeedAction()
    {
        $config = $this->getServiceLocator()->get('config');

        $regionCode = $this->getRequest()->getPost('region_code');
        $website = $this->getRequest()->getPost('website');
        $url = $this->getRequest()->getPost('url');
        $initUrl = $this->getRequest()->getPost('init_url');
        $publicDns = $this->getRequest()->getPost('public_dns');

        $code = 1;
        $message = "Speed Found";

        $neustarId = $error = false;
        $signature = md5($config['neustar']['key'] . $config['neustar']['secret'] . gmdate('U'));
        $neustarUrl = urldecode('http://api.neustar.biz/performance/tools/instanttest/1.0?apikey=' . $config['neustar']['key'] . '&sig=' . $signature);
        while (!$neustarId && !$error) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $neustarUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "url=$website");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch);
            $html_arr = json_decode($html);
            $error = @$html_arr->error->message;
            $error = (trim($error) == "Account Over Queries Per Second Limit" || trim($error) == "Gateway Time out") ? "" : $error;
            if ($error) {
                $code = 0;
                $message = "Neustar API not working !!!";
                $body = "<b>NEUSTAR_API_ERROR</b>" . '<br>';
                $body .= "<b>Output:</b><br>";
                $body .= $error . "<br>";

                Mailer::send($body, $regionCode, $initUrl, $url, $publicDns, $config);
            }

            $neustarId = @$html_arr->data->items->id;
            $sanfranciscoId = @$html_arr->data->items->locations[0]->id;
            $singaporeId = @$html_arr->data->items->locations[1]->id;
            $dublinId = @$html_arr->data->items->locations[2]->id;
            $washingtondcId = @$html_arr->data->items->locations[3]->id;
        }
        $return = array(
            'neustar_id' => $neustarId,
            'sanfrancisco_id' => $sanfranciscoId,
            'singapore_id' => $singaporeId,
            'dublin_id' => $dublinId,
            'washingtondc_id' => $washingtondcId,
            'code' => $code,
            'message' => $message,
        );
        return new JsonModel($return);
    }

    public function getspeedbylocationAction()
    {
        $config = $this->getServiceLocator()->get('config');

        $neustarId = $this->getRequest()->getPost('neustar_id');

        $signature = md5($config['neustar']['key'] . $config['neustar']['secret'] . gmdate('U'));

        $url = urldecode('api.neustar.biz/performance/tools/instanttest/1.0/' . $neustarId . '?apikey=' . $config['neustar']['key'] . '&sig=' . $signature);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        $html_arr = json_decode($html);

        $error = @$html_arr->error->message;
        $error = (trim($error) == "Account Over Queries Per Second Limit" || trim($error) == "Gateway Time out") ? "" : $error;
        if ($error) {
            $body = "<b>NEUSTAR_API_ERROR</b>" . '<br>';
            $body .= "<b>Output:</b><br>";
            $body .= $error . "<br>";

            Mailer::send($body, 'NO_DATA', 'NO_DATA', $url, 'NO_DATA', $config);
        }

        $return = array(
            'speed_sanfrancisco' => @$this->_getSpeedInSeconds($html_arr->data->items[0]->responseTime),
            'speed_singapore' => @$this->_getSpeedInSeconds($html_arr->data->items[1]->responseTime),
            'speed_dublin' => @$this->_getSpeedInSeconds($html_arr->data->items[2]->responseTime),
            'speed_washingtondc' => @$this->_getSpeedInSeconds($html_arr->data->items[3]->responseTime),
            'code' => 1,
            'message' => "Speed Found"
        );
        return new JsonModel($return);
    }

    public function highriseAction()
    {
        $name = $this->getRequest()->getPost('name');
        $company = $this->getRequest()->getPost('company');
        $communication_selection = $this->getRequest()->getPost('communication_selection');
        $communication_value = $this->getRequest()->getPost('communication_value');
        $comments = $this->getRequest()->getPost('comments');

        require_once(__DIR__ . "/../../../../../vendor/highrise/HighriseAPI.class.php");

        $highrise = new \HighriseAPI();
        $highrise->setAccount('aiscaler');
        $highrise->setToken('363e1f2a99409cc61da83e0ecba51eeb');

        $nameArr = explode(" ", $name);
        $FirstName = $nameArr[0];
        $LastName = "";
        for ($i = 1; $i < count($nameArr); $i++) {
            $LastName .= ' ' . $nameArr[$i];
        }

        $person = new \HighrisePerson($highrise);
        $person->setFirstName($FirstName);
        if ($LastName) {
            $person->setLastName($LastName);
        }
        $person->setCompanyName($company);
        if ($communication_selection == 'Phone') {
            $person->addPhoneNumber($communication_value);
        } else if ($communication_selection == 'Email') {
            $person->addEmailAddress($communication_value);
        }
        $person->setBackground($comments);

        if ($person->save()) {
            $id = $person->getId();
            $url = 'https://aiscaler.highrisehq.com/people/' . $id;

            $task = new \HighriseTask($highrise);
            $task->setBody("Contact " . $name . " ( " . $url . " )");
            $task->setFrame("Today");
            $task->setNotify(false);
            $users = $highrise->findAllUsers();
            $me = $highrise->findMe();
            foreach ($users as $user) {
                if ($user->getId() == 67429) { // 67429 is Id of Max Robbin
                    // if ($user->getId() == 1088508){ // 67429 is Id of Mohamed Chabchoub
                    $assigned_user = $user;
                }
            }

            $task->assignToUser($assigned_user);
            $task->save();

            $fromEmail = 'support@aiscaler.com';
            $replyToEmail = false;

            $toEmails11 = array("support@aiscaler.com");
            $subject1 = "aiscaler.com : Contact Engineer";
            $fromName1 = $name;
            $body1 = "A visitor filled in the popup form and wants to be contacted.<br> His name is: " . $name . " and his highrise entry is <a href='" . $url . "'>" . $url . "</a>.<br>";
            if (!empty($comments)) {
                $body1 .= "Content of his email was : " . $comments . ".<br>";
            }
            $body1 .= "Please contact him ASAP.";

            $sendEmail1 = Mailer::_directSend($fromEmail, $fromName1, $toEmails11, false, $replyToEmail, $subject1, $body1, $attachements = false);
            $sendEmail = $sendEmail1;
            if ($communication_selection == 'Email') {
                $toEmails2 = array($communication_value);
                $fromName2 = "aiscaler";
                $subject_2 = "aiscaler.com";
                $body2 = "Hi " . $FirstName . ",<br><br>Thank you for contacting aiScaler.<br> You requested to be contacted for a technical consult.<br> This is just an automatic email confirmation.<br> We will be in touch with you shortly. ";
                $sendEmail2 = Mailer::_directSend($fromEmail, $fromName2, $toEmails2, false, $replyToEmail, $subject_2, $body2, $attachements = false);
                $sendEmail = $sendEmail && $sendEmail2;
            }

            if ($sendEmail) {
                $return['code'] = 1;
                $return['message'] = "Success";
            } else {
                $return['code'] = 0;
                $return['message'] = "Error occured when sending email !!!";
            }
        } else {
            $return['code'] = 0;
            $return['message'] = "Error occured when saving to highrise !!!";
        }
        return new JsonModel($return);
    }

    public function displayreportAction()
    {
        $tableHtml = $this->getRequest()->getPost('table_html');
        if (!empty($tableHtml)) {
            require_once(__DIR__ . "/../../../../../vendor/dompdf/dompdf_config.inc.php");
            $url = $this->getRequest()->getPost('url');
            $getPercBy = $this->getRequest()->getPost('get_perc_by');
            $recommendedRegion = $this->getRequest()->getPost('recommanded_region');


            $name = "report_" . time() . "_" . rand(10000, 99999) . ".pdf";
            $path = APP_ROOT . '/data/files/report/' . $name;
            $dompdf = new \DOMPDF();
            $dompdf->set_protocol(APP_BASE);
            $dompdf->set_base_path('/');
            $viewRender = $this->getServiceLocator()->get('ViewRenderer');
            $view = new ViewModel(array(
                'recommendedRegion' => $recommendedRegion,
                'getPercBy' => $getPercBy,
                'tableHtml' => $tableHtml,
                'url' => $url
            ));
            $view->setTemplate('application/ajax/displayreport.phtml');
            $html = $viewRender->render($view);
            $dompdf->load_html($html);
            $dompdf->render();
            $output = $dompdf->output();
            file_put_contents($path, $output);
            $return['code'] = 1;
            $return['message'] = "PDF file was created successfully";
            $return['report_file_name'] = $name;
        } else {
            $return['code'] = 0;
            $return['message'] = "Can't create PDF file";
        }
        return new JsonModel($return);
    }

    public function senderrorAction()
    {
        $url = $this->getRequest()->getPost('url');
        if (!empty($url)) {
            $message = trim($this->getRequest()->getPost('message'));
            $initUrl = trim($this->getRequest()->getPost('init_url'));
            $publicDns = trim($this->getRequest()->getPost('public_dns'));
            $regionCode = $this->getRequest()->getPost('region_code');

            $body = "Testing failed for url:" . $url . "<br>";
            $body .= $message;

            Mailer::send($body, $regionCode, $initUrl, $url, $publicDns, $this->getServiceLocator()->get('config'));

            $return['msg'] = 'Email sent';
            $return['code'] = 1;
        } else {
            $return['msg'] = 'Error Occurred';
            $return['code'] = 0;
        }
        return new JsonModel($return);
    }

    public function sendpdfAction()
    {
        $fromEmail = trim($this->getRequest()->getPost('from_email'));
        $toEmail = trim($this->getRequest()->getPost('to_email'));
        $note = trim($this->getRequest()->getPost('note'));
        $pdfReport = APP_ROOT . trim($this->getRequest()->getPost('pdf_report'));
        if (!empty($fromEmail) && !empty($pdfReport)) {
            $senderEmail = 'support@aiscaler.com';
            $replyToEmail = false;
            $toEmails = array($toEmail);
            $attachements = array($pdfReport);
            $subject = "aiscaler.com : " . $fromEmail . " shared with you this report";
            $fromName = "aiScaler Test Tool";
            $body = "<div style='width:100%;margin-bottom:40px'><img style='float:right' src='https://aiscaler.com/wp-content/themes/aicache/images/main-logo.png'/></div>";
            $body .= "Hi,<br>" . $fromEmail . " shared with you this PDF report of aiscaler test tool.<br>";
            if ($note) {
                $body .= "Personal Note:<br>" . $note . "<br>";
            }
            $body .= "<br>Thanks.";

            $send_email = Mailer::_directSend($senderEmail, $fromName, $toEmails, false, $replyToEmail, $subject, $body, $attachements);

            if ($send_email) {
                $return['code'] = 1;
                $return['message'] = "Success";
            } else {
                $return['code'] = 0;
                $return['message'] = "Error occurred when sending email !!!";
            }
        } else {
            $return['code'] = 0;
            $return['message'] = "Error Occurred";
        }
        return new JsonModel($return);
    }

    public function getspeeddetailsAction()
    {
        $configs = $this->getServiceLocator()->get('config');

        $regionsArray = $configs['regionArray'];
        $speedOriginArr = array(
            'san_francisco' => 'San Francisco',
            'singapore' => 'Singapore',
            'dublin' => 'Dublin',
            'washington' => 'Washington D.C.'
        );
        $website = $this->getRequest()->getPost('url');
        $regionCode = $this->getRequest()->getPost('region_code');
        $location = $this->getRequest()->getPost('location');
        $speedOrigin = $this->getRequest()->getPost('speed_origine');
        $speed = $this->getRequest()->getPost('speed');
        $neustarLocationId = $this->getRequest()->getPost('neustar_location_id');
        $neustarLocationIdOrigin = $this->getRequest()->getPost('neustar_location_id_origine');
        $randomFaster = (int)$this->getRequest()->getPost('random_faster');

        // $neustar_location_id = 'e807591480c811e4a851002655ec6e4f/e807591480c811e4a851002655ec6e4f';
        // $neustar_location_id_origine = '90326a3080c811e4b1d4002655ec6e4f/9091fd9c80c811e4b1d4002655ec6e4f';

        $signature = md5($configs['neustar']['key'] . $configs['neustar']['secret'] . gmdate('U'));
        $url = urldecode('http://api.neustar.biz/performance/tools/instanttest/1.0/' . $neustarLocationId . '?apikey=' . $configs['neustar']['key'] . '&sig=' . $signature);
        $signature = md5($configs['neustar']['key'] . $configs['neustar']['secret'] . gmdate('U'));
        $urlOrigin = urldecode('http://api.neustar.biz/performance/tools/instanttest/1.0/' . $neustarLocationIdOrigin . '?apikey=' . $configs['neustar']['key'] . '&sig=' . $signature);

        $chOrigin = curl_init();
        curl_setopt($chOrigin, CURLOPT_URL, $urlOrigin);
        curl_setopt($chOrigin, CURLOPT_RETURNTRANSFER, 1);
        $htmlOrigin = curl_exec($chOrigin);
        $htmlArrOrigin = json_decode($htmlOrigin);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        $htmlArr = json_decode($html);


        $requestArr = $htmlArr->data[0]->harFile->log->entries;
        $requestOriginArr = $htmlArrOrigin->data[0]->harFile->log->entries;

        foreach ($requestArr as $index => $request) {
            $hostTemp = $request->request->url;
            $arrIndex[$hostTemp] = $index;
        }
        $viewData = array();
        foreach ($requestOriginArr as $key => $requestOrigin) {
            $hostOrigin = $requestOrigin->request->url;
            $loadOriginMs = (int)$requestOrigin->time;

            if (isset($arrIndex[$hostOrigin]) && $arrIndex[$hostOrigin]) {
                $internalOrExternal = 'external';
                $websiteRegex = str_replace(array('http://', 'https://', 'www.'), '', $website);
                $websiteRegex = str_replace('/', '', $websiteRegex);

                $hostOriginRegex = str_replace(array('http://', 'https://', 'www.'), '', $hostOrigin);
                $hostOriginArr = explode("/", $hostOriginRegex);
                $hostOriginRegex = $hostOriginArr[0];
                if (preg_match("/$websiteRegex$/", $hostOriginRegex)) {
                    $internalOrExternal = 'internal';
                }
                $index = $arrIndex[$hostOrigin];
                $loadMs = (int)$requestArr[$index]->time;
                if ($randomFaster) {
                    $loadMs = (int)($loadOriginMs * (1 - $randomFaster / 100));
                    $loadMs = ($loadMs == 0) ? 1 : $loadMs;
                }
                $viewData[] = array(
                    'type' => $internalOrExternal,
                    'hostOrigin' => $hostOrigin,
                    'loadOriginMs' => $loadOriginMs,
                    'loadMs' => $loadMs,

                );
            }
        }
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        $view = new ViewModel(array(
            'regionCode' => $regionCode,
            'speedOrigin' => $speedOrigin,
            'speed' => $speed,
            'viewData' => $viewData,
            'regionsArr' => $regionsArray,
            'regionsOrigin' => $speedOriginArr,
            'location' => $location
        ));
        $view->setTemplate('application/ajax/getspeeddetails.phtml');
        $html = $viewRender->render($view);


        $return['waterfall_html'] = $html;
        $return['code'] = 1;
        $return['message'] = "Speed Found";

        return new JsonModel($return);
    }

    public function killinstanceAction()
    {
        $regionCode = $this->getRequest()->getPost('region_code');
        $instanceId = $this->getRequest()->getPost('instanceId');
        $config = $this->getServiceLocator()->get('config');
        $instanceTable = $this->getServiceLocator()->get('InstanceTable');

        $ec2Client = \Aws\Ec2\Ec2Client::factory(array(
            'key' => $config['aws']['key'],
            'secret' => $config['aws']['secret'],
            'region' => $regionCode
        ));
        $results[] = AwsInstance::killInstance($regionCode, array($instanceId), $ec2Client);
        $instanceTable->deleteInstanceBy('instanceId', $instanceId);
        $return['code'] = 1;
        $return['message'] = "Instance has been deleted";
        return new JsonModel($return);
    }

    private function _getSpeedInSeconds($speedMs)
    {
        $speedS = intval($speedMs) / 1000;
        return number_format($speedS, 2, '.', '');
    }
    
}
