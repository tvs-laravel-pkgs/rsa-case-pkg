<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyType extends Model {
	use SoftDeletes;
	protected $table = 'policy_types';
	protected $fillable = [
		'company_id',
		'name',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}
}
