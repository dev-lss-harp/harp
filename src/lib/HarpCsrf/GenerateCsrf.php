<?php 
namespace Harp\lib\HarpCsrf;
use League\Flysystem\Filesystem;
use Harp\lib\HarpJson\Json;

class GenerateCsrf
{
    private const directory = 'csrf'; 
    private const filename = 'csrf.json';
    private Filesystem $FileSystemDir;
    private Filesystem $FileSystemFile;

    public function __construct($key,$path)
    {
        $this->FileSystemDir = new Filesystem($path);
        $this->FileSystemFile = new Filesystem(sprintf('%s%s%s',$path,DIRECTORY_SEPARATOR,self::filename));

        if(!$this->FileSystemDir->directoryExists(self::directory))
        {
            $this->FileSystemDir->createDirectory(self::directory);
        }

        $strFile = "{}";

        if($this->FileSystemFile->fileExists(self::filename))
        {
            $strFile = $this->FileSystemFile->read(self::filename);
        }

        $json = (new Json($strFile,Json::JSON_DECODE))
                ->exec()
                ->getResponse();

        dd($json);
    }
}