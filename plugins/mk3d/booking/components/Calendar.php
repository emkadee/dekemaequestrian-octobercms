<?php namespace Mk3d\Booking\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Controller;
use Mk3d\Booking\Models\Reservation;
use Mk3d\Booking\Models\Location;
use Log;
use Input;
use Validator;
use ValidationException;
use Mail;
use Config;



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
            ->whereDate('reservation_date', $date)
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
        
        $data = post();

        $rules = [
            'name'     => 'required',
            'email'    => 'required|email',
            'duration'     => 'required',
            'time'     => 'required',
            'location_id' => 'required',
            'date'     => 'required|date'
        ];

        if (isset($data['recurring']) && $data['recurring'] == 'true') {
            $rules['recurring_interval'] = 'required|integer|min:1';
            $rules['recurring_end_date'] = 'required|date|after:date';
        }


        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // Generate a unique recurring group ID if not provided
        $recurringGroupId = $data['recurring_group_id'] ?? uniqid('recurring_', true);

        


        $customer_name = Input::get('name');
        $customer_email = Input::get('email');
        $location_id = Input::get('location_id');
        $time = Input::get('time');
        $duration = Input::get('duration');
        $date = Input::get('date'); // Get the date from the form data
        $endDate = Input::get('recurring_end_date');
        $interval = Input::get('recurring_interval');


        // Calculate the number of reservations
        $startDate = new \DateTime($date);
        $endDate = isset($endDate) ? new \DateTime($endDate) : null;
        $interval = isset($interval) ? (int)$interval : 1;
        $recurringCount = 1;

        if ($endDate) {
            $weeksDiff = $startDate->diff($endDate)->days / 7;
            $recurringCount = (int)floor($weeksDiff / $interval) + 1;
        }
    
        Log::info('Customer Name: ' . $customer_name);
        Log::info('Customer Email: ' . $customer_email);
        Log::info('Location ID: ' . $location_id);
        Log::info('Time: ' . $time);
        Log::info('Duration: ' . $duration);
        Log::info('Date: ' . $date); // Log the date
        Log::info('Startdate: ' . $startDate->format('Y-m-d H:i:s'));
        Log::info('Enddate: ' . ($endDate ? $endDate->format('Y-m-d H:i:s') : 'N/A'));
        Log::info('Interval: ' . $interval);
        Log::info('recurringCount: ' . $recurringCount);

    
        $startTime = strtotime($time);
        $endTime = strtotime('+' . ($duration * 60) . ' minutes', $startTime);
        $endTime = strtotime('-1 minute', $endTime);

        // Store all reservation dates for the email
        $reservationDates = [];


        // Create reservations
        for ($i = 0; $i < $recurringCount; $i++) {
            $reservationDate = clone $startDate;
            $reservationDate->modify("+" . ($i * $interval) . " weeks");

            $startTime = strtotime($reservationDate->format('Y-m-d') . ' ' . $time);
            $endTime = $startTime + ($data['duration'] * 3600);               
            
            // Check for overlapping reservations
            $overlappingReservations = Reservation::where('location_id', $data['location_id'])
                ->whereDate('reservation_date', $reservationDate) // Ensure the date is considered
                ->where(function($query) use ($startTime, $endTime) {
                    $query->whereBetween('reservation_start_time', [date('Y-m-d H:i', $startTime), date('Y-m-d H:i', $endTime)])
                        ->orWhereBetween('reservation_end_time', [date('Y-m-d H:i', $startTime), date('Y-m-d H:i', $endTime)])
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

            // Generate a unique cancellation token
            $cancellationToken = uniqid('cancel_', true);    

            $reservation = new Reservation();
            $reservation->customer_name = $data['name'];
            $reservation->customer_email = $data['email'];
            $reservation->location_id = $data['location_id'];
            $reservation->location = Location::find($data['location_id']);
            $reservation->reservation_date = $reservationDate->format('Y-m-d');
            $reservation->reservation_start_time = date('H:i', $startTime);
            $reservation->reservation_end_time = date('H:i', $endTime);
            $reservation->recurring_group_id = $recurringGroupId; // Set the recurring group ID
            $reservation->cancellation_token = $cancellationToken; // Set the cancellation token

            $reservation->save();

            
            // Add the reservation date to the list for the email
            $reservationDates[] = [
                'date' => $reservationDate->format('Y-m-d'),
                'time' => date('H:i', $startTime),
                'end_time' => date('H:i', $endTime),
                'cancellation_link' => url('/cancellation/' . $cancellationToken), // Include the cancellation token
            ];
        }

        // Send the reservation confirmation email with the cancellation link
        $this->sendReservationConfirmationEmail($data['email'], $data['name'], $reservationDates);
        
        \Flash::success('Reservation successfully made!');
        return \Redirect::to('/calendar/' . $data['location_id'] . '?date=' . $data['date']);
    }

    //SEND RESERVATION CONFIRMATION EMAIL
    protected function sendReservationConfirmationEmail($email, $name, $reservationDates)
    {
        // Send the email (assuming you have a mail template set up)
        Mail::send('mk3d.booking::mail.reservation_confirmation', ['name' => $name, 'reservation_dates' => $reservationDates], function($message) use ($email, $name) {
            $message->to($email, $name);
            $message->subject('Reservation Confirmation');
        });
    }








    public function generateTimeSlots($date)
    {
        $locationId = $this->property('location_id');
        Log::info('Location ID: ' . $locationId);

        $locationSelected = Location::find($locationId);

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

        Log::info('Opening Time: ' . $locationSelected->opening_time . ' (' . $openingTime . ')');
        Log::info('Closing Time: ' . $locationSelected->closing_time . ' (' . $closingTime . ')');

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
        $matchReservationsWithTimeSlots = $this->generateTimeSlots($date,);

        foreach ($matchReservationsWithTimeSlots as $period => &$slots) {
            foreach ($slots as &$slot) {
                $slotStartTime = strtotime($slot['time']);
                $slotEndTime = strtotime('+30 minutes', $slotStartTime);

                Log::info('Checking Slot: '. $date . ' - ' . $slot['time'] . ' (' . $slotStartTime . ' - ' . $slotEndTime . ')');

                foreach ($reservations as $reservation) {
                    $reservationStartTime = strtotime($reservation->reservation_start_time);
                    $reservationEndTime = strtotime($reservation->reservation_end_time);

                    

                    Log::info('Reservation: ' . $reservation->reservation_start_time . ' (' . $reservationStartTime . ') - ' . $reservation->reservation_end_time . ' (' . $reservationEndTime . ')');

                    // Check if the reservation overlaps with the time slot
                    if ($reservationStartTime < $slotEndTime && $reservationEndTime > $slotStartTime) {
                        $slot['reserved'] = true;
                        Log::info('Slot ' . $slot['time'] . ' is reserved.');
                        break;
                    }
                }
            }
        }

        Log::info('Matched Time Slots: ' . json_encode($matchReservationsWithTimeSlots));

        return $matchReservationsWithTimeSlots;
    }



}
