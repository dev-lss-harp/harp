<?php
namespace Harp\lib\HarpEntityHandler;

use Illuminate\Database\Eloquent\Model;

interface EntityRemoveInterface
{    
    public function remove(Model $model);
}
