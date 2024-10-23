<?php namespace Mk3d\Contentblocks\Components;

use Cms\Classes\ComponentBase;
use Mk3d\Contentblocks\Models\Block as ContentBlockModel;
use RainLab\Pages\Classes\Page as StaticPage;
use Cms\Classes\Theme;
use Log;

class BlockSnippet extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'MK3D - Contentblock',
            'description' => 'Displays a block of content in different styles',
        ];
    }

    public function defineProperties()
    {
        return [
            'block_item_id' => [
                'title'       => 'Block Item',
                'description' => 'Select a block item to display',
                'type'        => 'dropdown',
                'options'     => $this->getBlockOptions()
            ],            
            'static_page_url' => [
                'title'       => 'Static Page URL',
                'description' => 'Select a static page to link to',
                'type'        => 'dropdown',
                'options'     => $this->getStaticPageOptions()
            ],
            'link_text' => [
                'title'       => 'Link text',
                'description' => 'Text for the link',
                'type'        => 'string',
                'default'     => 'Lees meer'
            ],
            'block_style' => [
                'title'       => 'Block style',
                'description' => 'Select a style for the block',
                'type'        => 'dropdown',
                'options' => [
                            'simple'    => 'Simple',
                            'textimage' => 'Text and image',
                            'card'      => 'Card'
                            ],
            ]
        ];
    }

    public function getBlockOptions()
    {
        $options = ContentBlockModel::all()->pluck('title', 'id')->toArray();
        #Log::info('Service Item Options: ', $options);
        return $options;
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
        #Log::info('Static Page Options: ', $options);
        return $options;
    }

    public function onRun()
    {
        $postId = $this->property('block_item_id'); 

        $this->page['contentBlock'] = $this->loadBlocks($postId);
        $this->page['staticPageUrl'] = $this->property('static_page_url');
        $this->page['linkText'] = $this->property('link_text');
        $this->page['blockStyle'] = $this->property('block_style');
    }

    public function loadBlocks($id)
    {
        if (!$id) {
            return null;
        }

        $block = ContentBlockModel::find($id);

        if (!$block) {
            Log::error("Block with ID $id not found.");
            return null;
        }
        #Log::info('Block Item Data: ' . json_encode($block->toArray()));


        return $block->toArray();
    }


}