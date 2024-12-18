<?php namespace Mk3d\ContactForm\Controllers;

use BackendMenu;
use Backend;
use Backend\Classes\Controller;
use Mk3d\ContactForm\Models\Reply;
use Mk3d\ContactForm\Models\Message;
use Mk3d\ContactForm\Models\Maillog;
use Backend\FormWidgets\RichEditor;
use Backend\Classes\FormField;
use Log;
use Mail;
use Session;
use Markdown;
use Carbon\Carbon;
use Redirect;
use Config;
use AjaxException;
use Flash;
/**
 * Messages Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Messages extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];    


    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $filterConfig = 'config_filter.yaml';


    public $requiredPermissions = ['mk3d.contactform.messages'];
/* 
    public function onRun()
    {
        // Define the scope for the status filter
        $scope = 'New'; // Replace 'default_status' with your desired default status

        // Apply the status filter using the static method
        Message::applyStatusFilter(Message::query(), $scope);
    }
 */


    public static function getStatusOptions()
    {
        $statusOptions = Message::getStatusOptions();        
        return $statusOptions + ['0' => 'All'];        
    }

    public static function applyStatusFilter($query, $scope)
    {
        if($scope->value == 0){
            return $query;
        } else {
            return $query->where('status', '=', $scope->value);
        }
    }


    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Mk3d.ContactForm', 'contactform', 'messages');
    }


    public function update($recordId = null, $context = null)
    {
        $this->vars['replyTitles'] = Reply::pluck('title', 'id')->toArray();
        $this->vars['messageId'] = $recordId; // Pass the message ID to the view
        $this->vars['replySubject'] = 'Re: ' . Message::find($recordId)->subject;

        // Create a FormField instance for the RichEditor
        $field = new FormField('reply_message', 'Reply message');
        $field->value = ''; // Set the initial value if needed

        // Create a RichEditor instance
        $richEditor = new RichEditor($this, $field);
        $richEditor->bindToController();

        // Pass the RichEditor instance to the view
        $this->vars['rich_editor'] = $richEditor;

        // Call the parent update method
        return $this->asExtension('FormController')->update($recordId);

    }
    

    public function onGetReplyMessage()
    {
        $replyId = post('reply_id');
        $messageId = post('message_id'); // Assuming you pass the message ID from the frontend
        $reply = Reply::find($replyId);
        $message = Message::find($messageId);

        Log::info('Reply ID: '.$replyId);
        Log::info('Message ID: '.$messageId);

        if ($reply && $message) {
            $replyMessage = str_replace('{{name}}', $message->name, $reply->message);
            $replyMessage = str_replace('{{message}}', $message->message, $replyMessage);
        } else {
            $replyMessage = '';
        }

        return ['message' => $replyMessage];
    }

    public function onSendEmail()
    {
        Log::info('onSendEmail called');
        $messageId = input('message_id');
        $mailContent = input('reply_message');
        $mailSubject = input('email_subject');

        $message = Message::find($messageId);

        Log::info('Message ID: '.$messageId);	
        Log::info('Mail content: '.$mailSubject);

        if ($message) {
            Log::info('Message found');
            $email = $message->email;
            $name = $message->name;

            // Use the Mail facade to send the email
            $this->sendReplyEmail(
                $name,
                $email, 
                $mailSubject, 
                $mailContent
            );
            if ($message->maillog_id) {
                $maillog = Maillog::find($message->maillog_id);
                $maillog_id = $maillog->maillog_id;

                Log::info ('Maillog not found, add content to maillog with id ' . $maillog_id);

                $mailContentComplete = $maillog->message . '<hr>' . '> Reply send on : <b>' . date('Y-m-d H:i:s') . '</b>' . '<hr>' . $mailContent;
                $maillog->message = $mailContentComplete;
                $maillog->save();

            } else {
                
                $maillog = new Maillog();                         

                $maillog->receiver = $name;
                $maillog->receiver_email = $email;
                $maillog->subject = $mailSubject;
                $maillog->message = '<hr>' . '> Reply send on : <b>' . date('Y-m-d H:i:s') . '</b>' . '<hr>' . $mailContent;

                $maillog->save();
                
                Log::info ('Maillog not found, created a new one with id ' . $maillog->id);
            }            

            $message->maillog_id = $maillog->id;
            $message->status = 'replied';
            $message->save(); 


            // Return a JSON response with the redirect URL
            Log::info('Redirect to messages');
            Flash::success('Email had been send!');
            return Redirect::to(Backend::url('mk3d/contactform/messages'));

        }
        // Return a JSON response with the redirect URL
        
        return Redirect::to(Backend::url('mk3d/contactform/messages/update/'.$messageId));




    }

    public function onDeleteOldMails()
    {

        $cutoffDate = Carbon::now()->subDays(30);
        $oldMessages = Message::where('created_at', '<', $cutoffDate)->get();

        foreach ($oldMessages as $oldMessage) {
            $oldMessage->delete();
        }

        return $this->listRefresh();
    }

    public function onDeleteOldMailsAndLogs()
    {

        $cutoffDate = Carbon::now()->subDays(30);
        $oldMessages = Message::where('created_at', '<', $cutoffDate)->get();

        foreach ($oldMessages as $oldMessage) {
            $oldMessageMaillog = Maillog::find($oldMessage->maillog_id);
            $oldMessageMaillog->delete();
            $oldMessage->delete();
        }

        return $this->listRefresh();
    }


    //SEND RESERVATION CONFIRMATION EMAIL
    protected function sendReplyEmail($name, $email, $mailSubject, $mailContent)
    {
        // Ensure the content is not null
        if ($mailContent !== null) {

            // Ensure proper Markdown structure
            $mailContent = $this->ensureProperMarkdownStructure($mailContent);

            // Parse the Markdown content to HTML
            $htmlContent = new Markdown();           
            $htmlContent = Markdown::parse($mailContent);

            Log::info('Parsed HTML Content:', ['html' => $htmlContent]);

            // Send the email (assuming you have a mail template set up)
            Mail::send('mk3d.contactform::mail.reply', [
                'subject' => $mailSubject,
                'name' => $name,
                'content' => $htmlContent,
                'email' => $email,
            ],             
            function($message) use ($email, $name, $mailSubject) {
                $message->to($email, $name);
                $message->subject($mailSubject);
            });


        } else {
            // Log an error if content is null
            error_log('Email content is null.');
        }
    }

    private function ensureProperMarkdownStructure($content)
    {
        // Ensure each HTML node is on its own line
        return preg_replace('/>(\s*)</', ">\n<", $content);
    }     
}