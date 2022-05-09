<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\RsaCase;
use App\Asp;
use App\Config;
use App\Http\Controllers\Controller;
use App\ServiceType;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class ActivityController extends Controller {
	private $successStatus = 200;

	public function createActivity(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);

		$errors = [];
		DB::beginTransaction();
		try {

			$validator = Validator::make($request->all(), [
				// 'crm_activity_id' => 'required|numeric|unique:activities',
				'crm_activity_id' => 'required|numeric',
				'data_src' => 'required|string',
				'asp_code' => [
					'required',
					'string',
					'max:24',
					Rule::exists('asps', 'asp_code')
						->where(function ($query) {
							$query->where('is_active', 1);
						}),
				],
				'case_number' => [
					'required',
					'string',
					'max:32',
					Rule::exists('cases', 'number')
						->where(function ($query) {
							$query->whereNull('deleted_at');
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
				'reason_for_asp_rejected_cc_details' => 'nullable|string',
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
				// 'asp_po_accepted' => 'nullable|numeric|max:1',
				// 'asp_po_rejected_reason' => 'nullable|string|max:191|exists:asp_po_rejected_reasons,name',
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
				'sla_achieved_delayed' => 'nullable|string|max:30',
				'waiting_time' => 'nullable|numeric',
				'cc_colleced_amount' => 'nullable|numeric',
				'cc_not_collected_amount' => 'nullable|numeric',
				'cc_total_km' => 'nullable|numeric',
				'description' => 'nullable|string',
				'remarks' => 'nullable|string',
				'asp_reached_date' => 'nullable|date_format:"Y-m-d H:i:s"',
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
				'amount_collected_from_customer' => 'nullable|numeric',
				'amount_refused_by_customer' => 'nullable|numeric',
			]);

			if ($validator->fails()) {
				//SAVE ACTIVITY API LOG
				$errors = $validator->errors()->all();
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//ASSIGN ZERO IF IT IS EMPTY
			if (!$request->cc_total_km) {
				$request->cc_total_km = 0;
			}
			if (!$request->cc_not_collected_amount) {
				$request->cc_not_collected_amount = 0;
			}
			if (!$request->cc_colleced_amount) {
				$request->cc_colleced_amount = 0;
			}

			//ALLOW ONLY LETTERS AND NUMBERS
			if (!preg_match("/^[a-zA-Z0-9]+$/", $request->case_number)) {
				//SAVE ACTIVITY API LOG
				$errors[] = 'Invalid Case Number';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

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
			])
				->first();
			if (!$data_src) {
				//SAVE ACTIVITY API LOG
				$errors[] = 'Invalid Data Source';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

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
				//SAVE ACTIVITY API LOG
				$errors[] = 'ASP is inactive';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

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
					//SAVE ACTIVITY API LOG
					$errors[] = 'Reason for ASP rejected cc details is required';
					saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Reason for ASP rejected cc details is required',
						],
					], $this->successStatus);
				}
			}

			if ($request->sla_achieved_delayed && strtolower($request->sla_achieved_delayed) != 'sla not met' && strtolower($request->sla_achieved_delayed) != 'sla met') {
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid sla_achieved_delayed',
					],
				], $this->successStatus);
			}

			if ($request->drop_location_type && strtolower($request->drop_location_type) != 'garage' && strtolower($request->drop_location_type) != 'dealer' && strtolower($request->drop_location_type) != 'customer preferred') {
				//SAVE ACTIVITY API LOG
				$errors[] = 'Invalid drop_location_type';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid drop_location_type',
					],
				], $this->successStatus);
			}

			if ($request->paid_to && strtolower($request->paid_to) != 'asp' && strtolower($request->paid_to) != 'online') {
				//SAVE ACTIVITY API LOG
				$errors[] = 'Invalid paid_to';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid paid_to',
					],
				], $this->successStatus);
			}

			if ($request->payment_mode && strtolower($request->payment_mode) != 'cash' && strtolower($request->payment_mode) != 'paytm' && strtolower($request->payment_mode) != 'online') {
				//SAVE ACTIVITY API LOG
				$errors[] = 'Invalid payment_mode';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

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

			$case_date = date('Y-m-d', strtotime($case->date));
			//August month 2020 cases should not be allowed due to cases were already closed - temporarily
			if ($case_date >= "2020-08-01" && $case_date <= "2020-08-31") {
				//SAVE CASE API LOG
				$errors[] = "Rejected as August month case closed";
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Rejected as August month case closed",
					],
				], $this->successStatus);
			}

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

			//ALLOW ACTIVITY CREATION OR UPDATION ONLY BEFORE 90 DAYS OF THE CASE DATE
			$caseDate = Carbon::parse($case_date);
			$caseDateAfter90Days = date('Y-m-d', strtotime($caseDate->addDays(90)));
			if (date('Y-m-d') > $caseDateAfter90Days) {
				//SAVE ACTIVITY API LOG
				$errors[] = 'Activity creation or updation will not be allowed after 90 days of the case date';
				saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Activity creation or updation will not be allowed after 90 days of the case date',
					],
				], $this->successStatus);
			}

			$activityExist = Activity::withTrashed()->where('crm_activity_id', $request->crm_activity_id)
				->first();
			if (!$activityExist) {
				$activity = new Activity([
					'crm_activity_id' => $request->crm_activity_id,
				]);
			} else {
				//ACTIVITY BELONGS TO SAME CASE
				if ($activityExist->case_id === $case->id) {
					//Allow case with intial staus and not payment processed statuses
					if ($activityExist->status_id == 2 || $activityExist->status_id == 4 || $activityExist->status_id == 1 || $activityExist->status_id == 15 || $activityExist->status_id == 16 || $activityExist->status_id == 17) {
						$activity = $activityExist;
					} else {
						//SAVE ACTIVITY API LOG
						$errors[] = 'Activity update will not be allowed. Case is under payment process';
						saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
						DB::commit();

						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'Activity update will not be allowed. Case is under payment process',
							],
						], $this->successStatus);
					}
				} else {
					//SAVE ACTIVITY API LOG
					$errors[] = 'The crm activity id has already been taken for another case';
					saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'The crm activity id has already been taken for another case',
						],
					], $this->successStatus);
				}
			}

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
				//CASE IS CLOSED
				if ($case->status_id == 4) {
					//IF SERVICE GROUP IS MECHANICAL
					if ($service_type->service_group_id == 2) {
						$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $data_src->id);
						if ($is_bulk) {
							//ASP Completed Data Entry - Waiting for L1 Bulk Verification
							$activity->status_id = 5;
						} else {
							//ASP Completed Data Entry - Waiting for L1 Individual Verification
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
			$activity->activity_status_id = $activity_status_id;
			$activity->data_src_id = $data_src->id;
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
				$detail->value = isset($request->{$activity_field->name}) ? $request->{$activity_field->name} : NULL;
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

					//SAVE ACTIVITY API LOG
					DB::rollBack();
					$errors[] = $response['error'];
					saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
					// DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							$response['error'],
						],
					], $this->successStatus);

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

						//IF MECHANICAL SERVICE GROUP
						if ($service_type->service_group_id == 2) {
							$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $activity->data_src_id);
							if ($is_bulk) {
								//ASP Completed Data Entry - Waiting for L1 Bulk Verification
								$activity->status_id = 5;
							} else {
								//ASP Completed Data Entry - Waiting for L1 Individual Verification
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

			//RELEASE ONHOLD ACTIVITIES WITH CLOSED OR CANCELLED CASES
			if (($case->status_id == 4 || $case->status_id == 3) && $activity->status_id == 17) {
				//MECHANICAL SERVICE GROUP
				if ($service_type->service_group_id == 2) {
					$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $activity->data_src_id);
					if ($is_bulk) {
						//ASP Completed Data Entry - Waiting for L1 Bulk Verification
						$statusId = 5;
					} else {
						//ASP Completed Data Entry - Waiting for L1 Individual Verification
						$statusId = 6;
					}
				} else {
					$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
				}
				$activity->status_id = $statusId;
				$activity->save();
			}

			//MARKING AS OWN PATROL ACTIVITY
			if ($activity->asp->workshop_type == 1) {
				//Own Patrol Activity - Not Eligible for Payout
				$activity->status_id = 16;
				$activity->save();
			}

			//UPDATE LOG ACTIVITY AND LOG MESSAGE
			logActivity3(config('constants.entity_types.ticket'), $activity->id, [
				'Status' => 'Imported through API',
				'Waiting for' => 'ASP Data Entry',
			], 361);

			$activity_log = ActivityLog::firstOrNew([
				'activity_id' => $activity->id,
			]);
			$activity_log->imported_at = date('Y-m-d H:i:s');
			$activity_log->asp_data_filled_at = date('Y-m-d H:i:s');
			if ($request->asp_accepted_cc_details) {
				$activity_log->bo_approved_at = date('Y-m-d H:i:s');
			}
			//NEW
			if (!$activity_log->exists) {
				$activity_log->created_by_id = 72;
			} else {
				$activity_log->updated_by_id = 72;
			}
			$activity_log->save();

			//SAVE ACTIVITY API LOG
			saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity saved successfully',
				'activity' => $activity,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//SAVE ACTIVITY API LOG
			$errors[] = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

	public function getInvoiceableActivities(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		$errors = [];
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
			]);

			if ($validator->fails()) {
				//SAVE INVOICEABLE ACTIVITIES API LOG
				$errors = $validator->errors()->all();
				saveApiLog(105, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$asp = Asp::select([
				'id',
				'asp_code',
				DB::raw('IF(has_gst && !is_auto_invoice, false, true) as is_auto_invoice'),
			])
				->where([
					'asp_code' => $request->asp_code,
				])->first();

			$invoiceable_activities = Activity::select(
				DB::raw('CAST(activities.crm_activity_id as UNSIGNED) as crm_activity_id'),
				// 'activities.crm_activity_id',
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
						->where('bo_km_charge.key_id', 172); //BO KM Charge OR PAYOUT AMOUNT
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
				->whereIn('activities.status_id', [11, 1]) //Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
				->where('cases.status_id', 4) //case closed
				->where('activities.data_src_id', '!=', 262) //NOT BO MANUAL
				->where('activities.asp_id', $asp->id)
				->orderBy('activities.created_at', 'desc')
				->get();

			//SAVE INVOICEABLE ACTIVITIES API LOG
			saveApiLog(105, NULL, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'invoiceable_activities' => $invoiceable_activities,
				'asp' => $asp,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//SAVE INVOICEABLE ACTIVITIES API LOG
			$errors[] = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			saveApiLog(105, NULL, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

	public function rejectActivityPo(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		$errors = [];
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'crm_activity_id' => 'required|numeric|exists:activities,crm_activity_id',
				'asp_po_rejected_reason' => 'required|string',
			]);

			if ($validator->fails()) {
				//SAVE REJECT ACTIVITY API LOG
				$errors = $validator->errors()->all();
				saveApiLog(104, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$activity = Activity::where([
				'crm_activity_id' => $request->crm_activity_id,
			])->first();

			//ALLOW REJECTION ONLY FOR (Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice)
			if ($activity->status_id != 1 && $activity->status_id != 11) {
				//SAVE REJECT ACTIVITY API LOG
				$errors[] = 'Rejection not allowed';
				saveApiLog(104, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Rejection not allowed',
				], $this->successStatus);
			}

			$activity->asp_po_accepted = 0;
			$activity->status_id = 4;
			$activity->asp_po_rejected_reason = $request->asp_po_rejected_reason;
			$activity->save();

			//SAVE REJECT ACTIVITY API LOG
			saveApiLog(104, NULL, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'mesage' => 'Status updated successfully!',
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//SAVE REJECT ACTIVITY API LOG
			$errors[] = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			saveApiLog(104, NULL, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			], $this->successStatus);
		}
	}

}
