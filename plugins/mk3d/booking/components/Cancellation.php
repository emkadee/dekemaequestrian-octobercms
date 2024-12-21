<?php namespace Mk3d\Booking\Components;

use Cms\Classes\ComponentBase;
use Mk3d\Booking\Models\Reservation;
use Mk3d\Booking\Models\Location;
use Log;
use Input;
use Flash;
use Mail;
use Config;
use Backend;


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

        Log::info('Recurring Reservations: ' . $recurringReservations);
       
    }

    //CANCEL RESERVATION


    public function onConfirmCancelReservation()
    {
        $cancellationToken = post('cancellation_token');
        $cancelAll = post('cancel_all', false);

        $reservation = Reservation::where('cancellation_token', $cancellationToken)->get();
        
        $messages = 'Deze reservering is geannuleerd.';
        $statusMessage = 'Geannuleerd';

        $i = 0;

        foreach ($reservation as $res) {
            if ($i<1){
                $location = Location::find($res->location_id);
                $locationName = $location->name;
                $email = $res->customer_email;
                $name = $res->customer_name;
                $reservationId = $res->id;
            }
            $reservationDetails[] = [
                'date' => $res->reservation_start_date,
                'end_date' => $res->reservation_end_date,
                'time' => $res->reservation_start_time,
                'end_time' => $res->reservation_end_time,
                'location' => $locationName,
                'messages' => $messages,
                'status_message' => $statusMessage
            ];

            $i++;
        } 

        // Send the reservation confirmation email with the cancellation link and the location name
        $mailSubject = 'Jouw reservering is geannuleerd';
        $this->sendReservationConfirmationEmail($email, $name, $mailSubject, $reservationDetails, $reservationId);

        $reservation = Reservation::where('cancellation_token', $cancellationToken)->first();

        if (!$reservation) {
            \Flash::error('Invalid or expired cancellation token.');
            return \Redirect::to('/');
        }

        if ($cancelAll) {
            Reservation::where('recurring_group_id', $reservation->recurring_group_id)->update(['status' => 'cancelled']);
            \Flash::success('All recurring reservations have been canceled.');
        } else {
            $reservation->update(['status' => 'cancelled']);
            \Flash::success('Your reservation has been canceled.');
        }

       



        return \Redirect::to('/');
    }

    protected function sendReservationConfirmationEmail($email, $name, $mailSubject, $reservationDetails, $reservationId)
    {
        // Debugging: Log the data to ensure it's correct
        Log::info('Reservation details: ' . json_encode($reservationDetails));

        // Send the email (assuming you have a mail template set up)
        Mail::send('mk3d.booking::mail.cancellation_confirmation', [
            'name' => $name, 
            'reservation_details' => $reservationDetails
        ], 
        
        function($message) use ($email, $name, $mailSubject) {
            $message->to($email, $name);
            $message->subject($mailSubject);
            $message->bcc(Config::get('mail.from.address'), Config::get('mail.from.name'));
        });


        $subject = 'Reservering geannuleerd';

        // Send an extra email to the configured 'from' address with a link to the corresponding message
        $fromAddress = Config::get('mail.from.address');
        $fromName = Config::get('mail.from.name');
        $reservationLink = Backend::url('mk3d/booking/reservations/update/' . $reservationId);

        Mail::send('mk3d.contactform::mail.admin_notification', ['messageLink' => $reservationLink], function($mail) use ($fromAddress, $fromName, $subject) {
            $mail->to($fromAddress, $fromName);
            $mail->subject($subject);
        });

    }


}

?>