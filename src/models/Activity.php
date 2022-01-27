<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\ImportCronJobPkg\ImportCronJob;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityRatecard;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\CaseCancelledReason;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\RsaCase;
use App\Asp;
use App\AspServiceType;
use App\Attachment;
use App\CallCenter;
use App\Client;
use App\Company;
use App\Config;
use App\ServiceType;
use App\Subject;
use App\VehicleMake;
use App\VehicleModel;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
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
		'deduction_reason',
		'bo_comments',
		'defer_reason',
		'is_exceptional_check',
		'exceptional_reason',
		'general_remarks',
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

	public static function searchMembershipTicket($r) {
		$key = $r->key;
		$list = self::select([
			'activities.id',
			'cases.number',
			'asps.asp_code',
			'service_types.name as service_type',
		])
			->join('cases', 'cases.id', 'activities.case_id')
			->join('asps', 'asps.id', 'activities.asp_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->where(function ($q) use ($key) {
				$q->where('cases.number', 'like', '%' . $key . '%')
				;
			})
			->where('activities.activity_status_id', '!=', 4) //OTHER THAN CANCELLED
			->get();
		return response()->json($list);
	}

	public static function getFormData($id = NULL, $for_deffer_activity) {
		$data = [];

		$data['activity'] = $activity = self::with([
			'case',
			'serviceType',
		])
			->findOrFail($id);

		$isMobile = 0; //WEB
		//MOBILE APP
		if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
			$isMobile = 1;
		}

		$data['service_types'] = Asp::where('asps.user_id', Auth::id())
			->where('asp_service_types.is_mobile', $isMobile)
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
			->where('is_mobile', $isMobile)
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
			->where('asp_id', $this->asp->id)
			->where('service_type_id', $this->serviceType->id)
			->where('is_mobile', $isMobile)
			->first();

		if (!$aspServiceTypeRateCard) {
			return [
				'success' => false,
				'error' => 'Service (' . $this->serviceType->name . ') not enabled for ASP (' . $this->asp->asp_code . ')',
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

	public function calculatePayoutAmount($data_src) {
		if ($this->financeStatus->po_eligibility_type_id == 342) {
			//No Payout
			return [
				'success' => true,
				'error' => 'Not Eligible for Payout',
			];
		}

		if ($data_src == 'CC') {
			$response = getActivityKMPrices($this->serviceType, $this->asp, $this->data_src_id);
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
			$response = ImportCronJob::getRecordsFromExcel($job, 'BS');
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

					$validator = Validator::make($record, [
						//CASE
						'case_number' => 'required|string|max:32',
						'case_date' => 'required',
						'case_data_filled_date' => 'required',
						'case_description' => 'nullable|string|max:255',
						'status' => [
							'required',
							'string',
							'max:191',
							Rule::exists('case_statuses', 'name')
								->where(function ($query) {
									$query->whereNull('deleted_at');
								}),
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
						'contact_name' => 'nullable|string|max:50',
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
						'membership_type' => 'required|string|max:191',
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
						'bd_lat' => 'nullable',
						'bd_long' => 'nullable',
						'bd_location' => 'nullable|string',
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
						'asp_rejected_cc_details_reason' => 'nullable|string',
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
						'activity_description' => 'nullable|string|max:191',
						'activity_remarks' => 'nullable|string|max:255',
						'asp_reached_date' => 'nullable',
						'asp_start_location' => 'nullable|string',
						'asp_end_location' => 'nullable|string',
						'onward_google_km' => 'nullable|numeric',
						'dealer_google_km' => 'nullable|numeric',
						'return_google_km' => 'nullable|numeric',
						'onward_km' => 'nullable|numeric',
						'dealer_km' => 'nullable|numeric',
						'return_km' => 'nullable|numeric',
						'drop_location_type' => 'nullable|string|max:24',
						'drop_dealer' => 'nullable|string',
						'drop_location' => 'nullable|string',
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
						'octroi_charges' => 'nullable|numeric',
						'excess_charges' => 'nullable|numeric',
						'manual_uploading_remarks' => 'required|string',
					]);

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

					$case_status = CaseStatus::where('name', $record['status'])->where('company_id', 1)->first();
					if (!$case_status) {
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
						$case->save();

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
									}
								}
							}
						}

						if ($case->status_id == 4) {
							//CLOSED
							$case
								->activities()
								->where([
									// Invoice Amount Calculated - Waiting for Case Closure
									'status_id' => 10,
								])
								->update([
									// Case Closed - Waiting for ASP to Generate Invoice
									'status_id' => 1,
								]);
						}

						$activity_save_eligible = true;
						$crm_activity_id = trim($record['crm_activity_id']);
						$activity_exist = Activity::withTrashed()->where('crm_activity_id', $crm_activity_id)->first();
						if (!$activity_exist) {
							$activity = new Activity([
								'crm_activity_id' => $crm_activity_id,
							]);
							$count_variable = 'new_count';
						} else {
							$activity_belongsto_case = Activity::withTrashed()->where('crm_activity_id', $crm_activity_id)
								->where('case_id', $case->id)
								->first();
							if ($activity_belongsto_case) {
								//Allow case with intial staus and not payment processed statuses
								if ($activity_belongsto_case->status_id == 2 || $activity_belongsto_case->status_id == 4 || $activity_belongsto_case->status_id == 1 || $activity_belongsto_case->status_id == 15 || $activity_belongsto_case->status_id == 16 || $activity_belongsto_case->status_id == 17) {
									$activity = Activity::withTrashed()->where('crm_activity_id', $crm_activity_id)->first();
									$count_variable = 'updated_count';
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
								//Invoice Amount Calculated - Waiting for Case Closure
								$activity->status_id = 10;
							} else {
								//CASE IS CLOSED
								if ($case->status_id == 4) {
									//IF MECHANICAL SERVICE GROUP
									if ($service_type->service_group_id == 2) {
										$is_bulk = self::checkTicketIsBulk($asp->id, $service_type->id, $record['cc_total_km'], $dataSourceId);
										if ($is_bulk) {
											//ASP Completed Data Entry - Waiting for BO Bulk Verification
											$activity->status_id = 5;
										} else {
											//ASP Completed Data Entry - Waiting for BO Individual Verification
											$activity->status_id = 6;
										}
									} else {
										//ASP Rejected CC Details - Waiting for ASP Data Entry
										$activity->status_id = 2;
									}
								} else {
									//ON HOLD
									$activity->status_id = 17;
								}
							}
							$activity->reason_for_asp_rejected_cc_details = $record['asp_rejected_cc_details_reason'];
							$activity->activity_status_id = $activity_status_id;
							$activity->data_src_id = $dataSourceId; //BO MANUAL
							$activity->save();

							$towingImagesMandatoryEffectiveDate = config('rsa.TOWING_IMAGES_MANDATORY_EFFECTIVE_DATE');
							if (date('Y-m-d') >= $towingImagesMandatoryEffectiveDate) {
								$activity->is_towing_attachments_mandatory = 1;
							} else {
								$activity->is_towing_attachments_mandatory = 0;
							}
							$activity->number = 'ACT' . $activity->id;
							$activity->save();

							if ($case->status_id == 3) {
								if ($activity->financeStatus->po_eligibility_type_id == 342) {
									//CANCELLED
									$activity->update([
										// Not Eligible for Payout
										'status_id' => 15,
									]);
								}
							}

							// CHECK CASE IS CLOSED
							if ($case->status_id == 4) {
								$activity->where([
									// Invoice Amount Calculated - Waiting for Case Closure
									'status_id' => 10,
								])
									->update([
										// Case Closed - Waiting for ASP to Generate Invoice
										'status_id' => 1,
									]);
							}

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
											//ASP Rejected CC Details - Waiting for ASP Data Entry
											$activity->status_id = 2;
										} else {
											//ON HOLD
											$activity->status_id = 17;
										}

										//IF MECHANICAL
										if ($service_type->service_group_id == 2) {
											$is_bulk = self::checkTicketIsBulk($asp->id, $service_type->id, $record['cc_total_km'], $activity->data_src_id);
											if ($is_bulk) {
												//ASP Completed Data Entry - Waiting for BO Bulk Verification
												$activity->status_id = 5;
											} else {
												//ASP Completed Data Entry - Waiting for BO Individual Verification
												$activity->status_id = 6;
											}
										}
									} else {
										//ON HOLD
										$activity->status_id = 17;
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

							//RELEASE ONHOLD ACTIVITIES WITH CLOSED OR CANCELLED CASES
							if ($case->status_id == 4 || $case->status_id == 3) {
								$caseActivities = $case->activities()->where('status_id', 17)->get();
								if ($caseActivities->isNotEmpty()) {
									foreach ($caseActivities as $key => $caseActivity) {
										//MECHANICAL SERVICE GROUP
										if ($caseActivity->serviceType && $caseActivity->serviceType->service_group_id == 2) {
											$cc_total_km = $caseActivity->detail(280) ? $caseActivity->detail(280)->value : 0;
											$isBulk = self::checkTicketIsBulk($caseActivity->asp_id, $caseActivity->serviceType->id, $cc_total_km);
											if ($isBulk) {
												//ASP Completed Data Entry - Waiting for BO Bulk Verification
												$statusId = 5;
											} else {
												//ASP Completed Data Entry - Waiting for BO Individual Verification
												$statusId = 6;
											}
										} else {
											$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
										}
										$caseActivity->update([
											'status_id' => $statusId,
										]);
									}
								}
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
							if ($record['asp_accepted_cc_details']) {
								$activity_log->bo_approved_at = date('Y-m-d H:i:s');
								$activity_log->bo_approved_by_id = $job->created_by_id;
							}
							//NEW
							if (!$activity_log->exists) {
								$activity_log->created_by_id = 72;
							} else {
								$activity_log->updated_by_id = 72;
							}
							$activity_log->save();
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
					$job->save();
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
		$km_charge = $below_range_price + $above_range_price;

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

	public static function checkTicketIsBulk($asp_id, $service_type_id, $asp_km, $dataSourceId) {
		$isMobile = 0; //WEB
		//MOBILE APP
		if ($dataSourceId == 260 || $dataSourceId == 263) {
			$isMobile = 1;
		}

		$is_bulk = true;
		$range_limit = 0;
		$aspServiceType = AspServiceType::where('asp_id', $asp_id)
			->where('service_type_id', $service_type_id)
			->where('is_mobile', $isMobile)
			->first();
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

}
