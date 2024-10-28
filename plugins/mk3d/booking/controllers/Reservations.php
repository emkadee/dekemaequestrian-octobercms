<?php namespace Mk3d\Booking\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Mk3d\Booking\Models\Reservation;
use Mk3d\Booking\Models\Location;
use Flash;
use Redirect;
use Log;
use Mail;
use Response;

/**
 * Reservations Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Reservations extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class
    ];

    


    public $formConfig = 'config_form.yaml';
    public $relationConfig = 'config_relation.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['mk3d.booking.reservations'];

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

    public function formExtendFields($form)
    {
        Log::info('formExtendFields called');
        $form->addFields([
            'delete_recurring' => [
                'label' => 'Delete Recurring',
                'type' => 'partial',
                'path' => '$/mk3d/booking/controllers/reservations/_delete_recurring.htm',
                'span' => 'full',
                'context' => ['update'],
            ],
            'recurring_reservations' => [
                'label' => 'Recurring Reservations',
                'type' => 'partial',
                'path' => '$/mk3d/booking/controllers/reservations/_recurring_reservations.php',
                'span' => 'full',
                'context' => ['update'],
            ],
        ]);
    }

    public function formExtendModel($model)
    {
        Log::info('formExtendModel: recurring_group_id = ' . $model->recurring_group_id);
        $model->recurringReservations = $model->getRecurringReservations();
        Log::info('formExtendModel: recurringReservations count = ' . $model->recurringReservations->count());
    }

    public function formBeforeUpdate($model)
    {
        Log::info('formBeforeUpdate: recurring_group_id = ' . $model->recurring_group_id);
        $model->recurringReservations = $model->getRecurringReservations();
        Log::info('formBeforeUpdate: recurringReservations count = ' . $model->recurringReservations->count());
    }

    public function formBeforeCreate($model)
    {
        $model->recurringReservations = [];
    }

    public function onDelete()
    {
        $reservationId = $this->params[0];
        $reservation = Reservation::find($reservationId);

        if ($reservation) {
            $reservation->delete();
            Flash::success('Reservation deleted successfully.');
        } else {
            Flash::error('Reservation not found.');
        }

        return Redirect::to(Backend::url('mk3d/booking/reservations'));
    }

    public function onDeleteRecurring()
    {
        $recurringGroupId = post('recurring_group_id');

        if (!$recurringGroupId) {
            Flash::error('Invalid recurring group ID.');
            return Redirect::back();
        }

        $reservations = Reservation::where('recurring_group_id', $recurringGroupId)->get();
        $this->sendCancellationEmail($reservations);

        Reservation::where('recurring_group_id', $recurringGroupId)->delete();
        Flash::success('All recurring reservations have been deleted.');

        return Redirect::back();
    }

    protected function sendCancellationEmail($reservations)
    {
        if ($reservations->isEmpty()) {
            return;
        }

        $customerEmail = $reservations->first()->customer_email;
        $customerName = $reservations->first()->customer_name;

        $data = [
            'name' => $customerName,
            'reservations' => $reservations,
        ];

        Mail::send('mk3d.booking::mail.cancellation_confirmation', $data, function($message) use ($customerEmail, $customerName) {
            $message->to($customerEmail, $customerName);
            $message->subject('Reservation Cancellation Confirmation');
        });
    }
    public function calendar()
    {
        $this->pageTitle = 'Calendar';
        BackendMenu::setContext('Mk3d.Booking', 'booking', 'calendar');

       

    }

    public function getReservations()
    {
        Log::info('getReservations method called');

        $reservations = Reservation::all();

        $events = $reservations->map(function($reservation) {
            Log::info('Reservation Date: ' . $reservation->reservation_date);
            Log::info('Reservation Start Time: ' . $reservation->reservation_start_time);
            Log::info('Reservation End Time: ' . $reservation->reservation_end_time);

            
            $startDate = $reservation->reservation_date->format('Y-m-d');
            $endDate = $reservation->reservation_date->format('Y-m-d');

            $location = Location::find($reservation->location_id);

            $startDateTime = $startDate . 'T' . $reservation->reservation_start_time;
            $endDateTime = $endDate . 'T' . $reservation->reservation_end_time;

            Log::info('Start DateTime: ' . $startDateTime);
            Log::info('End DateTime: ' . $endDateTime);

            return [
                'title' => $location->name . ' - ' . $reservation->customer_name,
                'start' => $startDateTime,
                'end' => $endDateTime,
                'description' => 'Reservation for ' . $reservation->customer_name,
                'url' => url('adminde/mk3d/booking/reservations/update/' . $reservation->id)
            ];
        });

        return Response::json($events);
    }
    

}
