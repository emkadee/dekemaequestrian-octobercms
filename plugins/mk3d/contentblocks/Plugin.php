<?php namespace Mk3d\Contentblocks;

use System\Classes\PluginBase;

/**
 * Plugin class
 */
class Plugin extends PluginBase
{
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

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'Mk3d\Contentblocks\Components\BlockSnippet' => 'contentBlock',
        ];
    }
    
    public function registerPageSnippets()
    {
        return [
            'Mk3d\Contentblocks\Components\BlockSnippet' => 'contentBlock',
        ];
    }

}
