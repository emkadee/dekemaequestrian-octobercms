<?php namespace Mk3d\Booking\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateLocationsTable Migration
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
        Schema::create('mk3d_booking_locations', function(Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 255);
            $table->time('opening_time');
            $table->time('closing_time');
            $table->integer('timeslot_duration');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('mk3d_booking_locations');
    }
};
