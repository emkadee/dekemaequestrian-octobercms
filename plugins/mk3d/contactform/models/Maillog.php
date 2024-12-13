<?php namespace Mk3d\Contactform\Models;

use Model;

/**
 * Maillog Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Maillog extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'mk3d_contactform_maillogs';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    protected $fillable = ['message'];
}
