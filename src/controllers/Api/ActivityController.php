<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityReport;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\ActivityWhatsappLog;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\RsaCase;
use Abs\RsaCasePkg\WhatsappWebhookResponse;
use App\Asp;
use App\Attachment;
use App\Config;
use App\CronLog;
use App\Http\Controllers\Controller;
use App\Invoices;
use App\ServiceType;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

			$errorMessages = [
				'reason_for_asp_rejected_cc_details.regex' => "Special characters are not allowed as the first character for reason for ASP rejected CC details!",
				'asp_activity_rejected_reason.regex' => "Special characters are not allowed as the first character for ASP activity rejected reason!",
				'description.regex' => "Special characters are not allowed as the first character for description!",
				'remarks.regex' => "Special characters are not allowed as the first character for remarks!",
				'asp_start_location.regex' => "Special characters are not allowed as the first character for ASP start location!",
				'asp_end_location.regex' => "Special characters are not allowed as the first character for ASP end location!",
				'drop_location.regex' => "Special characters are not allowed as the first character for drop location!",
			];

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
				'reason_for_asp_rejected_cc_details' => [
					'nullable',
					'string',
					'regex:/^[a-zA-Z0-9]/',
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
					'regex:/^[a-zA-Z0-9]/',
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
				'description' => [
					'nullable',
					'string',
					'regex:/^[a-zA-Z0-9]/',
				],
				'remarks' => [
					'nullable',
					'string',
					'regex:/^[a-zA-Z0-9]/',
				],
				'asp_reached_date' => 'nullable|date_format:"Y-m-d H:i:s"',
				'asp_start_location' => [
					'nullable',
					'string',
					'regex:/^[a-zA-Z0-9]/',
				],
				'asp_end_location' => [
					'nullable',
					'string',
					'regex:/^[a-zA-Z0-9]/',
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
					'regex:/^[a-zA-Z0-9]/',
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
				'octroi_charges' => 'nullable|numeric',
				'excess_charges' => 'nullable|numeric',
				'amount_collected_from_customer' => 'nullable|numeric',
				'amount_refused_by_customer' => 'nullable|numeric',
				'fuel_charges' => 'nullable|numeric',
			], $errorMessages);

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

			if ($request->drop_location_type && strtolower($request->drop_location_type) != 'garage' && strtolower($request->drop_location_type) != 'dealer' && strtolower($request->drop_location_type) != 'customer preferred' && strtolower($request->drop_location_type) != 'na') {
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

			$newActivity = false;
			$activityExist = Activity::withTrashed()->where('crm_activity_id', $request->crm_activity_id)
				->first();
			if (!$activityExist) {
				//ALLOW ACTIVITY CREATION ONLY BEFORE 90 DAYS OF THE CASE DATE
				if (date('Y-m-d') > $caseDateAfter90Days) {
					//SAVE ACTIVITY API LOG
					$errors[] = 'Activity create will not be allowed after 90 days of the case date';
					saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Activity create will not be allowed after 90 days of the case date',
						],
					], $this->successStatus);
				} else {
					$activity = new Activity([
						'crm_activity_id' => $request->crm_activity_id,
					]);
					$newActivity = true;
				}
			} else {
				//ACTIVITY BELONGS TO SAME CASE
				if ($activityExist->case_id === $case->id) {
					//Allow case with intial staus and not payment processed statuses
					if ($activityExist->status_id == 2 || $activityExist->status_id == 4 || $activityExist->status_id == 17) {
						//ALLOW ACTIVITY UPDATION ONLY BEFORE 90 DAYS OF THE CASE DATE
						if (date('Y-m-d') > $caseDateAfter90Days) {
							//SAVE ACTIVITY API LOG
							$errors[] = 'Activity update will not be allowed after 90 days of the case date';
							saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
							DB::commit();

							return response()->json([
								'success' => false,
								'error' => 'Validation Error',
								'errors' => [
									'Activity update will not be allowed after 90 days of the case date',
								],
							], $this->successStatus);
						} else {
							$activity = $activityExist;
						}
					} else {

						//IF IT IS IN NOT ELIGIBLE FOR PAYOUT STATUS
						if ($activityExist->status_id == 15 || $activityExist->status_id == 16) {
							$api_error = $errors[] = 'Activity update will not be allowed. Case is not eligible for payout';
						} else {
							$api_error = $errors[] = 'Activity update will not be allowed. Case is under payment process';
						}

						//SAVE ACTIVITY API LOG

						saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
						DB::commit();

						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								$api_error,
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
				//DISABLED DUE NEW WHATSAPP PROCESS
				// $activity->status_id = 10; //Invoice Amount Calculated - Waiting for Case Closure
				$activity->status_id = 17; //ON HOLD
			} else {
				//CASE IS CLOSED
				if ($case->status_id == 4) {
					// //IF MECHANICAL SERVICE GROUP - DISABLED
					// if ($service_type->service_group_id == 2) {
					// 	$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $data_src->id, $case->date);
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
							if (floatval($request->cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
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

			$activity->saveActivityChargesDetails();

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
							// TOW SERVICE
							if ($service_type->service_group_id == 3) {
								if ($asp->is_corporate == 1 || $activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
									//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
									if (floatval($request->cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
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
							if (floatval($request->cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
								$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
							} else {
								$activity->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
							}
						} else {
							$activity->status_id = 17; //ON HOLD
						}

						// //IF MECHANICAL SERVICE GROUP - DISABLED
						// if ($service_type->service_group_id == 2) {
						// 	$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $activity->data_src_id, $case->date);
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

			if ($case->status_id == 3) {
				if ($activity->financeStatus->po_eligibility_type_id == 342) {
					//CANCELLED
					$activity->update([
						// Not Eligible for Payout
						'status_id' => 15,
					]);
				}
			}

			$disableWhatsappAutoApproval = config('rsa')['DISABLE_WHATSAPP_AUTO_APPROVAL'];
			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];

			//IF ACTIVITY CREATED THEN SEND NEW BREAKDOWN ALERT WHATSAPP SMS TO ASP
			if ($newActivity && $activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
				//OTHER THAN TOW SERVICES || TOW SERVICE WITH CC KM GREATER THAN 2
				if (($service_type->service_group_id != 3 && ($disableWhatsappAutoApproval || (!$disableWhatsappAutoApproval && floatval($request->cc_total_km) > 2))) || ($service_type->service_group_id == 3 && floatval($request->cc_total_km) > 2)) {
					$activity->sendBreakdownAlertWhatsappSms();
				}
			}

			$breakdownAlertSent = Activity::breakdownAlertSent($activity->id);

			// CHECK CASE IS CLOSED
			if ($case->status_id == 4) {

				//SEND BREAKDOWN OR EMPTY RETURN CHARGES WHATSAPP SMS TO ASP
				if ($breakdownAlertSent && $asp && !empty($asp->whatsapp_number) && $activity->status_id == 10 && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $asp->has_whatsapp_flow == 1))) {
					$activity->sendBreakdownOrEmptyreturnChargesWhatsappSms();
				}

				$activity->where([
					// Invoice Amount Calculated - Waiting for Case Closure
					'status_id' => 10,
				])
					->update([
						// Case Closed - Waiting for ASP to Generate Invoice
						'status_id' => 1,
					]);

			}

			//RELEASE ONHOLD / ASP COMPLETED DATA ENTRY - WAITING FOR CALL CENTER DATA ENTRY ACTIVITIES WITH CLOSED OR CANCELLED CASES
			if (($case->status_id == 4 || $case->status_id == 3) && ($activity->status_id == 17 || $activity->status_id == 26)) {
				//WHATSAPP FLOW
				if ($breakdownAlertSent && $activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
					// ROS SERVICE
					if ($service_type->service_group_id != 3) {

						if (!$disableWhatsappAutoApproval) {
							$autoApprovalProcessResponse = $activity->autoApprovalProcess();
							if (!$autoApprovalProcessResponse['success']) {
								//SAVE ACTIVITY API LOG
								DB::rollBack();
								$errors[] = $autoApprovalProcessResponse['error'];
								saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 121);
								return response()->json([
									'success' => false,
									'error' => 'Validation Error',
									'errors' => [
										$autoApprovalProcessResponse['error'],
									],
								], $this->successStatus);
							}
						} else {
							//MECHANICAL SERVICE GROUP
							if ($service_type->service_group_id == 2) {
								$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $activity->data_src_id, $case->date);
								if ($is_bulk) {
									//ASP Completed Data Entry - Waiting for L1 Bulk Verification
									$statusId = 5;
								} else {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$statusId = 6;
								}
							} else {
								if ($asp->is_corporate == 1 || $activity->is_asp_data_entry_done == 1) {
									$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
								} else {
									$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
								}
							}

							//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
							if (floatval($request->cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
								$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
							}

							$activity->status_id = $statusId;
							$activity->save();
						}

					} else {
						// TOW SERVICE
						if ($asp->is_corporate == 1 || $activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
							$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
						} else {
							$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
						}

						//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
						if (floatval($request->cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
							$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
						}

						$activity->status_id = $statusId;
						$activity->save();
					}
				} else {
					// NORMAL FLOW

					//MECHANICAL SERVICE GROUP
					if ($service_type->service_group_id == 2) {
						$is_bulk = Activity::checkTicketIsBulk($asp->id, $service_type->id, $request->cc_total_km, $activity->data_src_id, $case->date);
						if ($is_bulk) {
							//ASP Completed Data Entry - Waiting for L1 Bulk Verification
							$statusId = 5;
						} else {
							//ASP Completed Data Entry - Waiting for L1 Individual Verification
							$statusId = 6;
						}
					} else {
						if ($asp->is_corporate == 1 || $activity->is_asp_data_entry_done == 1) {
							$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
						} else {
							$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
						}
					}

					//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
					if (floatval($request->cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
						$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
					}

					$activity->status_id = $statusId;
					$activity->save();
				}
			}

			//IF ACTIVITY CANCELLED THEN SEND ACTIVITY CANCELLED WHATSAPP SMS TO ASP
			if ($breakdownAlertSent && !empty($activity_status_id) && $activity_status_id == 4 && $activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
				$activity->sendActivityCancelledWhatsappSms();
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
			// if ($request->asp_accepted_cc_details) {
			// 	$activity_log->bo_approved_at = date('Y-m-d H:i:s');
			// }
			//NEW
			if (!$activity_log->exists) {
				$activity_log->created_by_id = 72;
			} else {
				$activity_log->updated_by_id = 72;
			}
			$activity_log->save();

			//SAVE ACTIVITY API LOG
			saveApiLog(103, $request->crm_activity_id, $request->all(), $errors, NULL, 120);

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

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
			$errorMessages = [
				'asp_po_rejected_reason.regex' => "Special characters are not allowed as the first character for ASP PO rejected reason!",
			];
			$validator = Validator::make($request->all(), [
				'crm_activity_id' => 'required|numeric|exists:activities,crm_activity_id',
				'asp_po_rejected_reason' => [
					'required',
					'string',
					'regex:/^[a-zA-Z0-9]/',
				],
			], $errorMessages);

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
			$activity->status_id = 4; //ASP Rejected Invoice Amount - Waiting for ASP Data Entry
			$activity->asp_po_rejected_reason = $request->asp_po_rejected_reason;
			$activity->save();

			//SAVE REJECT ACTIVITY API LOG
			saveApiLog(104, NULL, $request->all(), $errors, NULL, 120);

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

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

	public function whatsappWebhookResponse(Request $request) {
		// dd($request->payload);
		$whatsappWebhookResponse = new WhatsappWebhookResponse();
		$whatsappWebhookResponse->payload = json_encode($request->all());
		$whatsappWebhookResponse->status = 'Started';
		$whatsappWebhookResponse->save();

		DB::beginTransaction();
		try {
			if (!isset($request->payload) || (isset($request->payload) && empty($request->payload))) {
				$whatsappWebhookResponse->errors = 'Payload not found';
				$whatsappWebhookResponse->save();
				DB::commit();
				return response()->json([
					'success' => false,
					'errors' => [
						'Payload not found',
					],
				], $this->successStatus);
			}

			$payload = json_decode(stripslashes($request->payload));
			if (empty($payload)) {
				$whatsappWebhookResponse->errors = 'Payload is empty';
				$whatsappWebhookResponse->save();
				DB::commit();
				return response()->json([
					'success' => false,
					'errors' => [
						'Payload is empty',
					],
				], $this->successStatus);
			}
			$activity = Activity::where('crm_activity_id', $payload->activity_id)->first();
			if (!$activity) {
				$whatsappWebhookResponse->errors = 'Activity not found';
				$whatsappWebhookResponse->save();
				DB::commit();
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
					],
				], $this->successStatus);
			}

			//IF IT IS IN NOT ELIGIBLE FOR PAYOUT STATUS
			if ($activity->status_id == 15 || $activity->status_id == 16) {
				$whatsappWebhookResponse->errors = 'This activity is not eligible for payout';
				$whatsappWebhookResponse->save();
				DB::commit();
				return response()->json([
					'success' => false,
					'errors' => [
						'This activity is not eligible for payout',
					],
				], $this->successStatus);
			}

			$breakdownAlertSent = Activity::breakdownAlertSent($activity->id);
			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
			if ($breakdownAlertSent && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp && $activity->asp->has_whatsapp_flow == 1))) {
				if ($payload->type == "Breakdown Charges" || $payload->type == "Revised Breakdown Charges") {
					if ($activity->asp && !empty($activity->asp->whatsapp_number)) {

						// INITIAL CHARGES
						if ($payload->type == "Breakdown Charges") {
							$chargesSmsActive = ActivityWhatsappLog::where('activity_id', $activity->id)
								->whereIn('type_id', [1193, 1194])
								->where('is_new', 1)
								->first();
							if (!$chargesSmsActive) {
								//SEND MORE THAN ONE INPUT REPLAY WHATSAPP SMS TO ASP
								$activity->sendMorethanOneInputFromQuickReplyWhatsappSms();

								//UPDATE WEBHOOK STATUS
								$whatsappWebhookResponse->status = 'Failed';
								$whatsappWebhookResponse->errors = "ASP already responded to breakdown or empty charges";
								$whatsappWebhookResponse->save();
								DB::commit();
								return response()->json([
									'success' => false,
									'errors' => [
										'ASP already responded to breakdown or empty charges',
									],
								], $this->successStatus);
							}
						} else {
							// REVISED CHARGES
							$revisedChargesSmsActive = ActivityWhatsappLog::where('activity_id', $activity->id)
								->whereIn('type_id', [1202, 1203])
								->where('is_new', 1)
								->first();
							if (!$revisedChargesSmsActive) {
								//SEND MORE THAN ONE INPUT REPLAY WHATSAPP SMS TO ASP
								$activity->sendMorethanOneInputFromQuickReplyWhatsappSms();

								//UPDATE WEBHOOK STATUS
								$whatsappWebhookResponse->status = 'Failed';
								$whatsappWebhookResponse->errors = "ASP already responded to breakdown or empty charges";
								$whatsappWebhookResponse->save();
								DB::commit();
								return response()->json([
									'success' => false,
									'errors' => [
										'ASP already responded to breakdown or empty charges',
									],
								], $this->successStatus);
							}
						}
						$breakdownChargesAlreadyResponded = ActivityWhatsappLog::where('activity_id', $activity->id)
							->whereIn('type_id', [1195, 1196])
							->where('is_new', 1)
							->first();
						if (!$breakdownChargesAlreadyResponded) {
							if ($payload->value == 'Yes') {

								$activity->status_id = 11; // Waiting for Invoice Generation by ASP
								$activity->save();

								$activity->updateApprovalLog();

								//SAVE ACTIVITY REPORT FOR DASHBOARD
								ActivityReport::saveReport($activity->id);

								//SEND ASP ACCEPTANCE CHARGES WHATSAPP SMS TO ASP
								$activity->sendAspAcceptanceChargesWhatsappSms();
							} else {
								$activity->status_id = 2; // ASP Rejected CC Details - Waiting for ASP Data Entry
								$activity->is_asp_data_entry_done = NULL;
								$activity->save();

								//SAVE ACTIVITY REPORT FOR DASHBOARD
								ActivityReport::saveReport($activity->id);

								//SEND ASP CHARGES REJECTION WHATSAPP SMS TO ASP
								$activity->sendAspChargesRejectionWhatsappSms();
							}
							//UPDATE WEBHOOK STATUS
							$whatsappWebhookResponse->status = 'Completed';
							$whatsappWebhookResponse->save();
							DB::commit();
							return response()->json([
								'success' => true,
							], $this->successStatus);
						} else {
							//SEND MORE THAN ONE INPUT REPLAY WHATSAPP SMS TO ASP
							$activity->sendMorethanOneInputFromQuickReplyWhatsappSms();

							//UPDATE WEBHOOK STATUS
							$whatsappWebhookResponse->status = 'Failed';
							$whatsappWebhookResponse->errors = "ASP already responded to breakdown or empty charges";
							$whatsappWebhookResponse->save();
							DB::commit();
							return response()->json([
								'success' => false,
								'errors' => [
									'ASP already responded to breakdown or empty charges',
								],
							], $this->successStatus);
						}
					} else {
						//UPDATE WEBHOOK STATUS
						$whatsappWebhookResponse->status = 'Failed';
						$whatsappWebhookResponse->errors = "ASP not having whatsapp number";
						$whatsappWebhookResponse->save();
						DB::commit();
						return response()->json([
							'success' => false,
							'errors' => [
								'ASP not having whatsapp number',
							],
						], $this->successStatus);
					}
				} elseif ($payload->type == "ASP Charges Acceptance") {
					if ($activity->asp && !empty($activity->asp->whatsapp_number)) {
						$chargesAcceptanceSmsActive = ActivityWhatsappLog::where('activity_id', $activity->id)
							->where('type_id', 1195)
							->where('is_new', 1)
							->first();
						if (!$chargesAcceptanceSmsActive) {
							//SEND MORE THAN ONE INPUT REPLAY WHATSAPP SMS TO ASP
							$activity->sendMorethanOneInputFromQuickReplyWhatsappSms();

							//UPDATE WEBHOOK STATUS
							$whatsappWebhookResponse->status = 'Failed';
							$whatsappWebhookResponse->errors = "ASP already responded to acceptance charges";
							$whatsappWebhookResponse->save();
							DB::commit();
							return response()->json([
								'success' => false,
								'errors' => [
									'ASP already responded to acceptance charges',
								],
							], $this->successStatus);
						}

						$aspChargesAcceptanceAlreadyResponded = ActivityWhatsappLog::where('activity_id', $activity->id)
							->whereIn('type_id', [1197, 1198])
							->where('is_new', 1)
							->first();
						if (!$aspChargesAcceptanceAlreadyResponded) {

							// CHECK INVOICE ALREADY GENERATED
							if ($activity->status_id == 12 || $activity->status_id == 13 || $activity->status_id == 14) {
								//SEND INVOICE ALREADY GENERATED WHATSAPP SMS TO ASP - ENABLE AFTER TEMPLATE GIVEN BY BUSINESS TEAM
								$activity->sendInvoiceAlreadyGeneratedWhatsappSms();

								//UPDATE WEBHOOK STATUS
								$whatsappWebhookResponse->status = 'Failed';
								$whatsappWebhookResponse->errors = 'Invoice already been generated';
								$whatsappWebhookResponse->save();
								DB::commit();
								return response()->json([
									'success' => false,
									'errors' => [
										'Invoice already been generated',
									],
								], $this->successStatus);
							}

							if ($payload->value == 'Yes') {
								//EXCEPT(Case Closed - Waiting for ASP to Generate Invoice AND Waiting for Invoice Generation by ASP)
								if ($activity->status_id != 1 && $activity->status_id != 11) {
									//UPDATE WEBHOOK STATUS
									$whatsappWebhookResponse->status = 'Failed';
									$whatsappWebhookResponse->errors = 'ASP not accepted / case not closed';
									$whatsappWebhookResponse->save();
									DB::commit();
									return response()->json([
										'success' => false,
										'errors' => [
											'ASP not accepted / case not closed',
										],
									], $this->successStatus);
								}

								//GENERATE INVOICE NUMBER
								$invoiceNumber = generateInvoiceNumber();
								$invoiceDate = new Carbon();

								//CREATE INVOICE
								$crmActivityId[] = $activity->crm_activity_id;
								$createInvoiceResponse = Invoices::createInvoice($activity->asp, $crmActivityId, $invoiceNumber, NULL, $invoiceDate, '', true);

								if (!$createInvoiceResponse['success']) {
									DB::rollBack();
									$whatsappWebhookResponse->status = 'Failed';
									$whatsappWebhookResponse->errors = $createInvoiceResponse['message'];
									$whatsappWebhookResponse->save();
									return response()->json([
										'success' => false,
										'errors' => [
											$createInvoiceResponse['message'],
										],
									], $this->successStatus);
								}

								//SEND INDIVIDUAL INVOICING WHATSAPP SMS TO ASP
								$activity->sendIndividualInvoicingWhatsappSms($createInvoiceResponse['invoice']->id);
							} else {
								//SEND BULK INVOICING WHATSAPP SMS TO ASP
								$activity->sendBulkInvoicingWhatsappSms();
							}
							//UPDATE WEBHOOK STATUS
							$whatsappWebhookResponse->status = 'Completed';
							$whatsappWebhookResponse->save();
							DB::commit();
							return response()->json([
								'success' => true,
							], $this->successStatus);
						} else {
							//SEND MORE THAN ONE INPUT REPLAY WHATSAPP SMS TO ASP
							$activity->sendMorethanOneInputFromQuickReplyWhatsappSms();

							//UPDATE WEBHOOK STATUS
							$whatsappWebhookResponse->status = 'Failed';
							$whatsappWebhookResponse->errors = "ASP already responded to acceptance charges";
							$whatsappWebhookResponse->save();
							DB::commit();
							return response()->json([
								'success' => false,
								'errors' => [
									'ASP already responded to acceptance charges',
								],
							], $this->successStatus);
						}
					} else {
						//UPDATE WEBHOOK STATUS
						$whatsappWebhookResponse->status = 'Failed';
						$whatsappWebhookResponse->errors = "ASP not having whatsapp number";
						$whatsappWebhookResponse->save();
						DB::commit();
						return response()->json([
							'success' => false,
							'errors' => [
								'ASP not having whatsapp number',
							],
						], $this->successStatus);
					}
				}
			} else {
				//UPDATE WEBHOOK STATUS
				$whatsappWebhookResponse->status = 'Failed';
				$whatsappWebhookResponse->errors = "ASP does not have a Whatsapp flow";
				$whatsappWebhookResponse->save();
				DB::commit();
				return response()->json([
					'success' => false,
					'errors' => [
						'ASP does not have a Whatsapp flow',
					],
				], $this->successStatus);
			}

		} catch (\Exception $e) {
			DB::rollBack();
			//UPDATE WEBHOOK STATUS
			$whatsappWebhookResponse->status = 'Failed';
			$whatsappWebhookResponse->errors = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			$whatsappWebhookResponse->save();
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function uploadTowImages(Request $request) {
		// dd($request->all());
		$errors = [];
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'activity_id' => [
					'required',
					'string',
					'exists:activities,crm_activity_id',
				],
				'vehicle_pickup_image' => [
					'required',
					'string',
				],
				'vehicle_drop_image' => [
					'required',
					'string',
				],
				'inventory_job_sheet_image' => [
					'required',
					'string',
				],
				'other_image_one' => [
					'nullable',
					'string',
				],
				'other_image_two' => [
					'nullable',
					'string',
				],
			]);
			if ($validator->fails()) {
				//UPLOAD TOW IMAGE API LOG
				$errors = $validator->errors()->all();
				saveApiLog(111, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$activity = Activity::where('crm_activity_id', $request->activity_id)
				->whereIn('status_id', [2, 17]) //ASP Rejected CC Details - Waiting for ASP Data Entry OR On Hold
				->first();

			if (!$activity) {
				//UPLOAD TOW IMAGE API LOG
				$errors[] = "Activity not found";
				saveApiLog(111, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Activity not found',
					],
				], $this->successStatus);
			}

			if ($activity && $activity->serviceType && !empty($activity->serviceType->service_group_id) && $activity->serviceType->service_group_id != 3) {
				//UPLOAD TOW IMAGE API LOG
				$errors = $validator->errors()->all();
				saveApiLog(111, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Activity is not a towing service',
					],
				], $this->successStatus);
			}

			$destination = aspTicketAttachmentPath($activity->id, $activity->asp_id, $activity->service_type_id);
			Storage::makeDirectory($destination, 0777);

			//VEHICLE PICKUP ATTACHMENT
			if (!empty($request->vehicle_pickup_image)) {
				//REMOVE EXISTING ATTACHMENT
				$vehiclePickupAttachExist = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.VEHICLE_PICKUP_ATTACHMENT'))
					->first();
				if ($vehiclePickupAttachExist) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $vehiclePickupAttachExist->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $vehiclePickupAttachExist->attachment_file_name));
					}
					$vehiclePickupAttachExist->delete();
				}

				//STORE FILE
				$vehiclePickeupBase64Image = explode(";base64,", $request->vehicle_pickup_image);
				$vehiclePickeupExplodeImage = explode("image/", $vehiclePickeupBase64Image[0]);
				$vehiclePickeupImageExtension = $vehiclePickeupExplodeImage[1];
				$vehiclePickeupImageContents = file_get_contents($request->vehicle_pickup_image);
				$vehiclePickeupImageName = "vehicle-pickeup-image." . $vehiclePickeupImageExtension;
				$vehiclePickeupImagepath = $destination . "/" . $vehiclePickeupImageName;
				Storage::disk('local')->put($vehiclePickeupImagepath, $vehiclePickeupImageContents);

				//SAVE IN TABLE
				Attachment::create([
					'entity_type' => config('constants.entity_types.VEHICLE_PICKUP_ATTACHMENT'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $vehiclePickeupImageName,
				]);
			}

			//VEHICLE DROP ATTACHMENT
			if (!empty($request->vehicle_drop_image)) {
				//REMOVE EXISTING ATTACHMENT
				$vehicleDropAttachExist = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.VEHICLE_DROP_ATTACHMENT'))
					->first();
				if ($vehicleDropAttachExist) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $vehicleDropAttachExist->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $vehicleDropAttachExist->attachment_file_name));
					}
					$vehicleDropAttachExist->delete();
				}

				//STORE FILE
				$vehicleDropBase64Image = explode(";base64,", $request->vehicle_drop_image);
				$vehicleDropExplodeImage = explode("image/", $vehicleDropBase64Image[0]);
				$vehicleDropImageExtension = $vehicleDropExplodeImage[1];
				$vehicleDropImageContents = file_get_contents($request->vehicle_drop_image);
				$vehicleDropImageName = "vehicle-drop-image." . $vehicleDropImageExtension;
				$vehicleDropImagepath = $destination . "/" . $vehicleDropImageName;
				Storage::disk('local')->put($vehicleDropImagepath, $vehicleDropImageContents);

				//SAVE IN TABLE
				Attachment::create([
					'entity_type' => config('constants.entity_types.VEHICLE_DROP_ATTACHMENT'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $vehicleDropImageName,
				]);
			}

			//INVENTORY JOB SHEET ATTACHMENT
			if (!empty($request->inventory_job_sheet_image)) {
				//REMOVE EXISTING ATTACHMENT
				$inventoryJobSheetAttachExist = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.INVENTORY_JOB_SHEET_ATTACHMENT'))
					->first();
				if ($inventoryJobSheetAttachExist) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $inventoryJobSheetAttachExist->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $inventoryJobSheetAttachExist->attachment_file_name));
					}
					$inventoryJobSheetAttachExist->delete();
				}

				//STORE FILE
				$inventoryJobSheetBase64Image = explode(";base64,", $request->inventory_job_sheet_image);
				$inventoryJobSheetExplodeImage = explode("image/", $inventoryJobSheetBase64Image[0]);
				$inventoryJobSheetImageExtension = $inventoryJobSheetExplodeImage[1];
				$inventoryJobSheetImageContents = file_get_contents($request->inventory_job_sheet_image);
				$inventoryJobSheetImageName = "inventory-job-sheet-image." . $inventoryJobSheetImageExtension;
				$inventoryJobSheetImagepath = $destination . "/" . $inventoryJobSheetImageName;
				Storage::disk('local')->put($inventoryJobSheetImagepath, $inventoryJobSheetImageContents);

				//SAVE IN TABLE
				Attachment::create([
					'entity_type' => config('constants.entity_types.INVENTORY_JOB_SHEET_ATTACHMENT'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $inventoryJobSheetImageName,
				]);
			}

			//OTHER ATTACHMENT ONE
			if (!empty($request->other_image_one)) {
				//REMOVE EXISTING ATTACHMENT
				$otherAttachmentOneExist = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.OTHER_ATTACHMENT_ONE'))
					->first();
				if ($otherAttachmentOneExist) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $otherAttachmentOneExist->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $otherAttachmentOneExist->attachment_file_name));
					}
					$otherAttachmentOneExist->delete();
				}

				//STORE FILE
				$otherAttachmentOneBase64Image = explode(";base64,", $request->other_image_one);
				$otherAttachmentOneExplodeImage = explode("image/", $otherAttachmentOneBase64Image[0]);
				$otherAttachmentOneImageExtension = $otherAttachmentOneExplodeImage[1];
				$otherAttachmentOneImageContents = file_get_contents($request->other_image_one);
				$otherAttachmentOneImageName = "other-attachment-one-image." . $otherAttachmentOneImageExtension;
				$otherAttachmentOneImagepath = $destination . "/" . $otherAttachmentOneImageName;
				Storage::disk('local')->put($otherAttachmentOneImagepath, $otherAttachmentOneImageContents);

				//SAVE IN TABLE
				Attachment::create([
					'entity_type' => config('constants.entity_types.OTHER_ATTACHMENT_ONE'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $otherAttachmentOneImageName,
				]);
			}

			//OTHER ATTACHMENT TWO
			if (!empty($request->other_image_two)) {
				//REMOVE EXISTING ATTACHMENT
				$otherAttachmentTwoExist = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.OTHER_ATTACHMENT_TWO'))
					->first();
				if ($otherAttachmentTwoExist) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $otherAttachmentTwoExist->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $otherAttachmentTwoExist->attachment_file_name));
					}
					$otherAttachmentTwoExist->delete();
				}

				//STORE FILE
				$otherAttachmentTwoBase64Image = explode(";base64,", $request->other_image_two);
				$otherAttachmentTwoExplodeImage = explode("image/", $otherAttachmentTwoBase64Image[0]);
				$otherAttachmentTwoImageExtension = $otherAttachmentTwoExplodeImage[1];
				$otherAttachmentTwoImageContents = file_get_contents($request->other_image_two);
				$otherAttachmentTwoImageName = "other-attachment-two-image." . $otherAttachmentTwoImageExtension;
				$otherAttachmentTwoImagepath = $destination . "/" . $otherAttachmentTwoImageName;
				Storage::disk('local')->put($otherAttachmentTwoImagepath, $otherAttachmentTwoImageContents);

				//SAVE IN TABLE
				Attachment::create([
					'entity_type' => config('constants.entity_types.OTHER_ATTACHMENT_TWO'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $otherAttachmentTwoImageName,
				]);
			}

			//UPLOAD TOW IMAGE API LOG
			saveApiLog(111, NULL, $request->all(), $errors, NULL, 120);

			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
			$breakdownAlertSent = Activity::breakdownAlertSent($activity->id);

			//SEND IMAGE UPLOAD CONFIRMATION WHATSAPP SMS TO ASP
			if ($breakdownAlertSent && $activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
				$activity->sendImageUploadConfirmationWhatsappSms();
			}

			// IF CASE ALREADY CLOSED
			if ($activity->case->status_id == 4) {
				$activity->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
			} else {
				$activity->status_id = 26; //ASP Completed Data Entry - Waiting for Call Center Data Entry
			}
			$activity->towing_attachments_uploaded_on_whatsapp = 1; //UPLOADED
			$activity->save();

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Towing images uploaded successfully',
			], $this->successStatus);

		} catch (\Exception $e) {
			DB::rollBack();
			//UPLOAD TOW IMAGE API LOG
			$errors[] = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			saveApiLog(111, NULL, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function whatsappChargesAcceptanceStatusUpdate($cronLogId) {
		//CRON LOG SAVE
		$cronLog = CronLog::firstOrNew([
			'id' => $cronLogId,
		]);
		$cronLog->command = "update:whatsappChargesAcceptanceStatus";
		$cronLog->status = "Inprogress";
		$cronLog->created_at = Carbon::now();
		$cronLog->save();
		try {

			//LIVE
			$oneDayBefore = Carbon::parse(Carbon::now()->subHours(24))->format('Y-m-d H:i:s');

			//TESTING
			// $oneDayBefore = Carbon::parse(Carbon::now()->subHours(1))->format('Y-m-d H:i:s');

			$unresponsedAspChargesAcceptanceActivities = ActivityWhatsappLog::select([
				'activity_whatsapp_logs.id',
				'activity_whatsapp_logs.activity_id',
			])
				->join('activities', 'activities.id', 'activity_whatsapp_logs.activity_id')
				->where('activity_whatsapp_logs.created_at', '<=', $oneDayBefore)
				->where('activities.status_id', 25) //Waiting for Charges Acceptance by ASP
				->where('activity_whatsapp_logs.is_new', 1)
				->whereIn('activity_whatsapp_logs.type_id', [1193, 1194, 1202, 1203]) //Breakdown / Empty Return Charges AND Revised Breakdown / Empty Return Charges
				->groupBy('activity_whatsapp_logs.activity_id')
				->orderBy('activity_whatsapp_logs.id', 'desc')
				->get();

			$aspChargesAcceptanceUpdateErrors = [];
			if ($unresponsedAspChargesAcceptanceActivities->isNotEmpty()) {
				foreach ($unresponsedAspChargesAcceptanceActivities as $unresponsedAspChargesAcceptanceActivityKey => $unresponsedAspChargesAcceptanceActivityVal) {
					DB::beginTransaction();
					try {
						$activity = Activity::find($unresponsedAspChargesAcceptanceActivityVal->activity_id);
						$activity->status_id = 11; // Waiting for Invoice Generation by ASP
						$activity->save();

						$activity->updateApprovalLog();

						//SEND ASP ACCEPTANCE CHARGES WHATSAPP SMS TO ASP
						$activity->sendAspAcceptanceChargesWhatsappSms();

						DB::commit();
					} catch (\Exception $e) {
						DB::rollBack();
						$aspChargesAcceptanceUpdateErrors[] = "Activity (" . $unresponsedAspChargesAcceptanceActivityVal->activity_id . " : " . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
					}
				}
				$cronLog->remarks = "Activities found";
				$cronLog->errors = !empty($aspChargesAcceptanceUpdateErrors) ? json_encode($aspChargesAcceptanceUpdateErrors) : NULL;
			} else {
				$cronLog->remarks = "No activities found";
			}

			$cronLog->status = "Completed";
			$cronLog->updated_at = Carbon::now();
			$cronLog->save();
		} catch (\Exception $e) {
			//CRON LOG SAVE
			$cronLog->status = "Failed";
			$cronLog->errors = $e;
			$cronLog->updated_at = Carbon::now();
			$cronLog->save();
			dd($e);
		}
	}

}
