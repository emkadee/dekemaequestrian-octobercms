<?php namespace Mk3d\ContactForm\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Mk3d\ContactForm\Models\Reply;
use Mk3d\ContactForm\Models\Message;
use Mk3d\ContactForm\Models\Maillog;
use Log;
use Mail;
use Session;
use Markdown;
use Carbon\Carbon;
use Redirect;
use Config;
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


    public function update($recordId = null)
    {
        $this->vars['replyTitles'] = Reply::pluck('title', 'id')->toArray();
        $this->vars['messageId'] = $recordId; // Pass the message ID to the view

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
        $messageId = post('message_id');
        $mailContent = post('email_content');

        $message = Message::find($messageId);

        Log::info('Message ID: '.$messageId);	
        Log::info('Mail content: '.$mailContent);

        if ($message) {
            Log::info('Message found');
            $email = $message->email;
            $name = $message->name;
            $mailSubject = 'Re: ' . $message->subject;

            Log::info('Email: '.$email);
            Log::info('Name: '.$name);
            Log::info('Subject: '.$mailSubject);
            Log::info('Content: '.$mailContent);


            /* // Use the Mail facade to send the email
            Mail::send('mk3d.contactform::mail.reply', ['content' => $mailContent], function($message) use ($email) {
                $message->to($email);
                $message->subject('Reply to your message');
            }); */

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

            return Redirect::to('http://dev.dekemaequestrian.nl/adminde/mk3d/contactform/messages');

        }
        return Redirect::to('adminde/mk3d/contactform/messages');


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


    // *** MAIL SENDING METHODS *** //
    //SEND RESERVATION CONFIRMATION EMAIL
    protected function sendReplyEmailBackup($name, $email, $mailSubject, $mailContent)
    {
        // Ensure the content is not null
        if ($mailContent !== null) {

            // Ensure proper Markdown structure
            $mailContent = $this->ensureProperMarkdownStructure($mailContent);

            // Parse the Markdown content to HTML
            $htmlContent = new Markdown();           
            $htmlContent = Markdown::parse($mailContent);

            // Use the Mail facade to send the email
            Mail::send([], [], function($message) use ($email, $name, $mailSubject, $htmlContent) {
                $message->to($email, $name);
                $message->subject($mailSubject);
                $message->html($htmlContent); // Use the html method to set the HTML content
            });
        } else {
            // Log an error if content is null
            error_log('Email content is null.');
        }
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

    protected function sendReservationretertreConfirmationEmail($email, $name, $mailSubject, $reservationDetails)
    {
        // Debugging: Log the data to ensure it's correct
        Log::info('Reservation details: ' . json_encode($reservationDetails));

        // Send the email (assuming you have a mail template set up)
        Mail::send('mk3d.contactform::mail.reply', [
            'name' => $name, 
            'reservation_details' => $reservationDetails
        ], 
        
        function($message) use ($email, $name, $mailSubject) {
            $message->to($email, $name);
            $message->subject($mailSubject);
        });

    }

}