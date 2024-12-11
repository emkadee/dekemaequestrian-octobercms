<?php namespace Mk3d\Contactform\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Mk3d\Contactform\Models\Reply;

/**
 * StandardReply Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class StandardReply extends FormWidgetBase
{
    protected $defaultAlias = 'contactform_standard_reply';

    public function widgetDetails()
    {
        return [
            'name' => 'Rich Editor',
            'description' => 'A rich text editor widget.'
        ];
    }

    public function init()
    {
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('standardreply');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
        

        $this->vars['messageId'] = $this->model->id;
        $this->vars['replyTitles'] = Reply::pluck('title', 'id')->toArray();


    }

    public function loadAssets()
    {
        $this->addCss('/modules/backend/formwidgets/richeditor/assets/css/richeditor.css');
        $this->addJs('/modules/backend/formwidgets/richeditor/assets/js/richeditor.js');
    }

    public function getSaveValue($value)
    {
        return $value;
    }
}
