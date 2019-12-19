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
		'number',
		'data_src_id',
		'asp_id',
		'case_id',
		'service_type_id',
		'status_id',
		'asp_accepted_cc_details',
		'reason_for_asp_rejected_cc_details',
		'asp_po_accepted',
		'asp_po_rejected_reason_id',
		'asp_activity_status_id',
		'asp_activity_rejected_reason_id',
		'invoice_id',
		'activity_status_id',
		'description',
		'remarks',
		'deduction_reason',
		'bo_comments',
	];

	public function finance() {
		return $this->belongsTo('Abs\RsaCasePkg\ActivityFinanceStatus', 'finance_status_id');
	}

	public function details() {
		return $this->hasMany('Abs\RsaCasePkg\ActivityDetail', 'activity_id');
	}

	public function detail($key_id) {
		return $this->details()->where('key_id', $key_id)->first();
	}

	public function asp() {
		return $this->belongsTo('App\Asp', 'asp_id');
	}

	public function case () {
		return $this->belongsTo('Abs\RsaCasePkg\RsaCase', 'case_id');
	}

	public function serviceType() {
		return $this->belongsTo('App\ServiceType', 'service_type_id');
	}

	public function aspStatus() {
		return $this->belongsTo('Abs\RsaCasePkg\ActivityAspStatus', 'asp_status_id');
	}

	public function aspActivityRejectedReason() {
		return $this->belongsTo('Abs\RsaCasePkg\AspActivityRejectedReason', 'asp_activity_rejected_reason_id');
	}

	public function aspPoRejectedReason() {
		return $this->belongsTo('Abs\RsaCasePkg\AspPoRejectedReason', 'asp_po_rejected_reason_id');
	}

	public function invoice() {
		return $this->belongsTo('App\Invoice', 'invoice_id');
	}

	public function status() {
		return $this->belongsTo('Abs\RsaCasePkg\ActivityPortalStatus', 'status_id');
	}

	public function activityStatus() {
		return $this->belongsTo('Abs\RsaCasePkg\ActivityStatus', 'activity_status_id');
	}

	public function dropLocationType() {
		return $this->belongsTo('App\Entity', 'drop_location_type_id');
	}

	public function paymentMode() {
		return $this->belongsTo('App\Entity', 'payment_mode_id');
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

	public function calculatePayoutAmount($data_src) {
		if ($this->financeStatus->po_eligibility_type_id == 342) {
			//No Payout
			return [
				'success' => true,
				'error' => 'Not Eligible for Payout',
			];
		}

		if ($data_src == 'CC') {
			$response = getKMPrices($this->serviceType, $this->asp);
			if (!$response['success']) {
				return [
					'success' => false,
					'error' => $response['error'],
				];
			}

			$total_km = $this->detail(280)->value; //cc_total_km
			$collected = $this->detail(281)->value; //cc_colleced_amount
			$not_collected = $this->detail(282)->value; //cc_not_collected_amount

			$km_charge = $this->calculateKMCharge($response['asp_service_price'], $total_km);
			$payout_amount = $km_charge + $not_collected - $collected;

			$cc_km_charge = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 150,
			]);
			$cc_km_charge->value = $km_charge;
			$cc_km_charge->save();

			$cc_po_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 170,
			]);
			$cc_po_amount->value = $payout_amount;
			$cc_po_amount->save();
			return [
				'success' => true,
			];

		}

	}

	private function calculateKMCharge($price, $km) {
		if ($this->financeStatus->po_eligibility_type_id == 341) {
			// Empty Return Payout
			$below_range_price = $km == 0 ? 0 : $price->empty_return_range_price;
		} else {
			$below_range_price = $km == 0 ? 0 : $price->below_range_price;
		}

		$above_range_price = ($km > $price->range_limit) ? ($km - $price->range_limit) * $price->above_range_price : 0;
		$km_charge = $below_range_price + $above_range_price;

		if ($price->adjustment_type == 1) {
			//'Percentage'
			$adjustment = ($km_charge * $price->adjustment) / 100;
			$km_charge = $km_charge + $adjustment;
		} else {
			$adjustment = $price->adjustment;
			$km_charge = $km_charge + $adjustment;
		}
		return $km_charge;
	}

}
