<?php namespace Mk3d\ContactForm;

use Backend;
use System\Classes\PluginBase;

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
            'name' => 'ContactForm',
            'description' => 'No description provided yet...',
            'author' => 'Mk3d',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        //
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        //
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {

        return [
            'Mk3d\ContactForm\Components\FormSnippet' => 'myForm',
        ];
    }

    public function registerPageSnippets()
    {
        return [
            'Mk3d\ContactForm\Components\FormSnippet' => 'myForm',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {


        return [
            'mk3d.contactform.some_permission' => [
                'tab' => 'ContactForm',
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
            'contactform' => [
                'label' => 'ContactForm',
                'url' => Backend::url('mk3d/contactform/messages'),
                'icon' => 'icon-leaf',
                'permissions' => ['mk3d.contactform.*'],
                'order' => 500,
                'sideMenu' => [
                    'messsages' => [
                        'label' => 'Messages',
                        'icon' => 'icon-copy',
                        'url' => Backend::url('mk3d/contactform/messages'),
                        'permissions' => ['mk3d.booking.*'],
                    ],
                    'answers' => [
                        'label' => 'Answers',
                        'icon' => 'icon-copy',
                        'url' => Backend::url('mk3d/contactform/answers'),
                        'permissions' => ['mk3d.booking.*'],
                    ]

                ]
            ],
        ];
    }
}
