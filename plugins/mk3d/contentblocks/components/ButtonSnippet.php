<?php namespace Mk3d\Contentblocks\Components;

use Cms\Classes\ComponentBase;
use Mk3d\Contentblocks\Models\Block as ContentBlockModel;
use RainLab\Pages\Classes\Page as StaticPage;
use Cms\Classes\Theme;

class ButtonSnippet extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'MK3D - Button Snippet',
            'description' => 'Displays a button to link to a static page or a custom url',
        ];
    }

    public function defineProperties()
    {
        return [        
            'static_page_url' => [
                'title'       => 'Static Page URL',
                'description' => 'Select a static page to link to',
                'type'        => 'dropdown',
                'options'     => $this->getStaticPageOptions()
            ],
            'url' => [
                'title'       => 'Page URL',
                'description' => 'Type a url to link to',
                'type'        => 'string'
            ],
            'link_text' => [
                'title'       => 'Link text',
                'description' => 'Text for the link',
                'type'        => 'string',
                'default'     => 'Lees meer'
            ],
            
        ];
    }

   public function getStaticPageOptions()
    {
        $theme = Theme::getActiveTheme();
        if (!$theme) {
            Log::error('No active theme found.');
            return [];
        }

        $pages = StaticPage::listInTheme($theme, true);
        $options = [];
        foreach ($pages as $page) {
            $options[$page->url] = $page->title;
        }

        return $options;
    }

    public function onRun()
    {
        $this->page['staticPageUrl'] = $this->property('static_page_url');
        $this->page['url'] = $this->property('url');
        $this->page['linkText'] = $this->property('link_text');
    }

}