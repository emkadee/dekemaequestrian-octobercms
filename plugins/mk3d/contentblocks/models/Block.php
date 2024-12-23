<?php namespace Mk3d\Contentblocks\Models;

use Model;

/**
 * Model
 */
class Block extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var bool timestamps are disabled.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string table in the database used by the model.
     */
    public $table = 'mk3d_contentblocks_items';

    protected $fillable = [
        'title', 
        'description', 
        'image', 
        'type'
    ];

    protected $appends = ['type_label'];

    /**
     * @var array rules for validation.
     */
    public $rules = [
    ];

    // Define the getTypeLabelAttribute method as an accessor
    public function getTypeLabelAttribute()
    {
        $types = [
            'content' => 'Content',
            'review' => 'Review'
        ];

        return $types[$this->type] ?? 'Content';
    }

}
