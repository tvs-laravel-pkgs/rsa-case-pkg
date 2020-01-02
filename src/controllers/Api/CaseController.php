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
use Validator;

class CaseController extends Controller {
	private $successStatus = 200;

	public function save(Request $request) {
		DB::beginTransaction();
		try {

			//Dont allow updations if current status is Cancelled or Closed
			$case = RsaCase::where([
				'company_id' => 1,
				'number' => $request->number,
			])->first();
			if ($case && ($case->status_id == 3 || $case->status_id == 4)) {
				return response()->json([
					'success' => false,
					'error' => 'Update not allowed - Case already ' . $case->status->name,
				], $this->successStatus);
			}

			$validator = Validator::make($request->all(), [
				'number' => 'required|string|max:32',
				'date' => 'required|date_format:"Y-m-d H:i:s"',
				'data_filled_date' => 'required|date_format:"Y-m-d H:i:s"',
				'description' => 'nullable|string|max:255',
				'status' => 'required|string|max:191|exists:case_statuses,name',
				'cancel_reason' => 'nullable|string|max:100|exists:case_cancelled_reasons,name',
				'call_center' => 'required|string|max:64|exists:call_centers,name',
				'client' => 'required|string|max:124|exists:clients,name',
				'customer_name' => 'required|string|max:128',
				'customer_contact_number' => 'required|string|min:10|max:10',
				'contact_name' => 'nullable|string|max:128',
				'contact_number' => 'nullable|string|min:10|max:10',
				'vehicle_make' => 'required|string|max:191|exists:vehicle_makes,name',
				'vehicle_model' => 'nullable|string|max:191|exists:vehicle_models,name',
				'vehicle_registration_number' => 'required|string|max:191',
				'vin_no' => 'nullable|string|min:17|max:17',
				'membership_type' => 'required|string|max:255',
				'membership_number' => 'nullable|string|max:255',
				'subject' => 'required|string|max:191|exists:subjects,name',
				'km_during_breakdown' => 'nullable',
				'bd_lat' => 'nullable',
				'bd_long' => 'nullable',
				'bd_location' => 'nullable|string|max:2048',
				'bd_city' => 'nullable|string|max:128',
				'bd_state' => 'nullable|string|max:50|exists:states,name',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//ALLOW ONLY LETTERS AND NUMBERS
			if (!preg_match("/^[a-zA-Z0-9]+$/", $request->number)) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Invalid Case Number",
					],
				], $this->successStatus);
			}

			// if (preg_match("/([%\@!$#\^&()+*]+)/", $request->number)) {
			// 	return response()->json([
			// 		'success' => false,
			// 		'error' => 'Validation Error',
			// 		'errors' => [
			// 			"Invalid Case Number",
			// 		],
			// 	], $this->successStatus);
			// }

			$status = CaseStatus::where('name', $request->status)->where('company_id', 1)->first();
			$call_center = CallCenter::where('name', $request->call_center)->first();
			$client = Client::where('name', $request->client)->first();
			$vehicle_make = VehicleMake::where('name', $request->vehicle_make)->first();

			//CASE STATUS IS CANCELLED - CANCEL REASON IS MANDATORY
			if ($status->id == 3) {
				if (!$request->cancel_reason) {
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
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"Selected vehicle make doesn't matches with vehicle model",
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

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Case saved successfully',
				'case' => $case,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}

}
