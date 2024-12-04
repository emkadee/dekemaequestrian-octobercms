<?php namespace Mk3d\Contentblocks\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;
use Mk3d\Contentblocks\Models\Block as Contentblocks;

/**
 * Reviews Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Reviews extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['mk3d.contentblocks.reviews'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Mk3d.Contentblocks', 'contentblocks', 'reviews');

        Contentblocks::extend(function($model) {
            $model->bindEvent('model.beforeSave', function() use ($model) {
                $model->type = 'review';
            });
        });
    }

    public function listExtendQuery($query)
    {
        $query->where('type', 'review');
    }
}
