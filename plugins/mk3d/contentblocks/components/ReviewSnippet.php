<?php namespace Mk3d\Contentblocks\Components;

use Cms\Classes\ComponentBase;
use Mk3d\Contentblocks\Models\Block as ContentBlockModel;
use Cms\Classes\Theme;
use Log;

class ReviewSnippet extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'MK3D - Review Snippet',
            'description' => 'Displays a review',
        ];
    }

    public function defineProperties()
    {
        return [
            'block_item_id' => [
                'title'       => 'Review Item',
                'description' => 'Select a review to display',
                'type'        => 'dropdown',
                'options'     => $this->getBlockOptions()
            ]
        ];
    }

    public function onRender()
    {
        
   }

    public function getBlockOptions()
    {
        $options = ContentBlockModel::where('type', 'review')->pluck('title', 'id')->toArray();
        Log::info('Service Item Options: ', $options);
        return $options;
    }

    public function onRun()
    {
        $postId = $this->property('block_item_id'); 
        $this->page['contentBlock'] = $this->loadBlocks($postId);
        $this->page['blockStyle'] = $this->property('block_style');
    }

    public function loadBlocks($id)
    {
        if (!$id) {
            return null;
        }

        $block = ContentBlockModel::find($id);

        Log::info ('Review Item ID: ' . $id);


        if (!$block) {
            Log::error("Review with ID $id not found.");
            return null;
        }


        return $block->toArray();        
    }


}