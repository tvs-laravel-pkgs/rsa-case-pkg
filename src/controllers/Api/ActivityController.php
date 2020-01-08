<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\RsaCase;
use App\Asp;
use App\Config;
use App\Http\Controllers\Controller;
use App\ServiceType;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class ActivityController extends Controller {
	private $successStatus = 200;

	public function createActivity(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'crm_activity_id' => 'required|numeric|unique:activities',
				'data_src' => 'required|string',
				'asp_code' => 'required|string|max:24|exists:asps,asp_code',
				'case_number' => 'required|string|max:32|exists:cases,number',
				'sub_service' => 'required|string|max:50|exists:service_types,name',
				'asp_accepted_cc_details' => 'required|numeric',
				'reason_for_asp_rejected_cc_details' => 'nullable|string',
				'finance_status' => [
					'required',
					'string',
					'max:191',
					Rule::exists('activity_finance_statuses', 'name')
						->where(function ($query) {
							$query->where('company_id', 1);
						}),
				],

				// 'asp_po_accepted' => 'nullable|numeric|max:1',
				// 'asp_po_rejected_reason' => 'nullable|string|max:191|exists:asp_po_rejected_reasons,name',
				'asp_activity_status' => 'nullable|string|max:191|exists:activity_asp_statuses,name',
				'asp_activity_rejected_reason' => 'nullable|string|max:191|exists:asp_activity_rejected_reasons,name',
				'activity_status' => 'nullable|string|max:191|exists:activity_statuses,name',
				'cc_colleced_amount' => 'nullable|numeric',
				'cc_not_collected_amount' => 'nullable|numeric',
				'cc_total_km' => 'nullable|numeric',
				'description' => 'nullable|string|max:255',
				'remarks' => 'nullable|string|max:255',
				'asp_reached_date' => 'nullable|date_format:"Y-m-d H:i:s"',
				'asp_start_location' => 'nullable|string|max:256',
				'asp_end_location' => 'nullable|string|max:256',
				'onward_google_km' => 'nullable|numeric',
				'dealer_google_km' => 'nullable|numeric',
				'return_google_km' => 'nullable|numeric',
				'onward_km' => 'nullable|numeric',
				'dealer_km' => 'nullable|numeric',
				'return_km' => 'nullable|numeric',
				'drop_location_type' => 'nullable|string|max:24',
				'drop_dealer' => 'nullable|string|max:64',
				'drop_location' => 'nullable|string|max:512',
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
				'amount_collected_from_customer' => 'nullable|numeric',
				'amount_refused_by_customer' => 'nullable|numeric',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
			//ALLOW ONLY LETTERS AND NUMBERS
			if (!preg_match("/^[a-zA-Z0-9]+$/", $request->case_number)) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Invalid Case Number",
					],
				], $this->successStatus);
			}

			$data_src = Config::where([
				'entity_type_id' => 22,
				'name' => $request->data_src,
			])->first();
			if (!$data_src) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid Data Source',
					],
				], $this->successStatus);
			}

			$asp = Asp::where('asp_code', $request->asp_code)->first();

			//CHECK ASP IS NOT ACTIVE
			if (!$asp->is_active) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'ASP is inactive',
					],
				], $this->successStatus);
			}

			//ASP ACCEPTED CC DETAILS == 0 -- REASON IS MANDATORY
			if (!$request->asp_accepted_cc_details) {
				if (!$request->reason_for_asp_rejected_cc_details) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Reason for ASP rejected cc details is required',
						],
					], $this->successStatus);
				}
			}

			if ($request->drop_location_type && strtolower($request->drop_location_type) != 'garage' && strtolower($request->drop_location_type) != 'dealer' && strtolower($request->drop_location_type) != 'customer preferred') {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid drop_location_type',
					],
				], $this->successStatus);
			}

			if ($request->paid_to && strtolower($request->paid_to) != 'asp' && strtolower($request->paid_to) != 'online') {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid paid_to',
					],
				], $this->successStatus);
			}

			if ($request->payment_mode && strtolower($request->payment_mode) != 'cash' && strtolower($request->payment_mode) != 'paytm' && strtolower($request->payment_mode) != 'online') {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid payment_mode',
					],
				], $this->successStatus);
			}

			$service_type = ServiceType::where('name', $request->sub_service)->first();
			$asp_status = ActivityAspStatus::where('name', $request->asp_activity_status)->where('company_id', 1)->first();
			if (!$asp_status) {
				$asp_activity_status_id = NULL;
			} else {
				$asp_activity_status_id = $asp_status->id;
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

			$case = RsaCase::where('number', $request->case_number)->first();
			//CHECK CASE IS CLOSED
			// if ($case->status_id == 4) {
			// 	return response()->json([
			// 		'success' => false,
			// 		'error' => 'Validation Error',
			// 		'errors' => [
			// 			'Case already closed',
			// 		],
			// 	], $this->successStatus);
			// }

			$activity = new Activity([
				'crm_activity_id' => $request->crm_activity_id,
			]);
			$activity->fill($request->all());

			$finance_status = ActivityFinanceStatus::where([
				'company_id' => 1,
				'name' => $request->finance_status,
			])->first();
			$activity->finance_status_id = $finance_status->id;

			$activity->asp_id = $asp->id;
			$activity->case_id = $case->id;
			$activity->service_type_id = $service_type->id;
			$activity->asp_activity_status_id = $asp_activity_status_id;
			$activity->asp_activity_rejected_reason_id = $asp_activity_rejected_reason_id;

			//ASP ACCEPTED CC DETAILS == 1 AND ACTIVITY STATUS SUCCESSFUL OLD
			// if ($request->asp_accepted_cc_details && $activity_status_id == 7) {
			//ASP ACCEPTED CC DETAILS == 1
			if ($request->asp_accepted_cc_details) {
				//Invoice Amount Calculated - Waiting for Case Closure
				$activity->status_id = 10;
			} else {
				//ASP Rejected CC Details - Waiting for ASP Data Entry
				$activity->status_id = 2;
			}
			$activity->activity_status_id = $activity_status_id;
			$activity->data_src_id = $data_src->id;
			$activity->save();
			$activity->number = 'ACT' . $activity->id;
			$activity->save();

			if ($case->status_id == 3) {
				//CANCELLED
				$activity->update([
					// Not Eligible for Payout
					'status_id' => 15,
				]);
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
				$detail->value = $request->{$activity_field->name};
				$detail->save();
			}

			//CALCULATE PAYOUT ONLY IF FINANCE STATUS OF ACTIVITY IS ELIBLE FOR PO
			if ($activity->financeStatus->po_eligibility_type_id == 342) {
				//No Payout status
				$activity->status_id = 15;
				$activity->save();
			} else {
				$response = $activity->calculatePayoutAmount('CC');
				if (!$response['success']) {

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							$response['error'],
						],
					], $this->successStatus);

				}
			}

			//MARKING AS OWN PATROL ACTIVITY
			if ($activity->asp->workshop_type == 1) {
				//Own Patrol Activity - Not Eligible for Payout
				$activity->status_id = 16;
				$activity->save();
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity saved successfully',
				'activity' => $activity,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

	public function getInvoiceableActivities(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$asp = Asp::select(
				'id',
				'asp_code',
				'is_auto_invoice'
			)
				->where([
					'asp_code' => $request->asp_code,
				])->first();

			$invoiceable_activities = Activity::select(
				'activities.crm_activity_id',
				'cases.vehicle_registration_number',
				'bo_km_charge.value as km_charge',
				'bo_not_collected_amount.value as cc_not_collected_amount',
				'bo_colleced_amount.value as cc_colleced_amount',
				'bo_po_amount.value as payout_amount'
			)
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('cases', 'cases.id', 'activities.case_id')
				->leftJoin('activity_details as bo_km_charge', function ($join) {
					$join->on('bo_km_charge.activity_id', 'activities.id')
						->where('bo_km_charge.key_id', 152); //BO KM Charge
				})
				->leftJoin('activity_details as bo_not_collected_amount', function ($join) {
					$join->on('bo_not_collected_amount.activity_id', 'activities.id')
						->where('bo_not_collected_amount.key_id', 160); //bo_not_collected_amount
				})
				->leftJoin('activity_details as bo_colleced_amount', function ($join) {
					$join->on('bo_colleced_amount.activity_id', 'activities.id')
						->where('bo_colleced_amount.key_id', 159); //bo_colleced_amount
				})
				->leftJoin('activity_details as bo_po_amount', function ($join) {
					$join->on('bo_po_amount.activity_id', 'activities.id')
						->where('bo_po_amount.key_id', 182); //BO INVOICE AMOUNT
				})
				->whereIn('activities.status_id', [11, 1]) //BO Approved - Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
				->where('cases.status_id', 4) //case closed
				->where('activities.asp_id', $asp->id)
				->orderBy('activities.created_at', 'desc')
				->get();

			DB::commit();
			return response()->json([
				'success' => true,
				'invoiceable_activities' => $invoiceable_activities,
				'asp' => $asp,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false, 'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

	public function rejectActivityPo(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'crm_activity_id' => 'required|numeric|exists:activities,crm_activity_id',
				'asp_po_rejected_reason' => 'required|string',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$activity = Activity::where([
				'crm_activity_id' => $request->crm_activity_id,
			])->first();

			if ($activity->status_id != 1) {
				return response()->json([
					'success' => false,
					'error' => 'Rejection not allowed',
				], $this->successStatus);
			}

			$activity->asp_po_accepted = 0;
			$activity->status_id = 4;
			$activity->asp_po_rejected_reason = $request->asp_po_rejected_reason;
			$activity->save();

			DB::commit();
			return response()->json([
				'success' => true,
				'mesage' => 'Status updated successfully!',
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false, 'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

}
