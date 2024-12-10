<?php namespace Mk3d\Contentblocks\Components;

use Cms\Classes\ComponentBase;
use Media\Classes\MediaLibrary;
use Log;
/**
 * SliderSnippet Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class SliderSnippet extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Slider Snippet Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {

        return [        
            'media_folder' => [
                'title'       => 'Media folder to display',
                'description' => 'Select a Media folders which contains the images to display',
                'type'        => 'dropdown',
                'options'     => $this->getAllMediaFolders()
            ]
            
        ];
    }

    public function onRun()
    {
        $mediaFolder = $this->property('media_folder'); 
        $this->page['sliderImages'] = $this->loadImages($mediaFolder);

        $this->addJs('/plugins/mk3d/contentblocks/assets/slick/slick.js');
        $this->addJs('/plugins/mk3d/contentblocks/assets/slick/slickloader.js');

    }



    public function getAllMediaFolders()
    {
        $mediaLibrary = MediaLibrary::instance();
        $contents = $mediaLibrary->listFolderContents('/sliders');

        // Filter the contents to only include directories and extract their names
        $folders = array_filter($contents, function($item) {
            return $item->type === 'folder';
        });

        $folderNames = array_map(function($folder) {
            return $folder->path;
        }, $folders);

        // Remove the '/sliders/' prefix from the folder names
        $folderNamesWithoutPrefix = array_map(function($folderName) {
            return str_replace('/sliders/', '', $folderName);
        }, $folderNames);

        // Return the folder names as options
        $options = array_combine($folderNames, $folderNamesWithoutPrefix);
        Log::info('Media Folders: ', $options);
        return $options;
    }

    public function loadImages($folder)
    {
        $mediaLibrary = MediaLibrary::instance();
        return $mediaLibrary->listFolderContents($folder);
    }

}
