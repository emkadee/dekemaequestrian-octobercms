<?php namespace Mk3d\ContactForm\Components;

use Cms\Classes\ComponentBase;
use Mk3d\ContactForm\Models\Message;
use Input;
use Validator;
use Redirect;
use ValidationException;
use Session;
use Log;
use Mail;
use Config;
use Backend;


/**
 * FormSnippet Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class FormSnippet extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Form Snippet Component',
            'description' => 'Create a contactform..'
        ];
    }

    public function defineProperties()
    {
        return [
            'subject' => [
                'title' => 'Subject',
                'description' => 'The subject of the message',
                'default' => '',
                'type' => 'string',
            ],	
        ];
    }


    public function onRun() {   

        $this->page['subject'] = $this->property('subject');
        
    }
    

    public function onSubmit() {
        // Validate the form data
        $data = post();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ];

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // Create a new contact form message
        $newMessage = Message::create([
            'name' => Input::get('name'),
            'email' => Input::get('email'),
            'phone' => Input::get('phone'),
            'subject' => Input::get('subject'),
            'message' => Input::get('message'),
            'status' => Input::get('status', 'new'),
        ]);

        // Get the ID of the newly created message
        $messageId = $newMessage->id;
        $subject = 'New message: ' . $newMessage->subject;

        // Send an extra email to the configured 'from' address with a link to the corresponding message
        $fromAddress = Config::get('mail.from.address');
        $fromName = Config::get('mail.from.name');
        $messageLink = Backend::url('mk3d/contactform/messages/update/' . $messageId);

        Mail::send('mk3d.contactform::mail.admin_notification', ['messageLink' => $messageLink], function($mail) use ($fromAddress, $fromName, $subject) {
            $mail->to($fromAddress, $fromName);
            $mail->subject($subject);
        });


        // Redirect back to the form page
        return [
            'showform' => false,        
        ];
        
    }

}
