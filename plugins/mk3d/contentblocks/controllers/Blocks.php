<?php namespace Mk3d\Contentblocks\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;
use Mk3d\Contentblocks\Models\Block as Contentblocks;

class Blocks extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

/*     public $requiredPermissions = ['mk3d.contentblocks.blocks']; */

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mk3d.Contentblocks', 'contentblocks', 'blocks');
        
        Contentblocks::extend(function($model) {
            $model->bindEvent('model.beforeSave', function() use ($model) {
                $model->type = 'content';
            });
        });
        
    }

    public function listExtendQuery($query)
    {
        $query->where('type', 'content');
    }

    

}
