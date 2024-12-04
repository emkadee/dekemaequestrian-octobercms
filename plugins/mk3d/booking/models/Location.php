<?php namespace Mk3d\Booking\Models;

use Model;

/**
 * Location Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Location extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'mk3d_booking_locations';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required', 'string',
        'opening_time' => 'required', 'time',
        'closing_time' => 'required', 'time',
        'timeslot_duration' => 'required', 'time',
        'color' => 'required', 'color',
        'public_available' => 'required', 'boolean' 
    ];

    protected $fillable = ['name', 'opening_time', 'closing_time', 'timeslot_duration', 'color', 'public_available'];

    public $hasMany = [
        'reservation' => \Mk3d\Booking\Models\Reservation::class
    ];

}
