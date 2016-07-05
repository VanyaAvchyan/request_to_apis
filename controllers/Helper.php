<?php

class Helper
{
    /** Default data for ini file */
    private static $INI_DATA = [
        'last_location'  => false,
        'us_east_1'      => "us_east_1",
        'us_west_1'      => "us_west_1",
        'us_west_2'      => "us_west_2",
        'eu_west_1'      => "eu_west_1",
        'eu_central_1'   => "eu_central_1",
        'ap_southeast_1' => "ap_southeast_1",
        'ap_southeast_2' => "ap_southeast_2",
        'ap_northeast_1' => "ap_northeast_1",
        'sa_east_1'      => "sa_east_1"
    ];

    /**
     * Crating ini file if not exists
     * Checking main_config.php file existing
     * Checking ini file and get current queue location
     * Udate ini file with current data
     * After geting current location, checking file existing with that name
     * 
     * @param string $filename ini fil name,default name is locations.ini
     * @return type
     */
    public static function dataConfiguration($filename = 'locations.ini')
    {
        /** Crating ini file if not xists */
        $iniFile    = __DIR__.'/../config/'.$filename;
        if(!file_exists($iniFile))
            self::write_php_ini(self::$INI_DATA, $iniFile);
        
        /** Checking main_config.php file existing */
        $mainConfig = __DIR__.'/../config/main_config.php';
        if(!file_exists($mainConfig))
            dp('File '.$mainConfig.' does not exist.Please check your directory');
        
        /** Checking ini file and get current queue location*/
        $iniData = parse_ini_file($iniFile);
        $currentLocation = self::getQueuStep($iniData, $iniData['last_location']);
        
        /** Udate ini file with curreent data */
        $iniData['last_location'] = $currentLocation;
        self::write_php_ini($iniData, $iniFile);

        /** After geting current location, checking file existing with that name */
        $currentConfigPath =__DIR__ . "/../config/".$currentLocation.'.php';
        if(!file_exists($currentConfigPath))
            dp('File '.$currentConfigPath.' does not exist.Please check your directory');

        $currentConfig = include $currentConfigPath;
        $mainConfig    = include $mainConfig;
        $config = $mainConfig +$currentConfig;
        return $config;
    }

    /**
     * Create if not exists file
     * Inicializing data by ini file structure
     * 
     * @param array $array
     * @param string $file file name
     * @return void
     */
    public static function write_php_ini(array $array, $file)
    {
        $res = array();
        foreach($array as $key => $val)
        {
            if(is_array($val))
            {
                $res[] = "[$key]";
                foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
            }
            else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
        }
        self::safefilerewrite($file, implode("\r\n", $res));
    }

    /**
     * Put data into file using fwrite function
     * 
     * @param string $fileName file name where puting data
     * @param string $dataToSave
     * @return void
     */
    public static function safefilerewrite($fileName, $dataToSave)
    {    if ($fp = fopen($fileName, 'w'))
        {
            $startTime = microtime(true);
            do
            {  
                $canWrite = flock($fp, LOCK_EX);
                // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
                if(!$canWrite) usleep(round(rand(0, 100)*1000));
            } while ((!$canWrite)and((microtime(true)-$startTime) < 5));

            //file was locked so now we can store information
            if ($canWrite)
            {
                fwrite($fp, $dataToSave);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
    
    /**
     * Get current location
     * 
     * @param array $arr
     * @param string $key
     * @return string
     */
    public static function getQueuStep(array $arr, $key)
    {
        if(!$key) return next($arr);
        while(current($arr))
        {
            next($arr);
            if(current($arr) == $key)
            {
                if($next = next($arr))
                    return $next;
                else
                {
                    reset($arr);
                    return next($arr);
                }
            }
        }
        dp('Something wet wrong.Class '.__CLASS__.'. Method '.__FUNCTION__.'. Line '.__LINE__);
    }
}
