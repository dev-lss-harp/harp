<?php 
namespace Harp\lib\HarpCsrf;

use Harp\lib\HarpGuid\Guid;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Harp\lib\HarpJson\Json;

class GenerateCsrf
{
    private const directory = 'csrf'; 
    private const filename = 'csrf.json';
    private const key = 'csrf_token';
    private Filesystem $FileSystemDir;
    private Filesystem $FileSystemFile;

    public function __construct($path)
    {
        $this->FileSystemDir = new Filesystem(new LocalFilesystemAdapter($path));
        $this->FileSystemFile = new Filesystem(new LocalFilesystemAdapter(sprintf('%s%s%s',$path,DIRECTORY_SEPARATOR,self::directory)));

        if(!$this->FileSystemDir->directoryExists(self::directory))
        {
            $this->FileSystemDir->createDirectory(self::directory);
        }    
    }

    public function generate(?string $key = null,int $expired = 45)
    {
        $key = $key ?? self::key;

        $DateExpired = new \DateTime();
        $DateExpired->modify(sprintf('+%s minutes',$expired));

        $strFile = "{}";

        if($this->FileSystemFile->fileExists(self::filename))
        {
            $strFile = $this->FileSystemFile->read(self::filename);
        }

        $json = (new Json($strFile,Json::JSON_DECODE))
                ->getResponse();

        $json[$key] = 
        [
            'token' => base64_encode(Guid::newGuid()),
            'expired' => $DateExpired->format('Y-m-d H:i:s')
        ]; 

        $this->FileSystemFile->write(
            sprintf('%s%s',DIRECTORY_SEPARATOR,self::filename),
            json_encode($json)
        ); 

        return $json[$key]['token'];
    }
}