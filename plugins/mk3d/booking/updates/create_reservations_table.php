<?php namespace Mk3d\Booking\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateReservationsTable Migration
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
        Schema::create('mk3d_booking_reservations', function(Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('customer_name', 255);
            $table->string('customer_email', 255);
            $table->date('reservation_date');
            $table->time('reservation_start_time');
            $table->time('reservation_end_time');
            $table->integer('location_id');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('mk3d_booking_reservations');
    }
};
