<?php namespace Mk3d\Contactform\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateMaillogsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('mk3d_contactform_maillogs', function(Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('receiver');
            $table->string('receiver_email');
            $table->string('subject');
            $table->text('message');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('mk3d_contactform_maillogs');
    }
};
