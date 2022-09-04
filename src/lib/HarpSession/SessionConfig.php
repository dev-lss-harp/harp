<?php 
namespace Harp\lib\HarpSession;

use Exception;

class SessionConfig
{
    private $sessionName;
    private $gcProbability = 1;
    private $gcDivisor = 100;
    private $gcMaxLifeTime = 1440;
    private $autoStart = 0;
    private $cookieLifetime = 0;
    private $cookiePath = '/';
    private $cookieDomain = '';
    private $cookieHttponly = true;
    private $cookieSamesite = '';
    private $cookieSecure = false;
    private $useStrictMode = 0;
    private $useCookies = 1;
    private $useOnlyCookies = 1;
    private $cacheLimiter = 'nocache';
    private $cacheExpire = 180;


    public function __construct($sessionName)
    {
        $this->sessionName = is_string($sessionName) ? $sessionName : uniqid('sess_');

        $params = session_get_cookie_params();

        $this->cookiePath = $params['path'];

        $this->cookieDomain = $params['domain'];
                
        session_set_cookie_params($this->gcMaxLifeTime,$this->cookiePath,$this->cookieDomain,$this->cookieSecure,$this->cookieHttponly);
            
        session_name($this->sessionName);

        session_cache_expire($this->cacheExpire);
    }

    

    /**
     * Set the value of gcProbability
     *
     * @return  self
     */ 
    public function setGcProbability($gcProbability)
    {
        $this->gcProbability = $gcProbability;

        ini_set('session.gc_probability',(int)$this->gcProbability);

        return $this;
    }

    /**
     * Set the value of gcDivisor
     *
     * @return  self
     */ 
    public function setGcDivisor($gcDivisor)
    {
        $this->gcDivisor = $gcDivisor;

        ini_set('session.gc_divisor',(int)$this->gcDivisor);

        return $this;
    }

    /**
     * Set the value of useOnlyCookies
     *
     * @return  self
     */ 
    public function setUseOnlyCookies($useOnlyCookies)
    {
        $this->useOnlyCookies = $useOnlyCookies;

        ini_set('session.use_only_cookies',(bool)$this->useOnlyCookies);

        return $this;
    }

    /**
     * Set the value of autoStart
     *
     * @return  self
     */ 
    public function setAutoStart($autoStart)
    {
        $this->autoStart = $autoStart;

        ini_set('session.auto_start',(bool)$this->autoStart);

        return $this;
    }

    /**
     * Set the value of cookieLifetime
     *
     * @return  self
     */ 
    public function setCookieLifetime($cookieLifetime)
    {
        $this->cookieLifetime = $cookieLifetime;

        ini_set('session.cookie_lifetime',(int)$this->cookieLifetime);

        return $this;
    }

    /**
     * Set the value of cookiePath
     *
     * @return  self
     */ 
    public function setCookiePath($cookiePath)
    {
        $this->cookiePath = $cookiePath;

        ini_set('session.cookie_path',$this->cookiePath);

        return $this;
    }

    /**
     * Set the value of cookieDomain
     *
     * @return  self
     */ 
    public function setCookieDomain($cookieDomain)
    {
        $this->cookieDomain = $cookieDomain;

        ini_set('session.cookie_domain',$this->cookieDomain);

        return $this;
    }

    /**
     * Set the value of cookieHttponly
     *
     * @return  self
     */ 
    public function setCookieHttponly($cookieHttponly)
    {
        $this->cookieHttponly = $cookieHttponly;

        ini_set('session.cookie_httponly',(bool)$this->cookieHttponly);

        return $this;
    }

    /**
     * Set the value of cookieSamesite
     *
     * @return  self
     */ 
    public function setCookieSamesite($cookieSamesite)
    {
        $this->cookieSamesite = $cookieSamesite;

        ini_set('session.cookie_samesite',$this->cookieSamesite);

        return $this;
    }

    /**
     * Set the value of cookieSecure
     *
     * @return  self
     */ 
    public function setCookieSecure($cookieSecure)
    {
        $this->cookieSecure = $cookieSecure;

        ini_set('session.cookie_secure',(bool)$this->cookieSecure);

        return $this;
    }

    /**
     * Set the value of useStrictMode
     *
     * @return  self
     */ 
    public function setUseStrictMode($useStrictMode)
    {
        $this->useStrictMode = $useStrictMode;

        ini_set('session.use_strict_mode',(bool)$this->useStrictMode);

        return $this;
    }

    /**
     * Set the value of useCookies
     *
     * @return  self
     */ 
    public function setUseCookies($useCookies)
    {
        $this->useCookies = $useCookies;

        ini_set('session.use_cookies',(bool)$this->useCookies);

        return $this;
    }
    /**
     * Set the value of cacheLimiter
     *
     * @return  self
     */ 
    public function setCacheLimiter($cacheLimiter)
    {
        $this->cacheLimiter = $cacheLimiter;

        ini_set('session.cache_limiter',(int)$this->setCacheLimiter);

        return $this;
    }

    /**
     * Set the value of cacheExpire
     *
     * @return  self
     */ 
    public function setCacheExpire($cacheExpire)
    {
        $this->cacheExpire = $cacheExpire;

        ini_set('session.cache_expire',(int)$this->cacheExpire);

        return $this;
    }

    /**
     * Get the value of gcMaxLifeTime
     */ 
    public function getGcMaxLifeTime()
    {
        return $this->gcMaxLifeTime;
    }

    /**
     * Set the value of gcMaxLifeTime
     *
     * @return  self
     */ 
    public function setGcMaxLifeTime($gcMaxLifeTime)
    {
        $this->gcMaxLifeTime = $gcMaxLifeTime;

        ini_set('session.gc_maxlifetime',(int)$this->gcMaxLifeTime);

        return $this;
    }
}