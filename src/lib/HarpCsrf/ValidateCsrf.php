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
    private Filesystem $FileSystemFile;
    

    public function __construct($path)
    {
        $this->FileSystemFile = new Filesystem(new LocalFilesystemAdapter(sprintf('%s%s%s',$path,DIRECTORY_SEPARATOR,self::directory)));

        if(!$this->FileSystemFile->fileExists(self::filename))
        {
            throw new Exception(sprintf('File {%s} not found in directory!',self::filename),500);
        }
    } 

    public function validate($token,$key = null)
    {
        $key = $key ?? self::key;

        $strFile = $this->FileSystemFile->read(self::filename);

        $Json = new Json();

        if(!$Json->exec(Json::IS_JSON,$strFile))
        {
            throw new Exception(sprintf('Content in file {%s} is not a valid json!',self::filename),500);
        }
        
        $content = $Json->exec(Json::JSON_DECODE);
     
        
       
        if
            (
                !\array_key_exists($key,$content) 
                || 
                trim($token) != trim($content[$key]['token'])
                ||
                $expired = (new DateTime()) > (new DateTime($content[$key]['expired']))
            )
        {
            if($expired)
                $this->delete($key,$content);

            throw new Exception('CSRF token invalid!',500);
        }

    }

    private function delete($key,$content)
    {
        if(array_key_exists($key,$content))
        {
            unset($content[$key]);

            $this->FileSystemFile->write(
                sprintf('%s%s',DIRECTORY_SEPARATOR,self::filename),
                json_encode($content)
            ); 
        }
    }
}