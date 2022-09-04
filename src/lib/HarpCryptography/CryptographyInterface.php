<?php
namespace Harp\lib\HarpCryptography;

interface CryptographyInterface 
{
    const DEFAULT_KEY_PARAMETER_IV = 'DEFAULT_KEY_PARAMETER_IV';
    const DEFAULT_PARAMETER_IV = 'DEFAULT_PARAMETER_IV';
    const DEFAULT_ALGO = 'fnv132';
    const DEFAULT_CIPHER = 'AES-256-CBC';

    const DEFAULT_KEY_ENCRYPTION = 'DEFAULT_KEY_ENCRYPTION';
    public function encrypt($str,$key = null,$parameterIV = null);
    public function decrypt($str,$key = null,$parameterIV = null);
    
}
