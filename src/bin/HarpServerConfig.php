<?php
namespace Harp\bin;

class HarpServerConfig 
{
    private $Configuration = [];
    private $originalParams = [];
    
    const NO_USER_AGENT = 'NO_USER_AGENT';
    const HTTP = 'PACKAGE_HTTP';
    const PROTOCOL_REQUEST_HTTP = 'HTTP';
    const PROTOCOL_REQUEST_HTTPS = 'HTTPS';
    const REQUEST_PROTOCOL = 'REQUEST_PROTOCOL';
    const SERVER_PORT =  'SERVER_PORT';
    const REQUEST_PORT = 'REQUEST_PORT';
    const SERVER_PROTOCOL = 'SERVER_PROTOCOL';
    const VERSION_PROTOCOL = 'VERSION_PROTOCOL';
    
    public function __construct() 
    {
        $this->configureServerVariables();
        $this->getRequestCommunicationProtocol();
        $this->getRequestVersionCommunicationProtocol();
        $this->verifyRequestPort();
        $this->getBrowserWithoutBrowscap();
        $this->getRemoteAddr();
        $this->formatHttpReferer();   
        $this->defineConstants();
        
        $this->originalParams = $this->Configuration;
    }
    
    private function getBrowserWithoutBrowscap()
    {
        if(defined('HTTP_USER_AGENT'))
        {
            $userAgent = HTTP_USER_AGENT;

            $result = Array();

            preg_match('/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i',$userAgent,$result);

            $infoNavigator = Array
            (
                'BROWSER_VERSION'   => '0',
                'BROWSER_AGENT' => HTTP_USER_AGENT,
                'BROWSER_NAME'      => 'unknown',
            );

            if(count($result) == 3)
            {
                 if(preg_match('`Trident`i',$result[1]) && preg_match('/\brv[ :]+(\d+)/i',$userAgent,$r))
                 {
                    $infoNavigator['BROWSER_VERSION'] = isset($r[1]) ? $r[1] : $infoNavigator['BROWSER_VERSION'];
                    $infoNavigator['BROWSER_NAME'] = 'IE '. $infoNavigator['BROWSER_VERSION'];
                 }
                 else if(strtolower($result[1]) == 'firefox' && preg_match('/\b(Navigator)\/(\d+)/i',$userAgent,$r))
                 {
                    $infoNavigator['BROWSER_VERSION'] = isset($r[2]) ? $r[2] : $infoNavigator['BROWSER_VERSION'];

                    $browser = $r[1] == 'Navigator' ? 'Netscape' : $r[1];

                    $infoNavigator['BROWSER_NAME'] = $browser.' '. $infoNavigator['BROWSER_VERSION'];
                 }             
                 else if(strtolower($result[1]) == 'chrome' && preg_match('/\b(OPR|Edge)\/(\d+)/i',$userAgent,$r))
                 {
                    $infoNavigator['BROWSER_VERSION'] = isset($r[2]) ? $r[2] : $infoNavigator['BROWSER_VERSION'];

                    $browser = $r[1] == 'OPR' ? 'Opera' : $r[1];

                    $infoNavigator['BROWSER_NAME'] = $browser.' '. $infoNavigator['BROWSER_VERSION'];
                 }
                 else
                 {
                     $infoNavigator['BROWSER_VERSION'] = $result[2];
                     $infoNavigator['BROWSER_NAME'] = $result[1].' '. $infoNavigator['BROWSER_VERSION'];
                 }
            }

            foreach($infoNavigator as $var => $b)
            {
                if(!defined($var))
                {
                    $this->Configuration[$var] = $b;
                    define($var,$b);
                }
            }            
        }
    }
    
    public function getRemoteAddr()
    { 
        $ipRemote = isset($_SERVER['HTTP_CLIENT_IP']) ? filter_var($_SERVER['HTTP_CLIENT_IP'],FILTER_UNSAFE_RAW) : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? filter_var($_SERVER['HTTP_X_FORWARDED_FOR'],FILTER_UNSAFE_RAW) : filter_var($_SERVER['REMOTE_ADDR'],FILTER_UNSAFE_RAW));
        
        $ip = $this->getIPV4($ipRemote);
        
        $this->Configuration['HTTP_CLIENT_IP'] = $ip;
        
        return $ip;
    } 
    
    private function formatHttpReferer()
    {
        if(isset($_SERVER['HTTP_REFERER']))
        {
            $value = filter_var($_SERVER['HTTP_REFERER'],FILTER_UNSAFE_RAW);
            
            $p = explode('/p/',$value);
            $value = $p[0];
            $v = strtok($value,'?');
            
            $const = 'HTTP_REFERER';
            
            $this->Configuration[$const] = $v;

            if(!defined($const))
            {
                define($const,$v);
            }   
        }
    }
    
    private function normalizeNameConstant($name)
    {
        $const = str_ireplace('-','_',$name);
        
        return $const;
    }
        
    public function configureServerVariables()
    {          
        foreach($_SERVER as $i => $value)
        {
            $v = filter_var($value,FILTER_UNSAFE_RAW);
            
            $const = $this->normalizeNameConstant($i);

            if($const == 'HTTP_REFERER')
            {
                continue;  
            }
            
            $this->Configuration[$const] = $v;          
        }
        
        if(!isset($this->Configuration['HTTP_USER_AGENT']))
        {
             $this->Configuration['HTTP_USER_AGENT'] = self::NO_USER_AGENT;
        }     
    }
    
    private function setConstant($const,$value)
    {        
        if(!defined($const))
        {   
            define($const,$value);
        }   
    }
    
    private function defineConstants()
    {
        foreach($this->Configuration as $const => $value)
        {
            $const = $this->normalizeNameConstant($const);
            $this->setConstant($const,$value);
        }
    } 
    
    private function getRequestCommunicationProtocol()
    {
        $this->Configuration[self::REQUEST_PROTOCOL] = mb_strtolower(self::PROTOCOL_REQUEST_HTTP);
        
        if(isset($this->Configuration[self::PROTOCOL_REQUEST_HTTPS]) && $this->Configuration[self::PROTOCOL_REQUEST_HTTPS] != 'off')
        {
               $this->Configuration[self::REQUEST_PROTOCOL] = mb_strtolower(self::PROTOCOL_REQUEST_HTTPS);
        }
        else if(isset($this->Configuration[self::SERVER_PORT]) && $this->Configuration[self::SERVER_PORT] == 443)
        {
               $this->Configuration[self::REQUEST_PROTOCOL] = mb_strtolower(self::PROTOCOL_REQUEST_HTTPS);
        }
    }
    
    private function getRequestVersionCommunicationProtocol()
    {
        $this->Configuration[self::VERSION_PROTOCOL] = '1.0';
        
        if(isset($this->Configuration[self::SERVER_PROTOCOL]))
        {     
            $p = stristr($this->Configuration[self::SERVER_PROTOCOL], '/',false);

            if($p !== false)
            {
                $this->Configuration[self::VERSION_PROTOCOL] = substr($p,1);  
            }    
        }
    }    
    
    public function isValidIp($uip)
    {
        $cip = current(unpack("A4",inet_pton($uip)));

        $c = mb_strlen($cip);
        
        if($c == 16 || $c == 4 )
        {
            return $c;
        }
        
        return false;
    }


    public function ipv4ToIpv6($ip) 
    {
        $mask = '::ffff:';
        
        $IPv6 = (strpos($ip,'::') === 0);
        $IPv4 = (strpos($ip,'.') > 0);
        
        $s = $this->isValidIp($ip);
        
        if (!$s){return false;}
        
        if ($IPv6 && $IPv4)
        {
            $ip = substr($ip,strrpos($ip,':') + 1);
        }
        else if(!$IPv4)
        {
            return $ip;
        }
        
        $ip = array_pad(explode('.', $ip),4,0);
        
        if(count($ip) > 4)
        {
            return false;
        }
        
        for($i = 0; $i < 4; $i++)
        { 
            if($ip[$i] > 255)
            {
                return false;
                
            } 
        }
        
        $part7 = base_convert(($ip[0] * 256) + $ip[1], 10, 16);
        $part8 = base_convert(($ip[2] * 256) + $ip[3], 10, 16);
        
        $ip6 = mb_strtoupper($mask.$part7.':'.$part8);

        return $ip6;
    }
        
    private function verifyRequestPort()
    {
        $port = $this->Configuration[self::SERVER_PORT] == 80 
                ? null 
                : ':'.$this->Configuration[self::SERVER_PORT];
   
        $this->Configuration[self::REQUEST_PORT] = $port;        
    } 
    
    public function getHostName()
    {
        $host= gethostname();
        
        return $host;
    }
    
    public function get($key)
    {
        return isset($this->Configuration[$key]) ? $this->Configuration[$key] : null;
    }
    
    public function set($key,$value)
    {
       $key = $this->normalizeNameConstant($key);

       $this->Configuration[$key] = $value;
       
       if(!isset($this->originalParams[$key]))
       {
           $this->originalParams[$key] = $value;
           $this->setConstant($key,$value);
           
       }
       
       return $this;
    } 
    
    public function resetChanges()
    {
        $this->Configuration = $this->originalParams;
    }
    
    public function getIPV4()
    {
        return gethostbyname($this->getHostName());
    }
    
    public function getIPV6()
    {
        return $this->ipv4ToIpv6($this->getIPV4());
    }
    
    public function getAll()
    {
        return $this->Configuration;
    } 
}
