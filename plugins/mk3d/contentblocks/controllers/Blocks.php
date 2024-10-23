<?php namespace Mk3d\Contentblocks\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;

class Blocks extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mk3d.Contentblocks', 'main-menu-content');
    }

}
