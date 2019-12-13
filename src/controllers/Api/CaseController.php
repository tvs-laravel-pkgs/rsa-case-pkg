<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\CaseCancelledReason;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\RsaCase;
use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\ActivityAspStatus;
use Abs\RsaCasePkg\AspActivityRejectedReason;
use Abs\RsaCasePkg\AspPoRejectedReason;
use Abs\RsaCasePkg\ActivityPortalStatus;
use App\CallCenter;
use App\Dealer;
use App\Client;
use App\Config;
use App\Entity;
use App\District;
use App\Asp;
use App\Http\Controllers\Controller;
use App\MembershipType;
use App\ServiceType;
use App\VehicleMake;
use App\VehicleModel;
use DB;
use Illuminate\Http\Request;
use Validator;

class CaseController extends Controller {
	private $successStatus = 200;

	public function saveCase(Request $request) {
		DB::beginTransaction();
		try {
			$service_types = ServiceType::pluck('name')->toArray();
			$validator = Validator::make($request->all(), [
				//Ticket No
				'number' => 'required|string|max:32',
				//Ticket Date/Time /// YYYY-MM-DD HH:MM:SS /// 2017-01-24 15:31:38
				'date' => 'required|date_format:"Y-m-d H:i:s"',
				//Data filled on date/time
				'data_filled_date' => 'required|date_format:"Y-m-d H:i:s"',
				//Description
				'description' => 'nullable|string|max:255',
				//Case Status
				'status' => 'required|string|max:191|exists:case_statuses,name',
				//Case Cancel Reason
				'cancel_reason' => 'nullable|string|max:100|exists:case_cancelled_reasons,name',
				//Call centre
				'call_center' => 'required|string|max:64|exists:call_centers,name',
				//Client Name
				'client' => 'required|string|max:124|exists:clients,name',
				//Customer Name
				'customer_name' => 'required|string|max:128',
				//Customer Phone Number
				'customer_contact_number' => 'required|string|max:10',
				//Case Contact Name
				'contact_name' => 'nullable|string|max:128',
				//Case Contact Number
				'contact_number' => 'nullable|string|max:10',
				//Vehicle Make
				'vehicle_make' => 'required|string|max:191|exists:vehicle_makes,name',
				//Vehicle Model
				'vehicle_model' => 'nullable|string|max:191|exists:vehicle_models,name',
				//Vehicle Registration Number
				'vehicle_registration_number' => 'required|string|max:191',
				//VIN
				'vin_no' => 'nullable|string|max:191',
				//Membership Type
				'membership_type' => 'required|string|max:255|exists:membership_types,name',
				//Membership Number
				'membership_number' => 'nullable|string|max:255',
				//Subject
				'subject' => 'required|string|max:191|exists:subjects,name',
				//KM during breakdown
				'km_during_breakdown' => 'nullable',
				//BD Location Latitude
				'bd_lat' => 'nullable',
				//BD Location Longitude
				'bd_long' => 'nullable',
				//BD Location
				'bd_location' => 'nullable|string|max:2048',
				//BD City
				'bd_city' => 'nullable|string|max:128|exists:districts,name',
				//BD State
				'bd_state' => 'nullable|string|max:50|exists:states,name',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->all()], $this->successStatus);
			}

			$status = CaseStatus::where('name', $request->status)->first();
			$call_center = CallCenter::where('name', $request->call_center)->first();
			$client = Client::where('name', $request->client)->first();
			$vehicle_make = VehicleMake::where('name', $request->vehicle_make)->first();
			$vehicle_model = VehicleModel::where('vehicle_make_id', $vehicle_make->id)->first();
			$membership_type = MembershipType::where('name', $request->membership_type)->first();
			$subject = Subject::where('name', $request->subject)->first();

			$bd_city = District::where('name', $request->bd_city)->first();
			if (!$bd_city) {
				$bd_city_id = NULL;
			} else {
				$bd_city_id = $bd_city->id;
			}

			$cancel_reason = CaseCancelledReason::where('name', $request->cancel_reason)->first();
			if (!$cancel_reason) {
				$cancel_reason_id = NULL;
			} else {
				$cancel_reason_id = $cancel_reason->id;
			}

			$case = RsaCase::firstOrNew([
				'company_id' => 1,
				'number' => $request->number,
			]);
			$case->fill($request->all());
			$case->status_id = $status->id;
			$case->cancel_reason_id = $cancel_reason_id;
			$case->call_center_id = $call_center->id;
			$case->vehicle_model_id = $vehicle_model->id;
			$case->membership_type_id = $membership_type->id;
			$case->subject_id = $subject->id;
			$case->bd_city_id = $bd_city_id;
			$case->save();

			DB::commit();
			return response()->json(['success' => true, 'message' => 'Case saved successfully'], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}

	public function saveActivity(Request $request) {
		DB::beginTransaction();
		try {
			$service_types = ServiceType::pluck('name')->toArray();
			$validator = Validator::make($request->all(), [
				//Asp Code
				'asp_code' => 'required|string|max:24|exists:asps,asp_code',
				//Ticket No
				'case_id' => 'required|string|max:32|exists:cases,number',
				//Case Status
				'case_status' => 'required|string|max:191|exists:case_statuses,name',
				//Service
				'service' => 'required|string|max:50|exists:service_groups,name',
				//Sub Service
				'sub_service' => [
					'required',
					'string',
					'max:50',
					Rule::in($service_types),
				],
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
			$case_status = CaseStatus::where('name', $request->case_status)->first();
			$service_type = ServiceType::where('name', $request->sub_service)->first();
			$asp_status = ActivityAspStatus::where('name', $request->asp_status)->first();
			if(!$asp_status){
				$asp_status_id = NULL;
			}else{
				$asp_status_id = $asp_status->id;
			}

			$asp_activity_rejected_reason = AspActivityRejectedReason::where('name', $request->asp_activity_rejected_reason)->first();
			if(!$asp_activity_rejected_reason){
				$asp_activity_rejected_reason_id = NULL;
			}else{
				$asp_activity_rejected_reason_id = $asp_activity_rejected_reason->id;
			}

			$asp_po_rejected_reason = AspPoRejectedReason::where('name', $request->asp_po_rejected_reason)->first();
			if(!$asp_po_rejected_reason){
				$asp_po_rejected_reason_id = NULL;
			}else{
				$asp_po_rejected_reason_id = $asp_po_rejected_reason->id;
			}

			$status = ActivityPortalStatus::where('name', $request->status)->first();
			if(!$status){
				$status_id = NULL;
			}else{
				$status_id = $status->id;
			}

			$activity_status = ActivityStatus::where('name', $request->activity_status)->first();
			if(!$activity_status){
				$activity_status_id = NULL;
			}else{
				$activity_status_id = $activity_status->id;
			}

			$drop_location_type = Entity::where('name', $request->drop_location_type)->first();
			if(!$drop_location_type){
				$drop_location_type_id = NULL;
			}else{
				$drop_location_type_id = $drop_location_type->id;
			}

			$drop_dealer = Dealer::where('name', $request->drop_dealer)->first();
			if(!$drop_dealer){
				$drop_dealer_id = NULL;
			}else{
				$drop_dealer_id = $drop_dealer->id;
			}

			$paid_to = Config::where('name', $request->paid_to)->first();
			if(!$paid_to){
				$paid_to_id = NULL;
			}else{
				$paid_to_id = $paid_to->id;
			}

			$payment_mode = Entity::where('name', $request->payment_mode)->first();
			if(!$payment_mode){
				$payment_mode_id = NULL;
			}else{
				$payment_mode_id = $payment_mode->id;
			}

			$case = RsaCase::where('number', $request->case_id)->first();
			$case->status_id = $case_status->id;
			$case->save();
						
			$activity = Activity::firstOrNew([
				'crm_activity_id' => $request->crm_activity_id,
			]);
			$activity->fill($request->all());
			$activity->asp_id = $asp->id;
			$activity->case_id = $case->id;
			$activity->service_type_id = $service_type->id;
			$activity->asp_status_id = $asp_status_id;
			$activity->asp_activity_rejected_reason_id = $asp_activity_rejected_reason_id;
			if($asp_po_accepted == 'Accepted'){
				$activity->asp_po_accepted = 1;
			}else{
				$activity->asp_po_accepted = 0;
			}
			$activity->asp_po_rejected_reason_id = $asp_po_rejected_reason_id;
			$activity->status_id = $status_id;
			$activity->activity_status_id = $activity_status_id;
			$activity->drop_location_type_id = $drop_location_type_id;
			$activity->drop_dealer_id = $drop_dealer_id;
			$activity->paid_to_id = $paid_to_id;
			$activity->payment_mode_id = $payment_mode_id;
			$activity->save();

			DB::commit();
			return response()->json(['success' => true, 'message' => 'Activity created successfully'], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}

}
