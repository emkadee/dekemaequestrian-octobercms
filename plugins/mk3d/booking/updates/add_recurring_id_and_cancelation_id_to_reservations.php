<?php namespace Mk3d\Booking\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddRecurringGroupIdToReservationsTable extends Migration
{
    public function up()
    {
        Schema::table('mk3d_booking_reservations', function($table)
        {
            $table->string('recurring_group_id')->nullable();
            $table->string('cancellation_token')->unique()->nullable(); // Add the cancellation_token field
        });
    }

    public function down()
    {
        Schema::table('mk3d_booking_reservations', function($table)
        {
            $table->dropColumn('recurring_group_id');
            $table->dropColumn('cancellation_token'); // Drop the cancellation_token field
        });
    }
}