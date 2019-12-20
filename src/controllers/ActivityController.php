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
		//$activity_status_id = 1;
		//dd($activity_status_id);
		$this->data['activities'] = $activity = Activity::with([
			'asp',
			'serviceType',
			'case',
			'case.callcenter',
			'finance',

		])->select(
			'activities.id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
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
			$key_list = [ 'BO KM Travelled','BO Collected','BO Not Collected','ASP KM Travelled','ASP Collected','ASP Not Collected','CC PO Amount','CC Net Amount','CC Amount','amount'];
			foreach($key_list as $keyw){
				$key_name = str_replace(" ","_",strtolower($keyw));
				$var_key = Config::where('name',$keyw)->first();
				$var_key_val = ActivityDetail::where('activity_id',$activity_status_id)->where('key_id',$var_key->id)->first();
				if(strpos($key_name, 'amount') || strpos($key_name, 'collected') || strcmp("amount",$key_name)==0){
					$this->data['activities'][$key_name] = $var_key_val ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",","",number_format($var_key_val->value,2))) : '0.00';
				}else{
					$this->data['activities'][$key_name] = $var_key_val ? $var_key_val->value :0;
				}
			}
			$this->data['activities']['asp_service_type_data'] = AspServiceType::where('asp_id',$activity->asp_id)->where('service_type_id',$activity->service_type_id)->first();
			 
			 $config_ids = [302,303,300,304,296,297,284,285,283,286,287,289,290,288,291,280,299,281,280,282,305,306,307,308];
			 foreach($config_ids as $config_id){
			 	$config = Config::where('id',$config_id)->first();
				$detail = ActivityDetail::where('activity_id',$activity_status_id)->where('key_id',$config_id)->first();
				if(strpos($config->name, '_charges') || strpos($config->name, '_amount')){
					
					$this->data['activities'][$config->name] = $detail->value ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",","",number_format($detail->value,2))) : '0.00';
				}else{
					$this->data['activities'][$config->name]= $detail->value ?$detail->value :'-';
				}
			 }
		if (!$activity) {
			return response()->json(['success' => false, 'data' => "Activity not Found!!!"]);
		}
		return response()->json(['success' => true, 'data' => $this->data]);
		
	}

}
