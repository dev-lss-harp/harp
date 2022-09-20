<?php
namespace Harp\lib\HarpEntityHandler;

use Illuminate\Database\Eloquent\Model;

interface EntitySaveInterface
{    
    public function save(Model $model);
}
