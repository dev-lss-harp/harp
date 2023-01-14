<?php 
use Harp\lib\HarpJson\JsonWrite;
/**
 * JSON WRITE
 */
$JsonWrite = new JsonWrite(PATH_STORAGE);
            $JsonWrite->set('teste',['xpto' => 'z'],JsonWrite::OBJECT)
                      ->set('teste.x',[],JsonWrite::OBJECT)
                      ->set('teste.x.y','Leonardo Souza')
                      ->set('teste.x.r',[],JsonWrite::ARRAY)
                      ->set('teste.x.r.p',1)
                      ->set('teste.x.r.p',2)
                      ->set('teste.x.r.p',20.36)
                      ->set('teste.x.r.p.100',100.56)
                      ->delete('teste.x.r.p.1')
                      ->delete('teste.xpto');
print($JsonWrite->get());
$JsonWrite->save();

exit();