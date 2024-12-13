<?php namespace Mk3d\ContactForm\Models;

use Model;

/**
 * Message Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Message extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'mk3d_contactform_messages';

    public $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string'
    ];

    protected $fillable = ['name','email','phone','subject','message', 'is_read', 'status'];

    // Static method to get status labels
    public static function getStatusLabels()
    {
        return [
            'new' => 'New',
            'replied' => 'Replied',
            'read' => 'Read'
        ];
    }

    // Accessor to get the status label
    public function getStatusLabelAttribute()
    {
        $statuses = self::getStatusLabels();
        return $statuses[$this->status] ?? $this->status;
    }


    // Static method to get status options including 'All'
    public static function getStatusOptions()
    {
        $statusOptions = self::getStatusLabels();
        return ['0' => 'All'] + $statusOptions;
    }


}
