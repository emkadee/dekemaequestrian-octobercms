<?php namespace Mk3d\ContactForm\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Mk3d\ContactForm\Models\Answer;

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
        
        $form->addFields([
            'answers' => [
                'label' => 'Answers',
                'type' => 'partial',
                'path' => '$/mk3d/contactform/controllers/messages/select_and_show_answers.php',
                'span' => 'full',
                'context' => ['update'],
            ]
        ]);
    }

    public function update($recordId = null)
    {
        $this->vars['answerTitles'] = Answer::pluck('title', 'id')->toArray();

        // Call the parent update method
        return $this->asExtension('FormController')->update($recordId);
    }

    public function onGetAnswerMessage()
    {
        $answerId = post('answer_id');
        $answer = Answer::find($answerId);
        return ['message' => $answer ? $answer->message : ''];
    }
    

}
