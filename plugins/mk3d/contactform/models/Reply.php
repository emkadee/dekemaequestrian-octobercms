<?php namespace Mk3d\ContactForm\Models;

use Model;

/**
 * Answer Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Reply extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'mk3d_contactform_replies';

    /**
     * @var array rules for validation
     */
    
     public $rules = [
        'title' => 'required|string|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string'
    ];

    protected $fillable = ['title','subject','message'];
}
