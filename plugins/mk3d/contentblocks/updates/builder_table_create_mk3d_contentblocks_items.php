<?php namespace Mk3d\Contentblocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMk3dContentblocksItems extends Migration
{
    public function up()
    {
        Schema::create('mk3d_contentblocks_items', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title', 255);
            $table->text('description');
            $table->string('image', 255);
            $table->string('type', 255)->default('content');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mk3d_contentblocks_items');
    }
}
