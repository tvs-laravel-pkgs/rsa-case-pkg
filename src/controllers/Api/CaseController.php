<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\CaseCancelledReason;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\RsaCase;
use App\CallCenter;
use App\Client;
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
			$case = RsaCase::where([
				'company_id' => 1,
				'number' => $request->number,
			])->first();
			if ($case && ($case->status_id == 3 || $case->status_id == 4)) {
				//SAVE CASE API LOG
				$errors[] = 'Update not allowed - Case already ' . $case->status->name;
				saveApiLog(102, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Update not allowed - Case already ' . $case->status->name,
					],
				], $this->successStatus);
			}

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
					Rule::exists('case_cancelled_reasons', 'name')
						->where(function ($query) {
							$query->whereNull('deleted_at');
						}),
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
				'customer_contact_number' => 'required|string|min:10|max:10',
				'contact_name' => 'nullable|string|max:50',
				'contact_number' => 'nullable|string|min:10|max:10',
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
				saveApiLog(102, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//ALLOW ONLY LETTERS AND NUMBERS
			if (!preg_match("/^[a-zA-Z0-9]+$/", $request->number)) {
				//SAVE CASE API LOG
				$errors[] = 'Invalid Case Number';
				saveApiLog(102, $request->all(), $errors, NULL, 121);
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
			if ($status->id == 3) {
				if (!$request->cancel_reason) {
					//SAVE CASE API LOG
					$errors[] = 'Cancel reason is required';
					saveApiLog(102, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							"Cancel reason is required",
						],
					], $this->successStatus);
				}
			}
			//VEHICLE MODEL GOT BY VEHICLE MAKE
			$vehicle_model_by_make = VehicleModel::where('name', $request->vehicle_model)->where('vehicle_make_id', $vehicle_make->id)->first();
			if (!$vehicle_model_by_make) {
				//SAVE CASE API LOG
				$errors[] = "Selected vehicle make doesn't matches with vehicle model";
				saveApiLog(102, $request->all(), $errors, NULL, 121);
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
				saveApiLog(102, $request->all(), $errors, NULL, 121);
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
					saveApiLog(102, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							"Case should not start with cancelled or closed status",
						],
					], $this->successStatus);
				}
			}

			$case->fill($request->all());
			$case->status_id = $status->id;
			$case->cancel_reason_id = $cancel_reason_id;
			$case->call_center_id = $call_center->id;
			$case->client_id = $client->id;
			$case->vehicle_model_id = $vehicle_model_by_make->id;
			$case->subject_id = $subject->id;
			$case->save();

			if ($case->status_id == 3) {
				//CANCELLED
				$case
					->activities()
					->update([
						// Not Eligible for Payout
						'status_id' => 15,
					]);
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

			//SAVE CASE API LOG
			saveApiLog(102, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Case saved successfully',
				'case' => $case,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//SAVE CASE API LOG
			$errors[] = $e->getMessage() . ' Line:' . $e->getLine();
			saveApiLog(102, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . ' Line:' . $e->getLine(),
				],
			], $this->successStatus);
		}
	}

}
