<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityPortalStatus;
use Abs\RsaCasePkg\ActivityStatus;
use Abs\RsaCasePkg\RsaCase;
use App\Asp;
use App\AspServiceType;
use App\Attachment;
use App\CallCenter;
use App\Client;
use App\Config;
use App\Http\Controllers\Controller;
use App\Invoices;
use App\ServiceType;
use App\StateUser;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Validator;
use Yajra\Datatables\Datatables;

class ActivityController extends Controller {

	public function getFilterData() {
		$this->data['extras'] = [
			'call_center_list' => collect(CallCenter::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Call Center']),
			'service_type_list' => collect(ServiceType::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Sub Service']),
			'finance_status_list' => collect(ActivityFinanceStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Finance Status']),
			'portal_status_list' => collect(ActivityPortalStatus::select('name', 'id')->where('company_id', 1)->get()),
			'status_list' => collect(ActivityPortalStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Portal Status']),
			'activity_status_list' => collect(ActivityStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Activity Status']),
			'client_list' => collect(Client::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Client']),
			'export_client_list' => collect(Client::select('name', 'id')->get()),
			'asp_list' => collect(Asp::select('name', 'asp_code', 'id')->get()),
		];
		$this->data['auth_user_details'] = Auth::user();
		return response()->json($this->data);
	}

	public function getList(Request $request) {
		// dd($request->all());
		$from_date = null;
        $end_date = null;
        if (isset($request->date_range_period) && !empty($request->date_range_period)) {
            $period = explode(' to ', $request->date_range_period);
            $from = $period[0];
            $to = $period[1];
            $from_date = date('Y-m-d', strtotime($from));
            $end_date = date('Y-m-d', strtotime($to));
        }

		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			'activities.status_id as status_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			// 'asps.asp_code',
			DB::raw('CONCAT(asps.asp_code," / ",asps.name) as asp'),
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'configs.name as source',
			'call_centers.name as call_center'
		)

			->where(function ($query) use ($from_date, $end_date) {
                if (!empty($from_date) && !empty($end_date)) {
                    $query->whereRaw('DATE(cases.date) between "' . $from_date . '" and "' . $end_date . '"');
                }
            })
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftjoin('configs', 'configs.id', 'activities.data_src_id')
		// ->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->orderBy('cases.date', 'DESC')
			->groupBy('activities.id')
		;

		// if ($request->get('ticket_date')) {
		// 	$activities->whereRaw('DATE_FORMAT(cases.date,"%d-%m-%Y") =  "' . $request->get('ticket_date') . '"');
		// }

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
				$activities->where('users.id', Auth::id())
					->whereNotIn('activities.status_id', [2, 4, 15, 16, 17]);
			}
		}
		return Datatables::of($activities)
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->addColumn('action', function ($activity) {
				$status_id = 1;
				$return_status_ids = [5, 6, 8, 9, 11, 1, 7];

				$action = '<div class="dataTable-actions">
				<a href="#!/rsa-case-pkg/activity-status/' . $status_id . '/view/' . $activity->id . '">
					                <i class="fa fa-eye dataTable-icon--view" aria-hidden="true"></i>
					            </a>';
				if (Entrust::can('delete-activities')) {
					$action .= '<a onclick="angular.element(this).scope().deleteConfirm(' . $activity->id . ')" href="javascript:void(0)">
						                <i class="fa fa-trash dataTable-icon--trash cl-delete" data-cl-id =' . $activity->id . ' aria-hidden="true"></i>
						            </a>';
				}
				if (Entrust::can('backstep-activity') && in_array($activity->status_id, $return_status_ids)) {
					$action .= "<a href='javascript:void(0)' onclick='angular.element(this).scope().backConfirm(" . $activity . ")' class='ticket_back_button'><i class='fa fa-arrow-left dataTable-icon--edit-1' data-cl-id =" . $activity->id . " aria-hidden='true'></i></a>";
				}
				$action .= '</div>';
				return $action;
			})
			->make(true);
	}

	public function activityBackAsp(Request $request) {
		//	dd($request->all());
		$activity = Activity::findOrFail($request->activty_id);
		$return_status_ids = [5, 6, 8, 9, 11, 1, 7];

		if (!$activity) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['error' => 'Activity not found']);
		}

		if (!in_array($activity->status_id, $return_status_ids)) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['error' => 'Activity Cannot able move to asp']);
		}

		if (!Entrust::can('backstep-activity')) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['error' => 'Admin only have authorized to move asp']);
		}

		//WAITING FOR ASP DATA ENTRY
		if ($request->ticket_status_id == '1') {
			$activity->status_id = 2;
			$activity->updated_at = new Carbon();
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();

			if ($activity) {
				//log message
				$log_status = config('rsa.LOG_STATUES_TEMPLATES.ADMIN_TICKET_BACK_ASP');
				$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.ADMIN_TICKET_BACK_ASP');
				logActivity3(config('constants.entity_types.ticket'), $activity->id, [
					'Status' => $log_status,
					'Waiting for' => $log_waiting,
				], 361);

				$noty_message_template = 'WAITING_FOR_ASP_DATA_ENTRY';
				$user_id = $activity->asp->user->id;
				$number = [$activity->number];
				notify2($noty_message_template, $user_id, config('constants.alert_type.blue'), $number);

				return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['success' => 'Activity status moved to ASP data entry']);
			} else {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['error' => 'Activity status not moved to ASP data entry']);
			}
		} elseif ($request->ticket_status_id == '2') {
			//WAITING FOR ASP - BO DEFERRED
			$activity->status_id = 7;
			$activity->updated_at = new Carbon();
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();

			if ($activity) {
				//log message
				$log_status = config('rsa.LOG_STATUES_TEMPLATES.ADMIN_TICKET_BACK_BO_DEFERRED');
				$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.ADMIN_TICKET_BACK_BO_DEFERRED');
				logActivity3(config('constants.entity_types.ticket'), $activity->id, [
					'Status' => $log_status,
					'Waiting for' => $log_waiting,
				], 361);

				$noty_message_template = 'BO_DEFERRED';
				$user_id = $activity->asp->user->id;
				$number = [$activity->number];
				notify2($noty_message_template, $user_id, config('constants.alert_type.blue'), $number);

				return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['success' => 'Activity status moved to ASP BO deferred']);
			} else {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['error' => 'Activity status not moved to ASP BO deferred']);
			}
		}
	}

	public function delete($id) {
		Activity::where('id', $id)->delete();
		return response()->json(['success' => true]);
	}

	public function getBulkVerificationList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			DB::raw('CONCAT(asps.asp_code," / ",asps.name) as asp'),
			// 'asps.asp_code',
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'configs.name as source',
			'clients.name as client',
			'call_centers.name as call_center'
		)
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftjoin('configs', 'configs.id', 'activities.data_src_id')
		// ->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
		// ->where('activities.asp_accepted_cc_details', '!=', 1)
			->whereIn('activities.status_id', [5, 8])
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
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->setRowAttr([
				'id' => function ($activities) {
					return route('angular') . '/#!/rsa-case-pkg/activity-verification/2/view/' . $activities->id;
				},
			])
			->addColumn('action', function ($activities) {
				return '<input type="checkbox" class="ticket_id no-link child_select_all" name="activity_ids[]" value="' . $activities->id . '">';
			})
			->make(true);
	}

	public function getIndividualVerificationList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			DB::raw('CONCAT(asps.asp_code," / ",asps.name) as asp'),
			// 'asps.asp_code',
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'configs.name as source',
			'clients.name as client',
			'call_centers.name as call_center'
		)
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftjoin('configs', 'configs.id', 'activities.data_src_id')
		// ->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
		// ->where('activities.asp_accepted_cc_details', '!=', 1)
			->whereIn('activities.status_id', [6, 9])
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
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->setRowAttr([
				'id' => function ($activities) {
					return route('angular') . '/#!/rsa-case-pkg/activity-verification/2/view/' . $activities->id;
				},
			])
			->make(true);
	}

	public function viewActivityStatus($view_type_id = NULL, $activity_status_id) {
		//dd($view_type_id);
		$activity_data = Activity::findOrFail($activity_status_id);
		if ($view_type_id == 2) {
			if (!($activity_data && ($activity_data->status_id == 5 || $activity_data->status_id == 6 || $activity_data->status_id == 9 || $activity_data->status_id == 8))) {
				$errors[0] = "Activity is not valid for Verification!!!";
				return response()->json(['success' => false, 'errors' => $errors]);
			}
		}
		$this->data['activities'] = $activity = Activity::with([
			'invoice',
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
			// 'activities.id as activity_id',
			'activities.id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			DB::raw('DATE_FORMAT(activities.created_at,"%d-%m-%Y %H:%i:%s") as activity_date'),
			DB::raw('IF(activities.deduction_reason IS NULL,"-",deduction_reason) as deduction_reason'),
			DB::raw('IF(activities.bo_comments IS NULL,"-",bo_comments) as bo_comments'),
			DB::raw('IF(activities.defer_reason IS NULL,"-",defer_reason) as defer_reason'),
			'cases.number',
			'cases.customer_name as customer_name',
			'cases.vin_no',
			'cases.km_during_breakdown',
			'cases.bd_lat',
			'cases.bd_long',
			'cases.bd_location',
			'cases.bd_city',
			'cases.bd_state',
			'activities.number as activity_number',
			'activities.asp_po_accepted as asp_po_accepted',
			'activities.defer_reason as defer_reason',
			'activities.is_exceptional_check as is_exceptional_check',
			DB::raw('CASE
				    	WHEN (activities.is_exceptional_check = 1)
			    		THEN
			    			CASE
			    				WHEN (activities.exceptional_reason IS NULL)
			   					THEN
			    					"-"
			    				ELSE
			    					activities.exceptional_reason
			    			END
			    		ELSE
			    			"-"
					END as exceptional_reason'),
			//'activities.bo_comments as bo_comments',
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
			DB::raw('IF(activities.remarks IS NULL OR activities.remarks="","-",activities.remarks) as remarks'),
			//'activities.remarks as remarks',
			// 'cases.*',
			// DB::raw('CASE
			// 	    	WHEN (Invoices.invoice_no IS NOT NULL)
			//     		THEN
			//     			CASE
			//     				WHEN (asps.has_gst = 1 && asps.is_auto_invoice = 0)
			//    					THEN
			//     					Invoices.invoice_no
			//     				ELSE
			//     					CONCAT(Invoices.invoice_no,"-",Invoices.id)
			//     			END
			//     		ELSE
			//     			"NA"
			// 		END as invoice_no'),
			'Invoices.invoice_no',
			DB::raw('CASE
				    	WHEN (Invoices.asp_gst_registration_number IS NULL || Invoices.asp_gst_registration_number = "")
			    		THEN
			    			CASE
			    				WHEN (asps.gst_registration_number IS NULL && asps.gst_registration_number = "")
			   					THEN
			    					"NA"
			    				ELSE
			    					asps.gst_registration_number
			    			END
			    		ELSE
			    			Invoices.asp_gst_registration_number
					END as gst_registration_number'),
			DB::raw('CASE
				    	WHEN (Invoices.asp_pan_number IS NULL || Invoices.asp_pan_number = "")
			    		THEN
			    			CASE
			    				WHEN (asps.pan_number IS NULL && asps.pan_number = "")
			   					THEN
			    					"NA"
			    				ELSE
			    					asps.pan_number
			    			END
			    		ELSE
			    			Invoices.asp_pan_number
					END as pan_number'),
			DB::raw('IF(Invoices.invoice_amount IS NULL,"NA",format(Invoices.invoice_amount,2,"en_IN")) as invoice_amount'),
			DB::raw('IF(Invoices.invoice_amount IS NULL,"NA",Invoices.invoice_amount) as inv_amount'),
			DB::raw('IF((asps.has_gst =1 && asps.is_auto_invoice=0),"NO","Yes") as auto_invoice'),
			DB::raw('IF(Invoices.flow_current_status IS NULL,"NA",Invoices.flow_current_status) as flow_current_status'),
			DB::raw('IF(Invoices.created_at IS NULL,"NA",DATE_FORMAT(Invoices.created_at,"%d-%m-%Y")) as invoice_date'),
			'activity_finance_statuses.po_eligibility_type_id',
			'activities.finance_status_id',
			'activities.invoice_id',
			'activities.status_id as activity_portal_status_id',
			'bd_location_type.name as loction_type',
			'bd_location_category.name as location_category'

		)
			->leftJoin('asps', 'asps.id', 'activities.asp_id')
			->leftJoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftJoin('Invoices', 'activities.invoice_id', 'Invoices.id')
			->leftJoin('users', 'users.id', 'asps.user_id')
			->leftJoin('cases', 'cases.id', 'activities.case_id')
			->leftJoin('case_statuses', 'case_statuses.id', 'cases.status_id')
			->leftJoin('vehicle_models', 'cases.vehicle_model_id', 'vehicle_models.id')
			->leftJoin('vehicle_makes', 'vehicle_models.vehicle_make_id', 'vehicle_makes.id')
			->leftJoin('clients', 'clients.id', 'cases.client_id')
			->leftJoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftJoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftJoin('asp_activity_rejected_reasons', 'asp_activity_rejected_reasons.id', 'activities.asp_activity_rejected_reason_id')
			->leftJoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftJoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->leftJoin('configs as bd_location_type', 'bd_location_type.id', 'cases.bd_location_type_id')
			->leftJoin('configs as bd_location_category', 'bd_location_category.id', 'cases.bd_location_category_id')
			->groupBy('activities.id')
			->where('activities.id', $activity_status_id)
			->first();
		$this->data['activities']['km_travelled_attachments'] = $km_travelled_attachments = Attachment::where([['entity_id', '=', $activity_status_id], ['entity_type', '=', 16]])->get();
		$this->data['activities']['other_charges_attachments'] = $other_charges_attachments = Attachment::where([['entity_id', '=', $activity_status_id], ['entity_type', '=', 17]])->get();

		$ccServiceTypeValue = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 153)
			->first();
		$hasccServiceType = false;
		if ($ccServiceTypeValue) {
			$ccServiceType = ServiceType::where('name', $ccServiceTypeValue->value)->first();
			if ($ccServiceType) {
				$hasccServiceType = true;
			}
		}

		$aspServiceTypeValue = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 157)
			->first();
		$hasaspServiceType = false;
		if ($aspServiceTypeValue) {
			$aspServiceType = ServiceType::where('name', $aspServiceTypeValue->value)->first();
			if ($aspServiceType) {
				$hasaspServiceType = true;
			}
		}

		$other_charges_attachment_url = $km_travelled_attachment_url = [];
		if ($km_travelled_attachments->isNotEmpty()) {
			foreach ($km_travelled_attachments as $key => $km_travelled_attachment) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $km_travelled_attachment->attachment_file_name)) {
						$km_travelled_attachment_url[$key] = aspTicketAttachmentImage($km_travelled_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}
				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $km_travelled_attachment->attachment_file_name)) {
						$km_travelled_attachment_url[$key] = aspTicketAttachmentImage($km_travelled_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
				// if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $activity->serviceType->id . '/' . $km_travelled_attachment->attachment_file_name)) {
				// 	$km_travelled_attachment_url[$key] = aspTicketAttachmentImage($km_travelled_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $activity->serviceType->id);
				// }
			}
		}
		if ($other_charges_attachments->isNotEmpty()) {
			foreach ($other_charges_attachments as $key => $other_charges_attachment) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $other_charges_attachment->attachment_file_name)) {
						$other_charges_attachment_url[$key] = aspTicketAttachmentImage($other_charges_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}
				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $other_charges_attachment->attachment_file_name)) {
						$other_charges_attachment_url[$key] = aspTicketAttachmentImage($other_charges_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
				// if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $activity->serviceType->id . '/' . $other_charges_attachment->attachment_file_name)) {
				// 	$other_charges_attachment_url[$key] = aspTicketAttachmentImage($other_charges_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $activity->serviceType->id);
				// }
			}
		}
		$this->data['activities']['km_travelled_attachment_url'] = $km_travelled_attachment_url;
		$this->data['activities']['other_charges_attachment_url'] = $other_charges_attachment_url;
		//dd($this->data['activities']['km_travelled_attachment']->attachment_file_name,$activity_status_id,,$activity->serviceType->id);
		$key_list = [153, 157, 161, 158, 159, 160, 154, 155, 156, 170, 174, 180, 298, 179, 176, 172, 173, 179, 182, 171, 175, 181];
		foreach ($key_list as $keyw) {
			$var_key = Config::where('id', $keyw)->first();
			$key_name = str_replace(" ", "_", strtolower($var_key->name));
			$var_key_val = ActivityDetail::where('activity_id', $activity_status_id)->where('key_id', $var_key->id)->first();
			$raw_key_name = 'raw_' . $key_name;
			if (strpos($key_name, 'amount') || strpos($key_name, 'collected') || strcmp("amount", $key_name) == 0) {
				$this->data['activities'][$key_name] = $var_key_val ? (!empty($var_key_val->value) ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($var_key_val->value, 2))) : 0) : 0;
				$this->data['activities'][$raw_key_name] = $var_key_val ? (!empty($var_key_val->value) ? $var_key_val->value : 0) : 0;
			} else {
				$this->data['activities'][$key_name] = $var_key_val ? (!empty($var_key_val->value) ? $var_key_val->value : 0) : 0;
				$this->data['activities'][$raw_key_name] = $var_key_val ? (!empty($var_key_val->value) ? $var_key_val->value : 0) : 0;
			}

		}

		/*$this->data['activities']['invoice_activities'] = Activity::with(['case','serviceType','activityDetail'])->where('invoice_id',$activity->invoice_id)->get();*/
		if ($activity->invoice_id) {

			$this->data['activities']['invoice_activities'] = $invoice_activities = Activity::join('cases', 'cases.id', 'activities.case_id')
				->join('service_types', 'service_types.id', 'activities.service_type_id')
				->leftJoin('activity_details as km_charge', function ($join) {
					$join->on('km_charge.activity_id', 'activities.id')
						->where('km_charge.key_id', 172); //BO PO AMOUNT OR KM CHARGE
				})
				->leftJoin('activity_details as km_travelled', function ($join) {
					$join->on('km_travelled.activity_id', 'activities.id')
						->where('km_travelled.key_id', 158); //BO KM TRAVELLED
				})
				->leftJoin('activity_details as net_amount', function ($join) {
					$join->on('net_amount.activity_id', 'activities.id')
						->where('net_amount.key_id', 176); //BO NET AMOUNT
				})
				->leftJoin('activity_details as collect_amount', function ($join) {
					$join->on('collect_amount.activity_id', 'activities.id')
						->where('collect_amount.key_id', 159); //BO COLLECT AMOUNT
				})
				->leftJoin('activity_details as not_collected_amount', function ($join) {
					$join->on('not_collected_amount.activity_id', 'activities.id')
						->where('not_collected_amount.key_id', 160); //BO NOT COLLECT AMOUNT
				})
				->leftJoin('activity_details as total_tax_perc', function ($join) {
					$join->on('total_tax_perc.activity_id', 'activities.id')
						->where('total_tax_perc.key_id', 185); //BO TOTAL TAX PERC
				})
				->leftJoin('activity_details as total_tax_amount', function ($join) {
					$join->on('total_tax_amount.activity_id', 'activities.id')
						->where('total_tax_amount.key_id', 179); //BO TOTAL TAX AMOUNT
				})
				->leftJoin('activity_details as total_amount', function ($join) {
					$join->on('total_amount.activity_id', 'activities.id')
						->where('total_amount.key_id', 182); //BO INVOICE AMOUNT
				})
				->select(
					'cases.number',
					'activities.id',
					'activities.asp_id',
					'activities.crm_activity_id',
					DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y")as date'),
					'cases.vehicle_registration_number',
					'service_types.name as service_type',
					'km_charge.value as km_charge_value',
					'km_travelled.value as km_value',
					'not_collected_amount.value as not_collect_value',
					'net_amount.value as net_value',
					'collect_amount.value as collect_value',
					'total_amount.value as total_value',
					'total_tax_perc.value as total_tax_perc_value',
					'total_tax_amount.value as total_tax_amount_value'
				)
				->where('invoice_id', $activity->invoice_id)
				->groupBy('activities.id')
				->get();

			//CHECK NEW/OLD COMPANY ADDRESS BY INVOICE CREATION DATE
			$invoice = Invoices::find($activity->invoice_id);
			$inv_created = date('Y-m-d', strtotime(str_replace('/', '-', $invoice->created_at)));
			$automobile_company_effect_date = config('rsa.AUTOMOBILE_COMPANY_EFFECT_DATE');
			$ki_company_effect_date = config('rsa.KI_COMPANY_EFFECT_DATE');

			$this->data['activities']['auto_assist_company_address'] = false;
			$this->data['activities']['automobile_company_address'] = false;
			$this->data['activities']['ki_company_address'] = false;

			if ($inv_created < $automobile_company_effect_date) {
				$this->data['activities']['auto_assist_company_address'] = true;
			} elseif ($inv_created >= $automobile_company_effect_date && $inv_created < $ki_company_effect_date) {
				$this->data['activities']['automobile_company_address'] = true;
			} else {
				$this->data['activities']['ki_company_address'] = true;
			}

			$this->data['activities']['invoice_amount_in_word'] = getIndianCurrency($activity->inv_amount);
			if (count($invoice_activities) > 0) {
				foreach ($invoice_activities as $key => $invoice_activity) {
					$taxes = DB::table('activity_tax')->leftjoin('taxes', 'activity_tax.tax_id', '=', 'taxes.id')
						->where('activity_id', $invoice_activity->id)
						->select('taxes.tax_name', 'taxes.tax_rate', 'activity_tax.*')
						->get();
					$invoice_activity->taxes = $taxes;
				}
			}

			$this->data['activities']['signature_attachment'] = Attachment::where('entity_id', $invoice_activities[0]->asp_id)->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))->first();

			$this->data['activities']['signature_attachment_path'] = url('storage/' . config('rsa.asp_attachment_path_view'));

		}
		$this->data['activities']['asp_service_type_data'] = AspServiceType::where('asp_id', $activity->asp_id)
			->where('service_type_id', $activity->service_type_id)
			->first();
		$configs = Config::where('entity_type_id', 23)->get();
		foreach ($configs as $config) {
			$detail = ActivityDetail::where('activity_id', $activity_status_id)->where('key_id', $config->id)->first();
			if (strpos($config->name, '_charges') || strpos($config->name, '_amount')) {

				$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($detail->value, 2))) : '-') : '-';
				$raw_key_name = 'raw_' . $config->name;
				$this->data['activities'][$raw_key_name] = $detail ? (!empty($detail->value) ? $detail->value : '-') : '-';
			} elseif (strpos($config->name, 'date')) {
				$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? date("d-m-Y H:i:s", strtotime($detail->value)) : '-') : '-';
			} else {
				$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? $detail->value : '-') : '-';
			}
			//dump($config->name,$this->data['activities'][$config->name]);
		}
		/*if ($this->data['activities']['asp_service_type_data']->adjustment_type == 1) {
				$this->data['activities']['bo_deduction'] = ($this->data['activities']['raw_bo_po_amount'] * $this->data['activities']['asp_service_type_data']->adjustment) / 100;
			} elseif ($this->data['activities']['asp_service_type_data']->adjustment_type == 2) {
				//AMOUNT
				$this->data['activities']['bo_deduction'] = $this->data['activities']['asp_service_type_data']->adjustment;
		*/

		//FOR DIFFERENCE HIGHLIGHT
		$is_service_type_eligible = true;
		$is_km_travelled_eligible = true;
		$is_not_collected_eligible = true;
		$is_collected_eligible = true;
		$cc_service_type = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 153)
			->first();
		$asp_service_type = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 157)
			->first();
		if ($cc_service_type && $asp_service_type) {
			//Service Type
			if ($cc_service_type->value != $asp_service_type->value) {
				$is_service_type_eligible = false;
			}
			//KM Travelled
			$service_type = ServiceType::where('name', $cc_service_type->value)->first();
			if ($service_type) {
				$aspServiceType = AspServiceType::where('asp_id', $activity->asp_id)
					->where('service_type_id', $service_type->id)
					->first();
				if ($aspServiceType) {
					$range_limit = $aspServiceType->range_limit;
				} else {
					$range_limit = 0;
				}
				$cc_km_travelled = ActivityDetail::where('activity_id', $activity_status_id)
					->where('key_id', 280)
					->first();
				$asp_km_travelled = ActivityDetail::where('activity_id', $activity_status_id)
					->where('key_id', 154)
					->first();
				if ($cc_km_travelled && $asp_km_travelled) {
					$mis_km = floatval($cc_km_travelled->value);
					$asp_km = floatval($asp_km_travelled->value);

					$allowed_variation = 0.5;
					$five_percentage_difference = $mis_km * $allowed_variation / 100;
					if ($asp_km > $range_limit || $range_limit == 0) {
						if ($asp_km > $mis_km) {
							$km_difference = $asp_km - $mis_km;
							if ($km_difference > $five_percentage_difference) {
								$is_km_travelled_eligible = false;
							}
						}
					}
					if ($asp_km > $range_limit) {
						$is_km_travelled_eligible = false;
					}
				} else {
					$is_km_travelled_eligible = false;
				}
			} else {
				$is_km_travelled_eligible = false;
			}

		} else {
			$is_service_type_eligible = false;
			$is_km_travelled_eligible = false;
		}

		$cc_collected = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 281)
			->first();
		$cc_not_collected = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 282)
			->first();
		$asp_collected = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 155)
			->first();
		$asp_not_collected = ActivityDetail::where('activity_id', $activity_status_id)
			->where('key_id', 156)
			->first();

		//Not Collected Amount
		if ($cc_not_collected && $asp_not_collected) {
			$cc_not_collected_amt = floatval($cc_not_collected->value);
			$asp_not_collected_amt = floatval($asp_not_collected->value);
			if ($asp_not_collected_amt > $cc_not_collected_amt) {
				$is_not_collected_eligible = false;
			}
		} else {
			$is_not_collected_eligible = false;
		}

		//Collected Amount
		if ($cc_collected && $asp_collected) {
			$cc_collected_amt = floatval($cc_collected->value);
			$asp_collected_amt = floatval($asp_collected->value);
			if ($asp_collected_amt < $cc_collected_amt) {
				$is_collected_eligible = false;
			}
		} else {
			$is_collected_eligible = false;
		}

		$activityInfo = Activity::find($activity->id);
		if (!empty($activityInfo->case->submission_closing_date)) {
			$submission_closing_date = date('d-m-Y H:i:s', strtotime($activityInfo->case->submission_closing_date));
		} else {
			$submission_closing_date = date('d-m-Y H:i:s', strtotime("+3 months", strtotime($activityInfo->case->created_at)));
		}

		$is_case_lapsed = false;
		$case_lapsed_date = date('Y-m-d H:i:s', strtotime("+3 months", strtotime($activityInfo->case->created_at)));
		if (date('Y-m-d H:i:s') > $case_lapsed_date) {
			$is_case_lapsed = true;
		}

		$this->data['activities']['is_service_type_eligible'] = $is_service_type_eligible;
		$this->data['activities']['is_km_travelled_eligible'] = $is_km_travelled_eligible;
		$this->data['activities']['is_not_collected_eligible'] = $is_not_collected_eligible;
		$this->data['activities']['is_collected_eligible'] = $is_collected_eligible;
		$this->data['activities']['is_case_lapsed'] = $is_case_lapsed;
		$this->data['activities']['submission_closing_date'] = $submission_closing_date;

		return response()->json(['success' => true, 'data' => $this->data]);

	}

	public function approveActivity(Request $request) {
		//dd($request->all());
		DB::beginTransaction();
		try {
			$activity = Activity::findOrFail($request->activity_id);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => ['Activity not found'],
				]);
			}

			$asp_km_travelled = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 154]])->first();
			if (!$asp_km_travelled) {
				return response()->json([
					'success' => false,
					'errors' => ['Activity ASP KM not found'],
				]);
			}

			//CHECK BO KM > ASP KM
			if ($request->bo_km_travelled > $asp_km_travelled->value) {
				return response()->json([
					'success' => false,
					'errors' => ['Final KM should be less than or equal to ASP KM'],
				]);
			}
			$key_list = [158, 159, 160, 176, 172, 173, 179, 182];
			foreach ($key_list as $keyw) {
				$var_key = Config::where('id', $keyw)->first();
				$key_name = str_replace(" ", "_", strtolower($var_key->name));
				$value = $request->$key_name ? str_replace(",", "", $request->$key_name) : 0;
				$var_key_val = DB::table('activity_details')->updateOrInsert(
					[
						'activity_id' => $request->activity_id,
						'key_id' => $keyw, 'company_id' => 1,
					],
					[
						'value' => $value,
					]
				);
			}
			if (isset($request->is_exceptional_check)) {
				$activity->is_exceptional_check = $request->is_exceptional_check;
				if ($request->is_exceptional_check) {
					$activity->exceptional_reason = isset($request->exceptional_reason) ? $request->exceptional_reason : NULL;
				}
			}
			$activity->bo_comments = isset($request->bo_comments) ? $request->bo_comments : NULL;
			$activity->deduction_reason = isset($request->deduction_reason) ? $request->deduction_reason : NULL;
			$activity->status_id = 11;
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();
			$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_APPROVED_DEFERRED');
			$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_APPROVED');
			logActivity3(config('constants.entity_types.ticket'), $activity->id, [
				'Status' => $log_status,
				'Waiting for' => $log_waiting,
			], 361);

			$activity_log = ActivityLog::firstOrNew([
				'activity_id' => $activity->id,
			]);
			$activity_log->bo_approved_at = date('Y-m-d H:i:s');
			$activity_log->updated_by_id = Auth::id();
			$activity_log->save();

			//sending confirmation SMS to ASP
			// $mobile_number = $activity->asp->contact_number1;
			// $sms_message = 'Tkt waiting for Invoice';
			// sendSMS2($sms_message,$mobile_number,$activity->number);

			$mobile_number = $activity->asp->contact_number1;
			$sms_message = 'Tkt waiting for Invoice';
			$array = [$request->case_number];
			sendSMS2($sms_message, $mobile_number, $array);

			//sending notification to all BO users
			$asp_user = $activity->asp->user_id;
			$noty_message_template = 'BO_APPROVED';
			$number = [$request->case_number];
			notify2($noty_message_template, $asp_user, config('constants.alert_type.blue'), $number);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity approved successfully.',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function bulkApproveActivity(Request $request) {
		//dd($request->all());
		DB::beginTransaction();
		try {
			if (empty($request->activity_ids)) {
				return response()->json([
					'success' => false,
					'error' => 'Please select atleast one activity',
				]);
			}

			$activities = Activity::whereIn('id', $request->activity_ids)->get();
			if (count($activities) == 0) {
				return response()->json([
					'success' => false,
					'error' => 'Activities not found',
				]);
			}

			foreach ($activities as $key => $activity) {

				$activity->status_id = 11;
				$activity->updated_by_id = Auth::user()->id;
				$activity->save();

				$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_APPROVED_BULK');
				$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_APPROVED');
				logActivity3(config('constants.entity_types.ticket'), $activity->id, [
					'Status' => $log_status,
					'Waiting for' => $log_waiting,
				], 361);

				$activity_log = ActivityLog::firstOrNew([
					'activity_id' => $activity->id,
				]);
				$activity_log->bo_approved_at = date('Y-m-d H:i:s');
				$activity_log->updated_by_id = Auth::id();
				$activity_log->save();

				$mobile_number = $activity->asp->contact_number1;
				$sms_message = 'Tkt waiting for Invoice';
				$array = [$activity->case->number];
				sendSMS2($sms_message, $mobile_number, $array);

				//sending notification to all BO users
				$asp_user = $activity->asp->user_id;
				$noty_message_template = 'BO_APPROVED';
				$number = [$activity->case->number];
				notify2($noty_message_template, $asp_user, config('constants.alert_type.blue'), $number);
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activities approved successfully.',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function saveActivityDiffer(Request $request) {
		DB::beginTransaction();
		try {
			$activity = Activity::findOrFail($request->activity_id);
			$activity->status_id = 7;
			$activity->defer_reason = isset($request->defer_reason) ? $request->defer_reason : NULL;
			$activity->bo_comments = isset($request->bo_comments) ? $request->bo_comments : NULL;
			$activity->deduction_reason = isset($request->deduction_reason) ? $request->deduction_reason : NULL;
			//$activity->comments = $request->reason;
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();

			//Saving log record

			$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_DEFERED_DONE');
			$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_DEFERRED');
			logActivity3(config('constants.entity_types.ticket'), $activity->id, [
				'Status' => $log_status,
				'Waiting for' => $log_waiting,
			], 361);

			$activity_log = ActivityLog::firstOrNew([
				'activity_id' => $activity->id,
			]);
			$activity_log->bo_deffered_at = date('Y-m-d H:i:s');
			$activity_log->updated_by_id = Auth::id();
			$activity_log->save();

			//SMS record
			$mobile_number = $activity->asp->contact_number1;
			$sms_message = 'Deferred Tkt re-entry';
			$array = [$request->case_number];
			sendSMS2($sms_message, $mobile_number, $array);

			//sending notification to all BO users
			$asp_user = $activity->asp->user_id;
			$noty_message_template = 'BO_DEFERRED';
			$number = [$request->case_number];
			notify2($noty_message_template, $asp_user, config('constants.alert_type.red'), $number);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity deferred successfully.',
			]);

			//return redirect()->route('boActivitys')->with(['success' => 'Ticket deferred to ASP successfully.']);

		} catch (\Exception $e) {
			DB::rollBack();
			$message = ['error' => $e->getMessage()];
			$this->data['success'] = false;
			return response()->json(['data' => $this->data]);

			//return redirect()->back()->with($message)->withInput();
		}
	}
	public function verifyActivity(Request $request) {
		// dd($request->all());
		$number = $request->number;
		$validator = Validator::make($request->all(), [
			'number' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => [
					"Ticket number is required",
				],
			]);
		}

		$today = date('Y-m-d H:i:s'); //current date

		//THIS IS THE ORIGINAL CONDITION
		$threeMonthsBefore = date('Y-m-d H:i:s', strtotime("-3 months", strtotime($today))); //three months before

		$submission_closing_extended = false;
		$case = RsaCase::where('number', $number)
			->orWhere('vehicle_registration_number', $number)
			->first();
		if ($case && !empty($case->submission_closing_date)) {
			$submission_closing_extended = true;
		}
		//FOR CHANGE REQUEST BY TVS TEAM DATE GIVEN IN STATIC
		// $threeMonthsBefore = "2019-04-01";

		//CHECK TICKET EXIST WITH DATA ENTRY STATUS & DATE FOR ASP
		$query = Activity::select([
			'activities.id as id',
			'cases.created_at as case_created_at',
			// 'cases.date as case_date',
			DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y") as case_date'),
			'cases.number as case_number',
		])
			->join('cases', 'cases.id', 'activities.case_id')
			->where(function ($q) use ($number) {
				$q->where('cases.number', $number)
					->orWhere('cases.vehicle_registration_number', $number);
			});

		$query1 = clone $query;

		$ticket = $query1->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
			if ($submission_closing_extended) {
				$q->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
			} else {
				$q->where('cases.created_at', '>=', $threeMonthsBefore);
			}
		})
			->whereIn('activities.status_id', [2, 4])
			->where('activities.asp_id', Auth::user()->asp->id)
			->first();

		if ($ticket) {
			return response()->json([
				'success' => true,
				'activity_id' => $ticket->id,
			]);
		} else {
			//CHECK TICKET EXIST
			$query2 = clone $query;
			$ticket_exist = $query2->first();

			if ($ticket_exist) {

				$query3 = clone $query;
				//CHECK TICKET IS BELONGS TO ASP
				$asp_has_activity = $query3->where('activities.asp_id', Auth::user()->asp->id)
					->first();

				if (!$asp_has_activity) {
					return response()->json([
						'success' => false,
						'errors' => [
							"Ticket is not attended by " . Auth::user()->asp->asp_code . " as per CRM",
						],
					]);
				} else {

					//Restriction disable - temporarily for June 2020 & July 2020 tickets
					$sub_query = clone $query;
					$tickets = $sub_query->addSelect([
						'cases.created_at',
					])
						->whereIn('activities.status_id', [2, 4])
						->where('activities.asp_id', Auth::user()->asp->id)
						->get();

					if ($tickets->isNotEmpty()) {
						foreach ($tickets as $key => $ticket) {
							$ticket_creation_date = date('Y-m-d', strtotime($ticket->created_at));
							//If the ticket is June, then closing date is 27 Sep 2020
							if ($ticket_creation_date >= "2020-06-01" && $ticket_creation_date <= "2020-06-31") {
								$ticket_closing_date = "2020-09-27";
							} elseif ($ticket_creation_date >= "2020-07-01" && $ticket_creation_date <= "2020-07-31") {
								//If the ticket is July, then closing date is 11 Oct 2020
								$ticket_closing_date = "2020-10-11";
							} else {
								continue;
							}

							if ($today <= $ticket_closing_date) {
								return response()->json([
									'success' => true,
									'activity_id' => $ticket->id,
								]);
							}
						}
					}

					//CHECK IF TICKET DATE IS GREATER THAN 3 MONTHS OLDER
					$query4 = clone $query;
					$check_ticket_date = $query4->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
						if ($submission_closing_extended) {
							$q->where('cases.submission_closing_date', '<', date('Y-m-d H:i:s'));
						} else {
							$q->where('cases.created_at', '<', $threeMonthsBefore);
						}
					})
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if ($check_ticket_date) {
						return response()->json([
							'success' => false,
							'errors' => [
								"Please contact administrator.",
							],
						]);
					}
					$query5 = clone $query;
					$activity_on_hold = $query5->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
						if ($submission_closing_extended) {
							$q->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
						} else {
							$q->where('cases.created_at', '>=', $threeMonthsBefore);
						}
					})
						->where('activities.status_id', 17) //ON HOLD
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if ($activity_on_hold) {
						return response()->json([
							'success' => false,
							'errors' => [
								"Ticket On Hold",
							],
						]);
					}

					$query6 = clone $query;
					$activity_not_eligible_for_payment = $query6->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
						if ($submission_closing_extended) {
							$q->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
						} else {
							$q->where('cases.created_at', '>=', $threeMonthsBefore);
						}
					})
						->whereIn('activities.status_id', [15, 16]) // NOT ELIGIBLE FOR PAYOUT
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if ($activity_not_eligible_for_payment) {
						return response()->json([
							'success' => false,
							'errors' => [
								'Ticket not found',
							],
						]);
					}

					$query7 = clone $query;
					$activity_already_completed = $query7->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
						if ($submission_closing_extended) {
							$q->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
						} else {
							$q->where('cases.created_at', '>=', $threeMonthsBefore);
						}
					})
						->whereIn('activities.status_id', [5, 6, 7, 8, 9, 10, 11, 12, 13, 14])
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if ($activity_already_completed) {
						return response()->json([
							'success' => false,
							'errors' => [
								"Ticket already submitted. Case : " . $activity_already_completed->case_number . "(" . $activity_already_completed->case_date . ")",
							],
						]);
					}

					$query8 = clone $query;
					$case_with_cancelled_status = $query8->where('cases.status_id', 3) //CANCELLED
						->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
							if ($submission_closing_extended) {
								$q->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
							} else {
								$q->where('cases.created_at', '>=', $threeMonthsBefore);
							}
						})
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if ($case_with_cancelled_status) {
						return response()->json([
							'success' => false,
							'errors' => [
								"Ticket is cancelled",
							],
						]);
					}
					$query9 = clone $query;
					$case_with_closed_status = $query9->where('cases.status_id', 4) //CLOSED
						->where(function ($q) use ($submission_closing_extended, $threeMonthsBefore) {
							if ($submission_closing_extended) {
								$q->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
							} else {
								$q->where('cases.created_at', '>=', $threeMonthsBefore);
							}
						})
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if (!$case_with_closed_status) {
						return response()->json([
							'success' => false,
							'errors' => [
								"Ticket is closed",
							],
						]);
					}
				}
			} else {
				return response()->json([
					'success' => false,
					'errors' => [
						'Ticket not found',
					],
				]);
			}
		}

	}
	public function activityNewGetFormData($id = NULL) {
		$for_deffer_activity = 0;
		$this->data = Activity::getFormData($id, $for_deffer_activity);
		$this->data['case_details'] = $this->data['activity']->case;
		return response()->json($this->data);
	}

	public function updateActivity(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$activity = Activity::findOrFail($request->activity_id);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => ['Activity not found'],
				]);
			}

			$range_limit = 0;

			if (!empty($request->update_attach_map_id)) {
				$update_attach_km_map_ids = json_decode($request->update_attach_km_map_id, true);
				Attachment::whereIn('id', $update_attach_km_map_ids)->delete();
			}
			if (!empty($request->update_attach_other_id)) {
				$update_attach_other_ids = json_decode($request->update_attach_other_id, true);
				Attachment::whereIn('id', $update_attach_other_ids)->delete();
			}
			$cc_service_type_exist = ActivityDetail::where('activity_id', $activity->id)
				->where('key_id', 153)
				->first();
			$cc_service_type = ServiceType::where('name', $cc_service_type_exist->value)->first();

			$aspServiceType = AspServiceType::where('asp_id', $activity->asp_id)
				->where('service_type_id', $cc_service_type->id)
				->first();
			if ($aspServiceType) {
				$range_limit = $aspServiceType->range_limit;
			}

			if (!empty($request->comments)) {
				$activity->asp_resolve_comments = $request->comments;
			}

			$destination = aspTicketAttachmentPath($activity->id, $activity->asp_id, $activity->service_type_id);
			$status = Storage::makeDirectory($destination, 0777);

			if (!empty($request->other_attachment)):
				Attachment::where('entity_id', $activity->id)->where('entity_type', 17)->delete();
				foreach ($request->other_attachment as $key => $value) {
					if ($request->hasFile("other_attachment.$key")) {
						$key1 = $key + 1;
						$filename = "other_charges" . $key;
						$extension = $request->file("other_attachment.$key")->getClientOriginalExtension();
						$status = $request->file("other_attachment.$key")->storeAs($destination, $filename . '.' . $extension);
						$other_charge = $filename . '.' . $extension;
						$attachment = $Attachment = Attachment::create([
							'entity_type' => config('constants.entity_types.ASP_OTHER_ATTACHMENT'),
							'entity_id' => $activity->id,
							'attachment_file_name' => $other_charge,
						]);
					}
				}
			endif;

			if (!empty($request->map_attachment)):
				Attachment::where('entity_id', $activity->id)->where('entity_type', 16)->delete();

				foreach ($request->map_attachment as $key => $value) {
					if ($request->hasFile("map_attachment.$key")) {
						$key1 = $key + 1;
						$filename = "km_travelled_attachment" . $key;
						$extension = $request->file("map_attachment.$key")->getClientOriginalExtension();
						$status = $request->file("map_attachment.$key")->storeAs($destination, $filename . '.' . $extension);
						$km_travelled = $filename . '.' . $extension;
						$attachment = $Attachment = Attachment::create([
							'entity_type' => config('constants.entity_types.ASP_KM_ATTACHMENT'),
							'entity_id' => $activity->id,
							'attachment_file_name' => $km_travelled,
						]);
					}
				}
			endif;

			//Updating ticket status.. Check if "Bulk Approval" OR "Deferred Approval"
			$configs = Config::where('entity_type_id', 23)->get();
			foreach ($configs as $config) {
				$detail = ActivityDetail::where('activity_id', $activity->id)->where('key_id', $config->id)->first();
				$this->data['activities'][$config->name] = $detail ? ($detail->value ? $detail->value : 0) : 0;

			}
			$mis_km = $this->data['activities']['cc_total_km'];
			$not_collect_charges = $this->data['activities']['cc_not_collected_amount'];
			$asp_km = empty($request->km_travelled) ? 0 : $request->km_travelled;
			$asp_other = empty($request->other_charge) ? 0 : $request->other_charge;

			$is_bulk = true;

			//1. checking MIS and ASP Service
			if ($request->asp_service_type_id && ($cc_service_type->id != $request->asp_service_type_id)) {
				$is_bulk = false;
			}

			//2. checking MIS and ASP KMs
			$allowed_variation = 0.5;
			$five_percentage_difference = $mis_km * $allowed_variation / 100;
			if ($asp_km > $range_limit || $range_limit == 0) {
				if ($asp_km > $mis_km) {
					$km_difference = $asp_km - $mis_km;
					if ($km_difference > $five_percentage_difference) {
						if (!isset($request->km_attachment_exist) && empty($request->map_attachment)) {
							return response()->json([
								'success' => false,
								'errors' => ['Please attach google map screenshot'],
							]);
						}
						$is_bulk = false;

					}
				}
			}

			//checking ASP KMs exceed ASP service type range limit
			if ($asp_km > $range_limit) {
				$is_bulk = false;
			}

			//checking MIS and ASP not collected
			if ($asp_other > $not_collect_charges) {
				$is_bulk = false;

				//$for_delete_old_other_attachment = 0;
				if (!isset($request->other_attachment_exist) && empty($request->other_attachment)) {
					return response()->json([
						'success' => false,
						'errors' => ['Please attach other Attachment'],
					]);
				}
				if (empty($request->remarks_not_collected)) {
					return response()->json([
						'success' => false,
						'errors' => ['Please enter remarks comments for not collected'],
					]);
				}
			}

			//checking MIS and ASP collected
			$asp_collected_charges = empty($request->asp_collected_charges) ? 0 : $request->asp_collected_charges;
			if ($asp_collected_charges < $this->data['activities']['cc_colleced_amount']) {
				$is_bulk = false;
			}

			if (floatval($mis_km == 0)) {
				$is_bulk = false;
			}

			//ASP DATA RE-ENTRY - DEFERRED
			if ($request->data_reentry == '1') {
				if ($is_bulk) {
					$activity->status_id = 8;
				} else {
					$activity->status_id = 9;
				}
			} else {
				//ASP DATA ENTRY - NEW
				if ($is_bulk) {
					$activity->status_id = 5;
				} else {
					$activity->status_id = 6;
				}
			}

			$activity->service_type_id = $request->asp_service_type_id;

			if (!empty($request->comments)) {
				//$activity->comments = $request->comments;
				$activity->asp_resolve_comments = $request->comments;
			}

			if (!empty($request->remarks_not_collected)) {
				$activity->remarks = $request->remarks_not_collected;
			}

			if (!empty($request->general_remarks)) {
				$activity->general_remarks = $request->general_remarks;
			}
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();

			//UPDATE ASP ACTIVITY DETAILS & CALCULATE INVOICE AMOUNT FOR ASP & BO BASED ON ASP ENTERTED DETAILS
			$asp_key_ids = [
				//ASP
				157 => $activity->serviceType->name,
				154 => $request->km_travelled,
				156 => $request->other_charge,
				155 => $request->asp_collected_charges,
				//BO
				161 => $activity->serviceType->name,
				158 => $request->km_travelled,
				160 => $request->other_charge,
				159 => $request->asp_collected_charges,
			];
			foreach ($asp_key_ids as $key_id => $value) {
				$var_key_val = DB::table('activity_details')->updateOrInsert(['activity_id' => $activity->id, 'key_id' => $key_id, 'company_id' => 1], ['value' => $value]);
			}

			$response = getKMPrices($activity->serviceType, $activity->asp);
			if (!$response['success']) {
				return [
					'success' => false,
					'errors' => [
						$response['error'],
					],
				];
			}

			$price = $response['asp_service_price'];
			$total_km = $request->km_travelled; //ASP ENTERED KM
			$collected = $request->asp_collected_charges; //ASP COLLECTED
			$not_collected = $request->other_charge; //ASP NOT COLLECTED

			//INV AMOUNT FORMULA
			if ($activity->financeStatus->po_eligibility_type_id == 341) {
				// Empty Return Payout
				$below_range_price = $total_km == 0 ? 0 : $price->empty_return_range_price;
			} else {
				$below_range_price = $total_km == 0 ? 0 : $price->below_range_price;
			}

			$above_range_price = ($total_km > $price->range_limit) ? ($total_km - $price->range_limit) * $price->above_range_price : 0;
			$km_charge = $below_range_price + $above_range_price;

			//FORMULAE DISABLED AS PER CLIENT REQUEST
			// if ($price->adjustment_type == 1) {
			// 	//'Percentage'
			// 	$adjustment = ($km_charge * $price->adjustment) / 100;
			// 	$km_charge = $km_charge + $adjustment;
			// } else {
			// 	$adjustment = $price->adjustment;
			// 	$km_charge = $km_charge + $adjustment;
			// }

			$payout_amount = $km_charge;
			$net_amount = $payout_amount + $not_collected - $collected;
			$invoice_amount = $net_amount;

			$asp_po_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 171,
			]);
			$asp_po_amount->value = $payout_amount;
			$asp_po_amount->save();

			$bo_po_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 172,
			]);
			$bo_po_amount->value = $payout_amount;
			$bo_po_amount->save();

			$asp_net_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 175,
			]);
			$asp_net_amount->value = $net_amount;
			$asp_net_amount->save();

			$bo_net_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 176,
			]);
			$bo_net_amount->value = $net_amount;
			$bo_net_amount->save();

			$asp_invoice_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 181,
			]);
			$asp_invoice_amount->value = $invoice_amount;
			$asp_invoice_amount->save();

			$bo_invoice_amount = ActivityDetail::firstOrNew([
				'company_id' => 1,
				'activity_id' => $activity->id,
				'key_id' => 182,
			]);
			$bo_invoice_amount->value = $invoice_amount;
			$bo_invoice_amount->save();

			//TicketActivity::saveLog($log);

			//log message
			$log_status = config('rsa.LOG_STATUES_TEMPLATES.ASP_DATA_ENTRY_DONE');
			$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.ASP_DATA_ENTRY_DONE');
			logActivity3(config('constants.entity_types.ticket'), $activity->id, [
				'Status' => $log_status,
				'Waiting for' => $log_waiting,
			], 361);

			$activity_log = ActivityLog::firstOrNew([
				'activity_id' => $activity->id,
			]);
			$activity_log->asp_data_filled_at = date('Y-m-d H:i:s');
			$activity_log->updated_by_id = Auth::id();
			$activity_log->save();

			//sending confirmation SMS to ASP
			$mobile_number = $activity->asp->contact_number1;
			$sms_message = 'Tkt uptd successfully';
			$array = [$activity->case->number];
			// sendSMS2($sms_message, $mobile_number, $array);

			//sending notification to all ASP STATE MAPPED BO users
			//$bo_users = User::where('users.role_id', 6)->pluck('users.id'); //6 - Bo User role ID
			$state_id = Auth::user()->asp->state_id;
			// $bo_users = StateUser::where('state_id', $state_id)->pluck('user_id');
			$bo_users = DB::table('state_user')
				->join('users', 'users.id', 'state_user.user_id')
				->where('state_user.state_id', $state_id)
				->where('users.role_id', 6) //BO
				->pluck('state_user.user_id');

			if ($activity->status_id == 5) {
				$noty_message_template = 'ASP_DATA_ENTRY_DONE_BULK';
			} else {
				$noty_message_template = 'ASP_DATA_ENTRY_DONE_DEFFERED';
			}
			$ticket_number = [$activity->ticket_number];
			if (!empty($bo_users)) {
				foreach ($bo_users as $bo_user_id) {
					notify2($noty_message_template, $bo_user_id, config('constants.alert_type.blue'), $ticket_number);
				}
			}
			DB::commit();
			$message = ['success' => "Ticket informations saved successfully"];
			return response()->json(['success' => true]);
		} catch (\Exception $e) {
			DB::rollBack();
			dd($e);
			return response()->json(['success' => false]);

		}
	}
	public function getDeferredList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
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
			->where('users.id', Auth::id())
			->where('activities.status_id', 7) //BO Rejected - Waiting for ASP Data Re-Entry
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

		return Datatables::of($activities)
			->addColumn('action', function ($activity) {
				$action = '<div class="dataTable-actions ">
				<a href="#!/rsa-case-pkg/deferred-activity/update/' . $activity->id . '">
					                <i class="fa fa-pencil dataTable-icon--edit" aria-hidden="true"></i>
					            </a></div>';
				return $action;
			})
			->make(true);
	}

	public function activityDeferredGetFormData($id = NULL) {
		$for_deffer_activity = 1;
		$this->data = Activity::getFormData($id, $for_deffer_activity);
		$this->data['case'] = $this->data['activity']->case;
		return response()->json($this->data);
	}

	public function getApprovedList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			'asps.asp_code',
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'call_centers.name as call_center',
			DB::raw("FORMAT(bo_net_amount.value,2) as net_amount"),
			DB::raw("FORMAT(bo_not_collected_amount.value,2) as not_collected_amount"),
			DB::raw("FORMAT(bo_colleced_amount.value,2) as colleced_amount"),
			DB::raw("FORMAT(bo_invoice_amount.value,2) as invoice_amount")
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
			->leftJoin('activity_details as bo_net_amount', function ($join) {
				$join->on('bo_net_amount.activity_id', 'activities.id')
					->where('bo_net_amount.key_id', 176); //BO NET AMOUNT
			})
			->leftJoin('activity_details as bo_not_collected_amount', function ($join) {
				$join->on('bo_not_collected_amount.activity_id', 'activities.id')
					->where('bo_not_collected_amount.key_id', 160); //BO NOT COLLECTED
			})
			->leftJoin('activity_details as bo_colleced_amount', function ($join) {
				$join->on('bo_colleced_amount.activity_id', 'activities.id')
					->where('bo_colleced_amount.key_id', 159); //BO COLLECTED
			})
			->leftJoin('activity_details as bo_invoice_amount', function ($join) {
				$join->on('bo_invoice_amount.activity_id', 'activities.id')
					->where('bo_invoice_amount.key_id', 182); //BO INVOICE AMOUNT
			})
			->orderBy('cases.date', 'DESC')
			->groupBy('activities.id')
			->where('users.id', Auth::id())
			->whereIn('activities.status_id', [11, 1]) //BO Approved - Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
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
		if ($request->get('service_type_id')) {
			$activities->where('activities.service_type_id', $request->get('service_type_id'));
		}
		// if ($request->get('asp_status_id')) {
		// 	$activities->where('activities.status_id', $request->get('asp_status_id'));
		// }
		if ($request->get('finance_status_id')) {
			$activities->where('activities.finance_status_id', $request->get('finance_status_id'));
		}
		if ($request->get('client_id')) {
			$activities->where('cases.client_id', $request->get('client_id'));
		}

		return Datatables::of($activities)
			->setRowAttr([
				'id' => function ($activities) {
					return route('angular') . '/#!/rsa-case-pkg/activity-status/1/view/' . $activities->id;
				},
			])
			->addColumn('action', function ($activities) {
				return '<input type="checkbox" class="ticket_id no-link child_select_all" name="invoice_ids[]" value="' . $activities->id . '">';
			})
			->make(true);
	}

	public function getActivityEncryptionKey(Request $request) {
		if (empty($request->invoice_ids)) {
			return response()->json([
				'success' => false,
				'error' => 'Please select atleast one activity',
			]);
		}
		// $encryption_key = encryptStringInv(implode('-', $request->invoice_ids));
		$encryption_key = Crypt::encryptString(implode('-', $request->invoice_ids));
		return response()->json([
			'success' => true,
			'encryption_key' => $encryption_key,
		]);
	}

	public function getActivityApprovedDetails($encryption_key = '') {
		if (empty($encryption_key)) {
			return response()->json([
				'success' => false,
				'errors' => [
					'Activities not found',
				],
			]);
		}
		$decrypt = Crypt::decryptString($encryption_key);
		// $decrypt = decryptStringInv($encryption_key);
		$activity_ids = explode('-', $decrypt);
		if (empty($activity_ids)) {
			return response()->json([
				'success' => false,
				'errors' => [
					'Activities not found',
				],
			]);
		}
		$asp = Asp::with('rm')->find(Auth::user()->asp->id);
		if (!$asp) {
			return response()->json([
				'success' => false,
				'errors' => [
					'ASP not found',
				],
			]);
		}

		//CALCULATE TAX FOR INVOICE
		Invoices::calculateTax($asp, $activity_ids);
		$activities = Activity::join('cases', 'cases.id', 'activities.case_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftJoin('activity_details as km_charge', function ($join) {
				$join->on('km_charge.activity_id', 'activities.id')
					->where('km_charge.key_id', 172); //BO PO AMOUNT OR KM CHARGE
			})
			->leftJoin('activity_details as km_travelled', function ($join) {
				$join->on('km_travelled.activity_id', 'activities.id')
					->where('km_travelled.key_id', 158); //BO KM TRAVELLED
			})
			->leftJoin('activity_details as net_amount', function ($join) {
				$join->on('net_amount.activity_id', 'activities.id')
					->where('net_amount.key_id', 176); //BO NET AMOUNT
			})
			->leftJoin('activity_details as collect_amount', function ($join) {
				$join->on('collect_amount.activity_id', 'activities.id')
					->where('collect_amount.key_id', 159); //BO COLLECT AMOUNT
			})
			->leftJoin('activity_details as not_collected_amount', function ($join) {
				$join->on('not_collected_amount.activity_id', 'activities.id')
					->where('not_collected_amount.key_id', 160); //BO NOT COLLECT AMOUNT
			})
			->leftJoin('activity_details as total_tax_perc', function ($join) {
				$join->on('total_tax_perc.activity_id', 'activities.id')
					->where('total_tax_perc.key_id', 185); //BO TOTAL TAX PERC
			})
			->leftJoin('activity_details as total_tax_amount', function ($join) {
				$join->on('total_tax_amount.activity_id', 'activities.id')
					->where('total_tax_amount.key_id', 179); //BO TOTAL TAX AMOUNT
			})
			->leftJoin('activity_details as total_amount', function ($join) {
				$join->on('total_amount.activity_id', 'activities.id')
					->where('total_amount.key_id', 182); //BO INVOICE AMOUNT
			})
			->leftjoin('configs as data_sources', 'data_sources.id', 'activities.data_src_id')
			->select(
				'cases.number',
				'activities.id',
				'activities.asp_id as asp_id',
				'activities.crm_activity_id',
				DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y")as date'),
				'activity_portal_statuses.name as status',
				'call_centers.name as callcenter',
				'cases.vehicle_registration_number',
				'service_types.name as service_type',
				'km_charge.value as km_charge_value',
				'km_travelled.value as km_value',
				'not_collected_amount.value as not_collect_value',
				'net_amount.value as net_value',
				'collect_amount.value as collect_value',
				'total_amount.value as total_value',
				'total_tax_perc.value as total_tax_perc_value',
				'total_tax_amount.value as total_tax_amount_value',
				'data_sources.name as data_source'
			)
			->whereIn('activities.id', $activity_ids)
			->groupBy('activities.id')
			->get();

		if (count($activities) == 0) {
			return response()->json([
				'success' => false,
				'errors' => [
					'Activities not found',
				],
			]);
		}

		foreach ($activities as $key => $activity) {
			$taxes = DB::table('activity_tax')->leftjoin('taxes', 'activity_tax.tax_id', '=', 'taxes.id')->where('activity_id', $activity->id)->select('taxes.tax_name', 'taxes.tax_rate', 'activity_tax.*')->get();
			$activity->taxes = $taxes;
		}

		//GET INVOICE AMOUNT FROM ACTIVITY DETAIL
		$activity_detail = Activity::select(
			DB::raw('SUM(bo_invoice_amount.value) as invoice_amount')
		)
			->leftjoin('activity_details as bo_invoice_amount', function ($join) {
				$join->on('bo_invoice_amount.activity_id', 'activities.id')
					->where('bo_invoice_amount.key_id', 182); //BO INVOICE AMOUNT
			})
			->whereIn('activities.id', $activity_ids)
			->first();

		if (!$activity_detail) {
			return response()->json([
				'success' => false,
				'errors' => [
					'Invoice amount not found',
				],
			]);
		}

		$this->data['activities'] = $activities;
		$this->data['invoice_amount'] = number_format($activity_detail->invoice_amount, 2);
		$this->data['invoice_amount_in_word'] = getIndianCurrency($activity_detail->invoice_amount);
		$this->data['asp'] = $asp;
		$this->data['inv_no'] = generateInvoiceNumber();
		$this->data['inv_date'] = date("d-m-Y");
		$this->data['signature_attachment'] = Attachment::where('entity_id', $asp->id)
			->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))
			->first();
		$this->data['signature_attachment_path'] = url('storage/' . config('rsa.asp_attachment_path_view'));

		$this->data['action'] = 'ASP Invoice Confirmation';
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function generateInvoice(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			//STORE ATTACHMENT
			$value = "";
			if ($request->hasFile("filename")) {
				$destination = aspInvoiceAttachmentPath();
				$status = Storage::makeDirectory($destination, 0777);
				$extension = $request->file("filename")->getClientOriginalExtension();
				$max_id = Invoices::selectRaw("Max(id) as id")->first();
				if (!empty($max_id)) {
					$ids = $max_id->id + 1;
					$filename = "Invoice" . $ids . "." . $extension;
				} else {
					$filename = "Invoice1" . "." . $extension;
				}
				$status = $request->file("filename")->storeAs($destination, $filename);
				$value = $filename;
			}

			//GET ASP
			$asp = ASP::where('id', $request->asp_id)->first();

			//CHECK IF INVOICE ALREADY CREATED FOR ACTIVITY
			$activities = Activity::select(
				'invoice_id',
				'crm_activity_id',
				'number',
				'asp_id'
			)
				->whereIn('crm_activity_id', $request->crm_activity_ids)
				->get();

			if (!empty($activities)) {
				foreach ($activities as $key => $activity) {
					//CHECK ASP MATCHES WITH ACTIVITY ASP
					if ($activity->asp_id != $asp->id) {
						return response()->json([
							'success' => false,
							'error' => 'ASP not matched for activity ID ' . $activity->crm_activity_id,
						]);
					}
					//CHECK IF INVOICE ALREADY CREATED FOR ACTIVITY
					if (!empty($activity->invoice_id)) {
						return response()->json([
							'success' => false,
							'error' => 'Invoice already created for activity ' . $activity->crm_activity_id,
						]);
					}
				}
			}

			//SELF INVOICE
			if ($asp->has_gst && !$asp->is_auto_invoice) {
				if (!$request->invoice_no) {
					return response()->json([
						'success' => false,
						'error' => 'Invoice number is required',
					]);
				}
				if (!$request->inv_date) {
					return response()->json([
						'success' => false,
						'error' => 'Invoice date is required',
					]);
				}
				//CHECK IF ZERO AS FIRST LETTER
				$invoiceNumberfirstLetter = substr(trim($request->invoice_no), 0, 1);
				if (is_numeric($invoiceNumberfirstLetter)) {
					if ($invoiceNumberfirstLetter == 0) {
						return response()->json([
							'success' => false,
							'error' => 'Invoice number should not start with zero',
						]);
					}
				}

				$invoice_no = $request->invoice_no;
				$invoice_date = date('Y-m-d H:i:s', strtotime($request->inv_date));
			} else {
				//SYSTEM
				//GENERATE INVOICE NUMBER
				$invoice_no = generateInvoiceNumber();
				$invoice_date = new Carbon();
			}

			$invoice_c = Invoices::createInvoice($asp, $request->crm_activity_ids, $invoice_no, $invoice_date, $value);
			if (!$invoice_c['success']) {
				return response()->json([
					'success' => false,
					'error' => $invoice_c['message'],
				]);
			}

			DB::commit();

			if ($invoice_c['success']) {
				return response()->json([
					'success' => true,
					'message' => 'Invoice created successfully',
				]);
			}
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function updateCaseSubmissionClosingDate(Request $r) {
		// dd($r->all());
		DB::beginTransaction();
		try {
			$error_messages = [
				'name.required' => "Please select closing date",
				'remarks.required' => "Please Enter Remarks",
			];
			$validator = Validator::make($r->all(), [
				'closing_date' => [
					'required:true',
				],
				'remarks' => [
					'required:true',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$activity = Activity::find($r->activity_id);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
					],
				]);
			}
			$activity->case()->update([
				'submission_closing_date' => date('Y-m-d H:i:s', strtotime($r->closing_date)),
				'submission_closing_date_remarks' => $r->remarks,
			]);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Closing date updated successfully',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage(),
				],
			]);
		}
	}

	public function exportActivities(Request $request) {
		// dd($request->all());
		$error_messages = [
			'status_ids.required' => "Please Select Activity Status",
		];

		$validator = Validator::make($request->all(), [
			'status_ids' => [
				'required:true',
			],
		], $error_messages);

		if (empty($request->status_ids)) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['errors' => $validator->errors()->all()]);
		}
		ini_set('max_execution_time', 0);
		ini_set('display_errors', 1);
		ini_set("memory_limit", "10000M");
		ob_end_clean();
		ob_start();
		$date = explode("-", $request->period);
		$range1 = date("Y-m-d", strtotime($date[0]));
		$range2 = date("Y-m-d", strtotime($date[1]));

		$status_ids = trim($request->status_ids, '""');
		$status_ids = explode(',', $status_ids);
		$activities = Activity::join('cases', 'activities.case_id', '=', 'cases.id')
			->join('asps', 'activities.asp_id', '=', 'asps.id')
			->join('configs as data_source', 'data_source.id', '=', 'activities.data_src_id')
			->leftjoin('configs as bd_location_type', 'bd_location_type.id', '=', 'cases.bd_location_type_id')
			->leftjoin('configs as bd_location_category', 'bd_location_category.id', '=', 'cases.bd_location_category_id')
			->whereIn('activities.status_id', $status_ids);
		if ($request->filter_by == 'general') {
			$activities->where(function ($q) use ($range1, $range2) {
				$q->whereDate('cases.date', '>=', $range1)
					->whereDate('cases.date', '<=', $range2);
			});
		}
		if ($request->filter_by == 'activity') {
			//OLD CODE
			// $activities->where(function ($q) use ($request, $range1, $range2) {
			// 	$q->where(function ($query) use ($range1, $range2) {
			// 		$query->whereDate('activities.created_at', '>=', $range1)
			// 			->whereDate('activities.created_at', '<=', $range2)
			// 			->whereNull('activities.updated_at');
			// 	})
			// 		->orWhere(function ($query) use ($range1, $range2) {
			// 			$query->whereDate('activities.updated_at', '>=', $range1)
			// 				->whereDate('activities.updated_at', '<=', $range2);
			// 		});
			// });

			//NEW CODE
			$activities->join('activity_logs', 'activities.id', '=', 'activity_logs.activity_id')
				->where(function ($q) use ($range1, $range2) {
					$q->where(function ($query) use ($range1, $range2) {
						$query->whereRaw('DATE(activity_logs.imported_at) between "' . $range1 . '" and "' . $range2 . '"');
					})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.asp_data_filled_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.bo_deffered_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.bo_approved_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.invoice_generated_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.axapta_generated_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.payment_completed_at) between "' . $range1 . '" and "' . $range2 . '"');
						});
				});
		}

		$activities->select(
			'asps.*',
			'activities.*',
			'activities.id as id',
			'cases.bd_lat',
			'cases.bd_long',
			'cases.bd_location',
			'cases.bd_city',
			'cases.bd_state',
			DB::raw('COALESCE(bd_location_type.name, "--") as location_type'),
			DB::raw('COALESCE(data_source.name, "--") as data_source'),
			DB::raw('COALESCE(bd_location_category.name, "--") as location_category'),
			DB::raw('DATE_FORMAT(activities.updated_at, "%d-%m-%Y %H:%i:%s") as latest_updation_date')
		);
		if (!empty($request->get('asp_id'))) {
			$activities = $activities->where('activities.asp_id', $request->get('asp_id'));
		}
		if (!empty($request->get('client_id'))) {
			$activities = $activities->where('cases.client_id', $request->get('client_id'));
		}
		if (!empty($request->get('ticket'))) {
			$activities = $activities->where('cases.number', $request->get('ticket'));
		}

		if (!Entrust::can('view-all-activities')) {
			if (Entrust::can('view-mapped-state-activities')) {
				$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
				$activities = $activities->whereIn('asps.state_id', $states);
			}
		}
		$total_count = $activities->count('activities.id');
		if ($total_count == 0) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with(['errors' => ['No activities found for given period & statuses']]);
		}
		foreach ($status_ids as $key => $status_id) {
			$count_splitup[] = Activity::rightJoin('activity_portal_statuses', 'activities.status_id', 'activity_portal_statuses.id')
				->join('cases', 'cases.id', 'activities.case_id')
				->select(DB::raw('COUNT(activities.id) as activity_count'), 'activity_portal_statuses.id', 'activity_portal_statuses.name')

				->where('activity_portal_statuses.id', $status_id)
				->whereDate('cases.date', '>=', $range1)
				->whereDate('cases.date', '<=', $range2)
			//->groupBy('activity_portal_statuses.id')
				->first();
		}

		$selected_statuses = $status_ids;
		$summary_period = ['Period', date('d/M/Y', strtotime($range1)) . ' to ' . date('d/M/Y', strtotime($range2))];
		$summary[] = ['Status', 'Count'];

		foreach ($count_splitup as $status_data) {
			$summary[] = [$status_data['name'], $status_data['activity_count']];
		}
		$summary[] = ['Total', $total_count];
		$activity_details_header = [
			'ID',
			'Case Number',
			'Case Date',
			'Case Submission Closing Date',
			'Case Submission Closing Date Remarks',
			'CRM Activity ID',
			'Activity Number',
			'Activity Date',
			'Client Name',
			'Customer Name',
			'Customer Contact Number',
			'ASP Name',
			'Axapta Code',
			'ASP Code',
			'ASP Contact Number',
			'ASP EMail',
			'ASP has GST',
			'ASP Type',
			'Auto Invoice',
			'Workshop Name',
			'Workshop Type',
			'RM Name',
			'Location',
			'District',
			'State',
			'Vehicle Registration Number',
			'Vehicle Model',
			'Vehicle Make',
			'Case Status',
			'Finance Status',
			'Final Approved BO Service Type',
			'ASP Activity Rejected Reason',
			'ASP PO Accepted',
			'ASP PO Rejected Reason',
			'Portal Status',
			'Activity Status',
			'Activity Description',
			'Remarks',
			'Manual Uploading Remarks',
			'General Remarks',
			'Comments',
			'Deduction Reason',
			'Deferred Reason',
			'ASP Resolve Comments',
			'Is Exceptional',
			'Exceptional Reason',
			'Invoice Number',
			'Invoice Date',
			'Invoice Amount',
			'Invoice Status',
			// 'Payment Date',
			// 'Payment Mode',
			// 'Paid Amount',
			'BD Latitude',
			'BD Longitude',
			'BD Location',
			'BD City',
			'BD State',
			'Location Type',
			'Location Category',
		];
		$configs = Config::where('entity_type_id', 23)->pluck('id')->toArray();
		$key_list = [153, 157, 161, 158, 159, 160, 154, 155, 156, 170, 174, 180, 179, 176, 172, 173, 182, 171, 175, 181];
		$config_ids = array_merge($configs, $key_list);
		//dd($config_ids);
		foreach ($config_ids as $key => $config_id) {
			$config = Config::where('id', $config_id)->first();
			$activity_details_header[] = str_replace("_", " ", strtolower($config->name));
		}

		$status_headers = [
			'Imported through MIS Import',
			'Duration Between Import and ASP Data Filled',
			'ASP Data Filled',
			'Duration Between ASP Data Filled and BO deffered',
			'BO Deferred',
			'Duration Between ASP Data Filled and BO approved',
			'BO Approved',
			'Duration Between BO approved and Invoice generated',
			'Invoice Generated',
			'Duration Between Invoice generated and Axapta Generated',
			'Axapta Generated',
			'Duration Between Axapta Generated and Payment Completed',
			'Payment Completed',
			'Total No. Of Days',
			'Source',
			// 'Latest Updation Date',
		];
		$activity_details_data = [];
		//dd($activities);
		$activity_details_header = array_merge($activity_details_header, $status_headers);
		//dd($activity_details_header );
		$constants = config('constants');
		$activities = $activities
			->groupBy('activities.id')
			->get();
		foreach ($activities as $activity_key => $activity) {
			if (!empty($activity->case->submission_closing_date)) {
				$submission_closing_date = date('d-m-Y H:i:s', strtotime($activity->case->submission_closing_date));
			} else {
				$submission_closing_date = date('d-m-Y H:i:s', strtotime("+3 months", strtotime($activity->case->created_at)));
			}
			if (!empty($activity->invoice) && !empty($activity->invoice->created_at)) {
				$inv_created_at = date('d-m-Y', strtotime(str_replace('/', '-', $activity->invoice->created_at)));
			} else {
				$inv_created_at = '';
			}

			$activity_details_data[] = [
				$activity->id,
				$activity->case->number,
				date('d-m-Y H:i:s', strtotime($activity->case->date)),
				$submission_closing_date,
				!empty($activity->case->submission_closing_date_remarks) ? $activity->case->submission_closing_date_remarks : '',
				$activity->crm_activity_id,
				$activity->number,
				date('d-m-Y H:i:s', strtotime($activity->created_at)),
				$activity->case->client->name,
				$activity->case->customer_name,
				$activity->case->customer_contact_number,
				$activity->asp->name ? $activity->asp->name : '',
				$activity->asp->axpta_code ? $activity->asp->axpta_code : '',
				$activity->asp->asp_code ? $activity->asp->asp_code : '',
				$activity->asp->contact_number1 ? $activity->asp->contact_number1 : '',
				$activity->asp->email ? $activity->asp->email : '',
				$activity->asp->has_gst ? 'Yes' : 'No',
				$activity->asp->is_self == 1 ? 'Self' : 'Non Self',
				$activity->asp->is_auto_invoice == 1 ? 'Yes' : 'No',
				$activity->asp->workshop_name ? $activity->asp->workshop_name : '',
				$activity->asp->workshop_type ? array_flip($constants['workshop_types'])[$activity->asp->workshop_type] : '',
				$activity->asp->rm ? ($activity->asp->rm->name ? $activity->asp->rm->name : '') : '',
				$activity->asp->location ? ($activity->asp->location->name ? $activity->asp->location->name : '') : '',
				$activity->asp->district ? ($activity->asp->district->name ? $activity->asp->district->name : '') : '',
				$activity->asp->state ? ($activity->asp->state->name ? $activity->asp->state->name : '') : '',
				$activity->case->vehicle_registration_number,
				$activity->case->vehicleModel ? ($activity->case->vehicleModel->name ? $activity->case->vehicleModel->name : '') : '',
				$activity->case->vehicleModel ? ($activity->case->vehicleModel->vehiclemake ? $activity->case->vehicleModel->vehiclemake->name : '') : '',
				$activity->case->status ? $activity->case->status->name : '',
				$activity->financeStatus ? $activity->financeStatus->name : '',
				$activity->serviceType ? $activity->serviceType->name : '',
				$activity->aspActivityRejectedReason ? $activity->aspActivityRejectedReason->name : '',
				$activity->asp_po_accepted != NULL ? ($activity->asp_po_accepted == 1 ? 'Yes' : 'No') : '',
				$activity->aspPoRejectedReason ? $activity->aspPoRejectedReason->name : '',
				$activity->status ? $activity->status->name : '',
				$activity->activityStatus ? $activity->activityStatus->name : '',
				$activity->description != NULL ? $activity->description : '',
				$activity->remarks != NULL ? $activity->remarks : '',
				$activity->manual_uploading_remarks != NULL ? $activity->manual_uploading_remarks : '',
				$activity->general_remarks != NULL ? $activity->general_remarks : '',
				$activity->bo_comments != NULL ? $activity->bo_comments : '',
				$activity->deduction_reason != NULL ? $activity->deduction_reason : '',
				$activity->defer_reason != NULL ? $activity->defer_reason : '',
				$activity->asp_resolve_comments != NULL ? $activity->asp_resolve_comments : '',
				$activity->is_exceptional_check == 1 ? 'Yes' : 'No',
				$activity->exceptional_reason != NULL ? $activity->exceptional_reason : '',
				// $activity->invoice ? ($activity->asp->has_gst == 1 && $activity->asp->is_auto_invoice == 0 ? ($activity->invoice->invoice_no) : ($activity->invoice->invoice_no . '-' . $activity->invoice->id)) : '',
				$activity->invoice ? $activity->invoice->invoice_no : '',
				$inv_created_at,
				$activity->invoice ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($activity->invoice->invoice_amount, 2))) : '',
				$activity->invoice ? ($activity->invoice->invoiceStatus ? $activity->invoice->invoiceStatus->name : '') : '',
				// '',
				// '',
				// '',
				!empty($activity->bd_lat) ? $activity->bd_lat : '',
				!empty($activity->bd_long) ? $activity->bd_long : '',
				!empty($activity->bd_location) ? $activity->bd_location : '',
				!empty($activity->bd_city) ? $activity->bd_city : '',
				!empty($activity->bd_state) ? $activity->bd_state : '',
				$activity->location_type,
				$activity->location_category,
			];
			foreach ($config_ids as $config_id) {
				$config = Config::where('id', $config_id)->first();
				$detail = ActivityDetail::where('activity_id', $activity->id)->where('key_id', $config_id)->first();
				if (strcmp('amount', $config->name) == 0 || strpos($config->name, '_charges') || strpos($config->name, 'Amount') || strpos($config->name, 'Collected') || strpos($config->name, 'date')) {

					if ($detail) {
						if (strpos($config->name, 'date')) {
							$activity_details_data[$activity_key][] = ($detail->value != "") ? date('d-m-Y H:i:s', strtotime($detail->value)) : '';
						} else {
							$activity_details_data[$activity_key][] = ($detail->value != "") ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($detail->value, 2))) : '';
						}

					} else {
						$activity_details_data[$activity_key][] = '';
					}

				} else {

					$activity_details_data[$activity_key][] = $detail ? $detail->value : '';

				}
			}
			$total_days = 0;
			//dump($activity);
			$activity_log = ActivityLog::where('activity_id', $activity->id)->first();
			if ($activity_log) {

				$activity_details_data[$activity_key][] = $activity_log->imported_at ? date('d-m-Y H:i:s', strtotime($activity_log->imported_at)) : '';
				$tot = ($activity_log->imported_at && $activity_log->asp_data_filled_at) ? $this->findDifference($activity_log->imported_at, $activity_log->asp_data_filled_at) : '';
				$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
				$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

				$activity_details_data[$activity_key][] = $activity_log->asp_data_filled_at ? date('d-m-Y H:i:s', strtotime($activity_log->asp_data_filled_at)) : '';
				$tot = ($activity_log->asp_data_filled_at && $activity_log->bo_deffered_at) ? $this->findDifference($activity_log->asp_data_filled_at, $activity_log->bo_deffered_at) : '';
				$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
				$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

				$activity_details_data[$activity_key][] = $activity_log->bo_deffered_at ? date('d-m-Y H:i:s', strtotime($activity_log->bo_deffered_at)) : '';
				$tot = ($activity_log->asp_data_filled_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->asp_data_filled_at, $activity_log->bo_approved_at) : '';
				$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
				$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

				$activity_details_data[$activity_key][] = $activity_log->bo_approved_at ? date('d-m-Y H:i:s', strtotime($activity_log->bo_approved_at)) : '';
				$tot = ($activity_log->invoice_generated_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->invoice_generated_at, $activity_log->bo_approved_at) : '';
				$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
				$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

				$activity_details_data[$activity_key][] = $activity_log->invoice_generated_at ? date('d-m-Y H:i:s', strtotime($activity_log->invoice_generated_at)) : '';
				$tot = ($activity_log->invoice_generated_at && $activity_log->axapta_generated_at) ? $this->findDifference($activity_log->invoice_generated_at, $activity_log->axapta_generated_at) : '';
				$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
				$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

				$activity_details_data[$activity_key][] = $activity_log->axapta_generated_at ? date('d-m-Y H:i:s', strtotime($activity_log->axapta_generated_at)) : '';
				$tot = ($activity_log->axapta_generated_at && $activity_log->payment_completed_at) ? $this->findDifference($activity_log->axapta_generated_at, $activity_log->payment_completed_at) : '';
				$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
				$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

				$activity_details_data[$activity_key][] = $activity_log->payment_completed_at ? date('d-m-Y H:i:s', strtotime($activity_log->payment_completed_at)) : '';
				$activity_details_data[$activity_key][] = $total_days > 1 ? ($total_days . ' Days') : ($total_days . ' Day');

			} else {
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
				$activity_details_data[$activity_key][] = '';
			}

			// $activity_details_data[$activity_key][] = !empty($activity->latest_updation_date) ? $activity->latest_updation_date : '';
			$activity_details_data[$activity_key][] = $activity->data_source;
		}
		//dd('s');
		//$activity_details_data = array_merge($activity_details_header, $activity_details_data);
		Excel::create('Activity Status Report', function ($excel) use ($summary, $activity_details_header, $activity_details_data, $status_ids, $summary_period) {
			$excel->sheet('Summary', function ($sheet) use ($summary, $status_ids, $summary_period) {
				//dd($summary);
				$sheet->fromArray($summary, NULL, 'A1');
				$sheet->row(1, $summary_period);

				$sheet->cells('A1:B1', function ($cells) {
					$cells->setFont(array(
						'size' => '10',
						'bold' => true,
					))->setBackground('#CCC9C9');
				});
				$sheet->cells('A2:B2', function ($cells) {
					$cells->setFont(array(
						'size' => '10',
						'bold' => true,
					))->setBackground('#F3F3F3');
				});
				$cell_number = count($status_ids) + 3;
				$sheet->cells('A' . $cell_number . ':B' . $cell_number, function ($cell) {
					$cell->setFont(array(
						'size' => '10',
						'bold' => true,
					))->setBackground('#F3F3F3');
				});
			});

			$excel->sheet('Activity Informations', function ($sheet) use ($activity_details_header, $activity_details_data) {
				$sheet->setAutoSize(false);
				$sheet->fromArray($activity_details_data, NULL, 'A1');
				$sheet->row(1, $activity_details_header);
				$sheet->cells('A1:DS1', function ($cells) {
					$cells->setFont(array(
						'size' => '10',
						'bold' => true,
					))->setBackground('#CCC9C9');
				});
			});
		})->export('xls');

		return redirect()->back()->with(['success' => 'exported!']);
	}

	public function releaseOnHold(Request $r) {
		// dd($r->all());
		DB::beginTransaction();
		try {
			if (empty($r->case_date)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Please Select Case Date',
					],
				]);
			}
			$case_date = date('Y-m-d', strtotime($r->case_date));
			$activity_ids = Activity::select([
				'activities.id',
			])
				->join('cases', 'cases.id', 'activities.case_id')
				->where('activities.status_id', 17) //ONHOLD
				->whereDate('cases.date', '<=', $case_date)
				->pluck('id')
				->toArray();

			if (empty($activity_ids)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'No activities in the selected case date',
					],
				]);
			}

			Activity::whereIn('id', $activity_ids)->update([
				'status_id' => 2,
				'updated_by_id' => Auth::id(),
			]);
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'OnHold Cases have been released for the selected case date',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage(),
				],
			]);
		}
	}
	public static function findDifference($date1, $date2) {
		$date1 = date_create(date('Y-m-d', strtotime($date1)));
		$date2 = date_create(date('Y-m-d', strtotime($date2)));
		$diff_date = date_diff($date1, $date2);
		$duration = ($diff_date->format("%d") > 1) ? $diff_date->format("%d") : $diff_date->format("%d");
		return $duration;
	}

	public function searchAsps(Request $request) {
		return Asp::searchAsps($request);
	}

	public function searchClients(Request $request) {
		return Client::searchClient($request);
	}
}
