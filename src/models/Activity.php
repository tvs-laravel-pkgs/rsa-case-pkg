<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\ImportCronJobPkg\ImportCronJob;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityRatecard;
use Abs\RsaCasePkg\ActivityReport;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\ActivityWhatsappLog;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\CaseCancelledReason;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\RsaCase;
use App\Asp;
use App\AspAmendmentServiceType;
use App\AspServiceType;
use App\Attachment;
use App\CallCenter;
use App\Client;
use App\Company;
use App\Config;
use App\Invoices;
use App\Mail\ActivityWhatsappMailNoty;
use App\ServiceType;
use App\Subject;
use App\VehicleMake;
use App\VehicleModel;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use URL;
use Validator;

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
		'manual_uploading_remarks',
		'asp_resolve_comments',
		'cc_clarification',
		'deduction_reason',
		'bo_comments',
		'defer_reason',
		'is_exceptional_check',
		'exceptional_reason',
		'general_remarks',
		'not_eligible_moved_by_id',
		'not_eligible_moved_at',
		'not_eligible_reason',
		'towing_attachments_mandatory_by_id',
		'onhold_released_by_id',
		'onhold_released_at',
	];

	// Relationships --------------------------------------------------------------

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

	public function rateCard() {
		return $this->hasOne('Abs\RsaCasePkg\ActivityRatecard', 'activity_id');
	}

	public function log() {
		return $this->hasOne('Abs\RsaCasePkg\ActivityLog', 'activity_id');
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
		return $this->belongsTo('App\Invoices', 'invoice_id');
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

	public function towingAttachmentMandatoryBy() {
		return $this->belongsTo('App\User', 'towing_attachments_mandatory_by_id');
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

	public function activityTaxes() {
		return $this->belongsToMany('App\Tax', 'activity_tax')->withPivot('amount');
	}

	public function dataSource() {
		return $this->belongsTo('App\Config', 'data_src_id');
	}

	// Static Funcs --------------------------------------------------------------

	// public static function searchMembershipTicket($r) {
	// 	$key = $r->key;
	// 	$list = self::select([
	// 		'activities.id',
	// 		'cases.number',
	// 		'asps.asp_code',
	// 		'service_types.name as service_type',
	// 	])
	// 		->join('cases', 'cases.id', 'activities.case_id')
	// 		->join('asps', 'asps.id', 'activities.asp_id')
	// 		->join('service_types', 'service_types.id', 'activities.service_type_id')
	// 		->where(function ($q) use ($key) {
	// 			$q->where('cases.number', 'like', '%' . $key . '%')
	// 			;
	// 		})
	// 		->where('activities.activity_status_id', '!=', 4) //OTHER THAN CANCELLED
	// 		->get();
	// 	return response()->json($list);
	// }

	public static function getFormData($id = NULL, $for_deffer_activity) {
		$data = [];

		$data['activity'] = $activity = self::with([
			'case',
			'asp' => function ($q) {
				$q->select([
					'id',
					'asp_code',
					'name',
				]);
			},
			'serviceType',
			'financeStatus',
		])
			->findOrFail($id);

		$isMobile = 0; //WEB
		//MOBILE APP
		if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
			$isMobile = 1;
		}
		$data['service_types'] = self::getAspServiceTypesByAmendment($activity->asp_id, $activity->case->date, $isMobile);
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
		$data['border_charges'] = $activity->detail(316) ? $activity->detail(316)->value : 0.00;
		$data['green_tax_charges'] = $activity->detail(315) ? $activity->detail(315)->value : 0.00;
		$data['toll_charges'] = $activity->detail(314) ? $activity->detail(314)->value : 0.00;
		$data['eatable_item_charges'] = $activity->detail(313) ? $activity->detail(313)->value : 0.00;
		$data['fuel_charges'] = $activity->detail(319) ? $activity->detail(319)->value : 0.00;
		$data['waiting_time'] = $activity->detail(329) ? $activity->detail(329)->value : 0;

		$range_limit = "";
		$aspServiceType = self::getAspServiceRateCardByAmendment($activity->asp_id, $activity->case->date, $activity->service_type_id, $isMobile);
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
		$data['vehiclePickupAttach'] = Attachment::select([
			'id',
			'attachment_file_name',
		])
			->where('entity_type', config('constants.entity_types.VEHICLE_PICKUP_ATTACHMENT'))
			->where('entity_id', $activity->id)
			->first();
		$data['vehicleDropAttach'] = Attachment::select([
			'id',
			'attachment_file_name',
		])
			->where('entity_type', config('constants.entity_types.VEHICLE_DROP_ATTACHMENT'))
			->where('entity_id', $activity->id)
			->first();
		$data['inventoryJobSheetAttach'] = Attachment::select([
			'id',
			'attachment_file_name',
		])
			->where('entity_type', config('constants.entity_types.INVENTORY_JOB_SHEET_ATTACHMENT'))
			->where('entity_id', $activity->id)
			->first();
		$data['for_deffer_activity'] = $for_deffer_activity;
		$data['dropDealer'] = $activity->detail(294) ? $activity->detail(294)->value : '';
		$data['dropLocation'] = $activity->detail(295) ? $activity->detail(295)->value : '';
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

	public function saveActivityRatecard() {
		$isMobile = 0; //WEB
		//MOBILE APP
		if ($this->data_src_id == 260 || $this->data_src_id == 263) {
			$isMobile = 1;
		}

		//GET ELIGIBLE SERVICE TYPES BASED ON AMENDMENT
		$eligibleServiceTypes = self::getAspServiceTypesByAmendment($this->asp->id, $this->case->date, $isMobile);
		if ($eligibleServiceTypes->isEmpty()) {
			return [
				'success' => false,
				'error' => 'Service (' . $this->serviceType->name . ') is not enabled for the ASP (' . $this->asp->asp_code . ')',
			];
		} else {
			//CHECK IF THE GIVEN SERVICE TYPE EXISTS ON THE ELIGIBLE SERVICE TYPES
			$enteredServiceTypeExists = $eligibleServiceTypes->where('id', $this->serviceType->id)->first();
			if (!$enteredServiceTypeExists) {
				return [
					'success' => false,
					'error' => 'Service (' . $this->serviceType->name . ') is not enabled for the ASP (' . $this->asp->asp_code . ')',
				];
			}
		}

		$aspServiceTypeRateCard = self::getAspServiceRateCardByAmendment($this->asp->id, $this->case->date, $this->serviceType->id, $isMobile);
		if (!$aspServiceTypeRateCard) {
			return [
				'success' => false,
				'error' => 'Service (' . $this->serviceType->name . ') is not enabled for the ASP (' . $this->asp->asp_code . ')',
			];
		}

		$activityRateCard = ActivityRatecard::firstOrNew([
			'activity_id' => $this->id,
		]);
		if (!$activityRateCard->exists) {
			$activityRateCard->created_by_id = Auth::check() ? Auth::user()->id : 72;
		} else {
			$activityRateCard->updated_by_id = Auth::check() ? Auth::user()->id : 72;
		}
		$activityRateCard->range_limit = $aspServiceTypeRateCard->range_limit;
		$activityRateCard->below_range_price = $aspServiceTypeRateCard->below_range_price;
		$activityRateCard->above_range_price = $aspServiceTypeRateCard->above_range_price;
		$activityRateCard->waiting_charge_per_hour = $aspServiceTypeRateCard->waiting_charge_per_hour;
		$activityRateCard->empty_return_range_price = $aspServiceTypeRateCard->empty_return_range_price;
		$activityRateCard->adjustment_type = $aspServiceTypeRateCard->adjustment_type;
		$activityRateCard->adjustment = $aspServiceTypeRateCard->adjustment;
		$activityRateCard->below_range_price_margin = $aspServiceTypeRateCard->below_range_price_margin;
		$activityRateCard->above_range_price_margin = $aspServiceTypeRateCard->above_range_price_margin;
		$activityRateCard->fleet_count = $aspServiceTypeRateCard->fleet_count;
		$activityRateCard->is_mobile = $aspServiceTypeRateCard->is_mobile;
		$activityRateCard->save();

		return [
			'success' => true,
		];
	}

	public function saveActivityChargesDetails() {

		// GET CC DETAILS -----------------------------------------------------------

		$ccWaitingTime = ($this->detail(279) && !empty($this->detail(279)->value)) ? $this->detail(279)->value : 0;
		$ccServiceCharges = ($this->detail(302) && !empty($this->detail(302)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(302)->value)) : 0;
		$ccMembershipCharges = ($this->detail(303) && !empty($this->detail(303)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(303)->value)) : 0;
		$ccEatableItemsCharges = ($this->detail(304) && !empty($this->detail(304)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(304)->value)) : 0;
		$ccTollCharges = ($this->detail(305) && !empty($this->detail(305)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(305)->value)) : 0;
		$ccGreenTaxCharges = ($this->detail(306) && !empty($this->detail(306)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(306)->value)) : 0;
		$ccBorderCharges = ($this->detail(307) && !empty($this->detail(307)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(307)->value)) : 0;
		$ccOctroiCharges = ($this->detail(308) && !empty($this->detail(308)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(308)->value)) : 0;
		$ccExcessCharges = ($this->detail(309) && !empty($this->detail(309)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(309)->value)) : 0;
		$ccFuelCharges = ($this->detail(310) && !empty($this->detail(310)->value)) ? numberFormatToDecimalConversion(floatval($this->detail(310)->value)) : 0;

		// SAVE AGAINST ASP & BO ----------------------------------------------------

		$ccWaitingTime = $this->saveActivityDetail(279, $ccWaitingTime);
		$aspWaitingTime = $this->saveActivityDetail(329, $ccWaitingTime);
		$boWaitingTime = $this->saveActivityDetail(330, $ccWaitingTime);
		$ccServiceCharges = $this->saveActivityDetail(302, $ccServiceCharges);
		$aspServiceCharges = $this->saveActivityDetail(311, $ccServiceCharges);
		$boServiceCharges = $this->saveActivityDetail(320, $ccServiceCharges);
		$ccMembershipCharges = $this->saveActivityDetail(303, $ccMembershipCharges);
		$aspMembershipCharges = $this->saveActivityDetail(312, $ccMembershipCharges);
		$boMembershipCharges = $this->saveActivityDetail(321, $ccMembershipCharges);
		$ccEatableItemsCharges = $this->saveActivityDetail(304, $ccEatableItemsCharges);
		$aspEatableItemsCharges = $this->saveActivityDetail(313, $ccEatableItemsCharges);
		$boEatableItemsCharges = $this->saveActivityDetail(322, $ccEatableItemsCharges);
		$ccTollCharges = $this->saveActivityDetail(305, $ccTollCharges);
		$aspTollCharges = $this->saveActivityDetail(314, $ccTollCharges);
		$boTollCharges = $this->saveActivityDetail(323, $ccTollCharges);
		$ccGreenTaxCharges = $this->saveActivityDetail(306, $ccGreenTaxCharges);
		$aspGreenTaxCharges = $this->saveActivityDetail(315, $ccGreenTaxCharges);
		$boGreenTaxCharges = $this->saveActivityDetail(324, $ccGreenTaxCharges);
		$ccBorderCharges = $this->saveActivityDetail(307, $ccBorderCharges);
		$aspBorderCharges = $this->saveActivityDetail(316, $ccBorderCharges);
		$boBorderCharges = $this->saveActivityDetail(325, $ccBorderCharges);
		$ccOctroiCharges = $this->saveActivityDetail(308, $ccOctroiCharges);
		$aspOctroiCharges = $this->saveActivityDetail(317, $ccOctroiCharges);
		$boOctroiCharges = $this->saveActivityDetail(326, $ccOctroiCharges);
		$ccExcessCharges = $this->saveActivityDetail(309, $ccExcessCharges);
		$aspExcessCharges = $this->saveActivityDetail(318, $ccExcessCharges);
		$boExcessCharges = $this->saveActivityDetail(327, $ccExcessCharges);
		$ccFuelCharges = $this->saveActivityDetail(310, $ccFuelCharges);
		$aspFuelCharges = $this->saveActivityDetail(319, $ccFuelCharges);
		$boFuelCharges = $this->saveActivityDetail(328, $ccFuelCharges);
	}

	public function saveActivityDetail($keyId, $value) {
		$activityDetail = ActivityDetail::firstOrNew([
			'company_id' => 1,
			'activity_id' => $this->id,
			'key_id' => $keyId,
		]);
		$activityDetail->value = $value;
		$activityDetail->save();
		return $value;
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
			$response = getActivityKMPrices($this->serviceType, $this->asp, $this->data_src_id, $this->case->date);
			if (!$response['success']) {
				return [
					'success' => false,
					'error' => $response['error'],
				];
			}

			$saveActivityRatecardResponse = $this->saveActivityRatecard();
			if (!$saveActivityRatecardResponse['success']) {
				return [
					'success' => false,
					'error' => $saveActivityRatecardResponse['error'],
				];
			}

			$total_km = !empty($this->detail(280)->value) ? numberFormatToDecimalConversion(floatval($this->detail(280)->value)) : 0; //cc_total_km
			$collected = !empty($this->detail(281)->value) ? numberFormatToDecimalConversion(floatval($this->detail(281)->value)) : 0; //cc_colleced_amount
			$not_collected = !empty($this->detail(282)->value) ? numberFormatToDecimalConversion(floatval($this->detail(282)->value)) : 0; //cc_not_collected_amount

			//CALCULATE WAITING CHARGES AND STORE -----------------------------------------

			$ccWaitingTime = !empty($this->detail(279)->value) ? floatval($this->detail(279)->value) : 0;
			$ccWaitingCharge = 0;
			if (!empty($response['asp_service_price']->waiting_charge_per_hour) && !empty($ccWaitingTime)) {
				$ccWaitingCharge = numberFormatToDecimalConversion(floatval($ccWaitingTime / 60) * floatval($response['asp_service_price']->waiting_charge_per_hour));
			}
			$this->saveActivityDetail(331, $ccWaitingCharge); //CC WAITING CHARGE
			$this->saveActivityDetail(332, $ccWaitingCharge); //ASP WAITING CHARGE
			$this->saveActivityDetail(333, $ccWaitingCharge); //BO WAITING CHARGE

			//CALCULATE PAYOUT AMOUNT ----------------------------------------------------

			$km_charge = $this->calculateKMCharge($response['asp_service_price'], $total_km);
			$payout_amount = $km_charge;
			$net_amount = numberFormatToDecimalConversion(floatval(($payout_amount + $not_collected + $ccWaitingCharge) - $collected));
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

	public static function importFromExcel($job) {
		$job->status_id = 7201; //Inprogress
		$job->save();
		DB::beginTransaction();
		try {
			$response = ImportCronJob::getRecordsFromExcel($job, 'BT');
			$rows = $response['rows'];
			$header = $response['header'];
			$all_error_records = [];
			$updated_count = 0;

			if (!empty($rows)) {
				foreach ($rows as $k => $row) {
					DB::beginTransaction();
					$record = [];
					foreach ($header as $key => $column) {
						if (!$column) {
							continue;
						} else {
							$header_col = str_replace(' ', '_', strtolower($column));
							$record[$header_col] = $row[$key];
						}
					}
					$original_record = $record;
					$status = [];
					$status['errors'] = [];
					// dd($record);
					$save_eligible = true;

					$errorMessages = [
						'case_description.regex' => "Equal symbol (=) is not allowed as the first character for case description!",
						'bd_location.regex' => "Equal symbol (=) is not allowed as the first character for BD location!",
						'asp_rejected_cc_details_reason.regex' => "Equal symbol (=) is not allowed as the first character for ASP rejected cc details reason!",
						'asp_activity_rejected_reason.regex' => "Equal symbol (=) is not allowed as the first character for ASP activity rejected reason!",
						'activity_description.regex' => "Equal symbol (=) is not allowed as the first character for activity description!",
						'activity_remarks.regex' => "Equal symbol (=) is not allowed as the first character for activity remarks!",
						'asp_start_location.regex' => "Equal symbol (=) is not allowed as the first character for ASP start location!",
						'asp_end_location.regex' => "Equal symbol (=) is not allowed as the first character for ASP end location!",
						'drop_location.regex' => "Equal symbol (=) is not allowed as the first character for drop location!",
						'manual_uploading_remarks.regex' => "Equal symbol (=) is not allowed as the first character for manual uploading remarks!",
					];

					$validator = Validator::make($record, [
						//CASE
						'case_number' => 'required|string|max:32',
						'case_date' => 'required',
						'case_data_filled_date' => 'required',
						'case_description' => [
							'nullable',
							'string',
							'max:255',
							'regex:/^[^=]/',
						],
						'status' => [
							'required',
							'string',
							'max:191',
							// Rule::exists('case_statuses', 'name')
							// 	->where(function ($query) {
							// 		$query->whereNull('deleted_at');
							// 	}),
						],
						'cancel_reason' => [
							'nullable',
							'string',
							'max:100',
							// Rule::exists('case_cancelled_reasons', 'name')
							// 	->where(function ($query) {
							// 		$query->whereNull('deleted_at');
							// 	}),
						],
						'call_center' => [
							'required',
							'string',
							'max:64',
							Rule::exists('call_centers', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'client' => [
							'required',
							'string',
							'max:124',
							Rule::exists('clients', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'customer_name' => 'required|string|max:255',
						// 'customer_contact_number' => 'required|numeric|min:10|max:10',
						'contact_name' => 'nullable|string|max:255',
						// 'contact_number' => 'nullable|numeric|min:10|max:10',
						'vehicle_make' => [
							'required',
							'string',
							'max:191',
							Rule::exists('vehicle_makes', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'vehicle_model' => [
							'nullable',
							// 'regex:/^[\s\w-]*$/', //alpha_num with spaces
							'max:191',
							Rule::exists('vehicle_models', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'vehicle_registration_number' => 'nullable|max:20',
						'vin_no' => 'nullable|max:20',
						'membership_type' => 'nullable|string|max:191',
						'membership_number' => 'nullable|max:50',
						'subject' => [
							'required',
							'string',
							'max:191',
							Rule::exists('subjects', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'km_during_breakdown' => 'nullable|numeric',
						'bd_lat' => 'nullable|numeric',
						'bd_long' => 'nullable|numeric',
						'bd_location' => [
							'nullable',
							'string',
							'regex:/^[^=]/',
						],
						'bd_city' => 'nullable|string|max:255',
						'bd_state' => 'nullable|string|max:255',
						'bd_location_type' => [
							'required',
							'string',
							'max:191',
							Rule::exists('configs', 'name')
								->where(function ($query) {
									$query->where('entity_type_id', 39);
								}),
						],
						'bd_location_category' => [
							'required',
							'string',
							'max:60',
							Rule::exists('configs', 'name')
								->where(function ($query) {
									$query->where('entity_type_id', 40);
								}),
						],
						// 'bd_state' => [
						// 	'nullable',
						// 	'string',
						// 	'max:50',
						// 	Rule::exists('states', 'name')
						// 		->where(function ($query) {
						// 			$query->whereNull('deleted_at');
						// 		}),
						// ],

						'csr' => 'nullable',

						//ACTIVITY
						'crm_activity_id' => 'required|string',
						'data_source' => [
							'required',
							'string',
							'max:60',
							Rule::exists('configs', 'name')
								->where(function ($query) {
									$query->where('entity_type_id', 22);
								}),
						],
						'asp_code' => [
							'required',
							'string',
							'max:24',
							Rule::exists('asps', 'asp_code')
								->where(function ($query) {
									$query->where('is_active', 1);
								}),
						],
						'sub_service' => [
							'required',
							'string',
							'max:50',
							Rule::exists('service_types', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'asp_accepted_cc_details' => 'required|numeric',
						'asp_rejected_cc_details_reason' => [
							'nullable',
							'string',
							'regex:/^[^=]/',
						],
						'finance_status' => [
							'required',
							'string',
							'max:191',
							Rule::exists('activity_finance_statuses', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at')
										->where('company_id', 1);
								}),
						],
						'asp_activity_status' => [
							'nullable',
							'string',
							'max:191',
							Rule::exists('activity_asp_statuses', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'asp_activity_rejected_reason' => [
							'nullable',
							'string',
							'max:191',
							'regex:/^[^=]/',
							// Rule::exists('asp_activity_rejected_reasons', 'name')
							// 	->where(function ($query) {
							// 		$query->whereNull('deleted_at');
							// 	}),
						],
						'activity_status' => [
							'nullable',
							'string',
							'max:191',
							Rule::exists('activity_statuses', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
						],
						'sla_achieved_delayed' => 'required|string|max:30',
						'waiting_time' => 'nullable|numeric',
						'cc_colleced_amount' => 'nullable|numeric',
						'cc_not_collected_amount' => 'nullable|numeric',
						'cc_total_km' => 'nullable|numeric',
						'activity_description' => [
							'nullable',
							'string',
							'max:191',
							'regex:/^[^=]/',
						],
						'activity_remarks' => [
							'nullable',
							'string',
							'max:255',
							'regex:/^[^=]/',
						],
						'asp_reached_date' => 'nullable',
						'asp_start_location' => [
							'nullable',
							'string',
							'regex:/^[^=]/',
						],
						'asp_end_location' => [
							'nullable',
							'string',
							'regex:/^[^=]/',
						],
						'onward_google_km' => 'nullable|numeric',
						'dealer_google_km' => 'nullable|numeric',
						'return_google_km' => 'nullable|numeric',
						'onward_km' => 'nullable|numeric',
						'dealer_km' => 'nullable|numeric',
						'return_km' => 'nullable|numeric',
						'drop_location_type' => 'nullable|string|max:24',
						'drop_dealer' => 'nullable|string',
						'drop_location' => [
							'nullable',
							'string',
							'regex:/^[^=]/',
						],
						'drop_location_lat' => 'nullable|numeric',
						'drop_location_long' => 'nullable|numeric',
						'amount' => 'nullable|numeric',
						'paid_to' => 'nullable|string|max:24',
						'payment_mode' => 'nullable|string|max:50',
						'payment_receipt_no' => 'nullable|string|max:24',
						'service_charges' => 'nullable|numeric',
						'membership_charges' => 'nullable|numeric',
						'eatable_items_charges' => 'nullable|numeric',
						'toll_charges' => 'nullable|numeric',
						'green_tax_charges' => 'nullable|numeric',
						'border_charges' => 'nullable|numeric',
						// 'octroi_charges' => 'nullable|numeric',
						'excess_charges' => 'nullable|numeric',
						'manual_uploading_remarks' => [
							'required',
							'string',
							'regex:/^[^=]/',
						],
					], $errorMessages);

					if ($validator->fails()) {
						$status['errors'] = $validator->errors()->all();
						$save_eligible = false;
					}

					//ASSIGN ZERO IF IT IS EMPTY
					if (!$record['cc_total_km']) {
						$record['cc_total_km'] = 0;
					}
					if (!$record['cc_not_collected_amount']) {
						$record['cc_not_collected_amount'] = 0;
					}
					if (!$record['cc_colleced_amount']) {
						$record['cc_colleced_amount'] = 0;
					}

					//Dont allow updations if current status is Cancelled or Closed
					// $case = RsaCase::where([
					// 	'company_id' => 1,
					// 	'number' => $record['case_number'],
					// ])->first();
					// if ($case && ($case->status_id == 3 || $case->status_id == 4)) {
					// 	$status['errors'][] = 'Update not allowed - Case already ' . $case->status->name;
					// 	$save_eligible = false;
					// }

					//CASE VALIDATION START
					//Till May'20 Cases not allowed
					// $case_date_format_1 = PHPExcel_Style_NumberFormat::toFormattedString($record['case_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
					// $case_date_format_2 = PHPExcel_Style_NumberFormat::toFormattedString($record['case_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME6);
					// $case_date_format_3 = $case_date_format_1 . ' ' . $case_date_format_2;
					// $case_date_val = date('Y-m-d', strtotime($case_date_format_3));
					$case_date_val = date('Y-m-d', strtotime($record['case_date']));
					$case_restriction_date = config('rsa.CASE_RESTRICTION_DATE');
					if ($case_date_val <= $case_restriction_date) {
						$status['errors'][] = "Till May'20 Cases not allowed";
						$save_eligible = false;
					}

					//ALLOW ONLY LETTERS AND NUMBERS ANS HYPHENS
					// if (!preg_match("/^[a-zA-Z0-9]+$/", $record['case_number'])) {
					if (!preg_match("/^[A-Za-z0-9-]+$/", $record['case_number'])) {
						$status['errors'][] = 'Invalid Case Number';
						$save_eligible = false;
					}

					if (strtolower($record['status']) == "on hold") {
						$record['status'] = "In Progress";
					}

					if (strtolower($record['status']) == "pre-close") {
						$record['status'] = "Closed";
					}

					$case_status = CaseStatus::where('name', $record['status'])->where('company_id', 1)->first();
					if (!$case_status) {
						$status['errors'][] = 'Case status is invalid';
						$save_eligible = false;
					}
					$call_center = CallCenter::where('name', $record['call_center'])->first();
					if (!$call_center) {
						$save_eligible = false;
					}
					$client = Client::where('name', $record['client'])->first();
					if (!$client) {
						$save_eligible = false;
					}
					$vehicle_make = VehicleMake::where('name', $record['vehicle_make'])->first();
					if (!$vehicle_make) {
						$save_eligible = false;
						$vehicle_make_id = NULL;
					} else {
						$vehicle_make_id = $vehicle_make->id;
					}

					//CASE STATUS IS CANCELLED - CANCEL REASON IS MANDATORY
					if ($case_status && $case_status->id == 3) {
						if (!$record['cancel_reason']) {
							$status['errors'][] = 'Cancel reason is required';
							$save_eligible = false;
						}
					}
					//VEHICLE MODEL GOT BY VEHICLE MAKE
					$vehicle_model_by_make = VehicleModel::where('name', $record['vehicle_model'])->where('vehicle_make_id', $vehicle_make_id)->first();
					if (!$vehicle_model_by_make) {
						$status['errors'][] = 'Selected vehicle make doesn"t matches with vehicle model';
						$save_eligible = false;
					}

					//VIN NO OR VEHICLE REGISTRATION NUMBER ANY ONE IS MANDATORY
					if (!$record['vehicle_registration_number'] && !$record['vin_no']) {
						$status['errors'][] = 'VIN or Vehicle Registration Number is required';
						$save_eligible = false;
					}

					$subject = Subject::where('name', $record['subject'])->first();
					if (!$subject) {
						$save_eligible = false;
					}
					$cancel_reason = CaseCancelledReason::where('name', $record['cancel_reason'])->where('company_id', 1)->first();
					if (!$cancel_reason) {
						$cancel_reason_id = NULL;
					} else {
						$cancel_reason_id = $cancel_reason->id;
					}

					$case = RsaCase::firstOrNew([
						'company_id' => 1,
						'number' => $record['case_number'],
					]);

					//CASE NEW
					// if (!$case->exists) {
					// 	//WITH CANCELLED OR CLOSED STATUS
					// 	if ($case_status && ($case_status->id == 3 || $case_status->id == 4)) {
					// 		$status['errors'][] = 'Case should not start with cancelled or closed status';
					// 		$save_eligible = false;
					// 	}
					// } else {
					// 	// $updated_count++;
					// }
					//CASE VALIDATION END

					//ACTIVITY VALIDATION START
					$asp = Asp::where('asp_code', $record['asp_code'])->first();
					if (!$asp) {
						$save_eligible = false;
					}
					//CHECK ASP IS NOT ACTIVE
					if ($asp && !$asp->is_active) {
						$status['errors'][] = 'ASP is inactive';
						$save_eligible = false;
					}

					//ASP ACCEPTED CC DETAILS == 0 -- REASON IS MANDATORY
					if (!$record['asp_accepted_cc_details']) {
						if (!$record['asp_rejected_cc_details_reason']) {
							$status['errors'][] = 'Reason for ASP rejected cc details is required';
							$save_eligible = false;
						}
					}

					if (!empty($record['sla_achieved_delayed']) && strtolower($record['sla_achieved_delayed']) != 'sla not met' && strtolower($record['sla_achieved_delayed']) != 'sla met') {
						$status['errors'][] = 'Invalid sla_achieved_delayed';
						$save_eligible = false;
					}

					if (!empty($record['drop_location_type']) && strtolower($record['drop_location_type']) != 'garage' && strtolower($record['drop_location_type']) != 'dealer' && strtolower($record['drop_location_type']) != 'customer preferred') {
						$status['errors'][] = 'Invalid drop_location_type';
						$save_eligible = false;
					}

					if (!empty($record['paid_to']) && strtolower($record['paid_to']) != 'asp' && strtolower($record['paid_to']) != 'online') {
						$status['errors'][] = 'Invalid paid_to';
						$save_eligible = false;
					}

					if (!empty($record['payment_mode']) && strtolower($record['payment_mode']) != 'cash' && strtolower($record['payment_mode']) != 'paytm' && strtolower($record['payment_mode']) != 'online') {
						$status['errors'][] = 'Invalid payment_mode';
						$save_eligible = false;
					}

					$service_type = ServiceType::where('name', $record['sub_service'])->first();
					if (!$service_type) {
						$save_eligible = false;
					}

					$asp_status = ActivityAspStatus::where('name', $record['asp_activity_status'])->where('company_id', 1)->first();
					if (!$asp_status) {
						$asp_activity_status_id = NULL;
					} else {
						$asp_activity_status_id = $asp_status->id;
					}

					$asp_activity_rejected_reason = AspActivityRejectedReason::where('name', $record['asp_activity_rejected_reason'])->where('company_id', 1)->first();
					if (!$asp_activity_rejected_reason) {
						$asp_activity_rejected_reason_id = NULL;
					} else {
						$asp_activity_rejected_reason_id = $asp_activity_rejected_reason->id;
					}

					$activity_status = ActivityStatus::where('name', $record['activity_status'])->where('company_id', 1)->first();
					if (!$activity_status) {
						$activity_status_id = NULL;
					} else {
						$activity_status_id = $activity_status->id;
					}

					$finance_status = ActivityFinanceStatus::where([
						'company_id' => 1,
						'name' => $record['finance_status'],
					])->first();
					if (!$finance_status) {
						$save_eligible = false;
					}

					$bd_location_type = Config::where('name', $record['bd_location_type'])
						->where('entity_type_id', 39) // BD LOCATION TYPES
						->first();
					if ($bd_location_type) {
						$bd_location_type_id = $bd_location_type->id;
					} else {
						$save_eligible = false;
					}
					$bd_location_category = Config::where('name', $record['bd_location_category'])
						->where('entity_type_id', 40) // BD LOCATION CATEGORIES
						->first();
					if ($bd_location_category) {
						$bd_location_category_id = $bd_location_category->id;
					} else {
						$save_eligible = false;
					}

					$dataSource = Config::where('name', $record['data_source'])
						->where('entity_type_id', 22) // Activity Data Sources
						->first();
					if ($dataSource) {
						$dataSourceId = $dataSource->id;
					} else {
						$save_eligible = false;
					}

					//SAVE CASE AND ACTIVITY
					if ($save_eligible) {
						// $case_date1 = PHPExcel_Style_NumberFormat::toFormattedString($record['case_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
						// $case_date2 = PHPExcel_Style_NumberFormat::toFormattedString($record['case_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);
						// $case_date = $case_date1 . ' ' . $case_date2;
						$case_date = $record['case_date'];

						// $case_data_filled_date1 = PHPExcel_Style_NumberFormat::toFormattedString($record['case_data_filled_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
						// $case_data_filled_date2 = PHPExcel_Style_NumberFormat::toFormattedString($record['case_data_filled_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);
						// $case_data_filled_date = $case_data_filled_date1 . ' ' . $case_data_filled_date2;
						$case_data_filled_date = $record['case_data_filled_date'];

						// $asp_reached_date1 = PHPExcel_Style_NumberFormat::toFormattedString($record['asp_reached_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
						// $asp_reached_date2 = PHPExcel_Style_NumberFormat::toFormattedString($record['asp_reached_date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);
						// $record['asp_reached_date'] = $asp_reached_date1 . ' ' . $asp_reached_date2;

						$case->fill($record);
						$case->number = $record['case_number'];
						$case->date = $case_date;
						$case->data_filled_date = $case_data_filled_date;
						$case->description = $record['case_description'];
						$case->status_id = $case_status->id;
						$case->cancel_reason_id = $cancel_reason_id;
						$case->call_center_id = $call_center->id;
						$case->client_id = $client->id;
						$case->vehicle_model_id = $vehicle_model_by_make->id;
						$case->subject_id = $subject->id;
						$case->bd_location_type_id = $bd_location_type_id;
						$case->bd_location_category_id = $bd_location_category_id;
						$case->membership_type = !empty($record['membership_type']) ? $record['membership_type'] : NULL;
						$case->csr = !empty($record['csr']) ? $record['csr'] : NULL;
						$case->save();

						$activity_save_eligible = true;
						$newActivity = false;
						$crm_activity_id = trim($record['crm_activity_id']);
						$activity_exist = Activity::withTrashed()->where('crm_activity_id', $crm_activity_id)->first();
						if (!$activity_exist) {
							$activity = new Activity([
								'crm_activity_id' => $crm_activity_id,
							]);
							$count_variable = 'new_count';
							$newActivity = true;
						} else {
							$activity_belongsto_case = Activity::withTrashed()->where('crm_activity_id', $crm_activity_id)
								->where('case_id', $case->id)
								->first();
							if ($activity_belongsto_case) {
								//Allow case with intial staus and not payment processed statuses
								if ($activity_belongsto_case->status_id == 2 || $activity_belongsto_case->status_id == 4 || $activity_belongsto_case->status_id == 10 || $activity_belongsto_case->status_id == 17 || $activity_belongsto_case->status_id == 26) {

									$activity = Activity::withTrashed()->where('crm_activity_id', $crm_activity_id)->first();
									$count_variable = 'updated_count';
								} elseif ($activity_belongsto_case->status_id == 15 || $activity_belongsto_case->status_id == 16) {
									$status['errors'][] = 'Unable to update data. Case is not eligible for payout';
									$activity_save_eligible = false;
								} else {
									$status['errors'][] = 'Unable to update data. Case is under payment process';
									$activity_save_eligible = false;
								}
							} else {
								$status['errors'][] = 'The crm activity id has already been taken';
								$activity_save_eligible = false;
							}
						}

						if ($activity_save_eligible) {
							$activity->fill($record);

							$activity->finance_status_id = $finance_status->id;

							$activity->asp_id = $asp->id;
							$activity->case_id = $case->id;
							$activity->service_type_id = $service_type->id;
							$activity->asp_activity_status_id = $asp_activity_status_id;
							$activity->asp_activity_rejected_reason_id = $asp_activity_rejected_reason_id;
							$activity->description = $record['activity_description'];
							$activity->remarks = $record['activity_remarks'];
							$activity->manual_uploading_remarks = $record['manual_uploading_remarks'];

							//ASP ACCEPTED CC DETAILS == 1 AND ACTIVITY STATUS SUCCESSFUL OLD
							// if ($request->asp_accepted_cc_details && $activity_status_id == 7) {
							//ASP ACCEPTED CC DETAILS == 1
							if ($record['asp_accepted_cc_details']) {
								//DISABLED DUE NEW WHATSAPP PROCESS
								// $activity->status_id = 10; //Invoice Amount Calculated - Waiting for Case Closure
								$activity->status_id = 17; //ON HOLD
							} else {
								//CASE IS CLOSED
								if ($case->status_id == 4) {
									//IF MECHANICAL SERVICE GROUP - DISABLED
									// if ($service_type->service_group_id == 2) {
									// 	$is_bulk = self::checkTicketIsBulk($asp->id, $service_type->id, $record['cc_total_km'], $dataSourceId, $case->date);
									// 	if ($is_bulk) {
									// 		//ASP Completed Data Entry - Waiting for L1 Bulk Verification
									// 		$activity->status_id = 5;
									// 	} else {
									// 		//ASP Completed Data Entry - Waiting for L1 Individual Verification
									// 		$activity->status_id = 6;
									// 	}
									// }

									// TOW SERVICE
									if ($service_type->service_group_id == 3) {
										if ($asp->is_corporate == 1 || $activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
											//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
											if (floatval($record['cc_total_km']) <= 2 && $activity->is_asp_data_entry_done != 1) {
												$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
											} else {
												$activity->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
											}
										} else {
											$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
										}
									} else {
										$activity->status_id = 17; //ON HOLD
									}
								} else {
									$activity->status_id = 17; //ON HOLD
								}
							}
							$activity->reason_for_asp_rejected_cc_details = $record['asp_rejected_cc_details_reason'];
							$activity->activity_status_id = $activity_status_id;
							$activity->data_src_id = $dataSourceId; //BO MANUAL
							$activity->save();

							$activity->is_towing_attachments_mandatory = 0;
							//TOWING GROUP
							if ($service_type->service_group_id == 3) {
								$towingImagesMandatoryEffectiveDate = config('rsa.TOWING_IMAGES_MANDATORY_EFFECTIVE_DATE');
								if (date('Y-m-d', strtotime($case->date)) >= $towingImagesMandatoryEffectiveDate) {
									$activity->is_towing_attachments_mandatory = 1;
								}
							}
							$activity->number = 'ACT' . $activity->id;
							$activity->save();

							//SAVING ACTIVITY DETAILS
							$activity_fields = Config::where('entity_type_id', 23)->get();
							foreach ($activity_fields as $key => $activity_field) {
								$detail = ActivityDetail::firstOrNew([
									'company_id' => 1,
									'activity_id' => $activity->id,
									'key_id' => $activity_field->id,
								]);
								$detail->value = isset($record[$activity_field->name]) ? $record[$activity_field->name] : NULL;
								$detail->save();
							}

							$activity->saveActivityChargesDetails();

							//CALCULATE PAYOUT ONLY IF FINANCE STATUS OF ACTIVITY IS ELIBLE FOR PO
							if ($activity->financeStatus->po_eligibility_type_id == 342) {
								//No Payout status
								$activity->status_id = 15;
								$activity->save();
								$job->{$count_variable}++;
							} else {
								$response = $activity->calculatePayoutAmount('CC');
								if (!$response['success']) {
									$status['errors'][] = $response['error'];
								} else {
									$job->{$count_variable}++;
								}
								//IF DATA SRC IS CRM WEB APP
								if ($activity->data_src_id == 261) {
									//CASE IS CLOSED
									if ($case->status_id == 4) {
										//IF ROS ASP then changes status as Waitin for ASP data entry. If not change status as on hold
										if ($asp->is_ros_asp == 1) {
											// TOW SERVICE
											if ($service_type->service_group_id == 3) {
												if ($asp->is_corporate == 1 || $activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
													//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
													if (floatval($record['cc_total_km']) <= 2 && $activity->is_asp_data_entry_done != 1) {
														$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
													} else {
														$activity->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
													}
												} else {
													$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
												}
											} else {
												$activity->status_id = 17; //ON HOLD
											}
										} elseif ($asp->is_corporate == 1) {
											//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
											if (floatval($record['cc_total_km']) <= 2 && $activity->is_asp_data_entry_done != 1) {
												$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
											} else {
												$activity->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
											}
										} else {
											$activity->status_id = 17; //ON HOLD
										}

										// //IF MECHANICAL SERVICE GROUP - DISABLED
										// if ($service_type->service_group_id == 2) {
										// 	$is_bulk = self::checkTicketIsBulk($asp->id, $service_type->id, $record['cc_total_km'], $activity->data_src_id, $case->date);
										// 	if ($is_bulk) {
										// 		//ASP Completed Data Entry - Waiting for L1 Bulk Verification
										// 		$activity->status_id = 5;
										// 	} else {
										// 		//ASP Completed Data Entry - Waiting for L1 Individual Verification
										// 		$activity->status_id = 6;
										// 	}
										// }
									} else {
										$activity->status_id = 17; //ON HOLD
									}
									$activity->save();
								}
							}

							//MARKING AS OWN PATROL ACTIVITY
							if ($activity->asp->workshop_type == 1) {
								//Own Patrol Activity - Not Eligible for Payout
								$activity->status_id = 16;
								$activity->save();
							}

							$disableWhatsappAutoApproval = config('rsa')['DISABLE_WHATSAPP_AUTO_APPROVAL'];
							$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
							$enableWhatsappFlow = config('rsa')['ENABLE_FOR_WHATSAPP_FLOW_FOR_IMPORT']; // CURRENTLY NOT REQUIRED FOR IMPORTED TICKETS SAID BY MR.HYDER

							//IF ACTIVITY CREATED THEN SEND NEW BREAKDOWN ALERT WHATSAPP SMS TO ASP (TYPEID CHECK - SKIP WHATSAPP PROCESS IF IT IS FROM NEW CRM)
							if (empty($case->type_id) && $newActivity && $activity->asp && !empty($activity->asp->whatsapp_number) && $enableWhatsappFlow && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
								//OTHER THAN TOW SERVICES || TOW SERVICE WITH CC KM GREATER THAN 2
								if (($service_type->service_group_id != 3 && ($disableWhatsappAutoApproval || (!$disableWhatsappAutoApproval && floatval($record['cc_total_km']) > 2))) || ($service_type->service_group_id == 3 && floatval($record['cc_total_km']) > 2)) {
									$activity->sendBreakdownAlertWhatsappSms();
								}
							}

							$breakdownAlertSent = self::breakdownAlertSent($activity->id);

							if ($case->status_id == 3) {
								//CANCELLED
								if ($case->activities->isNotEmpty()) {
									foreach ($case->activities as $key => $activity) {
										//If Finance Status is Not Matured
										if ($activity->financeStatus->po_eligibility_type_id == 342) {
											//If ASP Workshop Type is Own Patrol Activity
											if ($activity->asp->workshop_type == 1) {
												$status_id = 16; //Own Patrol Activity - Not Eligible for Payout
											} else {
												$status_id = 15; // Not Eligible for Payout
											}
											$activity->update([
												'status_id' => $status_id,
											]);

											//SAVE ACTIVITY REPORT FOR DASHBOARD
											ActivityReport::saveReport($activity->id);
										}
									}
								}
							}

							//CLOSED
							if ($case->status_id == 4) {
								//UPDATE LOG
								$invoiceAmountCalculatedActivities = $case->activities()->where(['status_id' => 10])->get();
								if ($invoiceAmountCalculatedActivities->isNotEmpty()) {
									foreach ($invoiceAmountCalculatedActivities as $key => $invoiceAmountCalculatedActivity) {
										$activityLog = ActivityLog::firstOrNew([
											'activity_id' => $invoiceAmountCalculatedActivity->id,
										]);
										//NEW
										if (!$activityLog->exists) {
											$activityLog->created_by_id = 72;
										} else {
											$activityLog->updated_by_id = 72;
										}
										$activityLog->bo_approved_at = date('Y-m-d H:i:s');
										$activityLog->save();

										$invoiceAmountCalculatedActivityBreakdownAlertSent = self::breakdownAlertSent($invoiceAmountCalculatedActivity->id);

										//SEND BREAKDOWN OR EMPTY RETURN CHARGES WHATSAPP SMS TO ASP
										if ($invoiceAmountCalculatedActivityBreakdownAlertSent && $invoiceAmountCalculatedActivity->asp && !empty($invoiceAmountCalculatedActivity->asp->whatsapp_number) && $enableWhatsappFlow && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $invoiceAmountCalculatedActivity->asp->has_whatsapp_flow == 1))) {
											$invoiceAmountCalculatedActivity->sendBreakdownOrEmptyreturnChargesWhatsappSms();
										}

										$invoiceAmountCalculatedActivity->update([
											'status_id' => 1, //Case Closed - Waiting for ASP to Generate Invoice
										]);

										//SAVE ACTIVITY REPORT FOR DASHBOARD
										ActivityReport::saveReport($invoiceAmountCalculatedActivity->id);
									}
								}
							}

							//RELEASE ONHOLD / ASP COMPLETED DATA ENTRY - WAITING FOR CALL CENTER DATA ENTRY ACTIVITIES WITH CLOSED OR CANCELLED CASES
							if ($case->status_id == 4 || $case->status_id == 3) {
								$caseActivities = $case->activities()->whereIn('status_id', [17, 26])->get();
								if ($caseActivities->isNotEmpty()) {
									foreach ($caseActivities as $key => $caseActivity) {
										$caseActivityBreakdownAlertSent = self::breakdownAlertSent($caseActivity->id);
										$cc_total_km = $caseActivity->detail(280) ? $caseActivity->detail(280)->value : 0;

										//WHATSAPP FLOW
										if ($caseActivityBreakdownAlertSent && $caseActivity->asp && !empty($caseActivity->asp->whatsapp_number) && $enableWhatsappFlow && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $caseActivity->asp->has_whatsapp_flow == 1))) {
											// ROS SERVICE
											if ($caseActivity->serviceType && $caseActivity->serviceType->service_group_id != 3) {

												if (!$disableWhatsappAutoApproval) {
													$autoApprovalProcessResponse = $caseActivity->autoApprovalProcess();
													if (!$autoApprovalProcessResponse['success']) {
														$status['errors'][] = "Case Number : " . $caseActivity->case->number . " - " . $autoApprovalProcessResponse['error'];
													}
												} else {
													//MECHANICAL SERVICE GROUP
													if ($caseActivity->serviceType && $caseActivity->serviceType->service_group_id == 2) {
														$isBulk = self::checkTicketIsBulk($caseActivity->asp_id, $caseActivity->serviceType->id, $cc_total_km, $activity->data_src_id, $caseActivity->case->date);
														if ($isBulk) {
															//ASP Completed Data Entry - Waiting for L1 Bulk Verification
															$statusId = 5;
														} else {
															//ASP Completed Data Entry - Waiting for L1 Individual Verification
															$statusId = 6;
														}
													} else {
														if (($caseActivity->asp && $caseActivity->asp->is_corporate == 1) || $caseActivity->is_asp_data_entry_done == 1) {
															$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
														} else {
															$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
														}
													}

													//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
													if (floatval($cc_total_km) <= 2 && $caseActivity->is_asp_data_entry_done != 1) {
														$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
													}

													$caseActivity->update([
														'status_id' => $statusId,
													]);
												}

											} else {
												// TOW SERVICE
												if ($caseActivity->asp->is_corporate == 1 || $caseActivity->towing_attachments_uploaded_on_whatsapp == 1 || $caseActivity->is_asp_data_entry_done == 1) {
													//ASP Completed Data Entry - Waiting for L1 Individual Verification
													$statusId = 6;
												} else {
													$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
												}

												//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
												if (floatval($cc_total_km) <= 2 && $caseActivity->is_asp_data_entry_done != 1) {
													$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
												}

												$caseActivity->update([
													'status_id' => $statusId,
												]);
											}
										} else {
											// NORMAL FLOW

											//MECHANICAL SERVICE GROUP
											if ($caseActivity->serviceType && $caseActivity->serviceType->service_group_id == 2) {
												$isBulk = self::checkTicketIsBulk($caseActivity->asp_id, $caseActivity->serviceType->id, $cc_total_km, $activity->data_src_id, $caseActivity->case->date);
												if ($isBulk) {
													//ASP Completed Data Entry - Waiting for L1 Bulk Verification
													$statusId = 5;
												} else {
													//ASP Completed Data Entry - Waiting for L1 Individual Verification
													$statusId = 6;
												}
											} else {
												if (($caseActivity->asp && $caseActivity->asp->is_corporate == 1) || $caseActivity->is_asp_data_entry_done == 1) {
													$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
												} else {
													$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
												}
											}

											//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
											if (floatval($cc_total_km) <= 2 && $caseActivity->is_asp_data_entry_done != 1) {
												$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
											}

											$caseActivity->update([
												'status_id' => $statusId,
											]);
										}

										//SAVE ACTIVITY REPORT FOR DASHBOARD
										ActivityReport::saveReport($caseActivity->id);
									}
								}
							}

							//IF ACTIVITY CANCELLED THEN SEND ACTIVITY CANCELLED WHATSAPP SMS TO ASP
							if ($breakdownAlertSent && !empty($activity_status_id) && $activity_status_id == 4 && $activity->asp && !empty($activity->asp->whatsapp_number) && $enableWhatsappFlow && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
								$activity->sendActivityCancelledWhatsappSms();
							}

							//UPDATE LOG ACTIVITY AND LOG MESSAGE
							logActivity3(config('constants.entity_types.ticket'), $activity->id, [
								'Status' => 'Imported through MIS Import',
								'Waiting for' => 'ASP Data Entry',
							], 361);

							$activity_log = ActivityLog::firstOrNew([
								'activity_id' => $activity->id,
							]);
							$activity_log->imported_at = date('Y-m-d H:i:s');
							$activity_log->imported_by_id = $job->created_by_id;
							$activity_log->asp_data_filled_at = date('Y-m-d H:i:s');
							// if ($record['asp_accepted_cc_details']) {
							// 	$activity_log->bo_approved_at = date('Y-m-d H:i:s');
							// 	$activity_log->bo_approved_by_id = $job->created_by_id;
							// }
							//NEW
							if (!$activity_log->exists) {
								$activity_log->created_by_id = 72;
							} else {
								$activity_log->updated_by_id = 72;
							}
							$activity_log->save();

							//SAVE ACTIVITY REPORT FOR DASHBOARD
							ActivityReport::saveReport($activity->id);
						}
					}

					if (count($status['errors']) > 0) {
						DB::rollBack();
						// dump($status['errors']);
						$original_record['Record No'] = $k + 1;
						$original_record['Error Details'] = implode(',', $status['errors']);
						$all_error_records[] = $original_record;
						// $job->incrementError();
						$job->error_count++;
						$job->processed_count++;
						$job->remaining_count--;
						continue;
					}

					//UPDATING PROGRESS FOR EVERY FIVE RECORDS
					// if (($k + 1) % 5 == 0) {
					$job->processed_count++;
					$job->remaining_count--;
					if (($k + 1) % 250 == 0) {
						$job->save();
					}
					// }

					DB::commit();
				} //COMPLETED or completed with errors
			}

			$job->error_count;
			$job->processed_count;
			$job->remaining_count;
			// $job->updated_count = $updated_count;
			$job->status_id = $job->error_count == 0 ? 7202 : 7203;
			$job->save();

			ImportCronJob::generateImportReport([
				'job' => $job,
				'all_error_records' => $all_error_records,
			]);

			DB::commit();

		} catch (\Exception $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			DB::commit();
			dump($job->error_details);
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
		$km_charge = numberFormatToDecimalConversion(floatval($below_range_price + $above_range_price));

		//FORMULAE DISABLED AS PER CLIENT REQUEST
		// if ($price->adjustment_type == 1) {
		// 	//'Percentage'
		// 	$adjustment = ($km_charge * $price->adjustment) / 100;
		// 	$km_charge = $km_charge + $adjustment;
		// } else {
		// 	$adjustment = $price->adjustment;
		// 	$km_charge = $km_charge + $adjustment;
		// }
		return $km_charge;
	}

	public static function checkTicketIsBulk($asp_id, $service_type_id, $asp_km, $dataSourceId, $caseDate) {
		$isMobile = 0; //WEB
		//MOBILE APP
		if ($dataSourceId == 260 || $dataSourceId == 263) {
			$isMobile = 1;
		}

		$is_bulk = true;
		$range_limit = 0;
		$aspServiceType = self::getAspServiceRateCardByAmendment($asp_id, $caseDate, $service_type_id, $isMobile);
		if ($aspServiceType) {
			$range_limit = $aspServiceType->range_limit;
		}
		if (!empty($asp_km)) {
			if (floatval($asp_km) == 0) {
				$is_bulk = false;
			}
			//checking ASP KMs exceed ASP service type range limit
			if (floatval($asp_km) > floatval($range_limit)) {
				$is_bulk = false;
			}
		} else {
			$is_bulk = false;
		}
		return $is_bulk;
	}

	public function sendBreakdownAlertWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspCode = !empty($this->asp->asp_code) ? $this->asp->asp_code : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$caseNumber = $this->case ? (!empty($this->case->number) ? $this->case->number : '--') : '--';
		$caseDate = $this->case ? (!empty($this->case->date) ? date('d.m.Y', strtotime($this->case->date)) : '--') : '--';
		$activityNumber = $this->crm_activity_id;
		$customerName = $this->case ? (!empty($this->case->customer_name) ? $this->case->customer_name : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : '--') : '--';
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$payloadVehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$model = $this->case ? ($this->case->vehicleModel ? $this->case->vehicleModel->name : '--') : '--';
		$serviceType = $this->serviceType ? $this->serviceType->name : '--';
		$bdAddress = $this->case ? (!empty($this->case->bd_location) ? $this->case->bd_location : '--') : '--';
		$bdMapLocation = '--';
		if (!empty($this->case->bd_lat) && !empty($this->case->bd_long)) {
			$bdMapLocation = "https://maps.google.com/maps?q=" . $this->case->bd_lat . "," . $this->case->bd_long;
		}
		$dropAddress = $this->detail(295) ? (!empty($this->detail(295)->value) ? $this->detail(295)->value : '--') : '--';
		$dropLocationLat = $this->detail(296) ? (!empty($this->detail(296)->value) ? $this->detail(296)->value : '') : '';
		$dropLocationLong = $this->detail(297) ? (!empty($this->detail(297)->value) ? $this->detail(297)->value : '') : '';
		$dropMapLocation = '--';
		if (!empty($dropLocationLat) && !empty($dropLocationLong)) {
			$dropMapLocation = "https://maps.google.com/maps?q=" . $dropLocationLat . "," . $dropLocationLong;
		}
		$tollFreeNumber = '--';
		if ($this->case && $this->case->callcenter && !empty($this->case->callcenter->toll_free_number)) {
			$tollFreeNumber = $this->case->callcenter->toll_free_number;
		}
		$whatsAppNumber = '--';
		if ($this->case && $this->case->callcenter && !empty($this->case->callcenter->whatsapp_number)) {
			$whatsAppNumber = $this->case->callcenter->whatsapp_number;
		}

		$senderNumber = config('constants')['whatsapp_api_sender'];

		$sendBreakdownAlertMail = false;

		//ROS(Repaid Onsite) SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = 'case_assignment_ros';
			$bodyParameterValues = new \stdClass();
			$bodyParameterValues->{'0'} = $aspName;
			$bodyParameterValues->{'1'} = $caseDate;
			$bodyParameterValues->{'2'} = $activityNumber;
			$bodyParameterValues->{'3'} = $customerName;
			$bodyParameterValues->{'4'} = $vehicleNumber;
			$bodyParameterValues->{'5'} = $vin;
			$bodyParameterValues->{'6'} = $model;
			$bodyParameterValues->{'7'} = $serviceType;
			$bodyParameterValues->{'8'} = $bdAddress;
			$bodyParameterValues->{'9'} = $bdMapLocation;
			$bodyParameterValues->{'10'} = $tollFreeNumber;

			$inputRequests = [
				"message" => [
					"channel" => "WABA",
					"content" => [
						"preview_url" => false,
						"type" => "MEDIA_TEMPLATE",
						"mediaTemplate" => [
							"templateId" => $templateId,
							"bodyParameterValues" => $bodyParameterValues,
						],
					],
					"recipient" => [
						"to" => "91" . $aspWhatsAppNumber,
						"recipient_type" => "individual",
					],
					"sender" => [
						"from" => $senderNumber,
					],
					"preferences" => [
						"webHookDNId" => "1001",
					],
				],
				"metaData" => [
					"version" => "v1.0.9",
				],
			];
		} else {
			// TOWING SERVICE

			$sendBreakdownAlertMail = true;
			$templateId = 'case_assignment_tow_upload_image_bt_new';
			$bodyParameterValues = new \stdClass();
			$bodyParameterValues->{'0'} = $aspName;
			$bodyParameterValues->{'1'} = $caseDate;
			$bodyParameterValues->{'2'} = $activityNumber;
			$bodyParameterValues->{'3'} = $customerName;
			$bodyParameterValues->{'4'} = $vehicleNumber;
			$bodyParameterValues->{'5'} = $vin;
			$bodyParameterValues->{'6'} = $model;
			$bodyParameterValues->{'7'} = $serviceType;
			$bodyParameterValues->{'8'} = $bdAddress;
			$bodyParameterValues->{'9'} = $bdMapLocation;
			$bodyParameterValues->{'10'} = $dropAddress;
			$bodyParameterValues->{'11'} = $dropMapLocation;
			$bodyParameterValues->{'12'} = $tollFreeNumber;

			$payloadIndex = [
				"value" => "Upload Images",
				"activity_id" => $activityNumber,
				"vehicle_no" => $payloadVehicleNumber,
				"type" => "New Breakdown Alert",
			];

			$inputRequests = [
				"message" => [
					"channel" => "WABA",
					"content" => [
						"preview_url" => false,
						"type" => "MEDIA_TEMPLATE",
						"mediaTemplate" => [
							"templateId" => $templateId,
							"bodyParameterValues" => $bodyParameterValues,
							"buttons" => [
								"quickReplies" => [
									[
										"index" => "0",
										"payload" => json_encode($payloadIndex),
									],
								],
							],
						],
					],
					"recipient" => [
						"to" => "91" . $aspWhatsAppNumber,
						"recipient_type" => "individual",
					],
					"sender" => [
						"from" => $senderNumber,
					],
					"preferences" => [
						"webHookDNId" => "1001",
					],
				],
				"metaData" => [
					"version" => "v1.0.9",
				],
			];
		}

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1191, $inputRequests);

		//SEND BREAKDOWN ALERT EMAIL TO BUSINESS USERS
		$disableActivityWhatsappMailNoty = config('rsa')['DISABLE_ACTIVITY_WHATSAPP_MAIL_NOTY'];
		if ($sendBreakdownAlertMail && !$disableActivityWhatsappMailNoty) {
			// LIVE PURPOSE
			$toMailIds = config('rsa')['ACTIVITY_WHATSAPP_MAIL_NOTY_MAIL_IDS'];

			//TESTING PURPOSE
			// $toMailIds = [
			// 	"ramakrishnan@uitoux.in",
			// 	"sridhar@uitoux.in",
			// 	"karthick.r@uitoux.in",
			// ];
			$arr['content'] = 'The breakdown alert message has been triggered to the ASP(' . $aspCode . ') for the following case.';
			$arr['to_mail_ids'] = $toMailIds;
			$arr['caseNumber'] = $caseNumber;
			$arr['activityId'] = $activityNumber;
			$arr['vehicleNo'] = $payloadVehicleNumber;
			$arr['serviceType'] = $serviceType;
			$arr['company_header'] = view('partials/email-noty-company-header')->render();
			$MailInstance = new ActivityWhatsappMailNoty($arr);
			try {
				$Mail = Mail::send($MailInstance);
			} catch (\Exception $e) {
			}
		}
	}

	public function sendImageUploadConfirmationWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';

		$templateId = 'image_upload_confirmation_2';
		$senderNumber = config('constants')['whatsapp_api_sender'];

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $activityNumber;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1192, $inputRequests);
	}

	public static function breakdownAlertSent($activityId) {
		$breakdownAlertWhatsAppLog = ActivityWhatsappLog::where([
			'activity_id' => $activityId,
			'type_id' => 1191,
			'is_new' => 1,
		])
			->first();
		return $breakdownAlertWhatsAppLog ? true : false;
	}

	public function autoApprovalProcess() {
		$response = [];
		$totalKm = !empty($this->detail(280)->value) ? numberFormatToDecimalConversion(floatval($this->detail(280)->value)) : 0; // CC TOTAL KM
		$collectedCharges = !empty($this->detail(281)->value) ? numberFormatToDecimalConversion(floatval($this->detail(281)->value)) : 0; //CC COLLECTED AMOUNT
		$notCollectedCharges = !empty($this->detail(282)->value) ? numberFormatToDecimalConversion(floatval($this->detail(282)->value)) : 0; //CC NOT COLLECTED AMOUNT
		$autoApprovalKm = config('rsa')['ACTIVITY_AUTO_APPROVAL_KM'];

		if (empty($totalKm) || floatval($totalKm) <= 2) {
			$response['success'] = false;
			$response['error'] = "KM Travelled should be greater than 2";
			return $response;
		}

		// GREATER THAN PREDEFINED AUTO APPROVAL KM THEN APPROVE ONLY FOR PREDEFINED KM
		if (floatval($totalKm) >= floatval($autoApprovalKm)) {

			$aspServiceTypeGetResponse = getActivityKMPrices($this->serviceType, $this->asp, $this->data_src_id, $this->case->date);
			if (!$aspServiceTypeGetResponse['success']) {
				$response['success'] = false;
				$response['error'] = $aspServiceTypeGetResponse['error'];
				return $response;
			}

			$saveActivityRatecardResponse = $this->saveActivityRatecard();
			if (!$saveActivityRatecardResponse['success']) {
				$response['success'] = false;
				$response['error'] = $saveActivityRatecardResponse['error'];
				return $response;
			}

			$waitingTime = !empty($this->detail(279)->value) ? floatval($this->detail(279)->value) : 0;
			$waitingCharge = 0;
			if (!empty($aspServiceTypeGetResponse['asp_service_price']->waiting_charge_per_hour) && !empty($waitingTime)) {
				$waitingCharge = numberFormatToDecimalConversion(floatval($waitingTime / 60) * floatval($aspServiceTypeGetResponse['asp_service_price']->waiting_charge_per_hour));
			}

			$kmCharge = $this->calculateKMCharge($aspServiceTypeGetResponse['asp_service_price'], $autoApprovalKm);
			$netAmount = numberFormatToDecimalConversion(floatval(($kmCharge + $notCollectedCharges + $waitingCharge) - $collectedCharges));
			$invoiceAmount = $netAmount;

			$boKmTravelled = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 158,
			]);
			$boKmTravelled->value = $autoApprovalKm;
			$boKmTravelled->save();

			$boCollected = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 159,
			]);
			$boCollected->value = $collectedCharges;
			$boCollected->save();

			$boNotCollected = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 160,
			]);
			$boNotCollected->value = $notCollectedCharges;
			$boNotCollected->save();

			$boPoAmount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 172,
			]);
			$boPoAmount->value = $kmCharge;
			$boPoAmount->save();

			$boNetAmount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 176,
			]);
			$boNetAmount->value = $netAmount;
			$boNetAmount->save();

			$boInvoiceAmount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $this->id,
				'key_id' => 182,
			]);
			$boInvoiceAmount->value = $invoiceAmount;
			$boInvoiceAmount->save();
		} else {
			// WITHIN PREDEFINED AUTO APPROVAL KM THEN APPROVE THE GIVEN KM (TO CHECK GIVEN SERVICE TYPE EXISTS ON ELIGIBLE SERVICE TYPES)
			$aspServiceTypeGetResponse = getActivityKMPrices($this->serviceType, $this->asp, $this->data_src_id, $this->case->date);
			if (!$aspServiceTypeGetResponse['success']) {
				$response['success'] = false;
				$response['error'] = $aspServiceTypeGetResponse['error'];
				return $response;
			}
		}

		// UPDATE STATUS
		$this->status_id = 25; // Waiting for Charges Acceptance by ASP
		$this->updated_by_id = 72;
		$this->updated_at = Carbon::now();
		$this->save();

		//LOG SAVE
		$activityLog = ActivityLog::firstOrNew([
			'activity_id' => $this->id,
		]);
		$activityLog->bo_approved_at = Carbon::now();
		$activityLog->bo_approved_by_id = 72;
		$activityLog->updated_by_id = 72;
		$activityLog->updated_at = Carbon::now();
		$activityLog->save();

		$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
		if ($this->asp && !empty($this->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $this->asp->has_whatsapp_flow == 1))) {
			$this->sendBreakdownOrEmptyreturnChargesWhatsappSms();
		}

		$response['success'] = true;
		return $response;
	}

	public function updateApprovalLog() {
		//INDIVIDUAL
		$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_APPROVED_DEFERRED');
		$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_APPROVED');
		logActivity3(config('constants.entity_types.ticket'), $this->id, [
			'Status' => $log_status,
			'Waiting for' => $log_waiting,
		], 361);

		if ($this->asp && !empty($this->asp->contact_number1)) {
			sendSMS2("Tkt waiting for Invoice", $this->asp->contact_number1, [$this->case->number], NULL);
		}

		//sending notification to all BO users
		if ($this->asp && !empty($this->asp->user_id)) {
			notify2('BO_APPROVED', $this->asp->user_id, config('constants.alert_type.blue'), [$this->case->number]);
		}
	}

	public function sendBreakdownOrEmptyreturnChargesWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspCode = !empty($this->asp->asp_code) ? $this->asp->asp_code : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$caseNumber = $this->case ? (!empty($this->case->number) ? $this->case->number : '--') : '--';
		$activityNumber = $this->crm_activity_id;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$serviceType = $this->serviceType ? $this->serviceType->name : '--';
		$distance = $this->detail(158) ? (!empty($this->detail(158)->value) ? $this->detail(158)->value : '--') : '--';
		$payoutAmount = $this->detail(182) ? (!empty($this->detail(182)->value) ? $this->detail(182)->value : '--') : '--';

		$senderNumber = config('constants')['whatsapp_api_sender'];

		$sendBreakdownChargesMail = false;

		//NORMAL PAYOUT (BREAKDOWN CHARGES)
		if ($this->financeStatus && $this->financeStatus->id == 1) {
			$typeId = 1193;
			//ROS SERVICE
			if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
				$templateId = 'charges_details_ros';
				$sendBreakdownChargesMail = true;
			} else {
				//TOW SERVICE
				$templateId = 'charges_details_tow';
			}
		} else {
			//EMPTY RETURN PAYOUT (EMPTY RETURN CHARGES)
			$typeId = 1194;

			//ROS SERVICE
			if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
				$templateId = 'empty_return_charges_ros_new';
				$sendBreakdownChargesMail = true;
			} else {
				//TOW SERVICE
				$templateId = 'empty_return_charges_tow_new';
			}
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $serviceType;
		$bodyParameterValues->{'3'} = $activityNumber;
		$bodyParameterValues->{'4'} = $distance;
		$bodyParameterValues->{'5'} = $payoutAmount;

		$payloadIndexOne = [
			"value" => "Yes",
			"activity_id" => $activityNumber,
			"vehicle_no" => $vehicleNumber,
			"type" => "Breakdown Charges",
		];
		$payloadIndexTwo = [
			"value" => "No",
			"activity_id" => $activityNumber,
			"vehicle_no" => $vehicleNumber,
			"type" => "Breakdown Charges",
		];
		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
						"buttons" => [
							"quickReplies" => [
								[
									"index" => "0",
									"payload" => json_encode($payloadIndexOne),
								],
								[
									"index" => "1",
									"payload" => json_encode($payloadIndexTwo),
								],
							],
						],
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		// UPDATE ALREADY RESPONDED LOGS TO OLD
		ActivityWhatsappLog::whereIn('type_id', [1193, 1194, 1195, 1196, 1197, 1198])->where([
			'activity_id' => $this->id,
			'is_new' => 1,
		])
			->update([
				'is_new' => 0,
			]);

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, $typeId, $inputRequests);

		//SEND BREAKDOWN CHARGES EMAIL TO BUSINESS USERS
		$disableActivityWhatsappMailNoty = config('rsa')['DISABLE_ACTIVITY_WHATSAPP_MAIL_NOTY'];
		if ($sendBreakdownChargesMail && !$disableActivityWhatsappMailNoty) {
			// LIVE PURPOSE
			$toMailIds = config('rsa')['ACTIVITY_WHATSAPP_MAIL_NOTY_MAIL_IDS'];

			//TESTING PURPOSE
			// $toMailIds = [
			// 	"ramakrishnan@uitoux.in",
			// 	"sridhar@uitoux.in",
			// 	"karthick.r@uitoux.in",
			// ];
			$arr['content'] = 'The breakdown charges message has been triggered to the ASP(' . $aspCode . ') for the following case.';
			$arr['to_mail_ids'] = $toMailIds;
			$arr['caseNumber'] = $caseNumber;
			$arr['activityId'] = $activityNumber;
			$arr['vehicleNo'] = $vehicleNumber;
			$arr['serviceType'] = $serviceType;
			$arr['company_header'] = view('partials/email-noty-company-header')->render();
			$MailInstance = new ActivityWhatsappMailNoty($arr);
			try {
				$Mail = Mail::send($MailInstance);
			} catch (\Exception $e) {
			}
		}
	}

	public function sendRevisedBreakdownOrEmptyreturnChargesWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$serviceType = $this->serviceType ? $this->serviceType->name : '--';
		$distance = $this->detail(158) ? (!empty($this->detail(158)->value) ? $this->detail(158)->value : '--') : '--';
		$payoutAmount = $this->detail(182) ? (!empty($this->detail(182)->value) ? $this->detail(182)->value : '--') : '--';

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//NORMAL PAYOUT (BREAKDOWN CHARGES)
		if ($this->financeStatus && $this->financeStatus->id == 1) {
			$typeId = 1202;
			$templateId = 'revised_bd_charges';
		} else {
			//EMPTY RETURN PAYOUT (EMPTY RETURN CHARGES)
			$typeId = 1203;
			$templateId = 'revised_empty_return_charges';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $serviceType;
		$bodyParameterValues->{'3'} = $activityNumber;
		$bodyParameterValues->{'4'} = $distance;
		$bodyParameterValues->{'5'} = $payoutAmount;

		$payloadIndexOne = [
			"value" => "Yes",
			"activity_id" => $activityNumber,
			"vehicle_no" => $vehicleNumber,
			"type" => "Revised Breakdown Charges",
		];
		$payloadIndexTwo = [
			"value" => "No",
			"activity_id" => $activityNumber,
			"vehicle_no" => $vehicleNumber,
			"type" => "Revised Breakdown Charges",
		];
		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
						"buttons" => [
							"quickReplies" => [
								[
									"index" => "0",
									"payload" => json_encode($payloadIndexOne),
								],
								[
									"index" => "1",
									"payload" => json_encode($payloadIndexTwo),
								],
							],
						],
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		// UPDATE ALREADY RESPONDED LOGS TO OLD
		ActivityWhatsappLog::whereIn('type_id', [1193, 1194, 1195, 1196, 1197, 1198, 1202, 1203])->where([
			'activity_id' => $this->id,
			'is_new' => 1,
		])
			->update([
				'is_new' => 0,
			]);

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, $typeId, $inputRequests);
	}

	public function sendAspAcceptanceChargesWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$serviceType = $this->serviceType ? $this->serviceType->name : '--';

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//ROS SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = '_asp_charges_acceptance_ros_new';
		} else {
			//TOW SERVICE
			$templateId = 'asp_charges_acceptance_tow_new';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $serviceType;
		$bodyParameterValues->{'3'} = $activityNumber;

		$payloadIndexOne = [
			"value" => "Yes",
			"activity_id" => $activityNumber, //crm_act_id
			"vehicle_no" => $vehicleNumber,
			"type" => "ASP Charges Acceptance",
		];
		$payloadIndexTwo = [
			"value" => "No",
			"activity_id" => $activityNumber, //crm_act_id
			"vehicle_no" => $vehicleNumber,
			"type" => "ASP Charges Acceptance",
		];

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
						"buttons" => [
							"quickReplies" => [
								[
									"index" => "0",
									"payload" => json_encode($payloadIndexOne),
								],
								[
									"index" => "1",
									"payload" => json_encode($payloadIndexTwo),
								],
							],
						],
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		// UPDATE ALREADY RESPONDED LOGS TO OLD
		ActivityWhatsappLog::whereIn('type_id', [1197, 1198])->where([
			'activity_id' => $this->id,
			'is_new' => 1,
		])
			->update([
				'is_new' => 0,
			]);

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1195, $inputRequests);
	}

	public function sendAspChargesRejectionWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$serviceType = $this->serviceType ? $this->serviceType->name : '--';

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//ROS SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = 'asp_charges_rejection_ros';
		} else {
			//TOW SERVICE
			$templateId = 'asp_charges_rejection_tow';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $serviceType;
		$bodyParameterValues->{'3'} = $activityNumber;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1196, $inputRequests);
	}

	public function sendIndividualInvoicingWhatsappSms($invoiceId) {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$serviceType = $this->serviceType ? $this->serviceType->name : '--';

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//ROS SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = 'asp_for_single_invoicing_ros';
		} else {
			//TOW SERVICE
			$templateId = 'asp_for_single_invoicing_tow';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $serviceType;
		$bodyParameterValues->{'3'} = $activityNumber;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => true,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"media" => [
							"type" => "document",
							"url" => URL::asset('storage/app/public/invoices/' . $invoiceId . '.pdf'),
							"fileName" => "invoice-copy.pdf",
						],
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1197, $inputRequests);
	}

	public function sendBulkInvoicingWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//ROS SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = 'asp_for_bulk_invoicing_ros';
		} else {
			//TOW SERVICE
			$templateId = 'asp_for_bulk_invoicing_tow';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $activityNumber;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1198, $inputRequests);
	}

	public function sendInvoiceAlreadyGeneratedWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$vin = $this->case ? (!empty($this->case->vin_no) ? $this->case->vin_no : '--') : '--';
		$vehicleNumber = $this->case ? (!empty($this->case->vehicle_registration_number) ? $this->case->vehicle_registration_number : $vin) : '--';
		$activityNumber = $this->crm_activity_id;

		$senderNumber = config('constants')['whatsapp_api_sender'];
		$templateId = 'invoice_generated_2110';

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $vehicleNumber;
		$bodyParameterValues->{'2'} = $activityNumber;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1201, $inputRequests);
	}

	public function sendActivityCancelledWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;
		$activityNumber = $this->crm_activity_id;

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//ROS SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = 'asp_for_bulk_invoicing_ros_1';
		} else {
			//TOW SERVICE
			$templateId = 'asp_for_bulk_invoicing_tow_1';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;
		$bodyParameterValues->{'1'} = $activityNumber;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1199, $inputRequests);
	}

	public function sendMorethanOneInputFromQuickReplyWhatsappSms() {
		$aspName = !empty($this->asp->name) ? $this->asp->name : '--';
		$aspWhatsAppNumber = $this->asp->whatsapp_number;

		$senderNumber = config('constants')['whatsapp_api_sender'];

		//ROS SERVICE
		if ($this->serviceType && !empty($this->serviceType->service_group_id) && $this->serviceType->service_group_id != 3) {
			$templateId = 'asp_for_bulk_invoicing_ros_3';
		} else {
			//TOW SERVICE
			$templateId = 'asp_for_bulk_invoicing_tow_3';
		}

		$bodyParameterValues = new \stdClass();
		$bodyParameterValues->{'0'} = $aspName;

		$inputRequests = [
			"message" => [
				"channel" => "WABA",
				"content" => [
					"preview_url" => false,
					"type" => "MEDIA_TEMPLATE",
					"mediaTemplate" => [
						"templateId" => $templateId,
						"bodyParameterValues" => $bodyParameterValues,
					],
				],
				"recipient" => [
					"to" => "91" . $aspWhatsAppNumber,
					"recipient_type" => "individual",
				],
				"sender" => [
					"from" => $senderNumber,
				],
				"preferences" => [
					"webHookDNId" => "1001",
				],
			],
			"metaData" => [
				"version" => "v1.0.9",
			],
		];

		//SEND WHATSAPP SMS
		sendWhatsappSMS($this->id, 1200, $inputRequests);
	}

	//CHECK ASP HAS SERVICE TYPE AMENDMENT IF NOT EXIST THEN TAKE IT FROM ASP SERVICE TYPE (GET RATE CARD DETAILS)
	public static function getAspServiceRateCardByAmendment($aspId, $caseDate, $serviceTypeId, $isMobile) {

		//CHECK IF IT HAS AMENDMENT SERVICE TYPE
		$aspAmendmentServiceTypeExistBaseQuery = AspAmendmentServiceType::select([
			'asp_amendment_service_types.range_limit',
			'asp_amendment_service_types.below_range_price',
			'asp_amendment_service_types.above_range_price',
			'asp_amendment_service_types.waiting_charge_per_hour',
			'asp_amendment_service_types.empty_return_range_price',
			'asp_amendment_service_types.fleet_count',
			'asp_amendment_service_types.is_mobile',
		])
			->join('asp_amendments', 'asp_amendments.id', 'asp_amendment_service_types.amendment_id')
			->where('asp_amendment_service_types.asp_id', $aspId)
			->where('asp_amendment_service_types.service_type_id', $serviceTypeId)
			->where('asp_amendment_service_types.is_mobile', $isMobile)
			->where('asp_amendments.status_id', 1307) //APPROVED
		;

		$aspAmendmentNewServiceTypeExistSubQuery = clone $aspAmendmentServiceTypeExistBaseQuery;
		$aspAmendmentNewServiceTypeExist = $aspAmendmentNewServiceTypeExistSubQuery->where('asp_amendment_service_types.effective_from', '<=', date('Y-m-d', strtotime($caseDate)))
			->where('asp_amendment_service_types.type_id', 1311) //NEW
			->orderBy('asp_amendment_service_types.amendment_id', 'desc')
			->first();

		if ($aspAmendmentNewServiceTypeExist) {
			$aspServiceTypeRateCard = $aspAmendmentNewServiceTypeExist;
			$aspServiceTypeRateCard->adjustment_type = NULL;
			$aspServiceTypeRateCard->adjustment = NULL;
			$aspServiceTypeRateCard->below_range_price_margin = NULL;
			$aspServiceTypeRateCard->above_range_price_margin = NULL;
		} else {

			//IF NEW SERVICE TYPE NOT EXIST TAKE OLD ONE
			$aspAmendmentOldServiceTypeExistSubQuery = clone $aspAmendmentServiceTypeExistBaseQuery;
			$aspAmendmentOldServiceTypeExist = $aspAmendmentOldServiceTypeExistSubQuery->where('asp_amendment_service_types.type_id', 1312) //OLD
				->orderBy('asp_amendment_service_types.amendment_id', 'asc')
				->first();

			if ($aspAmendmentOldServiceTypeExist) {
				$aspServiceTypeRateCard = $aspAmendmentOldServiceTypeExist;
				$aspServiceTypeRateCard->adjustment_type = NULL;
				$aspServiceTypeRateCard->adjustment = NULL;
				$aspServiceTypeRateCard->below_range_price_margin = NULL;
				$aspServiceTypeRateCard->above_range_price_margin = NULL;
			} else {
				//ASP SERVICE TYPES
				$aspServiceTypeRateCard = AspServiceType::select([
					'range_limit',
					'below_range_price',
					'above_range_price',
					'waiting_charge_per_hour',
					'empty_return_range_price',
					'adjustment_type',
					'adjustment',
					'below_range_price_margin',
					'above_range_price_margin',
					'fleet_count',
					'is_mobile',
				])
					->where('asp_id', $aspId)
					->where('service_type_id', $serviceTypeId)
					->where('is_mobile', $isMobile)
					->first();
			}

		}

		return $aspServiceTypeRateCard;

	}

	//CHECK ASP HAS SERVICE TYPE AMENDMENT IF NOT EXIST THEN TAKE IT FROM ASP SERVICE TYPE (GET SUB SERVICE LIST)
	public static function getAspServiceTypesByAmendment($aspId, $caseDate, $isMobile) {

		//CHECK IF IT HAS AMENDMENT SERVICE TYPE
		$aspAmendmentServiceTypeExistBaseQuery = AspAmendmentServiceType::select([
			'asp_amendment_service_types.amendment_id',
		])
			->join('asp_amendments', 'asp_amendments.id', 'asp_amendment_service_types.amendment_id')
			->where('asp_amendment_service_types.asp_id', $aspId)
			->where('asp_amendment_service_types.is_mobile', $isMobile)
			->where('asp_amendments.status_id', 1307) //APPROVED
		;

		$aspAmendmentNewServiceTypeExistSubQuery = clone $aspAmendmentServiceTypeExistBaseQuery;
		$aspAmendmentNewServiceTypeExist = $aspAmendmentNewServiceTypeExistSubQuery->where('asp_amendment_service_types.effective_from', '<=', date('Y-m-d', strtotime($caseDate)))
			->where('asp_amendment_service_types.type_id', 1311) //NEW
			->orderBy('asp_amendment_service_types.amendment_id', 'desc')
			->first();

		if ($aspAmendmentNewServiceTypeExist) {
			$aspServiceTypes = AspAmendmentServiceType::select([
				'service_types.name',
				'service_types.id',
			])
				->join('service_types', 'service_types.id', 'asp_amendment_service_types.service_type_id')
				->where('asp_amendment_service_types.amendment_id', $aspAmendmentNewServiceTypeExist->amendment_id)
				->where('asp_amendment_service_types.asp_id', $aspId)
				->where('asp_amendment_service_types.is_mobile', $isMobile)
				->where('asp_amendment_service_types.type_id', 1311) //NEW
				->groupBy('asp_amendment_service_types.service_type_id')
				->get();
		} else {

			//IF NEW SERVICE TYPE NOT EXIST TAKE OLD ONE
			$aspAmendmentOldServiceTypeExistSubQuery = clone $aspAmendmentServiceTypeExistBaseQuery;
			$aspAmendmentOldServiceTypeExist = $aspAmendmentOldServiceTypeExistSubQuery->where('asp_amendment_service_types.type_id', 1312) //OLD
				->orderBy('asp_amendment_service_types.amendment_id', 'asc')
				->first();

			if ($aspAmendmentOldServiceTypeExist) {
				$aspServiceTypes = AspAmendmentServiceType::select([
					'service_types.name',
					'service_types.id',
				])
					->join('service_types', 'service_types.id', 'asp_amendment_service_types.service_type_id')
					->where('asp_amendment_service_types.amendment_id', $aspAmendmentOldServiceTypeExist->amendment_id)
					->where('asp_amendment_service_types.asp_id', $aspId)
					->where('asp_amendment_service_types.is_mobile', $isMobile)
					->where('asp_amendment_service_types.type_id', 1312) //OLD
					->groupBy('asp_amendment_service_types.service_type_id')
					->get();
			} else {
				//ASP SERVICE TYPES
				$aspServiceTypes = AspServiceType::select([
					'service_types.name',
					'service_types.id',
				])
					->join('service_types', 'service_types.id', 'asp_service_types.service_type_id')
					->where('asp_service_types.asp_id', $aspId)
					->where('asp_service_types.is_mobile', $isMobile)
					->groupBy('asp_service_types.service_type_id')
					->get();
			}

		}

		return $aspServiceTypes;

	}

	public static function getEncryptionKey(Request $request) {
		if (empty($request->invoice_ids)) {
			return response()->json([
				'success' => false,
				'error' => 'Please select atleast one activity',
			]);
		}

		$encryption_key = Crypt::encryptString(implode('-', $request->invoice_ids));
		return response()->json([
			'success' => true,
			'encryption_key' => $encryption_key,
		]);
	}

	public static function getApprovedDetails($encryption_key = '', $aspId = null) {
		DB::beginTransaction();
		try {
			if (empty($encryption_key)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activities not found',
					],
				]);
			}
			$decrypt = Crypt::decryptString($encryption_key);
			// $decrypt = decryptStringInv($encryption_key);
			$activity_ids = explode('-', $decrypt);
			if (empty($activity_ids)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activities not found',
					],
				]);
			}

			if (!empty($aspId)) {
				$aspId = $aspId;
			} else {
				$aspId = Auth::user()->asp->id;
			}

			$asp = Asp::with('rm')->find($aspId);
			if (!$asp) {
				return response()->json([
					'success' => false,
					'errors' => [
						'ASP not found',
					],
				]);
			}

			$activityBaseQuery = self::select([
				'cases.number',
				'activities.id',
				'activities.asp_id as asp_id',
				'activities.crm_activity_id',
				'activities.number as activityNumber',
				DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y")as date'),
				'activity_portal_statuses.name as status',
				'call_centers.name as callcenter',
				'cases.vehicle_registration_number',
				'service_types.name as service_type',
				'km_charge.value as km_charge_value',
				'km_travelled.value as km_value',
				'not_collected_amount.value as not_collect_value',
				'waiting_charges.value as waiting_charges',
				'net_amount.value as net_value',
				'collect_amount.value as collect_value',
				'total_amount.value as total_value',
				'total_tax_perc.value as total_tax_perc_value',
				'total_tax_amount.value as total_tax_amount_value',
				'data_sources.name as data_source',
				'service_groups.name as service_group_name',
			])
				->join('cases', 'cases.id', 'activities.case_id')
				->join('call_centers', 'call_centers.id', 'cases.call_center_id')
				->join('service_types', 'service_types.id', 'activities.service_type_id')
				->leftjoin('service_groups', 'service_groups.id', 'service_types.service_group_id')
				->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
				->leftJoin('activity_details as km_charge', function ($join) {
					$join->on('km_charge.activity_id', 'activities.id')
						->where('km_charge.key_id', 172); //BO PO AMOUNT OR KM CHARGE
				})
				->leftJoin('activity_details as km_travelled', function ($join) {
					$join->on('km_travelled.activity_id', 'activities.id')
						->where('km_travelled.key_id', 158); //BO KM TRAVELLED
				})
				->leftJoin('activity_details as net_amount', function ($join) {
					$join->on('net_amount.activity_id', 'activities.id')
						->where('net_amount.key_id', 176); //BO NET AMOUNT
				})
				->leftJoin('activity_details as collect_amount', function ($join) {
					$join->on('collect_amount.activity_id', 'activities.id')
						->where('collect_amount.key_id', 159); //BO COLLECT AMOUNT
				})
				->leftJoin('activity_details as not_collected_amount', function ($join) {
					$join->on('not_collected_amount.activity_id', 'activities.id')
						->where('not_collected_amount.key_id', 160); //BO NOT COLLECT AMOUNT
				})
				->leftJoin('activity_details as waiting_charges', function ($join) {
					$join->on('waiting_charges.activity_id', 'activities.id')
						->where('waiting_charges.key_id', 333); //BO WAITING CHARGE
				})
				->leftJoin('activity_details as total_tax_perc', function ($join) {
					$join->on('total_tax_perc.activity_id', 'activities.id')
						->where('total_tax_perc.key_id', 185); //BO TOTAL TAX PERC
				})
				->leftJoin('activity_details as total_tax_amount', function ($join) {
					$join->on('total_tax_amount.activity_id', 'activities.id')
						->where('total_tax_amount.key_id', 179); //BO TOTAL TAX AMOUNT
				})
				->leftJoin('activity_details as total_amount', function ($join) {
					$join->on('total_amount.activity_id', 'activities.id')
						->where('total_amount.key_id', 182); //BO INVOICE AMOUNT
				})
				->leftjoin('configs as data_sources', 'data_sources.id', 'activities.data_src_id')
				->whereIn('activities.id', $activity_ids)
				->whereIn('activities.status_id', [11, 1]) //Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
				->groupBy('activities.id');

			$activityCountQuery = clone $activityBaseQuery;
			$activitiesCount = $activityCountQuery->get();

			if ($activitiesCount->isEmpty()) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activities not found',
					],
				]);
			}

			//CALCULATE TAX FOR INVOICE
			Invoices::calculateTax($asp, $activity_ids);

			$activities = clone $activityBaseQuery;
			$activities = $activities->get();

			foreach ($activities as $key => $activity) {
				$taxes = DB::table('activity_tax')->leftjoin('taxes', 'activity_tax.tax_id', '=', 'taxes.id')->where('activity_id', $activity->id)->select('taxes.tax_name', 'taxes.tax_rate', 'activity_tax.*')->get();
				$activity->taxes = $taxes;
			}

			//GET INVOICE AMOUNT FROM ACTIVITY DETAIL
			$activity_detail = self::select(
				DB::raw('SUM(bo_invoice_amount.value) as invoice_amount')
			)
				->leftjoin('activity_details as bo_invoice_amount', function ($join) {
					$join->on('bo_invoice_amount.activity_id', 'activities.id')
						->where('bo_invoice_amount.key_id', 182); //BO INVOICE AMOUNT
				})
				->whereIn('activities.id', $activity_ids)
				->first();

			if (!$activity_detail) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Invoice amount not found',
					],
				]);
			}

			$data['activities'] = $activities;
			$data['invoice_amount'] = number_format($activity_detail->invoice_amount, 2);
			$data['invoice_amount_in_word'] = getIndianCurrency($activity_detail->invoice_amount);
			$data['asp'] = $asp;
			$data['inv_no'] = generateInvoiceNumber();
			$data['inv_date'] = date("d-m-Y");
			$data['signature_attachment'] = Attachment::where('entity_id', $asp->id)
				->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))
				->first();
			$data['signature_attachment_path'] = url('storage/' . config('rsa.asp_attachment_path_view'));
			$data['bill_from_details']['company_name'] = config('rsa.SRP_COMPANY_NAME');
			$data['bill_from_details']['registered_office'] = config('rsa.REGISTERED_OFFICE');
			$data['bill_from_details']['cin'] = config('rsa.CIN');
			$data['bill_from_details']['gstin'] = config('rsa.GSTIN');
			$data['bill_from_details']['pan'] = config('rsa.PAN');
			$data['action'] = 'ASP Invoice Confirmation';
			$data['success'] = true;
			$data['new_company_invoice_address'] = config('rsa.NEW_INVOICE_ADDRESS');
			DB::commit();
			return response()->json($data);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}
}
