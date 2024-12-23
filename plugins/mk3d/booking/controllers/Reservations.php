<?php namespace Mk3d\Booking\Controllers;

use BackendMenu;
use Backend;
use Backend\Classes\Controller;
use Mk3d\Booking\Models\Reservation;
use Mk3d\Booking\Models\Location;
use Flash;
use Redirect;
use Log;
use Mail;
use Response;
use Input;
use ValidationException;
use Validator;
use DateTime;
use Carbon\Carbon;
use Config;


/**
 * Reservations Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Reservations extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\RelationController::class
    ];  

    public $formConfig = 'config_form.yaml';
    public $relationConfig = 'config_relation.yaml';
    public $listConfig = 'config_list.yaml';
    public $filterConfig = 'config_filter.yaml';
    public $requiredPermissions = ['mk3d.booking.reservations'];

    // Set default values for attributes
    protected $attributes = [
        'update_customer' => true,
    ];


    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Mk3d.Booking', 'booking', 'reservations');

        $this->addCss('https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.15/index.global.min.css');
        $this->addJs('https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.15/index.global.min.js');
        $this->addJs('https://cdnjs.cloudflare.com/ajax/libs/tippy.js/2.5.4/tippy.all.js');
    }

    // *** FILTER METHODS *** //

    public static function applyFutureReservationsFilter($query, $scope)
    {
        return $query->where('reservation_end_date', '>=', Carbon::now()->startOfDay()->toDateTimeString());
    }

    public static function getLocationOptions()
    {
        $locations = Location::all()->pluck('name', 'id')->toArray();        
        return ['0' => 'All locations'] + $locations;
        
    }
    
    public static function applyLocationFilter($query, $scope)
    {
        if($scope->value == 0){
            return $query;
        } else {
            return $query->where('location_id', '=', $scope->value);
        }
    }

    public static function getStatusOptions()
    {
        $statusOptions = Reservation::getStatusOptions();        
        return ['0' => 'All'] + $statusOptions;
        
    }

    public static function applyStatusFilter($query, $scope)
    {
        if($scope->value == 0){
            return $query;
        } else {
            return $query->where('status', '=', $scope->value);
        }
    }



    public function formExtendFields($form)
    {

        $reservationId = $form->model->id;

        $hasRecurringReservations = false;

        // Retrieve the reservation with the given ID
        $reservation = Reservation::find($reservationId);

        if ($reservation) {
            // Retrieve the recurring group ID of the reservation
            $recurringGroupId = $reservation->recurring_group_id;

            // Check if there are other reservations with the same recurring group ID
            $recurringReservations = Reservation::where('recurring_group_id', $recurringGroupId)
                ->where('id', '!=', $reservationId) // Exclude the current reservation
                ->get();
                        

            if ($recurringReservations->isNotEmpty()) {
                // There are recurring reservations
                $form->addFields([
                    'recurring_reservations' => [
                        'label' => '',
                        'type' => 'partial',
                        'path' => '$/mk3d/booking/controllers/reservations/_recurring_reservations.php',
                        'span' => 'full',
                        'context' => ['update'],
                    ],
                ]);
                $hasRecurringReservations = true;
            } else {
                // No recurring reservations found
                Log::info('No recurring reservations found for reservation ID: ' . $reservationId);
                $hasRecurringReservations = false;
            }
        } else {
            // Reservation not found
            Log::info('Reservation not found with ID: ' . $reservationId);
        }
    
        $this->vars['hasRecurringReservations'] = $hasRecurringReservations;     
        
        // Check if the form is in create or update context
        if ($form->context == 'create') {
            // Add the partial with the JavaScript
            $form->addFields([
                '_create_update_js' => [
                    'type' => 'partial',
                    'path' => '$/mk3d/booking/controllers/reservations/_create_update_js.htm',
                    'span' => 'full',
                ],
            ]);
        }        

        // Call the model's setFormFieldVisibility method
        $form->model->setFormFieldVisibility($form, $form->context);
    }



    // *** CRUD METHODS *** //
    //Create a new reservation
    public function onCreate()
    {
        $reservation = new Reservation();
       
        $reservation->status = 'pending';
        $reservation->save();

        Flash::success('Reservation created successfully.');
        return Redirect::to(Backend::url('mk3d/booking/reservations/update/' . $reservation->id));
    }

    public function onDeleteOldreservations()
    {
        $cutoffDate = Carbon::now()->subDays(30);
        $oldreservations = Reservation::where('reservation_end_date', '<', $cutoffDate)->get();

        foreach ($oldreservations as $oldreservation) {
            $oldreservation->delete();
        }

        return $this->listRefresh();
    }



    //Create a recurring reservation
    public function create_onSave()
    {
        $formData = post('Reservation');        
        //Get the location ID from the location dropdown and change the post variable accordingly
        $formData['location_id'] = $formData['location'];
        unset($formData['location']);

        $formData['recurring_group_id'] =uniqid('recurring_', true);

        Log::info('Data = ' . print_r($formData, true));

        // Validate the input data
        $rules = [
            // Validation rules for reservation fields
            'recurring' => 'nullable|boolean',
            'frequency' => 'nullable|in:1,2,4',
            'recurring_end_date' => 'nullable|date|after:today',
            'update_customer' => 'nullable|boolean',
        ];

        $validation = Validator::make($formData, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // Calculate the number of reservations
        $startDate = new \DateTime($formData['reservation_start_date']);

        if(!$formData['reservation_end_date']){
            $formData['reservation_end_date'] = $formData['reservation_start_date'];
        }

        $endDate = new \DateTime($formData['reservation_end_date']);
        $recurringEndDate = isset($formData['recurring_end_date']) ? new \DateTime($formData['recurring_end_date']) : null; 
        $interval = isset($formData['frequenty']) ? (int)$formData['frequenty'] : 1;
        $recurringCount = 1;

        if ($endDate) {
            $weeksDiff = $startDate->diff($recurringEndDate)->days / 7;
            $recurringCount = (int)floor($weeksDiff / $interval) + 1;
        }

        if ($recurringCount > 1) {
            $startTime = strtotime($formData['reservation_start_time']);
            $endTime = strtotime($formData['reservation_end_time']);

            // Create an empty array to store all reservation dates for the email
            $reservationDetails = [];

            // Generate a unique recurring group ID if not provided
            $recurringGroupId = uniqid('recurring_', true);

            //Set the messages for the update email
            $messages = [];

            // Create reservations
            for ($i = 0; $i < $recurringCount; $i++) {
                $reservationDate = clone $startDate;
                $reservationEndDate = clone $endDate;
                $reservationDate->modify("+" . ($i * $interval) . " weeks");
                $reservationEndDate->modify("+" . ($i * $interval) . " weeks");

                $startTime = strtotime($formData['reservation_start_time']);
                $endTime = strtotime($formData['reservation_end_time']);     
                
                // Check for overlapping reservations
                $overlappingReservations = Reservation::where('location_id', $formData['location_id'])
                    ->whereDate('reservation_start_date', $reservationDate) // Ensure the date is considered
                    ->where(function($query) use ($startTime, $endTime) {
                        $startTimePlusOneMinute = strtotime('+1 minute', $startTime);
                        $endTimeMinusOneMinute = strtotime('-1 minute', $endTime);

                        $query->whereBetween('reservation_start_time', [date('Y-m-d H:i', $startTime), date('Y-m-d H:i', $endTimeMinusOneMinute)])
                            ->orWhereBetween('reservation_end_time', [date('Y-m-d H:i', $startTimePlusOneMinute), date('Y-m-d H:i', $endTime)])
                            ->orWhere(function($query) use ($startTime, $endTime) {
                                $query->where('reservation_start_time', '<', date('Y-m-d H:i', $startTime))
                                    ->where('reservation_end_time', '>', date('Y-m-d H:i', $endTime));
                            });
                    })
                    ->exists();

                if ($overlappingReservations) {
                    \Flash::error('The selected timeslot is already reserved. Please choose a different timeslot.');
                    return;
                }

                $reservation = new Reservation();
                $reservation->customer_name = $formData['customer_name'];
                $reservation->customer_email = $formData['customer_email'];
                $reservation->location_id = $formData['location_id'];
                $reservation->reservation_start_date = $reservationDate->format('Y-m-d');
                $reservation->reservation_end_date = $reservationEndDate->format('Y-m-d');
                $reservation->reservation_start_time = date('H:i', $startTime);
                $reservation->reservation_end_time = date('H:i', $endTime);
                $reservation->recurring_group_id = $recurringGroupId; // Set the recurring group ID
                $reservation->cancellation_token = uniqid('cancel_', true);   // Set the cancellation token
                $reservation->status = $formData['status'];
                $reservation->save();               
                
                // Add the reservationDetails for the email
                $messages = [];

                if ($reservation->status == 'confirmed' || $reservation->status == 'Confirmed'){
                    //Startdatum aangepast
                    array_push($messages, 'Jouw reservering is akkoord!');
                } else if ($reservation->status == 'pending' || $reservation->status == 'Pending'){
                    //Startdatum aangepast
                    array_push($messages, 'Uw krijgt nog een bevestiging van deze reservering als deze akkoord is.');
                }      
                
                $reservationDetails[] = [
                    'date' => $reservationDate->format('Y-m-d'),
                    'end_date'=> $reservationEndDate->format('Y-m-d'),
                    'time' => date('H:i', $startTime),
                    'end_time' => date('H:i', $endTime),
                    'messages' => $messages,
                    'location' => Location::find($formData['location_id'])->name,
                    'status_message' => $formData['status'] == 'confirmed' ? 'Akkoord' : 'In aanvraag',
                    'cancellation_link' => url('/cancellation/' . $reservation->cancellation_token), // Include the cancellation token
                ];
            }
        } else {
            $messages = [];

            if(!$formData['reservation_end_date']){
                $formData['reservation_end_date'] = $formData['reservation_start_date'];
            }    

            $reservation = new Reservation();
            $reservation->customer_name = $formData['customer_name'];
            $reservation->customer_email = $formData['customer_email'];
            $reservation->location_id = $formData['location_id'];
            $reservation->reservation_start_date = $formData['reservation_start_date'];            
            $reservation->reservation_end_date = $formData['reservation_end_date'];  
            $reservation->reservation_start_time = $formData['reservation_start_time'];
            $reservation->reservation_end_time = $formData['reservation_end_time'];
            $reservation->cancellation_token = uniqid('cancel_', true);   // Set the cancellation token
            $reservation->status = $formData['status'];
            $reservation->save(); 

            // Add the reservationDetails for the email
            if ($reservation->status == 'confirmed' || $reservation->status == 'Confirmed'){
                //Startdatum aangepast
                array_push($messages, 'Jouw reservering is akkoord!');
            } else if ($reservation->status == 'pending' || $reservation->status == 'Pending'){
                //Startdatum aangepast
                array_push($messages, 'Uw krijgt nog een bevestiging van deze reservering als deze akkoord is.');
            }

            $reservationDetails[] = [
                'date' => $formData['reservation_start_date'],
                'end_date'=> $formData['reservation_end_date'],
                'time' => $formData['reservation_start_time'],
                'end_time' => $formData['reservation_end_time'],
                'location' => Location::find($formData['location_id'])->name,
                'messages' => $messages,
                'status_message' => $formData['status'] == 'confirmed' ? 'Akkoord' : 'In aanvraag',
                'cancellation_link' => url('/cancellation/' . $reservation->cancellation_token), // Include the cancellation token
            ];
        }         

        // Send the reservation confirmation email with the reservationDetails  
        if($formData['update_customer']){
            $this->sendReservationConfirmationEmail(
                $formData['customer_email'], 
                $formData['customer_name'], 
                $reservationDetails,
                $formData['status']
            );
        }
        
        \Flash::success('Reservation successfully made!');
        return Redirect::to(Backend::url('mk3d/booking/reservations'));

    }

    //UPDATE A RESERVATION//
    public function update_onSave($recordId = null)
    {
        $formData = post('Reservation');

        // Find the reservation or create a new one
        $reservation = Reservation::find($recordId);
        if (!$reservation) {
            $reservation = new Reservation();
        }
        
        // Check if the update_customer field is true
        $updateCustomer = isset($formData['update_customer']) && $formData['update_customer'];

        // Set the content for the email is update_customer is true
        if ($updateCustomer) {

            if($formData['status'] == 'cancelled' || $formData['status'] == 'Cancelled'){
                $statusMessage = 'Geannuleerd';
            } else {
                $statusMessage = $formData['status'] == 'confirmed' ? 'Akkoord' : 'In aanvraag';
            }
            
            // Add the reservation date to the list for the email
            $reservationDetails[] = [
                'date' => $formData['reservation_start_date'],
                'end_date' => $formData['reservation_end_date'],
                'time' => $formData['reservation_start_time'],
                'end_time' => $formData['reservation_end_time'],
                'location' => Location::find($formData['location'])->name,
                'messages' => $this->setMessages($reservation, $formData, true),
                'status_message' => $statusMessage,
                'cancellation_link' => url('/cancellation/' . $reservation->cancellation_token), // Include the cancellation token
            ];

            
        }

        // Fill the reservation with the form data and save the reservation        
        $reservation->customer_name = $formData['customer_name'];
        $reservation->customer_email = $formData['customer_email'];
        $reservation->location_id = $formData['location'];
        $reservation->reservation_start_date = $formData['reservation_start_date'];
        $reservation->reservation_end_date = $formData['reservation_end_date'];
        $reservation->reservation_start_time = $formData['reservation_start_time'];
        $reservation->reservation_end_time = $formData['reservation_end_time'];
        $reservation->cancellation_token = uniqid('cancel_', true);   // Set the cancellation token
        $reservation->status = $formData['status'];
        $reservation->save();    

        if ($updateCustomer) {
            $this->sendReservationConfirmationEmail(
                $formData['customer_email'], 
                $formData['customer_name'], 
                $reservationDetails,
                $formData['status']
            );
        }
        
        Flash::success('Reservation updated successfully.');
        return Redirect::to('adminde/mk3d/booking/reservations');
    }

    //UPDATE ALL RECURRING RESERVATIONS
    public function onSaveForRecurring($model)
    {

        // Retrieve form data
        $formData = post('Reservation');

        // Retrieve reservation ID and get the reservation
        $reservationId = $model;
        $reservation = Reservation::find($reservationId); 

        if (!$reservation) {
            Flash::error('Reservation not found.');
            return Redirect::back();
        }      

        // Retrieve recurring group ID
        $recurringGroupId = $reservation->recurring_group_id;

        if (!$recurringGroupId) {
            Flash::error('Invalid recurring group ID.');
            return Redirect::back();
        }

        // Retrieve all recurring reservations in the future
        $now = Carbon::now();
        $reservations = Reservation::where('recurring_group_id', $recurringGroupId)
            ->where('reservation_start_date', '>=', $now)
            ->get();

        // Update all recurring reservations
        foreach ($reservations as $recurringReservation) {           


            $recurringReservation->customer_name = $formData['customer_name'];
            $recurringReservation->customer_email = $formData['customer_email'];
            $recurringReservation->reservation_start_time = $formData['reservation_start_time'];
            $recurringReservation->reservation_end_time = $formData['reservation_end_time'];
            $recurringReservation->location_id = $formData['location'];
            $recurringReservation->status = $formData['status'];
            $recurringReservation->save();

            if ($formData['update_customer']) {
                if($formData['status'] == 'cancelled' || $formData['status'] == 'Cancelled'){
                    $statusMessage = 'Geannuleerd';
                } else {
                    $statusMessage = $formData['status'] == 'confirmed' ? 'Akkoord' : 'In aanvraag';
                }

                $reservationDetails[] = [
                    'date' => $recurringReservation->reservation_start_date,
                    'end_date' => $recurringReservation->reservation_end_date,
                    'time' => $formData['reservation_start_time'],
                    'end_time' => $formData['reservation_end_time'],
                    'location' => Location::find($formData['location'])->name,
                    'messages' => $this->setMessages($reservation, $formData, true),
                    'status_message' => $statusMessage,
                    'cancellation_link' => url('/cancellation/' . $recurringReservation->cancellation_token), // Include the cancellation token

                ];
            }    
        }

        if ($formData['update_customer']) {

            
            $this->sendReservationConfirmationEmail(
                $formData['customer_email'], 
                $formData['customer_name'], 
                $reservationDetails,
                $formData['status']
            );
    
        }

        Flash::success('All recurring reservations have been updated.');
        return Redirect::to(Backend::url('mk3d/booking/reservations/'));
    }



    // *** MAIL SENDING METHODS *** //
    //SEND RESERVATION CONFIRMATION EMAIL
    protected function sendReservationConfirmationEmail($email, $name, $reservationDetails, $status)
    {
        
        if ($status == 'cancelled' || $status == 'Cancelled') {
            $template = 'mk3d.booking::mail.cancellation_confirmation';
            $mailSubject = 'Annulering reservering';
            $statusMessage = 'De reservering is geannuleerd.';
        } else if ($status == 'confirmed' || $status == 'Confirmed') {
            $template = 'mk3d.booking::mail.reservation_confirmation';
            $mailSubject = 'Bevestiging reservering';
            $statusMessage = 'De reservering is bevestigd. We zien je graag verschijnen!';
        } else {
            $template = 'mk3d.booking::mail.reservation_confirmation';
            $mailSubject = 'Aanvraag reservering';
            $statusMessage = 'Bedankt voor de aanvraag! We nemen zo snel mogelijk contact met je op.';
        }

        // Send the email (assuming you have a mail template set up)
        Mail::send($template, [
            'name' => $name, 
            'statusMessage' => $statusMessage,
            'reservation_details' => $reservationDetails
        ], 
        
        function($message) use ($email, $name, $mailSubject) {
            $message->to($email, $name);
            $message->subject($mailSubject);
            $message->bcc(Config::get('mail.from.address'), Config::get('mail.from.name'));
        });

    }


    //SET THE MESSAGES FOR THE EMAIL
    protected function setMessages($reservation, $formData, $recurring)
    {
        $messages = [];
        
        if (isset($reservation->location_id) && $reservation->location_id != $formData['location']) {
            // Locatie aangepast
            array_push($messages, 'De locatie van de reservering is aangepast.');
        }        

        if (!$recurring && $reservation->reservation_start_date != $formData['reservation_start_date']) {
            // Startdatum aangepast
            array_push($messages, 'De startdatum van de reservering is aangepast.');
            
        }        
        if (!$recurring && $reservation->reservation_end_date != $formData['reservation_end_date']) {
            // Einddatum aangepast
            array_push($messages, 'De einddatum van de reservering is aangepast.');
        }

        if ($reservation->reservation_start_time != $formData['reservation_start_time']) {
            // Starttijd aangepast
            array_push($messages, 'De starttijd van de reservering is aangepast.');
        }
    
        if ($reservation->reservation_end_time != $formData['reservation_end_time']) {
            // Eindtijd aangepast
            array_push($messages, 'De eindtijd van de reservering is aangepast.');
        }
    
        if ($reservation->status != $formData['status'] && ($formData['status'] == 'cancelled' || $formData['status'] == 'Cancelled')) {
            // Status aangepast
            array_push($messages, 'De reservering is geannuleerd');
        }  

        if ($reservation->status != $formData['status'] && ($formData['status'] == 'confirmed' || $formData['status'] == 'Confirmed')) {
            // Status aangepast
            array_push($messages, 'De reservering is akkoord!');
        }     

        return $messages;
    }




    //RECURRING METHODS
    public function onDeleteRecurring()
    {
        $recurringGroupId = post('recurring_group_id');

        if (!$recurringGroupId) {
            Flash::error('Invalid recurring group ID.');
            return Redirect::back();
        }

        $reservations = Reservation::where('recurring_group_id', $recurringGroupId)->get();

        // Check if the update_customer field is true and send the email if so
        $updateCustomer = isset($formdata['update_customer']) && $formdata['update_customer'];
        if ($updateCustomer) {
            $this->sendCancellationEmail($reservations);        
        }
       

        Reservation::where('recurring_group_id', $recurringGroupId)->delete();
        Flash::success('All recurring reservations have been deleted.');

        return Redirect::to(Backend::url('mk3d/booking/reservations'));
    }    

    // *** CALENDAR METHODS *** //

    //Set calendar CMS page and menu
    public function calendar()
    {
        $this->pageTitle = 'Calendar';
        BackendMenu::setContext('Mk3d.Booking', 'booking', 'calendar');       

    }


    //Function to get all reservations in JSON for the calendar
    public function getReservations()
    {

        $reservations = Reservation::all();

        $events = $reservations->map(function($reservation) {
            
            $startDate = date_format($reservation->reservation_start_date, "Y-m-d");	
            $endDate = date_format($reservation->reservation_end_date, "Y-m-d");
            $startTime = date_format($reservation->reservation_start_time, "H:i");	
            $endTime = date_format($reservation->reservation_end_time, "H:i");


            $location = Location::find($reservation->location_id);

            $color = $location->color;
            
            if($reservation->status == 'Pending' OR $reservation->status  == 'pending'){
                $color = '#b5b5b5';
            } elseif ($reservation->status == 'cancelled' OR $reservation->status == 'Cancelled'){ 
                $color = '#dc3545';
            }


            $startDateTime = $startDate . 'T' . $startTime;
            $endDateTime = $endDate . 'T' . $endTime;

            return [
                'title' => $reservation->customer_name . ' - ' . $reservation->status,
                'start' => $startDateTime,
                'end' => $endDateTime,
                'description' => 'Reservation for ' . $reservation->customer_name,
                'color' => $color,
                'url' => url('adminde/mk3d/booking/reservations/update/' . $reservation->id)
            ];
            
        });

        return Response::json($events); 

     }
    

}
