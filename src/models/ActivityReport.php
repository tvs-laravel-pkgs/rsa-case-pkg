<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\RsaCasePkg\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityReport extends Model {
	use SoftDeletes;
	use SeederTrait;

	protected $fillable = [
		'activity_id',
	];

	protected $table = 'activity_reports';
	public $timestamps = true;

	// Relationships --------------------------------------------------------------

	public function activity(): BelongsTo {
		return $this->belongsTo(Activity::class, 'activity_id');
	}
}
