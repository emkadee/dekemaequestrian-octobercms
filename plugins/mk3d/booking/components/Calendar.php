<?php namespace Mk3d\Booking\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Controller;
use Mk3d\Booking\Models\Reservation;
use Mk3d\Booking\Models\Location;
use Log;
use Input;
use Validator;
use ValidationException;
use AjaxException;
use Mail;
use Config;
use Redirect;
use Flash;
use Carbon\Carbon;

class Calendar extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Calendar Component',
            'description' => 'No description provided yet...'
        ];
    }


    public function defineProperties()
    {
        return [
            'location_id' => [
                'title' => 'Location ID',
                'description' => 'The ID of the location to display reservations for',
                'type' => 'integer',
                'default' => '{{ :location_id }}'
            ],
        ];
    }

    public $matchReservationsWithTimeSlots;
    public $location_id;
    public $date;
    public $location_name;



    public function onRun()
    {
        $this->page['matchReservationsWithTimeSlots'] = $this->matchReservationsWithTimeSlots();
        $this->page['location_id'] = $this->property('location_id');
        $this->page['date'] = input('date', date('Y-m-d'));  
        
        // Fetch the location name
        $location = Location::find($this->property('location_id'));

        if ($location) {
            Log::info('Location : ' . $location->name);
            $this->page['location_name'] = $location->name;
            $this->page->title = $location->name . ' | Direct reserveren'; 
        }
        
    }

    public function reservations()
    {
        // Get the location ID from the URL
        $locationId = $this->param('location_id');
        Log::info('Location ID: ' . $locationId);

        $date = input('date', date('Y-m-d'));

        // Query reservations for the specific location
        $reservations = Reservation::with('location')
            ->where('location_id', $locationId)
            ->whereDate('reservation_start_date', $date)
            ->get();

        Log::info('Reservations: ' . $reservations->toJson());

        return $reservations;
    }

    public function locations(){
        
        $locationId = $this->property('location_id');
        Log::info('Location ID: ' . $locationId);

        $reservations = Reservation::with('location')
            ->where('location_id', $locationId)
            ->get();

        $locationSelected = Location::find($locationId);

        if (!$locationSelected) {
            Log::error('Location not found.');
            return [];
        }

        $locations = [];

        $openingTime = strtotime($locationSelected->opening_time);
        $closingTime = strtotime($locationSelected->closing_time);


        // Adjust closing time if it is 00:00:00 to be the next day
        if (date('H:i:s', $closingTime) == '00:00:00') {
            $closingTime = strtotime('+1 day', $closingTime);
        }
    
        Log::info('Opening Time: ' . $locationSelected->opening_time . ' (' . $openingTime . ')');
        Log::info('Closing Time: ' . $locationSelected->closing_time . ' (' . $closingTime . ')');
    
        for ($time = $openingTime; $time <= $closingTime; $time = strtotime('+30 minutes', $time)) {
            $locations[] = date("H:i", $time);
        }
    
        Log::info('Location Times: ' . json_encode($locations));
    
        return $locations;        
        
    } 
    

    public function onFilterTimeslots()
    {
        Log::info('onFilterTimeslots has run');

        $date = post('date', date('Y-m-d'));
        $locationId = post('location_id');

        Log::info('Filtering timeslots for date: ' . $date . ' and location ID: ' . $locationId);

        $this->page['date'] = $date;
        $this->page['location_id'] = $locationId;
        // Fetch the location name
        $location = Location::find($this->property('location_id'));
        $this->page['location_name'] = $location->name;   
        $this->page['matchReservationsWithTimeSlots'] = $this->matchReservationsWithTimeSlots($date, $locationId);
        
    }

    public function onSubmitReservation() {      
        
        $formData = post();        

        Log::info('Request data: ' . json_encode(post()));

        $rules = [
            'name'     => 'required',
            'email'    => 'required|email',
            'duration'     => 'required',
            'time'     => 'required',
            'location_id' => 'required',
            'date'     => 'required|date'
        ];

        if (isset($formData['recurring']) && $formData['recurring'] == 'true') {
            $rules['recurring_interval'] = 'required|integer|min:1';
            $rules['recurring_end_date'] = 'required|date|after:date';
        }

        $validation = Validator::make($formData, $rules);

        if ($validation->fails()) {
            Log::info('Validation failed: ' . json_encode($validation->errors()));
            throw new ValidationException($validation);
        }        


        $customer_name = Input::get('name');
        $customer_email = Input::get('email');
        $location_id = Input::get('location_id');
        $time = Input::get('time');
        $duration = Input::get('duration');
        $date = Input::get('date'); // Get the date from the form data
        $endDate = Input::get('recurring_end_date');
        $interval = Input::get('recurring_interval');

        $location = Location::find($location_id);
        $openingTime = strtotime($location->opening_time);
        $closingTime = strtotime($location->closing_time);
        $locationName = $location->name;
        // Adjust closing time if it is 00:00:00 to be the next day
        if (date('H:i:s', $closingTime) == '00:00:00') {
            $closingTime = strtotime('+1 day', $closingTime);
        }

        // Calculate the number of reservations
        $startDate = new \DateTime($date);
        $endDate = isset($endDate) ? new \DateTime($endDate) : null;
        $interval = isset($interval) ? (int)$interval : 1;
        $recurringCount = 1;

        if ($endDate) {
            $weeksDiff = $startDate->diff($endDate)->days / 7;
            $recurringCount = (int)floor($weeksDiff / $interval) + 1;
        }
    
        $startTime = strtotime($time);
        $endTime = strtotime('+' . ($duration * 60) . ' minutes', $startTime);

        // Check if the reservation end time is after the closing time
        if ($endTime > $closingTime) {
            throw new AjaxException('De ' . strtolower($locationName) . ' sluit om ' . date('H:i', $closingTime) . '. Kies een eerdere tijd.');
        }

        // Create an empty array to store all reservation dates for the email
        $reservationDates = [];      

        // Generate a unique recurring group ID if not provided
        $recurringGroupId = $formData['recurring_group_id'] ?? uniqid('recurring_', true);

        $message = "";
        $overlappingReservations = [];

        // Check for overlapping reservations
        for ($i = 0; $i < $recurringCount; $i++) {
            $reservationDate = clone $startDate;
            $reservationDate->modify("+" . ($i * $interval) . " weeks");

            /* $startTime = strtotime($reservationDate->format('Y-m-d') . ' ' . $time); */
            $endTime = $startTime + ($formData['duration'] * 3600);   
            
            Log::info('Start Time: ' . $startTime);
            Log::info('End Time: ' . $endTime);
            
            // Check for overlapping reservations
           /*  $overlappingReservation = Reservation::where('location_id', $formData['location_id'])
            ->whereDate('reservation_start_date', $reservationDate) // Ensure the date is considered
            ->where(function($query) use ($startTime, $endTime) {
                $startTimePlusOneMinute = date('H:i', strtotime('+1 minute', $startTime));
                $endTimeMinusOneMinute = date('H:i', strtotime('-1 minute', $endTime));
                
                
                $query->where(function($query) use ($startTimePlusOneMinute, $endTimeMinusOneMinute) {
                    //New       20:00 - 21:00
                    //Existing  20:30 - 21:30
                    //21:01 > 20:30 = true
                    //21:30 < 21:01 = true

                    //New       22:30 - 23:30
                    //Existing  23:00 - 0:00


                    $query->where('reservation_start_time', '>', $startTimePlusOneMinute)
                          ->where('reservation_end_time', '>', $endTimeMinusOneMinute);
    
                });
            })
            ->get();  */

            $overlappingReservation = Reservation::where('location_id', $formData['location_id'])
            ->whereDate('reservation_start_date', $reservationDate) // Ensure the date is considered
            ->where(function($query) use ($startTime, $endTime) {
                $startTimePlusOneMinute = date('H:i:s', strtotime('+1 minute', $startTime));
                $endTimeMinusOneMinute = date('H:i:s', strtotime('-1 minute', $endTime));
        
                $query->where(function($query) use ($startTime, $endTime, $startTimePlusOneMinute, $endTimeMinusOneMinute) {
                    $query->where(function($query) use ($startTime, $endTime) {
                        $query->where('reservation_start_time', '<', date('H:i:s', $endTime))
                              ->where('reservation_end_time', '>', date('H:i:s', $startTime));
                    })
                    ->orWhere(function($query) use ($startTime, $endTime) {
                        $query->where('reservation_start_time', '<', date('H:i:s', $endTime))
                              ->where('reservation_end_time', '>', date('H:i:s', $startTime));
                    })
                    ->orWhere(function($query) use ($startTime, $endTime) {
                        $query->where('reservation_start_time', '>=', date('H:i:s', $startTime))
                              ->where('reservation_end_time', '<=', date('H:i:s', $endTime));
                    });
                });
            })
            ->get();

            if ($overlappingReservation->count() > 0) {
                Log::info('Overlapping reservation found : ' . json_encode($overlappingReservation));
                array_push($overlappingReservations, $overlappingReservation);
            }
 
        }
            
        Log::info('Overlapping reservations found : ' . json_encode($overlappingReservations));

        if (count($overlappingReservations) > 0) {
            
            foreach ($overlappingReservations as $overlappingReservationGroup) {
                foreach ($overlappingReservationGroup as $overlappingReservation) {
                    $startDate = $this->dutchDate($overlappingReservation['reservation_start_date'], 'l d F Y');


                    $startTime = date('H:i', strtotime($overlappingReservation['reservation_start_time']));
                    $endTime = date('H:i', strtotime($overlappingReservation['reservation_end_time']));

                    $message .= 'De '. strtolower($locationName) .' is op ' . $startDate . ' van ' . $startTime . ' tot ' . $endTime . ' al bezet<br />';
                }
            }
            Log::info('Overlapping reservation found');
            throw new AjaxException($message);
        
        } else {
            // Check for overlapping reservations
            for ($i = 0; $i < $recurringCount; $i++) {
                $reservationDate = clone $startDate;
                $reservationDate->modify("+" . ($i * $interval) . " weeks");

                $startTime = strtotime($reservationDate->format('Y-m-d') . ' ' . $time);
                $endTime = $startTime + ($formData['duration'] * 3600); 

                $reservation = new Reservation();
                $reservation->customer_name = $formData['name'];
                $reservation->customer_email = $formData['email'];
                $reservation->location_id = $formData['location_id'];
                $reservation->location = Location::find($formData['location_id']);
                $reservation->reservation_start_date = $reservationDate->format('Y-m-d');
                $reservation->reservation_end_date = $reservationDate->format('Y-m-d');
                $reservation->reservation_start_time = date('H:i', $startTime);
                $reservation->reservation_end_time = date('H:i', $endTime);
                $reservation->recurring_group_id = $recurringGroupId; // Set the recurring group ID
                $reservation->cancellation_token = uniqid('cancel_', true);   // Set the cancellation token
                $reservation->status = 'Pending';
                $reservation->save();

                //Set the messages for each reservation for the update email
                $messages = [];
                array_push($messages, 'Je krijgt nog een bevestiging van deze reservering als deze akkoord is.');                
                $statusMessage = 'In aanvraag';
                
                
                $location = Location::find($formData['location_id']);
                $locationName = $location->name;  
                // Add the reservation date to the list for the email
                $reservationDetails[] = [
                    'date' => $reservationDate->format('Y-m-d'),
                    'time' => date('H:i', $startTime),
                    'end_time' => date('H:i', $endTime),
                    'location' => $locationName,
                    'messages' => $messages,
                    'status_message' => $statusMessage,
                    'cancellation_link' => url('/cancellation/' . $reservation->cancellation_token), // Include the cancellation token
                ];
            }            
        }
        
        
        // Send the reservation confirmation email with the cancellation link and the location name
        $mailSubject = 'Update van jouw reservering';        
        $this->sendReservationConfirmationEmail($formData['email'], $formData['name'], $mailSubject, $reservationDetails);
        
        Log::info('No overlapping reservation found');
        // Proceed with reservation logic if no overlap
        // Return a success response

        Flash::success('Jouw resevering is geplaatst. Je hebt een bevestiging per mail ontvangen.');
        return \Redirect::to('/calendar/' . $formData['location_id'] . '?date=' . $formData['date']);
    
    }

/*     //SEND RESERVATION CONFIRMATION EMAIL
    protected function sendReservationConfirmationEmail($email, $name, $locationName, $$reservationDetails)
    {
        // Send the email (assuming you have a mail template set up)
        Mail::send('mk3d.booking::mail.reservation_confirmation', ['name' => $name, 'location' => $locationName, 'reservation_details' => $$reservationDetails], function($message) use ($email, $name) {
            $message->to($email, $name);
            $message->subject('Reservation Confirmation');
        });

    } */

    protected function sendReservationConfirmationEmail($email, $name, $mailSubject, $reservationDetails)
    {
        // Debugging: Log the data to ensure it's correct
        Log::info('Reservation details: ' . json_encode($reservationDetails));

        // Send the email (assuming you have a mail template set up)
        Mail::send('mk3d.booking::mail.reservation_confirmation', [
            'name' => $name, 
            'reservation_details' => $reservationDetails
        ], 
        
        function($message) use ($email, $name, $mailSubject) {
            $message->to($email, $name);
            $message->subject($mailSubject);
        });

    }

    public function dutchDate($date_time, $format)
    {
        $carbon = new Carbon($date_time);
        $carbon->locale('nl_NL');

        // Format the date using Carbon's localization
        $formattedDate = $carbon->translatedFormat($format);
        return $formattedDate;
    }








    public function generateTimeSlots($date)
    {
        $locationId = $this->property('location_id');
        Log::info('Location ID: ' . $locationId);
        

        $locationSelected = Location::find($locationId);

        if($locationSelected->public_available == false){
            return [];
        }

        if (!$locationSelected) {
            Log::error('Location not found.');
            return [];
        }

        $timeSlotsGenerated = [
            'morning' => [],
            'daytime' => [],
            'evening' => []
        ];

        $openingTime = strtotime($locationSelected->opening_time);
        $closingTime = strtotime($locationSelected->closing_time);

        // Adjust closing time if it is 00:00:00 to be the next day
        if (date('H:i:s', $closingTime) == '00:00:00') {
            $closingTime = strtotime('+1 day', $closingTime);
        }

        // Get the current date and time
        $now = new \DateTime();

        for ($time = $openingTime; $time < $closingTime; $time = strtotime('+30 minutes', $time)) {
            $formattedTime = date("H:i", $time);
            $hour = date("H", $time);

            // Check if the time slot is in the past
            $slotDateTime = new \DateTime($date . ' ' . $formattedTime);
            $dateTimePassed = $slotDateTime < $now;

            if ($hour < 12) {
                $timeSlotsGenerated['morning'][] = [
                    'time' => $formattedTime,
                    'reserved' => false,
                    'passed' => $dateTimePassed
                ];
            } elseif ($hour < 18) {
                $timeSlotsGenerated['daytime'][] = [
                    'time' => $formattedTime,
                    'reserved' => false,
                    'passed' => $dateTimePassed
                ];
            } else {
                $timeSlotsGenerated['evening'][] = [
                    'time' => $formattedTime,
                    'reserved' => false,
                    'passed' => $dateTimePassed
                ];
            }
        }

        Log::info('Time Slots Generated: ' . json_encode($timeSlotsGenerated));

        return $timeSlotsGenerated;
    }

    public function matchReservationsWithTimeSlots($date = null, $locationId = null)
    {
        $reservations = $this->reservations($date, $locationId);
        $matchReservationsWithTimeSlots = $this->generateTimeSlots($date);

        foreach ($matchReservationsWithTimeSlots as $period => &$slots) {
            foreach ($slots as &$slot) {
                $slotStartTime = strtotime($slot['time']);
                $slotEndTime = strtotime('+30 minutes', $slotStartTime);

                foreach ($reservations as $reservation) {
                    $reservationStartTime = strtotime($reservation->reservation_start_time);
                    $reservationEndTime = strtotime($reservation->reservation_end_time);

                    // Adjust reservation end time if it spans into the next day
                    if ($reservationEndTime <= $reservationStartTime) {
                        $reservationEndTime = strtotime('+1 day', $reservationEndTime);
                    }

                    // Check if the reservation overlaps with the time slot
                    if ($reservationStartTime < $slotEndTime && $reservationEndTime > $slotStartTime) {
                        $slot['reserved'] = true;
                        break;
                    }
                }
            }
        }

        Log::info('Matched Time Slots: ' . json_encode($matchReservationsWithTimeSlots));

        return $matchReservationsWithTimeSlots;
    }



}
