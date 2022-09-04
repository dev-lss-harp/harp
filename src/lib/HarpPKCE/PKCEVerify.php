<?php 
namespace Harp\lib\HarpPKCE;

use Harp\lib\HarpGuid\Guid;

class PKCEVerify
{
    private $codeVerify;
    public function __construct()
    {
        $this->codeVerify = PKCE::base64UrlEncode(Guid::newGuid());
    }

    public function getCodeVerify()
    {
        return $this->codeVerify;
    }
}