<?php namespace Mk3d\PagesMod;

use Backend;
use System\Classes\PluginBase;
use RainLab\Pages\Classes\Page as StaticPage;
use Event;


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
            'name' => 'PagesMod',
            'description' => 'MK3D - Changes the default 300px width to 100% in the placeholders from the Rainlab.Pages plugin',
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

    public function boot()
    {
        Event::listen('pages.object.save', function ($controller, $object, $type) {
            if ($type === 'page' && $object instanceof StaticPage) {
                $this->updateStylesInPlaceholders($object);
                $object->save();
            }
        });
    }

    protected function updateStylesInPlaceholders(StaticPage $page)
    {
        $placeholders = $page->placeholders;

        foreach ($placeholders as $key => $value) {
            if (is_string($value)) {
                $placeholders[$key] = str_replace('300px', '100%', $value);
            }
        }

        $page->placeholders = $placeholders;
    }

}
