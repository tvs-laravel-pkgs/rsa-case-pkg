<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;

class ActivityReportSync extends Model {
	protected $table = 'activity_report_sync';
	protected $fillable = [
		'activity_id',
		'sync_status',
		'errors',
	];

	public function activity() {
		return $this->belongsTo('Abs\RsaCasePkg\Activity', 'activity_id');
	}

}