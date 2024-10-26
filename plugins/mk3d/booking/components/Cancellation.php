<?php namespace Mk3d\Booking\Components;

use Cms\Classes\ComponentBase;
use Mk3d\Booking\Models\Reservation;
use Mk3d\Booking\Models\Location;
use Log;
use Input;
use Flash;


class Cancellation extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Cancellation Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'cancellation_token' => [
                'title' => 'Cancellation Token',
                'description' => 'The ID of the location to display reservations for',
                'type' => 'string',
                'default' => '{{ :cancellation_token }}'
            ]
        ];
    }

    public $cancellationToken;

    public function onRun()
    {
        $this->page['cancellation_token'] = $this->property('cancellation_token');
        
        
        $cancellationToken = $this->param('cancellation_token');
        Log::info('Cancelling reservation : ' . $cancellationToken);

        $reservation = Reservation::where('cancellation_token', $cancellationToken)->first();

        if (!$reservation) {
            \Flash::error('Invalid or expired cancellation token.');
            return \Redirect::to('/');
        }

        $recurringReservations = Reservation::where('recurring_group_id', $reservation->recurring_group_id)->get();
        
        $this->page['isRecurring'] = Reservation::where('recurring_group_id', $reservation->recurring_group_id)->count() > 1;
        $this->page['cancellationToken'] = $cancellationToken;
        $this->page['recurringGroupId'] = $reservation->recurring_group_id;
        $this->page['recurringReservations'] = $recurringReservations; 
        $this->page['reservation'] = $reservation; 
       
    }

    //CANCEL RESERVATION


    public function onConfirmCancelReservation()
    {
        $cancellationToken = post('cancellation_token');
        $cancelAll = post('cancel_all', false);

        $reservation = Reservation::where('cancellation_token', $cancellationToken)->first();


        if (!$reservation) {
            \Flash::error('Invalid or expired cancellation token.');
            return \Redirect::to('/');
        }

        if ($cancelAll) {
            Reservation::where('recurring_group_id', $reservation->recurring_group_id)->delete();
            \Flash::success('All recurring reservations have been canceled.');
        } else {
            $reservation->delete();
            \Flash::success('Your reservation has been canceled.');
        }

        return \Redirect::to('/');
    }

}

?>