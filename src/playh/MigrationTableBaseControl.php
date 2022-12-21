<?php
namespace Harp\playh;

use Illuminate\Database\Capsule\Manager as CapsuleManager;

class MigrationTableBaseControl
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up($name = 'default')
    {
        
        CapsuleManager::schema($name)->create('migrations',function($table){
            $t = microtime(true);
            $micro = sprintf("%06d",($t - floor($t)) * 1000000);
            $d = new \DateTime( date('Y-m-d H:i:s.'.$micro, $t));

            $table->bigIncrements('id');
            $table->string('migration',255);
            $table->string('table',120);
            $table->string('action',12);
            $table->integer('batch');
            $table->timestamp('created_at')->default($d->format("Y-m-d H:i:s.u"));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down($name = 'default')
    {
        CapsuleManager::schema($name)->dropIfExists('migrations');
    }
}
