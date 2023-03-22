<?php 
namespace Harp\lib\HarpCsrf;

use DateTime;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;;
use Harp\lib\HarpJson\Json;

class ValidateCsrf
{
    private const directory = 'csrf'; 
    private const filename = 'csrf.json';
    private const key = 'csrf_token';
    private $content = [];
    private Filesystem $FileSystemFile;
    

    public function __construct($path)
    {
        $this->FileSystemFile = new Filesystem(new LocalFilesystemAdapter(sprintf('%s%s%s',$path,DIRECTORY_SEPARATOR,self::directory)));

        if(!$this->FileSystemFile->fileExists(self::filename))
        {
            throw new Exception(sprintf('File {%s} not found in directory!',self::filename),500);
        }
    } 

    public function get($key = null)
    {
        $key = $key ?? self::key;
  
        /*$strFile = $this->FileSystemFile->read(self::filename);

        $Json = new Json();

        if(!$Json->exec(Json::IS_JSON_NON_SCALAR,$strFile))
        {
            throw new Exception(sprintf('Content in file {%s} is not a valid json!',self::filename),500);
        }
        
        $this->content = $Json->exec(Json::JSON_DECODE);*/

        $this->contents();

        if(!array_key_exists($key,$this->content))
        {
            throw new Exception(sprintf('Access key {%s} invalid!',$key),401);
        }

        return $this->content[$key];
    }

    private function contents()
    {
        $strFile = $this->FileSystemFile->read(self::filename);

        $Json = new Json();

        if(!$Json->exec(Json::IS_JSON_NON_SCALAR,$strFile))
        {
            throw new Exception(sprintf('Content in file {%s} is not a valid json!',self::filename),500);
        }
        
        $this->content = $Json->exec(Json::JSON_DECODE);

        return $this->content;
    }

    public function deleteAllExpired()
    {
        $this->contents();

        foreach($this->content as $key => $csrf)
        {
            $Expired = new DateTime($csrf['expired']);
            $Expired->modify(sprintf("+%s minutes",10));

            if((new DateTime()) > $Expired)
            {
                $this->delete($key);
            }
        }
       
    }

    public function validate($token,$key = null)
    {
        $content = $this->get($key);

        if
            (
                trim($token) != trim($content['token'])
                ||
                $expired = (new DateTime()) > (new DateTime($content['expired']))
            )
        {
            if($expired)
                $this->delete($key);

            throw new Exception('CSRF token invalid!',500);
        }

    }

    public function delete($key)
    {
        $this->contents();
        if(array_key_exists($key,$this->content))
        {
            unset($this->content[$key]);

            $this->FileSystemFile->write(
                sprintf('%s%s',DIRECTORY_SEPARATOR,self::filename),
                json_encode($this->content)
            ); 
        }
    }
}