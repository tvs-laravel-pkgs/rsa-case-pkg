<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityDetail extends Model {
	use SoftDeletes;
	protected $table = 'activity_details';
	protected $fillable = [
		'company_id',
		'activity_id',
		'key_id',
		'value',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}

	public function createdBy() {
		return $this->belongsTo('App\User', 'created_by_id');
	}

	public function updatedBy() {
		return $this->belongsTo('App\User', 'updated_by_id');
	}

	public function deletedBy() {
		return $this->belongsTo('App\User', 'deleted_by_id');
	}
}
