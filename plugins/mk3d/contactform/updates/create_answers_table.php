<?php namespace Mk3d\ContactForm\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateMessagesTable Migration
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
        Schema::create('mk3d_contactform_answers', function(Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('title');
            $table->string('subject');
            $table->string('message');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('mk3d_contactform_answers');
    }
};
