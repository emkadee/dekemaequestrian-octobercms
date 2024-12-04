<?php namespace Mk3d\Booking;

use Backend;
use System\Classes\PluginBase;
use View;
use Twig\Environment;
use Twig\TwigFilter;
use IntlDateFormatter;
use Event;
use Log;


/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Booking',
            'description' => 'No description provided yet...',
            'author' => 'Mk3d',
            'icon' => 'icon-leaf'
        ];
    }
    public function boot()
    {
        // Register the view namespace
/*         View::addNamespace('mk3d.booking', base_path() . '/plugins/mk3d/booking/views'); */
        View::share('site_name', 'Dekema Equestrian');


        
    }



    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        //
    }


 /* 
    public function registerMailTemplates()
    {
        return [
            'mk3d.booking::mail.reservation_confirmation' => 'reservation_confirmation',
            'mk3d.booking::mail.cancellation_confirmation' => 'cancellation_confirmation',
            'mk3d.booking::mail.reservation_update' => 'reservation_update',
        ];
    }
   
    public function registerMailLayouts()
    {
        return [
            'mk3d.booking::mail.booking_layout' => 'Booking Layout',
        ];
    } */


    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'Mk3d\Booking\Components\Calendar' => 'BookingCalendar',
            'Mk3d\Booking\Components\Cancellation' => 'BookingCancellation',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {  

        return [
            'mk3d.booking.some_permission' => [
                'tab' => 'Booking',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [
            'booking' => [
                'label' => 'Booking',
                'url' => Backend::url('mk3d/booking/reservations'),
                'icon' => 'icon-leaf',
                'permissions' => ['mk3d.booking.*'],
                'order' => 500,

                'sideMenu' => [
                    'reservations' => [
                        'label' => 'Reservations',
                        'icon' => 'icon-copy',
                        'url' => Backend::url('mk3d/booking/reservations'),
                        'permissions' => ['mk3d.booking.*'],
                    ],
                    'locations' => [
                        'label' => 'Locations',
                        'icon' => 'icon-copy',
                        'url' => Backend::url('mk3d/booking/locations'),
                        'permissions' => ['mk3d.booking.*'],
                    ],
                    'calendar' => [
                        'label' => 'Calendar',
                        'icon' => 'icon-copy',
                        'url' => Backend::url('mk3d/booking/reservations/calendar'),
                        'permissions' => ['mk3d.booking.*'],
                    ]
                ]
            ],
        ];
    }


}
