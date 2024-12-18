<?php namespace Mk3d\Booking\Models;

use Model;
use DateTime;
use Log;
use Validator;

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
        'reservation_start_date' => 'required', 'date',
        'reservation_end_date' => 'required', 'date',
        'reservation_start_time' => 'required', 'time',
        'reservation_end_time' => 'required', 'time',
        'recurring_group_id', 'nullable|string',
        'cancellation_token', 'nullable|string',
        'location_id' => 'required', 'integer'
    ];

    protected $dates = [
        'reservation_start_date',
        'reservation_end_date',
        'reservation_start_time',
        'reservation_end_time',
    ];


/*     public function getReservationDateAttribute($value)
    {
        return Carbon::parse($value)->setTimezone('Europe/Amsterdam');
    }

    public function getEndDateAttribute($value)
    {
        return Carbon::parse($value)->setTimezone('Europe/Amsterdam');
    } */
    
    protected $fillable = [
        'customer_name', 
        'customer_email', 
        'reservation_start_date', 
        'reseration_end_date', 
        'reservation_start_time', 
        'reservation_end_time', 
        'location_id', 
        'recurring_group_id', 
        'cancellation_token',
        'status'
    ];

    protected $appends = ['status_label'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Remove the update_customer field from the attributes
            unset($model->attributes['update_customer']);
        });
    }

    public function beforeValidate()
    {
        $rules = [
            'recurring' => 'nullable|boolean',
            'frequency' => 'nullable|in:1,2,4',
            'recurring_end_date' => 'nullable|date|after:today',
            'update_customer' => 'nullable|boolean'	
        ];

        $validation = Validator::make($this->attributes, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }
    }

    public function afterSave()
    {
        if ($this->recurring) {
            $this->createRecurringReservations();
        }
    }

    public function setFormFieldVisibility($form, $context = null)
    {
        if ($context == 'update') {
            $form->getField('recurring')->hidden = true;
            $form->getField('frequency')->hidden = true;
            $form->getField('recurring_end_date')->hidden = true;
        }
    }

    protected function createRecurringReservations()
    {
        $frequency = $this->frequency;
        $endDate = new DateTime($this->reservation_end_date);
        $currentDate = new DateTime($this->reservation_start_date);

        while ($currentDate < $endDate) {
            $currentDate->modify("+{$frequency} weeks");

            if ($currentDate >= $endDate) {
                break;
            }

            $newReservation = $this->replicate();
            $newReservation->reservation_end_date = $currentDate->format('Y-m-d H:i:s');
            $newReservation->save();
        }
    }

    // Static method to get status labels
    public static function getStatusLabels()
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled'
        ];
    }


    // Accessor to get the status label
    public function getStatusLabelAttribute()
    {
        $statuses = self::getStatusLabels();
        return $statuses[$this->status] ?? $this->status;
    }


    // Static method to get status options including 'All'
    public static function getStatusOptions()
    {
        $statusOptions = self::getStatusLabels();
        return ['0' => 'All'] + $statusOptions;
    }


    public function getRecurringReservationsAttribute()
    {
        $reservations = self::where('recurring_group_id', $this->recurring_group_id)
            ->where('id', '!=', $this->id)
            ->get();
        
        foreach ($reservations as $reservation) {
            $reservation->reservation_start_date = $reservation->reservation_start_date;
            $reservation->reservation_start_time = (new DateTime($reservation->reservation_start_time))->format('H:i');
            $reservation->reservation_end_time = (new DateTime($reservation->reservation_end_time))->format('H:i');
        }

        Log::info('Recurring Reservations: ' . $reservations);

        return $reservations;    
    }
    


    public function getRecurringReservations()
    {

            
        $recurringGroupId = $this->recurring_group_id;
        if (is_string($recurringGroupId)) {
            $recurringGroupId = [$recurringGroupId];
        }

        $reservations = self::whereIn('recurring_group_id', $recurringGroupId)
                        ->where('id', '!=', $this->id)
                        ->get();

        
        Log::info('Recurring Reservations from model: ' . $reservations);

        foreach ($reservations as $reservation) {
            $reservation->reservation_start_time = dutchDate($reservation->reservation_start_time, '%H:%M');
            $reservation->reservation_end_time = dutchDate($reservation->reservation_end_time, '%H:%M');
            $reservation->reservation_start_date = dutchDate($reservation->reservation_start_date, '%Y-%m-%d');
        }       

        return $reservations;

    }

    public $belongsTo = [
        'location' => \Mk3d\Booking\Models\Location::class
    ];

    

}
