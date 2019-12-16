<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\RsaCase;
use App\Asp;
use App\Config;
use App\Dealer;
use App\Entity;
use App\Http\Controllers\Controller;
use App\ServiceType;
use DB;
use Illuminate\Http\Request;
use Validator;

class ActivityController extends Controller {
	private $successStatus = 200;

	public function save(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				//Asp Code
				'asp_code' => 'required|string|max:24|exists:asps,asp_code',
				//Ticket No
				'case_number' => 'required|string|max:32|exists:cases,number',
				//Case Status
				'case_status' => 'required|string|max:191|exists:case_statuses,name',
				//Service
				'service' => 'required|string|max:50|exists:service_groups,name',
				//Sub Service
				'sub_service' => 'required|string|max:50|exists:service_types,name',
				//ASP Status
				'asp_status' => 'nullable|string|max:191|exists:activity_asp_statuses,name',
				'asp_activity_rejected_reason' => 'nullable|string|max:191|exists:asp_activity_rejected_reasons,name',
				'asp_po_accepted' => 'nullable|string|max:50',
				'asp_po_rejected_reason' => 'nullable|string|max:191|exists:asp_po_rejected_reasons,name',
				'status' => 'nullable|string|max:191|exists:activity_portal_statuses,name',
				'activity_status' => 'nullable|string|max:191|exists:activity_statuses,name',
				//Service Description
				'service_description' => 'nullable|string|max:255',
				//Amount
				'amount' => 'nullable|numeric',
				//Remarks
				'remarks' => 'nullable|string|max:255',
				//Drop Location Type
				'drop_location_type' => 'nullable|string|max:24',
				//Drop Dealer
				'drop_dealer' => 'nullable|string|max:64',
				//Drop Location
				'drop_location' => 'nullable|string|max:512',
				//Drop Location Lat
				'drop_location_lat' => 'nullable|numeric',
				//Drop Location Long
				'drop_location_long' => 'nullable|numeric',
				//Extra Short Km
				'excess_km' => 'nullable|numeric',
				'crm_activity_id' => 'required|numeric',
				//Asp Reached Datetime
				'asp_reached_date' => 'nullable|date_format:"Y-m-d H:i:s"',
				//Asp Start Location
				'asp_start_location' => 'nullable|string|max:256',
				//Asp End Location
				'asp_end_location' => 'nullable|string|max:256',
				//Asp BD Google KM
				'asp_bd_google_km' => 'nullable|numeric',
				//BD Dealer Google KM
				'bd_dealer_google_km' => 'nullable|numeric',
				//Return Google KM
				'return_google_km' => 'nullable|numeric',
				//Asp BD Return Empty KM
				'asp_bd_return_empty_km' => 'nullable|numeric',
				//BD Dealer KM
				'bd_dealer_km' => 'nullable|numeric',
				//Return KM
				'return_km' => 'nullable|numeric',
				//Total Travel Google KM
				'total_travel_google_km' => 'nullable|numeric',
				//Paid To
				'paid_to' => 'nullable|string|max:24|exists:configs,name',
				//Payment Mode
				'payment_mode' => 'nullable|string|max:50|exists:entities,name',
				//Payment Receipt No
				'payment_receipt_no' => 'nullable|string|max:24',
				//Service Charges
				'service_charges' => 'nullable|numeric',
				//Membership Charges
				'membership_charges' => 'nullable|numeric',
				//Toll Charges
				'toll_charges' => 'nullable|numeric',
				//Green Tax Charges
				'green_tax_charges' => 'nullable|numeric',
				//Border Charges
				'border_charges' => 'nullable|numeric',
				//Amount Collected From Customer
				'amount_collected_from_customer' => 'nullable|numeric',
				//Amount Refused From Customer
				'amount_refused_by_customer' => 'nullable|numeric',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->all()], $this->successStatus);
			}

			$asp = Asp::where('asp_code', $request->asp_code)->first();
			$case_status = CaseStatus::where('name', $request->case_status)->where('company_id', 1)->first();
			$service_type = ServiceType::where('name', $request->sub_service)->first();
			$asp_status = ActivityAspStatus::where('name', $request->asp_status)->where('company_id', 1)->first();
			if (!$asp_status) {
				$asp_status_id = NULL;
			} else {
				$asp_status_id = $asp_status->id;
			}

			$asp_activity_rejected_reason = AspActivityRejectedReason::where('name', $request->asp_activity_rejected_reason)->where('company_id', 1)->first();
			if (!$asp_activity_rejected_reason) {
				$asp_activity_rejected_reason_id = NULL;
			} else {
				$asp_activity_rejected_reason_id = $asp_activity_rejected_reason->id;
			}

			$asp_po_rejected_reason = AspPoRejectedReason::where('name', $request->asp_po_rejected_reason)->where('company_id', 1)->first();
			if (!$asp_po_rejected_reason) {
				$asp_po_rejected_reason_id = NULL;
			} else {
				$asp_po_rejected_reason_id = $asp_po_rejected_reason->id;
			}

			$activity_status = ActivityStatus::where('name', $request->activity_status)->where('company_id', 1)->first();
			if (!$activity_status) {
				$activity_status_id = NULL;
			} else {
				$activity_status_id = $activity_status->id;
			}

			$drop_location_type = Entity::where('name', $request->drop_location_type)->where('company_id', 1)->first();
			if (!$drop_location_type) {
				$drop_location_type_id = NULL;
			} else {
				$drop_location_type_id = $drop_location_type->id;
			}

			$drop_dealer = Dealer::where('name', $request->drop_dealer)->first();
			if (!$drop_dealer) {
				$drop_dealer_id = NULL;
			} else {
				$drop_dealer_id = $drop_dealer->id;
			}

			$paid_to = Config::where('name', $request->paid_to)->first();
			if (!$paid_to) {
				$paid_to_id = NULL;
			} else {
				$paid_to_id = $paid_to->id;
			}

			$payment_mode = Entity::where('name', $request->payment_mode)->where('company_id', 1)->first();
			if (!$payment_mode) {
				$payment_mode_id = NULL;
			} else {
				$payment_mode_id = $payment_mode->id;
			}

			//CASE STATUS UPDATE
			$case = RsaCase::where('number', $request->case_number)->first();
			$case->status_id = $case_status->id;
			$case->save();

			//ACTIVITY SAVE
			$is_activity_detail_new = true;
			$activity = Activity::firstOrNew([
				'crm_activity_id' => $request->crm_activity_id,
			]);
			if ($activity->exists) {
				$is_activity_detail_new = false;
			}

			//ACTIVITY STATUS SUCCESSFUL
			if ($activity_status_id == 7) {
				$status_id = 2; //INVOICE AMOUNT CALCULATED - WAITING FOR ASP INVOICE AMOUNT CONFIRMATION
				//ASP PO ACCEPTED EXIST
				if ($request->asp_po_accepted) {
					if ($request->asp_po_accepted == 'Accepted') {
						$asp_po_accepted = 1;
						$status_id = 3; //ASP ACCEPTED INVOICE AMOUNT - WAITING FOR INVOICE GENERATION BY ASP
					} else {
						$asp_po_accepted = 0;
						$status_id = 4; //ASP REJECTED INVOICE AMOUNT - WAITING FOR ASP DATA ENTRY
					}
				} else {
					$asp_po_accepted = 0;
				}
			} else {
				$status_id = 1; //WAITING FOR WORK COMPLETION
			}

			$activity->fill($request->all());
			$activity->asp_id = $asp->id;
			$activity->case_id = $case->id;
			$activity->service_type_id = $service_type->id;
			$activity->asp_status_id = $asp_status_id;
			$activity->asp_activity_rejected_reason_id = $asp_activity_rejected_reason_id;
			$activity->asp_po_accepted = $asp_po_accepted;
			$activity->asp_po_rejected_reason_id = $asp_po_rejected_reason_id;
			$activity->status_id = $status_id;
			$activity->activity_status_id = $activity_status_id;
			$activity->drop_location_type_id = $drop_location_type_id;
			$activity->drop_dealer_id = $drop_dealer_id;
			$activity->paid_to_id = $paid_to_id;
			$activity->payment_mode_id = $payment_mode_id;
			$activity->save();
			$activity->number = 'ACT' . $activity->id;
			$activity->save();

			//ACTIVITY FIELDS SAVE
			//UPDATE
			if (!$is_activity_detail_new) {
				$asp_km_travelled = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 154,
				]);
				$asp_km_travelled->company_id = 1;
				$asp_km_travelled->activity_id = $activity->id;
				$asp_km_travelled->key_id = 154;
				$asp_km_travelled->value = $request->total_travel_google_km;
				$asp_km_travelled->created_by_id = 72;
				$asp_km_travelled->updated_by_id = 72;
				$asp_km_travelled->save();

				$asp_collected = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 155,
				]);
				$asp_collected->company_id = 1;
				$asp_collected->activity_id = $activity->id;
				$asp_collected->key_id = 155;
				$asp_collected->value = $request->amount_collected_from_customer;
				$asp_collected->created_by_id = 72;
				$asp_collected->updated_by_id = 72;
				$asp_collected->save();

				$asp_not_collected = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 156,
				]);
				$asp_not_collected->company_id = 1;
				$asp_not_collected->activity_id = $activity->id;
				$asp_not_collected->key_id = 156;
				$asp_not_collected->value = $request->amount_refused_by_customer;
				$asp_not_collected->created_by_id = 72;
				$asp_not_collected->updated_by_id = 72;
				$asp_not_collected->save();

				$asp_service_type = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 157,
				]);
				$asp_service_type->company_id = 1;
				$asp_service_type->activity_id = $activity->id;
				$asp_service_type->key_id = 157;
				$asp_service_type->value = $service_type->id;
				$asp_service_type->created_by_id = 72;
				$asp_service_type->updated_by_id = 72;
				$asp_service_type->save();

				//ACTIVITY STATUS SUCCESSFUL OR ASSIGNED OR CANCELLED
				if ($activity->activity_status_id == 7 || $activity->activity_status_id == 2 || $activity->activity_status_id == 4) {
					//PAYOUT AMOUNT
					$asp_po_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 171,
					]);
					$asp_po_amount->company_id = 1;
					$asp_po_amount->activity_id = $activity->id;
					$asp_po_amount->key_id = 171;
					$asp_po_amount->value = $request->amount;
					$asp_po_amount->created_by_id = 72;
					$asp_po_amount->updated_by_id = 72;
					$asp_po_amount->save();

					//NET AMOUNT (PAYOUT AMOUNT - AMOUNT COLLECTED FROM CUSTOMER)
					$asp_net_amount_val = ((!empty($request->amount) ? $request->amount : 0) - (!empty($request->amount_collected_from_customer) ? $request->amount_collected_from_customer : 0));

					$asp_net_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 175,
					]);
					$asp_net_amount->company_id = 1;
					$asp_net_amount->activity_id = $activity->id;
					$asp_net_amount->key_id = 175;
					$asp_net_amount->value = $asp_net_amount_val;
					$asp_net_amount->created_by_id = 72;
					$asp_net_amount->updated_by_id = 72;
					$asp_net_amount->save();

					//INVOICE AMOUNT (NET AMOUNT + AMOUNT NOT COLLECTED FROM CUSTOMER)
					$asp_invoice_amount_val = ((!empty($asp_net_amount_val) ? $asp_net_amount_val : 0) + (!empty($request->amount_refused_by_customer) ? $request->amount_refused_by_customer : 0));

					$asp_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 181,
					]);
					$asp_amount->company_id = 1;
					$asp_amount->activity_id = $activity->id;
					$asp_amount->key_id = 181;
					$asp_amount->value = $asp_invoice_amount_val;
					$asp_amount->created_by_id = 72;
					$asp_amount->updated_by_id = 72;
					$asp_amount->save();
				}

			} else {
				//NEW
				$cc_km_travelled = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 150,
				]);
				$cc_km_travelled->company_id = 1;
				$cc_km_travelled->activity_id = $activity->id;
				$cc_km_travelled->key_id = 150;
				$cc_km_travelled->value = $request->total_travel_google_km;
				$cc_km_travelled->created_by_id = 72;
				$cc_km_travelled->updated_by_id = 72;
				$cc_km_travelled->save();

				$cc_collected = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 151,
				]);
				$cc_collected->company_id = 1;
				$cc_collected->activity_id = $activity->id;
				$cc_collected->key_id = 151;
				$cc_collected->value = $request->amount_collected_from_customer;
				$cc_collected->created_by_id = 72;
				$cc_collected->updated_by_id = 72;
				$cc_collected->save();

				$cc_not_collected = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 152,
				]);
				$cc_not_collected->company_id = 1;
				$cc_not_collected->activity_id = $activity->id;
				$cc_not_collected->key_id = 152;
				$cc_not_collected->value = $request->amount_refused_by_customer;
				$cc_not_collected->created_by_id = 72;
				$cc_not_collected->updated_by_id = 72;
				$cc_not_collected->save();

				$cc_service_type = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $activity->id,
					'key_id' => 153,
				]);
				$cc_service_type->company_id = 1;
				$cc_service_type->activity_id = $activity->id;
				$cc_service_type->key_id = 153;
				$cc_service_type->value = $service_type->id;
				$cc_service_type->created_by_id = 72;
				$cc_service_type->updated_by_id = 72;
				$cc_service_type->save();

				//ACTIVITY STATUS SUCCESSFUL OR ASSIGNED OR CANCELLED
				if ($activity->activity_status_id == 7 || $activity->activity_status_id == 2 || $activity->activity_status_id == 4) {
					//PAYOUT AMOUNT
					$cc_po_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 170,
					]);
					$cc_po_amount->company_id = 1;
					$cc_po_amount->activity_id = $activity->id;
					$cc_po_amount->key_id = 170;
					$cc_po_amount->value = $request->amount;
					$cc_po_amount->created_by_id = 72;
					$cc_po_amount->updated_by_id = 72;
					$cc_po_amount->save();

					//NET AMOUNT (PAYOUT AMOUNT - AMOUNT COLLECTED FROM CUSTOMER)
					$cc_net_amount_val = ((!empty($request->amount) ? $request->amount : 0) - (!empty($request->amount_collected_from_customer) ? $request->amount_collected_from_customer : 0));

					$cc_net_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 174,
					]);
					$cc_net_amount->company_id = 1;
					$cc_net_amount->activity_id = $activity->id;
					$cc_net_amount->key_id = 174;
					$cc_net_amount->value = $cc_net_amount_val;
					$cc_net_amount->created_by_id = 72;
					$cc_net_amount->updated_by_id = 72;
					$cc_net_amount->save();

					//INVOICE AMOUNT (NET AMOUNT + AMOUNT NOT COLLECTED FROM CUSTOMER)
					$cc_invoice_amount_val = ((!empty($cc_net_amount_val) ? $cc_net_amount_val : 0) + (!empty($request->amount_refused_by_customer) ? $request->amount_refused_by_customer : 0));

					$cc_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 180,
					]);
					$cc_amount->company_id = 1;
					$cc_amount->activity_id = $activity->id;
					$cc_amount->key_id = 180;
					$cc_amount->value = $cc_invoice_amount_val;
					$cc_amount->created_by_id = 72;
					$cc_amount->updated_by_id = 72;
					$cc_amount->save();
				}
			}

			$service_charges = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 162,
			]);
			$service_charges->company_id = 1;
			$service_charges->activity_id = $activity->id;
			$service_charges->key_id = 162;
			$service_charges->value = $request->service_charges;
			$service_charges->created_by_id = 72;
			$service_charges->updated_by_id = 72;
			$service_charges->save();

			$membership_charges = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 163,
			]);
			$membership_charges->company_id = 1;
			$membership_charges->activity_id = $activity->id;
			$membership_charges->key_id = 163;
			$membership_charges->value = $request->membership_charges;
			$membership_charges->created_by_id = 72;
			$membership_charges->updated_by_id = 72;
			$membership_charges->save();

			$toll_charges = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 165,
			]);
			$toll_charges->company_id = 1;
			$toll_charges->activity_id = $activity->id;
			$toll_charges->key_id = 165;
			$toll_charges->value = $request->toll_charges;
			$toll_charges->created_by_id = 72;
			$toll_charges->updated_by_id = 72;
			$toll_charges->save();

			$green_tax_charges = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 166,
			]);
			$green_tax_charges->company_id = 1;
			$green_tax_charges->activity_id = $activity->id;
			$green_tax_charges->key_id = 166;
			$green_tax_charges->value = $request->green_tax_charges;
			$green_tax_charges->created_by_id = 72;
			$green_tax_charges->updated_by_id = 72;
			$green_tax_charges->save();

			$border_charges = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 167,
			]);
			$border_charges->company_id = 1;
			$border_charges->activity_id = $activity->id;
			$border_charges->key_id = 167;
			$border_charges->value = $request->border_charges;
			$border_charges->created_by_id = 72;
			$border_charges->updated_by_id = 72;
			$border_charges->save();

			DB::commit();
			return response()->json(['success' => true, 'message' => 'Activity saved successfully'], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}

	public function getEligiblePOList(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->all()], $this->successStatus);
			}

			$eligible_pos = Activity::select(
				'asps.asp_code',
				'cases.number as case_number',
				'case_statuses.name as case_status',
				'service_types.name as sub_service',
				'service_groups.name as service',
				'activity_asp_statuses.name as asp_status',
				'asp_activity_rejected_reasons.name as asp_activity_rejected_reason',
				DB::raw('IF(activities.asp_po_accepted = 1,"Accepted","Rejected") as asp_po_accepted'),
				'asp_po_rejected_reasons.name as asp_po_rejected_reason',
				'activity_portal_statuses.name as status',
				'activity_statuses.name as activity_status',
				'activities.service_description',
				DB::raw('IF(asp_invoice_amounts.value,asp_invoice_amounts.value,cc_invoice_amounts.value) as amount'),
				'activities.remarks',
				'drop_location_types.name as drop_location_type',
				'dealers.name as drop_dealer',
				'activities.drop_location',
				'activities.drop_location_lat',
				'activities.drop_location_long',
				'activities.excess_km',
				'activities.crm_activity_id',
				'activities.asp_reached_date',
				'activities.asp_start_location',
				'activities.asp_end_location',
				'activities.asp_bd_google_km',
				'activities.bd_dealer_google_km',
				'activities.return_google_km',
				'activities.asp_bd_return_empty_km',
				'activities.bd_dealer_km',
				'activities.return_km',
				'activities.total_travel_google_km',
				'activities.asp_bd_google_km',
				'paidto.name as paid_to',
				'payment_modes.name as payment_mode',
				'activities.payment_receipt_no',
				'service_c.value as service_charges',
				'membership_c.value as membership_charges',
				'toll_c.value as toll_charges',
				'green_tax_c.value as green_tax_charges',
				'border_c.value as border_charges',
				DB::raw('IF(asp_not_collected.value,asp_not_collected.value,cc_not_collected.value) as amount_refused_by_customer'),
				DB::raw('IF(asp_collected.value,asp_collected.value,cc_collected.value) as amount_collected_from_customer')
			)
				->leftjoin('asps', 'asps.id', 'activities.asp_id')
				->leftjoin('cases', 'cases.id', 'activities.case_id')
				->leftjoin('case_statuses', 'case_statuses.id', 'cases.status_id')
				->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
				->leftjoin('service_groups', 'service_groups.id', 'service_types.service_group_id')
				->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
				->leftjoin('asp_activity_rejected_reasons', 'asp_activity_rejected_reasons.id', 'activities.asp_activity_rejected_reason_id')
				->leftjoin('asp_po_rejected_reasons', 'asp_po_rejected_reasons.id', 'activities.asp_po_rejected_reason_id')
				->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
				->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
				->leftjoin('activity_details as cc_invoice_amounts', function ($join) {
					$join->on('cc_invoice_amounts.activity_id', 'activities.id')
						->where('cc_invoice_amounts.key_id', 180); //CC AMOUNT
				})
				->leftjoin('activity_details as asp_invoice_amounts', function ($join) {
					$join->on('asp_invoice_amounts.activity_id', 'activities.id')
						->where('asp_invoice_amounts.key_id', 181); //ASP AMOUNT
				})
				->leftjoin('entities as drop_location_types', 'drop_location_types.id', 'activities.drop_location_type_id')
				->leftjoin('dealers', 'dealers.id', 'activities.drop_dealer_id')
				->leftjoin('configs as paidto', 'paidto.id', 'activities.paid_to_id')
				->leftjoin('entities as payment_modes', 'payment_modes.id', 'activities.payment_mode_id')
				->leftjoin('activity_details as service_c', function ($join) {
					$join->on('service_c.activity_id', 'activities.id')
						->where('service_c.key_id', 162); //SERVICE CHARGES
				})
				->leftjoin('activity_details as membership_c', function ($join) {
					$join->on('membership_c.activity_id', 'activities.id')
						->where('membership_c.key_id', 163); //MEMBERSHIP CHARGES
				})
				->leftjoin('activity_details as toll_c', function ($join) {
					$join->on('toll_c.activity_id', 'activities.id')
						->where('toll_c.key_id', 165); //TOLL CHARGES
				})
				->leftjoin('activity_details as green_tax_c', function ($join) {
					$join->on('green_tax_c.activity_id', 'activities.id')
						->where('green_tax_c.key_id', 166); //GREEN TAX CHARGES
				})
				->leftjoin('activity_details as border_c', function ($join) {
					$join->on('border_c.activity_id', 'activities.id')
						->where('border_c.key_id', 167); //BORDER CHARGES
				})
				->leftjoin('activity_details as cc_collected', function ($join) {
					$join->on('cc_collected.activity_id', 'activities.id')
						->where('cc_collected.key_id', 151); //CC COLLECTED
				})
				->leftjoin('activity_details as cc_not_collected', function ($join) {
					$join->on('cc_not_collected.activity_id', 'activities.id')
						->where('cc_not_collected.key_id', 152); //CC NOT COLLECTED
				})
				->leftjoin('activity_details as asp_collected', function ($join) {
					$join->on('asp_collected.activity_id', 'activities.id')
						->where('asp_collected.key_id', 155); //ASP COLLECTED
				})
				->leftjoin('activity_details as asp_not_collected', function ($join) {
					$join->on('asp_not_collected.activity_id', 'activities.id')
						->where('asp_not_collected.key_id', 156); //ASP NOT COLLECTED
				})
				->where('activities.asp_po_accepted', 1) //ASP ACCEPTED
				->groupBy('activities.id')
				->get();

			DB::commit();
			return response()->json([
				'success' => true,
				'eligible_pos' => $eligible_pos,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}

}
