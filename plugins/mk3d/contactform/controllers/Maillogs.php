<?php namespace Mk3d\Contactform\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Carbon\Carbon;
use Mk3d\ContactForm\Models\Maillog;

/**
 * Maillog Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Maillogs extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['mk3d.contactform.maillog'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Mk3d.ContactForm', 'contactform', 'maillog');
    }

    public function onDeleteOldMaillogs()
    {

        $cutoffDate = Carbon::now()->subDays(30);
        $oldMaillogs = Maillog::where('created_at', '<', $cutoffDate)->get();

        foreach ($oldMaillogs as $oldMaillog) {
            $oldMaillog->delete();
        }

        return $this->listRefresh();
    }
  
}
