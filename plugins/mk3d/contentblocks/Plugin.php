<?php namespace Mk3d\Contentblocks;

use System\Classes\PluginBase;
use Backend;

/**
 * Plugin class
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Contentblocks',
            'description' => 'MK3D - Provides content blocks, buttons and image sliders as snippets.',
            'author'      => 'Mk3d',
            'icon'        => 'icon-cube'
        ];
    }
    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        
    }

    public function registerPermissions()
    {
        return [
            'mk3d.contentblocks.access_blocks' => [
                'tab'   => 'Contentblocks',
                'label' => 'Manage content blocks'
            ],
        ];
    }
    /**
     * registerNavigation used by the backend.
     */
    

    public function registerNavigation()
    {
        return [
            'contentblocks' => [
                'label'       => 'Contentblocks',
                'url'         => Backend::url('mk3d/contentblocks/blocks'),
                'icon'        => 'icon-cube',
                'permissions' => ['mk3d.contentblocks.*'],
                'order'       => 600,

                'sideMenu' => [
                    'Content' => [
                        'label'       => 'Blocks',
                        'icon'        => 'icon-cube',
                        'url'         => Backend::url('mk3d/contentblocks/blocks'),
                        'permissions' => ['mk3d.contentblocks.*'],
                    ],
                    'Reviews' => [
                        'label'       => 'Reviews',
                        'icon'        => 'icon-comment',
                        'url'         => Backend::url('mk3d/contentblocks/reviews'),
                        'permissions' => ['mk3d.contentblocks.*'],
                    ],
                ],
            ],
        ];
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'Mk3d\Contentblocks\Components\BlockSnippet' => 'contentBlock',
            'Mk3d\Contentblocks\Components\ButtonSnippet' => 'buttonBlock',
            'Mk3d\Contentblocks\Components\ReviewSnippet' => 'reviewBlock',
            'Mk3d\Contentblocks\Components\SliderSnippet' => 'sliderBlock',
        ];
    }
    
    public function registerPageSnippets()
    {
        return [
            'Mk3d\Contentblocks\Components\BlockSnippet' => 'contentBlock',
            'Mk3d\Contentblocks\Components\ButtonSnippet' => 'buttonBlock',
            'Mk3d\Contentblocks\Components\ReviewSnippet' => 'reviewBlock',
            'Mk3d\Contentblocks\Components\SliderSnippet' => 'sliderBlock',
        ];
    }

   



}
