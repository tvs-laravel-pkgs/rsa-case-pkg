<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityRatecard extends Model {
	use SoftDeletes;
	use SeederTrait;

	protected $table = 'activity_ratecards';

	protected $fillable = [
		'activity_id',
		'range_limit',
		'below_range_price',
		'above_range_price',
		'waiting_charge_per_hour',
		'empty_return_range_price',
		'adjustment_type',
		'adjustment',
	];

	// Relationships --------------------------------------------------------------

	public function activity() {
		return $this->belongsTo('App\Activity', 'activity_id');
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