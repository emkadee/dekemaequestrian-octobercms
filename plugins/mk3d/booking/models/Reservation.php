<?php namespace Mk3d\Booking\Models;

use Model;

/**
 * Reservation Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Reservation extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'mk3d_booking_reservations';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'customer_name' => 'required', 'string',
        'customer_email' => 'required', 'email',
        'reservation_date' => 'required', 'date',
        'reservation_start_time' => 'required', 'time',
        'reservation_end_time' => 'required', 'time',
        'location_id' => 'required', 'integer',
        'recurring_group_id',
        'cancellation_token'
    ];
    protected $fillable = ['customer_name', 'customer_email', 'reservation_date', 'reservation_start_time', 'reservation_end_time', 'location_id'];

    
    public $belongsTo = [
        'location' => \Mk3d\Booking\Models\Location::class
    ];

}
