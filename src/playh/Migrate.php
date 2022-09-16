<?php
namespace Harp\playh;

//start_use
use Harp\app\api\storage\migrations\CriarTextoSecao;
    use Harp\app\api\storage\migrations\CriarTabelaImagem;
//end_use

//start_require
require_once('/home/lss/web-dev/jobs7Api/app/api/storage/migrations/CriarTextoSecao.php');
require_once('/home/lss/web-dev/jobs7Api/app/api/storage/migrations/CriarTabelaImagem.php');
//end_require

class Migrate
{
    private $s = 1;

    //start_declare
private $CriarTextoSecao = null;
    private $CriarTabelaImagem = null;
//end_declare

    public function __construct(){}

    public function exists()
    {
        return $this->s;
    }

    //start_methods
public function getCriarTextoSecao(){

       $this->CriarTextoSecao = new CriarTextoSecao();
       return $this->CriarTextoSecao;

   }

   public function getCriarTabelaImagem(){

       $this->CriarTabelaImagem = new CriarTabelaImagem();
       return $this->CriarTabelaImagem;

   }

//end_methods

}
