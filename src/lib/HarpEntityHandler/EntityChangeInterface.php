<?php
namespace Harp\lib\HarpEntityHandler;

use Illuminate\Database\Eloquent\Model;

interface EntityChangeInterface
{    
    public function change(Model $model);
}
