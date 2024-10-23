<?php namespace Mk3d\Booking\Updates;

use Seeder;
use Mk3d\Booking\Models\Location;

class SeedLocationTable extends Seeder
{
    public function run()
    {
        $location = Location::create([
            'name' => 'Rijhal',
            'opening_time' => '07:00',
            'closing_time' => '00:00',
            'timeslot_duration' => '60',
        ]);
    }
}