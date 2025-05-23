<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityReport;
use Abs\RsaCasePkg\CaseCancelledReason;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\RsaCase;
use App\CallCenter;
use App\Client;
use App\Config;
use App\Http\Controllers\Controller;
use App\Subject;
use App\VehicleMake;
use App\VehicleModel;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class CaseController extends Controller {
	private $successStatus = 200;

	public function save(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);

		$errors = [];
		DB::beginTransaction();
		try {
			//Dont allow updations if current status is Cancelled or Closed
			// $case = RsaCase::where([
			// 	'company_id' => 1,
			// 	'number' => $request->number,
			// ])->first();
			// if ($case && ($case->status_id == 3 || $case->status_id == 4)) {
			// 	//SAVE CASE API LOG
			// 	$errors[] = 'Update not allowed - Case already ' . $case->status->name;
			// 	saveApiLog(102, $case->number, $request->all(), $errors, NULL, 121);
			// 	DB::commit();

			// 	return response()->json([
			// 		'success' => false,
			// 		'error' => 'Validation Error',
			// 		'errors' => [
			// 			'Update not allowed - Case already ' . $case->status->name,
			// 		],
			// 	], $this->successStatus);
			// }

			$errorMessages = [
				'description.regex' => "Equal symbol (=) is not allowed as the first character for description!",
				'bd_location.regex' => "Equal symbol (=) is not allowed as the first character for BD location!",
			];

			$validator = Validator::make($request->all(), [
				'number' => 'required|string|max:32',
				'date' => 'required|date_format:"Y-m-d H:i:s"',
				'data_filled_date' => 'required|date_format:"Y-m-d H:i:s"',
				'description' => [
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
				// 'customer_name' => 'required|string|max:255',
				'customer_name' => isset($request->type_id) && $request->type_id == 1461
				? 'nullable|string|max:255'
				: 'required|string|max:255',
				// 'customer_contact_number' => 'required|string|min:10|max:10',
				// 'customer_contact_number' => 'required|string',
				'customer_contact_number' => isset($request->type_id) && $request->type_id == 1461
				? 'nullable|string'
				: 'required|string',
				'contact_name' => 'nullable|string|max:255',
				// 'contact_number' => 'nullable|string|min:10|max:10',
				'contact_number' => 'nullable|string',
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
					'string',
					'max:191',
					Rule::exists('vehicle_models', 'name')
						->where(function ($query) {
							$query->whereNull('deleted_at');
						}),
				],
				'vehicle_registration_number' => 'nullable|string|max:11',
				'vin_no' => 'nullable|string|max:20',
				'membership_type' => 'nullable|string|max:191',
				'membership_number' => 'nullable|string|max:50',
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
					'nullable',
					'string',
					'max:191',
					Rule::exists('configs', 'name')
						->where(function ($query) {
							$query->where('entity_type_id', 39);
						}),
				],
				'bd_location_category' => [
					'nullable',
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
				'pickup_lat' => 'nullable|string|max:60',
				'pickup_long' => 'nullable|string|max:60',
				'pickup_dealer_name' => 'nullable|string|max:255',
				'pickup_dealer_state' => 'nullable|string|max:255',
				'pickup_dealer_city' => 'nullable|string|max:255',
				'pickup_location_pincode' => 'nullable|string|max:10',
				'drop_dealer_name' => 'nullable|string|max:255',
				'drop_dealer_state' => 'nullable|string|max:255',
				'drop_dealer_city' => 'nullable|string|max:255',
				'drop_location_pincode' => 'nullable|string|max:10',
				'contact_name_at_pickup' => 'nullable|string|max:255',
				'contact_number_at_pickup' => 'nullable|string|max:20',
				'contact_name_at_drop' => 'nullable|string|max:255',
				'contact_number_at_drop' => 'nullable|string|max:20',
				'delivery_request_pickup_date' => 'nullable|date',
				'delivery_request_pickup_time' => 'nullable|string|max:60',
			], $errorMessages);

			if ($validator->fails()) {
				//SAVE CASE API LOG
				$errors = $validator->errors()->all();
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//Till May'20 Cases not allowed
			$case_date = date('Y-m-d', strtotime($request->date));
			$case_restriction_date = config('rsa.CASE_RESTRICTION_DATE');
			if ($case_date <= $case_restriction_date) {
				//SAVE CASE API LOG
				$errors[] = "Till May'20 Cases not allowed";
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Till May'20 Cases not allowed",
					],
				], $this->successStatus);
			}

			//August month 2020 cases should not be allowed due to cases were already closed - temporarily
			if ($case_date >= "2020-08-01" && $case_date <= "2020-08-31") {
				//SAVE CASE API LOG
				$errors[] = "Rejected as August month case closed";
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Rejected as August month case closed",
					],
				], $this->successStatus);
			}

			//ALLOW ONLY LETTERS AND NUMBERS
			if (!preg_match("/^[a-zA-Z0-9]+$/", $request->number)) {
				//SAVE CASE API LOG
				$errors[] = 'Invalid Case Number';
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Invalid Case Number",
					],
				], $this->successStatus);
			}

			if (strtolower($request->status) == "on hold") {
				$request->status = "In Progress";
			}

			if (strtolower($request->status) == "pre-close") {
				$request->status = "Closed";
			}

			$status = CaseStatus::where('name', $request->status)->where('company_id', 1)->first();
			if (!$status) {
				//SAVE CASE API LOG
				$errorMsg = "Case Status is invalid";
				$errors[] = $errorMsg;
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						$errorMsg,
					],
				], $this->successStatus);
			}

			$call_center = CallCenter::where('name', $request->call_center)->first();
			$client = Client::where('name', $request->client)->first();
			$vehicle_make = VehicleMake::where('name', $request->vehicle_make)->first();

			//CASE STATUS IS CANCELLED - CANCEL REASON IS MANDATORY
			// if ($status->id == 3) {
			// 	if (!$request->cancel_reason) {
			// 		//SAVE CASE API LOG
			// 		$errors[] = 'Cancel reason is required';
			// 		saveApiLog(102, $request->all(), $errors, NULL, 121);
			// 		DB::commit();

			// 		return response()->json([
			// 			'success' => false,
			// 			'error' => 'Validation Error',
			// 			'errors' => [
			// 				"Cancel reason is required",
			// 			],
			// 		], $this->successStatus);
			// 	}
			// }
			//VEHICLE MODEL GOT BY VEHICLE MAKE
			$vehicle_model_by_make = VehicleModel::where('name', $request->vehicle_model)->where('vehicle_make_id', $vehicle_make->id)->first();
			if (!$vehicle_model_by_make) {
				//SAVE CASE API LOG
				$errors[] = "Selected vehicle make doesn't matches with vehicle model";
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Selected vehicle make doesn't matches with vehicle model",
					],
				], $this->successStatus);
			}

			//VIN NO OR VEHICLE REGISTRATION NUMBER ANY ONE IS MANDATORY
			if (!$request->vehicle_registration_number && !$request->vin_no) {
				//SAVE CASE API LOG
				$errors[] = 'VIN or Vehicle Registration Number is required';
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"VIN or Vehicle Registration Number is required",
					],
				], $this->successStatus);
			}

			$subject = Subject::where('name', $request->subject)->first();

			$cancel_reason = CaseCancelledReason::where('name', $request->cancel_reason)->where('company_id', 1)->first();
			if (!$cancel_reason) {
				$cancel_reason_id = NULL;
			} else {
				$cancel_reason_id = $cancel_reason->id;
			}

			$case = RsaCase::firstOrNew([
				'company_id' => 1,
				'number' => $request->number,
			]);

			$check_cancelled_or_close = true;
			//Vehicle Delivery
			if (isset($request->type_id) && $request->type_id == 1461) {
				$check_cancelled_or_close = false;
			}

			//CASE NEW
			if (!$case->exists) {
				//WITH CANCELLED OR CLOSED STATUS
				if (($status->id == 3 || $status->id == 4) && $check_cancelled_or_close == true) {
					//SAVE CASE API LOG
					$errors[] = 'Case should not start with cancelled or closed status';
					saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							"Case should not start with cancelled or closed status",
						],
					], $this->successStatus);
				}
			} else {
				//EXISTS
				$caseDate = date('Y-m-d', strtotime($case->date));
				//August month 2020 cases should not be allowed due to cases were already closed - temporarily
				if ($caseDate >= "2020-08-01" && $caseDate <= "2020-08-31") {
					//SAVE CASE API LOG
					$errors[] = "Rejected as August month case closed";
					saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							"Rejected as August month case closed",
						],
					], $this->successStatus);
				}
			}

			$bd_location_type = Config::where('name', $request->bd_location_type)
				->where('entity_type_id', 39) // BD LOCATION TYPES
				->first();
			if ($bd_location_type) {
				$bd_location_type_id = $bd_location_type->id;
			} else {
				$bd_location_type_id = NULL;
			}

			$bd_location_category = Config::where('name', $request->bd_location_category)
				->where('entity_type_id', 40) // BD LOCATION CATEGORIES
				->first();
			if ($bd_location_category) {
				$bd_location_category_id = $bd_location_category->id;
			} else {
				$bd_location_category_id = NULL;
			}

			$case->fill($request->all());
			$case->status_id = $status->id;
			$case->cancel_reason_id = $cancel_reason_id;
			$case->call_center_id = $call_center->id;
			$case->client_id = $client->id;
			$case->vehicle_model_id = $vehicle_model_by_make->id;
			$case->subject_id = $subject->id;
			$case->bd_location_type_id = $bd_location_type_id;
			$case->bd_location_category_id = $bd_location_category_id;
			$case->membership_type = !empty($request->membership_type) ? $request->membership_type : NULL;
			$case->csr = !empty($request->csr) ? $request->csr : NULL;

			//VEHICLE DELIVERY REQUEST COLUMNS
			$case->type_id = !empty($request->type_id) ? $request->type_id : NULL;
			$case->pickup_lat = !empty($request->pickup_lat) ? $request->pickup_lat : NULL;
			$case->pickup_long = !empty($request->pickup_long) ? $request->pickup_long : NULL;
			$case->pickup_dealer_name = !empty($request->pickup_dealer_name) ? $request->pickup_dealer_name : NULL;
			$case->pickup_dealer_location = !empty($request->pickup_dealer_location) ? $request->pickup_dealer_location : NULL;
			$case->pickup_dealer_state = !empty($request->pickup_dealer_state) ? $request->pickup_dealer_state : NULL;
			$case->pickup_dealer_city = !empty($request->pickup_dealer_city) ? $request->pickup_dealer_city : NULL;
			$case->pickup_location_pincode = !empty($request->pickup_location_pincode) ? $request->pickup_location_pincode : NULL;
			$case->drop_dealer_name = !empty($request->drop_dealer_name) ? $request->drop_dealer_name : NULL;
			$case->drop_dealer_location = !empty($request->drop_dealer_location) ? $request->drop_dealer_location : NULL;
			$case->drop_dealer_state = !empty($request->drop_dealer_state) ? $request->drop_dealer_state : NULL;
			$case->drop_dealer_city = !empty($request->drop_dealer_city) ? $request->drop_dealer_city : NULL;
			$case->drop_location_pincode = !empty($request->drop_location_pincode) ? $request->drop_location_pincode : NULL;
			$case->contact_name_at_pickup = !empty($request->contact_name_at_pickup) ? $request->contact_name_at_pickup : NULL;
			$case->contact_number_at_pickup = !empty($request->contact_number_at_pickup) ? $request->contact_number_at_pickup : NULL;
			$case->contact_name_at_drop = !empty($request->contact_name_at_drop) ? $request->contact_name_at_drop : NULL;
			$case->contact_number_at_drop = !empty($request->contact_number_at_drop) ? $request->contact_number_at_drop : NULL;
			$case->delivery_request_pickup_date = !empty($request->delivery_request_pickup_date) ? date('Y-m-d', strtotime($request->delivery_request_pickup_date)) : NULL;
			$case->delivery_request_pickup_time = !empty($request->delivery_request_pickup_time) ? $request->delivery_request_pickup_time : NULL;
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

							//SAVE ACTIVITY REPORT FOR DASHBOARD
							ActivityReport::saveReport($activity->id);
						}
					}
				}
			}

			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];

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

						$invoiceAmountCalculatedActivityBreakdownAlertSent = Activity::breakdownAlertSent($invoiceAmountCalculatedActivity->id);
						//SEND BREAKDOWN OR EMPTY RETURN CHARGES WHATSAPP SMS TO ASP
						if ($invoiceAmountCalculatedActivityBreakdownAlertSent && $invoiceAmountCalculatedActivity->asp && !empty($invoiceAmountCalculatedActivity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $invoiceAmountCalculatedActivity->asp->has_whatsapp_flow == 1))) {
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

			$disableWhatsappAutoApproval = config('rsa')['DISABLE_WHATSAPP_AUTO_APPROVAL'];
			//RELEASE ONHOLD / ASP COMPLETED DATA ENTRY - WAITING FOR CALL CENTER DATA ENTRY ACTIVITIES WITH CLOSED OR CANCELLED CASES
			if ($case->status_id == 4 || $case->status_id == 3) {
				$activities = $case->activities()->whereIn('status_id', [17, 26])->get();
				if ($activities->isNotEmpty()) {
					foreach ($activities as $key => $activity) {
						$cc_total_km = $activity->detail(280) ? $activity->detail(280)->value : 0;

						$activityBreakdownAlertSent = Activity::breakdownAlertSent($activity->id);
						//WHATSAPP FLOW
						if ($activityBreakdownAlertSent && $activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
							// ROS SERVICE
							if ($activity->serviceType && $activity->serviceType->service_group_id != 3) {

								if (!$disableWhatsappAutoApproval) {
									$autoApprovalProcessResponse = $activity->autoApprovalProcess();
									if (!$autoApprovalProcessResponse['success']) {
										//SAVE CASE API LOG
										DB::rollBack();
										$errors[] = $autoApprovalProcessResponse['error'];
										saveApiLog(102, $activity->case->number, $request->all(), $errors, NULL, 121);
										return response()->json([
											'success' => false,
											'error' => 'Validation Error',
											'errors' => [
												"Case Number : " . $activity->case->number . " - " . $autoApprovalProcessResponse['error'],
											],
										], $this->successStatus);
									}
								} else {
									//MECHANICAL SERVICE GROUP
									if ($activity->serviceType && $activity->serviceType->service_group_id == 2) {
										$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $activity->case->date);
										if ($is_bulk) {
											//ASP Completed Data Entry - Waiting for L1 Bulk Verification
											$status_id = 5;
										} else {
											//ASP Completed Data Entry - Waiting for L1 Individual Verification
											$status_id = 6;
										}
									} else {
										if (($activity->asp && $activity->asp->is_corporate == 1) || $activity->is_asp_data_entry_done == 1) {
											//ASP Completed Data Entry - Waiting for L1 Individual Verification
											$status_id = 6;
										} else {
											$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
										}
									}

									//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
									if (floatval($cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
										$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
									}

									$activity->update([
										'status_id' => $status_id,
									]);
								}

							} else {
								// TOW SERVICE
								if ($activity->asp->is_corporate == 1 || $activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$status_id = 6;
								} else {
									$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
								}

								//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
								if (floatval($cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
									$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
								}

								$activity->update([
									'status_id' => $status_id,
								]);
							}
						} else {
							// NORMAL FLOW

							//MECHANICAL SERVICE GROUP
							if ($activity->serviceType && $activity->serviceType->service_group_id == 2) {
								$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $activity->case->date);
								if ($is_bulk) {
									//ASP Completed Data Entry - Waiting for L1 Bulk Verification
									$status_id = 5;
								} else {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$status_id = 6;
								}
							} else {
								if (($activity->asp && $activity->asp->is_corporate == 1) || $activity->is_asp_data_entry_done == 1) {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$status_id = 6;
								} else {
									$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
								}
							}

							//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
							if (floatval($cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
								$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
							}

							$activity->update([
								'status_id' => $status_id,
							]);
						}

						//SAVE ACTIVITY REPORT FOR DASHBOARD
						ActivityReport::saveReport($activity->id);
					}
				}
			}

			//SAVE CASE API LOG
			saveApiLog(102, $request->number, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Case saved successfully',
				'case' => $case,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//SAVE CASE API LOG
			$errors[] = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);

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
