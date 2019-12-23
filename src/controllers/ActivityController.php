<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityPortalStatus;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\ActivityDetail;
use App\CallCenter;
use App\Client;
use App\Config;
use App\AspServiceType;
use App\Http\Controllers\Controller;
use App\ServiceType;
use App\StateUser;
use DB;
use Auth;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ActivityController extends Controller {

	public function getFilterData() {
		$this->data['extras'] = [
			'call_center_list' => collect(CallCenter::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Call Center']),
			'service_type_list' => collect(ServiceType::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Sub Service']),
			'finance_status_list' => collect(ActivityFinanceStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Status']),
			'status_list' => collect(ActivityPortalStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Status']),
			'activity_status_list' => collect(ActivityStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Activity Status']),
			'client_list' => collect(Client::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Client']),
		];
		return response()->json($this->data);
	}

	public function getList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			'asps.asp_code',
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'call_centers.name as call_center'
		)
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
		// ->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->orderBy('cases.date', 'DESC')
			->groupBy('activities.id')
		;

		if ($request->get('ticket_date')) {
			$activities->whereRaw('DATE_FORMAT(cases.date,"%d-%m-%Y") =  "' . $request->get('ticket_date') . '"');
		}
		if ($request->get('call_center_id')) {
			$activities->where('cases.call_center_id', $request->get('call_center_id'));
		}
		if ($request->get('case_number')) {
			$activities->where('cases.number', 'LIKE', '%' . $request->get('case_number') . '%');
		}
		if ($request->get('asp_code')) {
			$activities->where('asps.asp_code', 'LIKE', '%' . $request->get('asp_code') . '%');
		}
		if ($request->get('service_type_id')) {
			$activities->where('activities.service_type_id', $request->get('service_type_id'));
		}
		// if ($request->get('asp_status_id')) {
		// 	$activities->where('activities.status_id', $request->get('asp_status_id'));
		// }
		if ($request->get('finance_status_id')) {
			$activities->where('activities.finance_status_id', $request->get('finance_status_id'));
		}
		if ($request->get('status_id')) {
			$activities->where('activities.status_id', $request->get('status_id'));
		}
		if ($request->get('activity_status_id')) {
			$activities->where('activities.activity_status_id', $request->get('activity_status_id'));
		}
		if ($request->get('client_id')) {
			$activities->where('cases.client_id', $request->get('client_id'));
		}

		if (!Entrust::can('view-all-activities')) {
			if (Entrust::can('view-mapped-state-activities')) {
				$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
				$activities->whereIn('asps.state_id', $states);
			}
			if (Entrust::can('view-own-activities')) {
				$activities->where('users.id', Auth::id());
			}
		}
		return Datatables::of($activities)
			->addColumn('action', function ($activity) {
				$action = '<div class="dataTable-actions">
				<a href="#!/rsa-case-pkg/activity-status/view/' . $activity->id . '">
					                <i class="fa fa-eye dataTable-icon--view" aria-hidden="true"></i>
					            </a>';
				if (Entrust::can('delete-activities')) {
					$action .= '<a onclick="angular.element(this).scope().deleteConfirm(' . $activity->id . ')" href="javascript:void(0)">
						                <i class="fa fa-trash dataTable-icon--trash cl-delete" data-cl-id =' . $activity->id . ' aria-hidden="true"></i>
						            </a>';
				}
				$action .= '</div>';
				return $action;
			})
			->make(true);
	}

	public function delete($id) {
		Activity::where('id', $id)->delete();
		return response()->json(['success' => true]);
	}

	public function getVerificationList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			'asps.asp_code',
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'call_centers.name as call_center'
		)
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
		// ->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->where('activities.asp_accepted_cc_details', '!=', 1)
			->orderBy('cases.date', 'DESC')
			->groupBy('activities.id')
		;

		if ($request->get('ticket_date')) {
			$activities->whereRaw('DATE_FORMAT(cases.date,"%d-%m-%Y") =  "' . $request->get('ticket_date') . '"');
		}
		if ($request->get('call_center_id')) {
			$activities->where('cases.call_center_id', $request->get('call_center_id'));
		}
		if ($request->get('case_number')) {
			$activities->where('cases.number', 'LIKE', '%' . $request->get('case_number') . '%');
		}
		if ($request->get('asp_code')) {
			$activities->where('asps.asp_code', 'LIKE', '%' . $request->get('asp_code') . '%');
		}
		if ($request->get('service_type_id')) {
			$activities->where('activities.service_type_id', $request->get('service_type_id'));
		}
		if ($request->get('finance_status_id')) {
			$activities->where('activities.finance_status_id', $request->get('finance_status_id'));
		}
		if ($request->get('status_id')) {
			$activities->where('activities.status_id', $request->get('status_id'));
		}
		if ($request->get('activity_status_id')) {
			$activities->where('activities.activity_status_id', $request->get('activity_status_id'));
		}
		if ($request->get('client_id')) {
			$activities->where('cases.client_id', $request->get('client_id'));
		}

		if (!Entrust::can('verify-all-activities')) {
			if (Entrust::can('verify-mapped-activities')) {
				$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
				$activities->whereIn('asps.state_id', $states);
			}
		}
		return Datatables::of($activities)
			->addColumn('action', function ($activity) {
				$action = '<div class="dataTable-actions">
								<a href="#!/rsa-case-pkg/activity-status/view/' . $activity->id . '">
					                <i class="fa fa-eye dataTable-icon--view" aria-hidden="true"></i>
					            </a>
					            </div>';
				return $action;
			})
			->make(true);
	}

	public function viewActivityStatus($activity_status_id) {
		$this->data['activities'] = $activity = Activity::with([
			'asp',
			'asp.rms',
			'asp.state',
			'asp.district',
			'asp.location',
			'asp.taxGroup',
			'asp.taxGroup.taxes',
			'serviceType',
			'case',
			'case.callcenter',
			'financeStatus',
		])->select(
			'activities.id as activity_id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			//DB::raw('DATE_FORMAT(asps.asp_reached_date,"%d-%m-%Y %H:%i:%s") as asp_r_date'),
			'cases.number',
			'activities.asp_po_accepted as asp_po_accepted',
			'cases.vehicle_registration_number',
			'case_statuses.name as case_status',
			'vehicle_models.name as vehicle_model',
			'vehicle_makes.name as vehicle_make',
			'asps.asp_code',
			'activities.asp_id as asp_id',
			'activities.service_type_id as service_type_id',
			//'asps.name',
			'service_types.name as service',
			'activity_finance_statuses.name as asp_status',
			DB::raw('IF(activities.asp_activity_rejected_reason_id IS NULL,"-",asp_activity_rejected_reasons.name) as asp_activity_rejected_reason'),
			//'activity_asp_statuses.name as asp_status',
			'activity_portal_statuses.name as activity_portal_status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'call_centers.name as call_center',
			'asp_po_rejected_reason',
			'activities.description as description',
			DB::raw('IF(activities.remarks IS NULL,"activities.remarks","-") as remarks'),

			//'activities.remarks as remarks',
			'cases.*',
			DB::raw('IF(invoices.invoice_no IS NULL,"-","invoices.invoice_no") as invoice_no'),
			//DB::RAW('invoices.invoice_no) as invoice_no',
			DB::raw('IF(invoices.invoice_amount IS NULL,"-",format(invoices.invoice_amount,2,"en_IN")) as invoice_amount'),
			DB::raw('IF(invoices.invoice_amount IS NULL,"-",format(invoices.invoice_amount,2,"en_IN")) as invoice_amount'),
			DB::raw('IF(invoices.flow_current_status IS NULL,"-",invoices.flow_current_status) as flow_current_status'),
			DB::raw('IF(invoices.start_date IS NULL,"-",DATE_FORMAT(invoices.start_date,"%d-%m-%Y %H:%i:%s")) as invoice_date')
		)
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('invoices', 'activities.invoice_id', 'invoices.id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('case_statuses', 'case_statuses.id', 'cases.status_id')
			->leftjoin('vehicle_models', 'cases.vehicle_model_id', 'vehicle_models.id')
			->leftjoin('vehicle_makes', 'vehicle_models.vehicle_make_id', 'vehicle_makes.id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftjoin('asp_activity_rejected_reasons', 'asp_activity_rejected_reasons.id', 'activities.asp_activity_rejected_reason_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->groupBy('activities.id')
			->where('activities.id', $activity_status_id)
			->first();
			//dd($activity);
			$key_list = [ 158,159,160,154,155,156,170,174,180,298,179,176,172,173,179,];
			foreach($key_list as $keyw){
				$var_key = Config::where('id',$keyw)->first();
				$key_name = str_replace(" ","_",strtolower($var_key->name));
				$var_key_val = ActivityDetail::where('activity_id',$activity_status_id)->where('key_id',$var_key->id)->first();
				if(strpos($key_name, 'amount') || strpos($key_name, 'collected') || strcmp("amount",$key_name)==0){
					$this->data['activities'][$key_name] = $var_key_val ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",","",number_format($var_key_val->value,2))) : '0.00';
					$raw_key_name = 'raw_'.$key_name;
					$this->data['activities'][$raw_key_name] =$var_key_val ? $var_key_val->value :0;
				}else{
					$this->data['activities'][$key_name] = $var_key_val ? $var_key_val->value :0;
				}
			}
			$this->data['activities']['asp_service_type_data'] = AspServiceType::where('asp_id',$activity->asp_id)->where('service_type_id',$activity->service_type_id)->first();
			/* 
			 $config_ids = [302,303,300,304,296,297,284,285,283,286,287,289,290,288,291,280,299,281,280,282,305,306,307,308];*/
			$configs = Config::where('entity_type_id',23)->get();
			 foreach($configs as $config){
				$detail = ActivityDetail::where('activity_id',$activity_status_id)->where('key_id',$config->id)->first();
				if(strpos($config->name, '_charges') || strpos($config->name, '_amount')){
					
					$this->data['activities'][$config->name] = $detail->value ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",","",number_format($detail->value,2))) : '0.00';
					$raw_key_name = 'raw_'.$config->name;
					$this->data['activities'][$raw_key_name] =$detail->value ?$detail->value :0;
				}else{
					$this->data['activities'][$config->name]= $detail->value ?$detail->value :'-';
				}
			 }
		if (!$activity) {
			return response()->json(['success' => false, 'data' => "Activity not Found!!!"]);
		}
		return response()->json(['success' => true, 'data' => $this->data]);
		
	}
	

	public function approveActivity(Request $request){
		//dd($request->all());
		//dd('dd');
		DB::beginTransaction();
		try {
			$activty = Activity::findOrFail($request->activity_id);
			if (!$activty) {
				$this->data['success'] = true;
			$this->data['errors'][] = 'Activity not found.';
				return response()->json(['data' => $this->data]);
			}

			//CHECK BO KM > ASP KM
			/*if ($request->bo_km_travelled > $activty->asp_km) {
				return redirect()->back()->with(['error' => 'Final KM should be less than or equal to ASP KM']);
			}*/
			//old code to clarify
			/*if ($activty->is_self) {
				$activty->flow_current_status = "Waiting for Invoice Generation";
			} else {
				$activty->flow_current_status = "Waiting for Invoice Generation";
			}*/
			//on hold
			/*if (!empty($request->is_exceptional_check)) {
				$activty->is_exceptional = $request->is_exceptional_check;
				$activty->exceptional_reason = $request->exceptional_reason_check;
			}*/

			//calculaing mis invoice amount
			
			//dd($payout);
			// $prices = getKMPrices($service, $activty->asp, $activty);
			// if($prices['success']){
			//     $payout = calculatePayout($prices['asp_service_price'],$km_travelled);
			// }else{
			//     return redirect()->back()->with(['error' => $prices['error']]);
			// }
			// $mis_service_total = ($payout+$not_collected_by_asp)-$collected_by_asp;
			//dd(Auth::user());
			$key_list = [ 158,159,160,176,172,173,179,182,];
			foreach($key_list as $keyw){
				$var_key = Config::where('id',$keyw)->first();
				$key_name = str_replace(" ","_",strtolower($var_key->name));
				$value = $request->$key_name ? str_replace(",","",$request->$key_name) : 0;
				$var_key_val = DB::table('activity_details')->updateOrInsert(['activity_id' => $request->activity_id, 'key_id' => $keyw,'company_id' =>1], ['value' => $value]);
			}

			$activty->status_id = 11;
			//$activty->is_exceptional = $request->is_exceptional_check;
			//$activty->exceptional_reason = empty($request->is_exceptional_check) ? '' : $request->exceptional_reason_check;
			$activty->save();
			$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_APPROVED_DEFERRED');
			$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_APPROVED');
			logActivity2(config('constants.entity_types.ticket'), $activty->id, [
				'Status' => $log_status,
				'Waiting for' => $log_waiting,

			]);

			//sending confirmation SMS to ASP
			// $mobile_number = $activty->asp->contact_number1;
			// $sms_message = 'BO_APPROVED';
			// sendSMS2($sms_message,$mobile_number,$activty->number);

			$mobile_number = $activty->asp->contact_number1;
			$sms_message = 'BO_APPROVED';
			$array = [$activty->number];
			// sendSMS2($sms_message, $mobile_number, $array);

			//sending notification to all BO users
			$asp_user = $activty->asp->user_id;
			$noty_message_template = 'BO_APPROVED';
			$number = [$activty->number];
			notify2($noty_message_template, $asp_user, config('constants.alert_type.blue'), $number);

			DB::commit();
			$this->data['success'] = true;
			$this->data['message'] = 'Ticket approved successfully.';
			return response()->json(['data' => $this->data]);

		} catch (\Exception $e) {
			DB::rollBack();
			dd($e);
			$message = ['error' => $e->getMessage()];
			$this->data['success'] = false;
			$this->data['message'] = 'Ticket deferred to ASP successfully.';
			return response()->json(['data' => $this->data]);
			
		}
	}

	public function saveActivityDiffer(Request $request) {
		DB::beginTransaction();
		try {
			//dd($request->all());
			$activity = Activity::findOrFail($request->activity_id);
			$activity->status_id = 7;
			//$activity->comments = $request->reason;
			$activity->save();

			//Saving log record

			$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_DEFERED_DONE');
			$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_DEFERRED');
			logActivity2(config('constants.entity_types.ticket'), $activity->id, [
				'Status' => $log_status,
				'Waiting for' => $log_waiting,
			]);

			//SMS record
			$mobile_number = $activity->asp->contact_number1;
			$sms_message = 'BO_DEFERRED';
			$array = [$activity->number];
			sendSMS2($sms_message, $mobile_number, $array);

			//sending notification to all BO users
			$asp_user = $activity->asp->user_id;
			$noty_message_template = 'BO_DEFERRED';
			$number = [$activity->number];
			notify2($noty_message_template, $asp_user, config('constants.alert_type.red'), $number);

			DB::commit();
			$this->data['success'] = true;
			$this->data['message'] = 'Ticket deferred successfully.';
			return response()->json(['data' => $this->data]);

			//return redirect()->route('boTickets')->with(['success' => 'Ticket deferred to ASP successfully.']);

		} catch (\Exception $e) {
			DB::rollBack();
			$message = ['error' => $e->getMessage()];
			$this->data['success'] = false;
			return response()->json(['data' => $this->data]);

			//return redirect()->back()->with($message)->withInput();
		}
	}
}
