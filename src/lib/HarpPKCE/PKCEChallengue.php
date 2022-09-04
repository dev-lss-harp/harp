<?php 
namespace Harp\lib\HarpPKCE;

use Exception;

class PKCEChallengue
{
    private $algos = [
        'sha256' => 'S256',
        'plain' => 'plain'
    ];
    private $algo = 'sha256';
    private $Verify;
    private $codeChallengue;
    public function __construct(PKCEVerify $Verify,$algo = 'sha256',$safe = false)
    {
        $this->Verify = $Verify;

        if(!isset($this->algos[$algo]))
        {
            throw new Exception('algo {'.$algo.'} invalid!');
        }

        $this->algo = $this->algos[$algo];

        $hash = hash($algo,$this->Verify->getCodeVerify(),true);
 
        $this->codeChallengue = PKCE::base64UrlEncode($hash); 
     
        return $this;
    }

    public function getVerify()
    {
        return $this->Verify;
    }    

    public function getChallengue()
    {
        return $this->codeChallengue;
    }

    public function getChallengueMethod()
    {
        return $this->algo;
    }
}