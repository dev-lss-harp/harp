<?php
namespace Harp\playh;

//start_use
use Harp\app\api\storage\migrations\CriarTableTipoImagem;
    use Harp\app\api\storage\migrations\CriarTabelaImagem;
//end_use

//start_require
require_once('/home/lss/web-dev/jobs7Api/app/api/storage/migrations/CriarTableTipoImagem.php');
require_once('/home/lss/web-dev/jobs7Api/app/api/storage/migrations/CriarTabelaImagem.php');
//end_require

class Migrate
{
    private $s = 1;

    //start_order
public $orders = [
  'CriarTabelaImagem'=>1,
  'CriarTableTipoImagem'=>0
];//end_order

    //start_declare
private $CriarTableTipoImagem = null;
    private $CriarTabelaImagem = null;
//end_declare

    public function __construct(){}

    public function exists()
    {
        return $this->s;
    }

    //start_methods
public function getCriarTableTipoImagem(){

       $this->CriarTableTipoImagem = new CriarTableTipoImagem();
       return $this->CriarTableTipoImagem;

   }

   public function getCriarTabelaImagem(){

       $this->CriarTabelaImagem = new CriarTabelaImagem();
       return $this->CriarTabelaImagem;

   }

//end_methods

}
