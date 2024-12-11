<?php namespace Mk3d\ContactForm\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Mk3d\ContactForm\Models\Reply;
use Mk3d\ContactForm\Models\Message;
use Log;
use Mail;
use Session;

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
    public $requiredPermissions = ['mk3d.contactform.messages'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Mk3d.ContactForm', 'contactform', 'messages');
    }

    public function formExtendFields($form)
    {
        
       /*  $form->addFields([
            'replies' => [
                'label' => 'Replies',
                'type' => 'partial',
                'path' => '$/mk3d/contactform/controllers/messages/select_and_show_replies.php',
                'span' => 'full',
                'context' => ['update'],
            ]
        ]); */
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
        $messageId = post('message_id');
        $mailContent = post('email_content');

        $message = Message::find($messageId);

        Log::info('Message ID: '.$messageId);	

        if ($message) {
            $email = $message->email;
            $name = $message->name;
            $mailSubject = 'Re: ' . $message->subject;


            // Use the Mail facade to send the email
            Mail::send('mk3d.contactform::mail.reply', ['content' => $mailContent], function($message) use ($email) {
                $message->to($email);
                $message->subject('Reply to your message');
            });


            /* // Use the Mail facade to send the email
            $this->sendReservationConfirmationEmail(
                $name,
                $email, 
                $mailSubject, 
                $mailContent
            );
 */

            $message->status = 'Reply sent';
            $message->save(); 

            // Set a flash message
            Session::flash('success', 'Email sent successfully!');

            redirect('backend/mk3d/contactform/messages');

        }

        return ['status' => 'error'];
    }

    // *** MAIL SENDING METHODS *** //
    //SEND RESERVATION CONFIRMATION EMAIL
    protected function sendReservationConfirmationEmail($name, $email, $mailSubject, $mailContent)
    {


        Mail::send('mk3d.contactform::mail.reply', [
            'mailContent' => $mailContent,
        ], 
        
            function($mailContent) use ($email, $name, $mailSubject) {
                $mailContent->to($email, $name);
                $mailContent->subject($mailSubject);
        });

    }
        

}
