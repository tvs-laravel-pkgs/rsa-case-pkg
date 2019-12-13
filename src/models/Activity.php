<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'activities';
	protected $fillable = [
		'crm_activity_id',
		'case_id',
		'asp_status_id',
		'asp_id',
		'status_id',
		'service_description',
		'cancel_reason',
		'km_during_breakdown',
		'amount',
		'payment_mode_id',
		'remarks',
		'drop_location_type_id',
		'drop_dealer_id',
		'drop_location',
		'drop_location_lat',
		'drop_location_long',
		'asp_accepted_rejected',
		'reject_cancel_reason',
		'asp_reached_datetime',
		'asp_start_location',
		'asp_end_location',
		'asp_bd_google_km',
		'bd_dealer_google_km',
		'return_google_km',
		'asp_bd_return_empty_km',
		'bd_dealer_km',
		'return_km',
		'total_travel_google_km',
		'total_travel_km',
		'service_charges',
		'membership_charges',
		'toll_charges',
		'green_tax_charges',
		'border_charges',
		'paid_to_id',
		'payment_receipt_no',
		'amount_collected',
		'amount_refused',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];

	public function case () {
		return $this->belongsTo('App\Case', 'case_id');
	}

	public function aspStatus() {
		return $this->belongsTo('App\AspStatus', 'asp_status_id');
	}

	public function asp() {
		return $this->belongsTo('App\Asp', 'asp_id');
	}

	public function status() {
		return $this->belongsTo('App\ActivityStatus', 'status_id');
	}

	public function paymentMode() {
		return $this->belongsTo('App\Entity', 'payment_mode_id');
	}

	public function dropLocationType() {
		return $this->belongsTo('App\Entity', 'drop_location_type_id');
	}

	public function dropDealer() {
		return $this->belongsTo('App\Dealer', 'drop_dealer_id');
	}

	public function paidTo() {
		return $this->belongsTo('App\Config', 'paid_to_id');
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

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

}
