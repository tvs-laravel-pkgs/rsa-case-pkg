<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsaCase extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'cases';
	protected $fillable = [
		'company_id',
		'type_id',
		'number',
		'date',
		'data_filled_date',
		'description',
		'status_id',
		'cancel_reason_id',
		'call_center_id',
		'client_id',
		'customer_name',
		'customer_contact_number',
		'contact_name',
		'contact_number',
		'vehicle_model_id',
		'vehicle_registration_number',
		'vin_no',
		'membership_type',
		'membership_number',
		'subject_id',
		'km_during_breakdown',
		'bd_lat',
		'bd_long',
		'bd_location',
		'bd_city',
		'bd_state',
		'bd_location_type_id',
		'bd_location_category_id',
		'submission_closing_date',
		'submission_closing_date_remarks',
		'csr',
		'pickup_dealer_name',
		'pickup_dealer_location',
		'pickup_dealer_state',
		'pickup_dealer_city',
		'pickup_location_pincode',
		'drop_dealer_name',
		'drop_dealer_location',
		'drop_dealer_state',
		'drop_dealer_city',
		'drop_location_pincode',
		'contact_name_at_pickup',
		'contact_number_at_pickup',
		'contact_name_at_drop',
		'contact_number_at_drop',
		'delivery_request_pickup_date',
		'delivery_request_pickup_time',
	];

	// Attributes --------------------------------------------------------------

	public function getDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}

	// Relationships --------------------------------------------------------------

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}

	public function callcenter() {
		return $this->belongsTo('App\CallCenter', 'call_center_id');
	}

	public function client() {
		return $this->belongsTo('App\Client', 'client_id');
	}

	public function eligibilityType() {
		return $this->belongsTo('App\Entity', 'eligibility_type_id');
	}

	public function vehicleModel() {
		return $this->belongsTo('App\VehicleModel', 'vehicle_model_id');
	}

	public function subject() {
		return $this->belongsTo('App\Subject', 'subject_id');
	}

	public function policy() {
		return $this->belongsTo('App\Policy', 'policy_id');
	}

	public function status() {
		return $this->belongsTo('Abs\RsaCasePkg\CaseStatus', 'status_id');
	}

	public function activities() {
		return $this->hasMany('Abs\RsaCasePkg\Activity', 'case_id');
	}

	public function city() {
		return $this->belongsTo('App\District', 'bd_city_id');
	}

	public function state() {
		return $this->belongsTo('App\State', 'bd_state_id');
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

	public function bdLocationType() {
		return $this->belongsTo('App\Config', 'bd_location_type_id');
	}

	public function bdLocationCategory() {
		return $this->belongsTo('App\Config', 'bd_location_category_id');
	}

	// Static Funcs --------------------------------------------------------------

	public static function searchMembershipTicket($r) {
		$key = $r->key;
		$list = self::select([
			'id',
			'number',
		])
			->where(function ($q) use ($key) {
				$q->where('number', 'like', '%' . $key . '%')
				;
			})
			->where('status_id', '!=', 3) //OTHER THAN CANCELLED
			->get();
		return response()->json($list);
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
