<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model {
	use SoftDeletes;
	use SeederTrait;

	protected $table = 'activity_logs';
	protected $fillable = [
		'activity_id',
	];

	// Relationships --------------------------------------------------------------

	public function activity() {
		return $this->belongsTo('Abs\RsaCasePkg\Activity', 'activity_id');
	}

	public function importedBy() {
		return $this->belongsTo('App\User', 'imported_by_id');
	}

	public function aspDataFilledBy() {
		return $this->belongsTo('App\User', 'asp_data_filled_by_id');
	}

	public function defferedToCcBy() {
		return $this->belongsTo('App\User', 'deferred_to_cc_by_id');
	}

	public function ccClarifiedBy() {
		return $this->belongsTo('App\User', 'cc_clarified_by_id');
	}

	public function boDefferedBy() {
		return $this->belongsTo('App\User', 'bo_deffered_by_id');
	}

	public function boApprovedBy() {
		return $this->belongsTo('App\User', 'bo_approved_by_id');
	}

	public function l2DefferedBy() {
		return $this->belongsTo('App\User', 'l2_deffered_by_id');
	}

	public function l2ApprovedBy() {
		return $this->belongsTo('App\User', 'l2_approved_by_id');
	}

	public function l3DefferedBy() {
		return $this->belongsTo('App\User', 'l3_deffered_by_id');
	}

	public function l3ApprovedBy() {
		return $this->belongsTo('App\User', 'l3_approved_by_id');
	}

	public function l4DefferedBy() {
		return $this->belongsTo('App\User', 'l4_deffered_by_id');
	}

	public function l4ApprovedBy() {
		return $this->belongsTo('App\User', 'l4_approved_by_id');
	}

	public function invoiceGeneratedBy() {
		return $this->belongsTo('App\User', 'invoice_generated_by_id');
	}

	public function axaptaGeneratedBy() {
		return $this->belongsTo('App\User', 'axapta_generated_by_id');
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