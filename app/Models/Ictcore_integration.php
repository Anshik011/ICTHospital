<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Ictcore_integration extends Model {
	protected $table = 'ictcore_integration';
	protected $fillable = ['ictcore_url','ictcore_user','ictcore_password','ictcore_account_id','method','type','type1'];

	/**
	 * Encrypt telecom API password at rest.
	 *
	 * @var array
	 */
	protected $casts = [
		'ictcore_password' => 'encrypted',
	];
}
