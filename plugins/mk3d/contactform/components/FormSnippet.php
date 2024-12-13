<?php namespace Mk3d\ContactForm\Components;

use Cms\Classes\ComponentBase;
use Mk3d\ContactForm\Models\Message;
use Input;
use Validator;
use Redirect;
use ValidationException;
use Session;


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
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }


    public function onRun() {


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
        Message::create([
            'name' => Input::get('name'),
            'email' => Input::get('email'),
            'phone' => Input::get('phone'),
            'subject' => Input::get('subject'),
            'message' => Input::get('message'),
            'status' => Input::get('status', 'new'),
        ]);


        // Redirect back to the form page
        return [
            'showform' => false,        
        ];
        
    }

}
