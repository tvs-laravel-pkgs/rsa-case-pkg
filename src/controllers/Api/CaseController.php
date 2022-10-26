<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityLog;
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

			$validator = Validator::make($request->all(), [
				'number' => 'required|string|max:32',
				'date' => 'required|date_format:"Y-m-d H:i:s"',
				'data_filled_date' => 'required|date_format:"Y-m-d H:i:s"',
				'description' => 'nullable|string|max:255',
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
				// 'customer_contact_number' => 'required|string|min:10|max:10',
				'customer_contact_number' => 'required|string',
				'contact_name' => 'nullable|string|max:50',
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
					'required',
					'string',
					'max:191',
					Rule::exists('vehicle_models', 'name')
						->where(function ($query) {
							$query->whereNull('deleted_at');
						}),
				],
				'vehicle_registration_number' => 'nullable|string|max:11',
				'vin_no' => 'nullable|string|max:20',
				'membership_type' => 'required|string|max:191',
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
				'bd_lat' => 'nullable',
				'bd_long' => 'nullable',
				'bd_location' => 'nullable|string',
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
			]);

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

			$status = CaseStatus::where('name', $request->status)->where('company_id', 1)->first();
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
			$vehicle_model_by_make = VehicleModel::where('name', $request->vehicle_model)
				->where('vehicle_make_id', $vehicle_make->id)
				->first();
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

			if (empty($vehicle_model_by_make->vehicle_category_id)) {
				//SAVE CASE API LOG
				$errors[] = "Vehicle category not mapped for the vehicle model";
				saveApiLog(102, $request->number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Vehicle category not mapped for the vehicle model",
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

			//CASE NEW
			if (!$case->exists) {
				//WITH CANCELLED OR CLOSED STATUS
				if ($status->id == 3 || $status->id == 4) {
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

						//SEND BREAKDOWN OR EMPTY RETURN CHARGES WHATSAPP SMS TO ASP
						if ($invoiceAmountCalculatedActivity->asp && !empty($invoiceAmountCalculatedActivity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $invoiceAmountCalculatedActivity->asp->has_whatsapp_flow == 1))) {
							$invoiceAmountCalculatedActivity->sendBreakdownOrEmptyreturnChargesWhatsappSms();
						}

					}
				}

				$case->activities()
					->where([
						// Invoice Amount Calculated - Waiting for Case Closure
						'status_id' => 10,
					])
					->update([
						// Case Closed - Waiting for ASP to Generate Invoice
						'status_id' => 1,
					]);
			}

			//RELEASE ONHOLD / ASP COMPLETED DATA ENTRY - WAITING FOR CALL CENTER DATA ENTRY ACTIVITIES WITH CLOSED OR CANCELLED CASES
			if ($case->status_id == 4 || $case->status_id == 3) {
				$activities = $case->activities()->whereIn('status_id', [17, 26])->get();
				if ($activities->isNotEmpty()) {
					foreach ($activities as $key => $activity) {

						//WHATSAPP FLOW
						if ($activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
							// ROS SERVICE
							if ($activity->serviceType && $activity->serviceType->service_group_id != 3) {
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
								// TOW SERVICE
								if ($activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$status_id = 6;
								} else {
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
								$cc_total_km = $activity->detail(280) ? $activity->detail(280)->value : 0;
								$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $case->vehicleModel->vehiclecategory->id);
								if ($is_bulk) {
									//ASP Completed Data Entry - Waiting for L1 Bulk Verification
									$status_id = 5;
								} else {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$status_id = 6;
								}
							} else {
								if ($activity->is_asp_data_entry_done == 1) {
									//ASP Completed Data Entry - Waiting for L1 Individual Verification
									$status_id = 6;
								} else {
									$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
								}
							}
							$activity->update([
								'status_id' => $status_id,
							]);
						}
					}
				}
			}

			//teststst

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
