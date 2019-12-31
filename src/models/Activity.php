<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\RsaCasePkg\ActivityDetail;
use App\Asp;
use App\AspServiceType;
use App\Attachment;
use App\Company;
use App\Config;
use Auth;
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

	public function financeStatus() {
		return $this->belongsTo('Abs\RsaCasePkg\ActivityFinanceStatus', 'finance_status_id');
	}

	public function details() {
		return $this->hasMany('Abs\RsaCasePkg\ActivityDetail', 'activity_id');
	}

	public function detail($key_id) {
		return $this->details()->where('key_id', $key_id)->first();
	}

	public function activityDetail() {
		return $this->hasOne('Abs\RsaCasePkg\ActivityDetail', 'activity_id');
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

	public static function getFormData($id = NULL, $for_deffer_activity) {
		$data = [];

		$data['activity'] = $activity = self::findOrFail($id);
		$data['service_types'] = Asp::where('user_id', Auth::id())
			->join('asp_service_types', 'asp_service_types.asp_id', '=', 'asps.id')
			->join('service_types', 'service_types.id', '=', 'asp_service_types.service_type_id')
			->select('service_types.name', 'asp_service_types.service_type_id as id')
			->get();
		if ($for_deffer_activity) {
			$asp_km_travelled = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 154]])->first();
			if (!$asp_km_travelled) {
				return $data = [
					'success' => false,
					'error' => 'Activity ASP KM not found',
				];
			}
			$asp_other_charge = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 156]])->first();
			if (!$asp_other_charge) {
				return $data = [
					'success' => false,
					'error' => 'Activity ASP other charges not found',
				];
			}

			$asp_collected_charges = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 155]])->first();
			if (!$asp_collected_charges) {
				return $data = [
					'success' => false,
					'error' => 'Activity ASP collected charges not found',
				];
			}

			$data['asp_collected_charges'] = $asp_collected_charges->value;
			$data['asp_other_charge'] = $asp_other_charge->value;
			$data['asp_km_travelled'] = $asp_km_travelled->value;
		}

		$cc_km_travelled = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 280]])->first();
		if (!$cc_km_travelled) {
			return $data = [
				'success' => false,
				'error' => 'Activity CC KM not found',
			];
		}
		$cc_other_charge = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 282]])->first();
		if (!$cc_other_charge) {
			return $data = [
				'success' => false,
				'error' => 'Activity CC other charges not found',
			];
		}
		$cc_collected_charges = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 281]])->first();
		if (!$cc_collected_charges) {
			return $data = [
				'success' => false,
				'error' => 'Activity CC collected charges not found',
			];
		}

		$data['cc_collected_charges'] = $cc_collected_charges->value;
		$data['cc_other_charge'] = $cc_other_charge->value;
		$data['cc_km_travelled'] = $cc_km_travelled->value;

		$range_limit = "";
		$aspServiceType = AspServiceType::where('asp_id', $activity->asp_id)
			->where('service_type_id', $activity->service_type_id)
			->first();
		if ($aspServiceType) {
			$range_limit = $aspServiceType->range_limit;
		}
		$data['range_limit'] = $range_limit;
		$data['km_attachment'] = Attachment::where('entity_type', '=', config('constants.entity_types.ASP_KM_ATTACHMENT'))
			->where('entity_id', '=', $activity->id)
			->select('id', 'attachment_file_name')
			->get();
		$data['other_attachment'] = Attachment::where('entity_type', '=', config('constants.entity_types.ASP_OTHER_ATTACHMENT'))
			->where('entity_id', '=', $activity->id)
			->select('id', 'attachment_file_name')
			->get();
		$data['success'] = true;
		return $data;
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
			$payout_amount = $km_charge;
			$net_amount = $payout_amount + $not_collected - $collected;
			$invoice_amount = $net_amount;

			$cc_service_type = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 153,
			]);
			$cc_service_type->value = $this->serviceType->name;
			$cc_service_type->save();

			$asp_service_type = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 157,
			]);
			$asp_service_type->value = $this->serviceType->name;
			$asp_service_type->save();

			$bo_service_type = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 161,
			]);
			$bo_service_type->value = $this->serviceType->name;
			$bo_service_type->save();

			$asp_km_travelled = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 154,
			]);
			$asp_km_travelled->value = $total_km;
			$asp_km_travelled->save();

			$bo_km_travelled = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 158,
			]);
			$bo_km_travelled->value = $total_km;
			$bo_km_travelled->save();

			$asp_collected = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 155,
			]);
			$asp_collected->value = $collected;
			$asp_collected->save();

			$bo_collected = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 159,
			]);
			$bo_collected->value = $collected;
			$bo_collected->save();

			$asp_not_collected = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 156,
			]);
			$asp_not_collected->value = $not_collected;
			$asp_not_collected->save();

			$bo_not_collected = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 160,
			]);
			$bo_not_collected->value = $not_collected;
			$bo_not_collected->save();

			$cc_km_charge = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 150,
			]);
			$cc_km_charge->value = $km_charge;
			$cc_km_charge->save();

			// $payout_amount = $km_charge;
			// $net_amount = $payout_amount - $collected;
			// $invoice_amount = $net_amount + $not_collected;

			$cc_po_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 170,
			]);
			$cc_po_amount->value = $payout_amount;
			$cc_po_amount->save();

			$cc_net_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 174,
			]);
			$cc_net_amount->value = $net_amount;
			$cc_net_amount->save();

			$cc_invoice_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 180,
			]);
			$cc_invoice_amount->value = $invoice_amount;
			$cc_invoice_amount->save();

			$asp_po_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 171,
			]);
			$asp_po_amount->value = $payout_amount;
			$asp_po_amount->save();

			$bo_po_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 172,
			]);
			$bo_po_amount->value = $payout_amount;
			$bo_po_amount->save();

			$asp_net_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 175,
			]);
			$asp_net_amount->value = $net_amount;
			$asp_net_amount->save();

			$bo_net_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 176,
			]);
			$bo_net_amount->value = $net_amount;
			$bo_net_amount->save();

			$asp_invoice_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 181,
			]);
			$asp_invoice_amount->value = $invoice_amount;
			$asp_invoice_amount->save();

			$bo_invoice_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 182,
			]);
			$bo_invoice_amount->value = $invoice_amount;
			$bo_invoice_amount->save();

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
