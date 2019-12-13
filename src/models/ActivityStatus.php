<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityStatus extends Model {
	use SoftDeletes;
	protected $table = 'activity_statuses';
	protected $fillable = [
		'company_id',
		'name',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];
}
