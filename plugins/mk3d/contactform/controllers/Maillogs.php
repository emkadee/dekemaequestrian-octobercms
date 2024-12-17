<?php namespace Mk3d\Contactform\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Carbon\Carbon;
use Mk3d\ContactForm\Models\Maillog;
use Mk3d\ContactForm\Models\Message;
use Log;

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
            $message = Message::where('maillog_id', $oldMaillog->id)->first();
            $message->delete();
            $oldMaillog->delete();
        }

        return $this->listRefresh();
    }

    public function update($recordId = null, $context = null)
    {
        Log::info('Maillog onUpdate called with recordId: ' . $recordId);
        $message = Message::where('maillog_id', $recordId)->first(); // Use first() instead of get() to get a single record
        if ($message) {
            Log::info('Maillog onUpdate found message with id: ' . $message->id);
            $this->vars['message_id'] = $message->id; // Pass the message_id to the view
        } else {
            $this->vars['message_id'] = null; // Handle the case where no message is found
        }
        return $this->asExtension('FormController')->update($recordId);
    }
  
}
