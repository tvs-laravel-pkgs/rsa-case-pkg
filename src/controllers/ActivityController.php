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

	public function getFilterData($type = '') {
		if ($type == 'approved') {
			$show_message = false;
			$nocInfo = checkNocInfo();
			//NOC NOT GENERATED FOR PREV QUARTERS
			if ($nocInfo['noc']) {
				$payment_pending_tickets_prev_qrts = Activity::join('cases', 'cases.id', 'activities.case_id')
					->where('activities.asp_id', Auth::user()->asp->id)
					->whereNotIn('activities.status_id', [2, 4, 7, 14])
					->whereDate('cases.date', '>=', $nocInfo['period_start_date'])
					->whereDate('cases.date', '<=', $nocInfo['period_end_date'])
					->get();

				if ($payment_pending_tickets_prev_qrts->count() > 0) {
					$show_message = true;
					$message = "Kindly complete the payment for the previous quarter tickets, so that you can continue with current quarter tickets.";
				} else {
					$show_message = true;
					$message = "Kindly generate NOC for the previous quarter tickets, so that you can continue with current quarter tickets.";

				}
			}
		} else {
			$show_message = false;
		}

		$this->data['extras'] = [
			'call_center_list' => collect(CallCenter::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Call Center']),
			'service_type_list' => collect(ServiceType::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Sub Service']),
			'finance_status_list' => collect(ActivityFinanceStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Finance Status']),
			'portal_status_list' => collect(ActivityPortalStatus::select('name', 'id')->where('company_id', 1)->get()),
			'status_list' => collect(ActivityPortalStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Portal Status']),
			'activity_status_list' => collect(ActivityStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Activity Status']),
			'client_list' => collect(Client::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Client']),
			'export_client_list' => collect(Client::select('name', 'id')->get()),
			'asp_list' => collect(Asp::select('name', 'id')->get()),
			'show_message' => ($show_message) ? $message : '',
		];

		return response()->json($this->data);
	}

	public function getList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			'activities.crm_activity_id',
			'activities.status_id as status_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			'asps.asp_code',
			'service_types.name as sub_service',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'configs.name as source',
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
				$activities->where('users.id', Auth::id())
					->whereNotIn('activities.status_id', [2, 4]);
			}
		}
		return Datatables::of($activities)
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
				logActivity2(config('constants.entity_types.ticket'), $activity->id, [
					'Status' => $log_status,
					'Waiting for' => $log_waiting,
				]);

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
				logActivity2(config('constants.entity_types.ticket'), $activity->id, [
					'Status' => $log_status,
					'Waiting for' => $log_waiting,
				]);

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
			'asps.asp_code',
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
			'asps.asp_code',
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
			'activities.id as activity_id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			DB::raw('DATE_FORMAT(activities.created_at,"%d-%m-%Y %H:%i:%s") as activity_date'),
			DB::raw('IF(activities.deduction_reason IS NULL,"-",deduction_reason) as deduction_reason'),
			DB::raw('IF(activities.bo_comments IS NULL,"-",bo_comments) as bo_comments'),
			DB::raw('IF(activities.defer_reason IS NULL,"-",defer_reason) as defer_reason'),
			'cases.number',
			'cases.customer_name as customer_name',
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
			'cases.*',
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
			DB::raw('IF(Invoices.start_date IS NULL,"NA",DATE_FORMAT(Invoices.start_date,"%d-%m-%Y")) as invoice_date'),
			'activity_finance_statuses.po_eligibility_type_id',
			'activities.finance_status_id',
			'activities.invoice_id'

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
			->groupBy('activities.id')
			->where('activities.id', $activity_status_id)
			->first();

		$this->data['activities']['km_travelled_attachments'] = $km_travelled_attachments = Attachment::where([['entity_id', '=', $activity_status_id], ['entity_type', '=', 16]])->get();
		$this->data['activities']['other_charges_attachments'] = $other_charges_attachments = Attachment::where([['entity_id', '=', $activity_status_id], ['entity_type', '=', 17]])->get();
		$other_charges_attachment_url = $km_travelled_attachment_url = [];
		foreach ($km_travelled_attachments as $key => $km_travelled_attachment) {
			$km_travelled_attachment_url[$key] = aspTicketAttachmentImage($km_travelled_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $activity->serviceType->id);
		}
		foreach ($other_charges_attachments as $key => $other_charges_attachment) {
			$other_charges_attachment_url[$key] = aspTicketAttachmentImage($other_charges_attachment->attachment_file_name, $activity_status_id, $activity->asp->id, $activity->serviceType->id);
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

			$this->data['activities']['invoice_amount_in_word'] = getIndianCurrency($activity->inv_amount);
			if (count($invoice_activities) > 0) {
				foreach ($invoice_activities as $key => $activity) {
					$taxes = DB::table('activity_tax')->leftjoin('taxes', 'activity_tax.tax_id', '=', 'taxes.id')->where('activity_id', $activity->id)->select('taxes.tax_name', 'taxes.tax_rate', 'activity_tax.*')->get();
					$activity->taxes = $taxes;
				}
			}

			$this->data['activities']['signature_attachment'] = Attachment::where('entity_id', $invoice_activities[0]->asp_id)->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))->first();

			$this->data['activities']['signature_attachment_path'] = url('storage/' . config('rsa.asp_attachment_path_view'));

		}

		$this->data['activities']['asp_service_type_data'] = AspServiceType::where('asp_id', $activity->asp_id)->where('service_type_id', $activity->service_type_id)->first();
		$configs = Config::where('entity_type_id', 23)->get();
		foreach ($configs as $config) {
			$detail = ActivityDetail::where('activity_id', $activity_status_id)->where('key_id', $config->id)->first();
			if (strpos($config->name, '_charges') || strpos($config->name, '_amount')) {

				$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($detail->value, 2))) : '-') : '-';
				$raw_key_name = 'raw_' . $config->name;
				$this->data['activities'][$raw_key_name] = $detail ? (!empty($detail->value) ? $detail->value : '-') : '-';
			} else if (strpos($config->name, 'date')) {
				$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? date("d-m-Y H:i:s", strtotime($detail->value)) : '-') : '-';
			} else {
				$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? $detail->value : '-') : '-';
			}
			//dump($config->name,$this->data['activities'][$config->name]);
		}
		/*if ($this->data['activities']['asp_service_type_data']->adjustment_type == 1) {
				$this->data['activities']['bo_deduction'] = ($this->data['activities']['raw_bo_po_amount'] * $this->data['activities']['asp_service_type_data']->adjustment) / 100;
			} else if ($this->data['activities']['asp_service_type_data']->adjustment_type == 2) {
				//AMOUNT
				$this->data['activities']['bo_deduction'] = $this->data['activities']['asp_service_type_data']->adjustment;
		*/
		//dd( $this->data);
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
				$var_key_val = DB::table('activity_details')->updateOrInsert(['activity_id' => $request->activity_id, 'key_id' => $keyw, 'company_id' => 1], ['value' => $value]);
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
			// $sms_message = 'BO_APPROVED';
			// sendSMS2($sms_message,$mobile_number,$activity->number);

			$mobile_number = $activity->asp->contact_number1;
			$sms_message = 'BO_APPROVED';
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
				$sms_message = 'BO_APPROVED';
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
			$sms_message = 'BO_DEFERRED';
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
		//dd($request->all());
		$number = $request->number;
		$validator = Validator::make($request->all(), [
			'number' => 'required',
		]);

		if ($validator->fails()) {
			$response = ['success' => false, 'errors' => ["Ticket number is required"]];
			return response()->json($response);
		}

		$today = date('Y-m-d'); //current date

		$nocInfo = checkNocInfo();

		//THIS IS THE ORIGINAL CONDITION
		$threeMonthsBefore = date('Y-m-d', strtotime("-3 months", strtotime($today))); //three months before

		//FOR CHANGE REQUEST BY TVS TEAM DATE GIVEN IN STATIC
		// $threeMonthsBefore = "2019-04-01";
		$asp = Asp::where('id', Auth::user()->asp->id)->first();
		$case = RsaCase::where(function ($q) use ($number) {
			$q->where('number', $number)
				->orWhere('vehicle_registration_number', $number);
		})->first();

		if (!$case) {
			$response = ['success' => false, 'errors' => ["Ticket not found"]];
			return response()->json($response);
		} else {
			$case_with_cancelled_status = RsaCase::where(function ($q) use ($number) {
				$q->where('number', $number)
					->orWhere('vehicle_registration_number', $number);
			})
				->where('status_id', 3) //CANCELLED
				->first();
			if ($case_with_cancelled_status) {
				$response = ['success' => false, 'errors' => ["Ticket is cancelled"]];
				return response()->json($response);
			}
			$case_with_closed_status = RsaCase::where(function ($q) use ($number) {
				$q->where('number', $number)
					->orWhere('vehicle_registration_number', $number);
			})
				->where('status_id', 4) //CLOSED
				->first();
			//dd($case_with_closed_status);
			if (!$case_with_closed_status) {
				$response = ['success' => false, 'errors' => ["Ticket is not closed"]];
				return response()->json($response);
			}
			$case_date = date('Y-m-d', strtotime($case->created_at));
			if ($case_date < $threeMonthsBefore) {
				$response = ['success' => false, 'errors' => ["Please contact administrator."]];
				return response()->json($response);
			} else {
				$activity_asp = Activity::join('cases', 'cases.id', 'activities.case_id')
					->where([
						['activities.asp_id', Auth::user()->asp->id],
						['activities.case_id', $case->id],
					])
					->first();
				if ($activity_asp) {
					//NOC NOT GENERATED FOR PREV QUARTERS
					if ($nocInfo['noc']) {
						$payment_pending_tickets_prev_qrts = Activity::join('cases', 'cases.id', 'activities.case_id')
							->where([
								['activities.asp_id', Auth::user()->asp->id],
								['activities.case_id', $case->id],
							])
							->whereNotIn('activities.status_id', [2, 4, 7, 14])
							->whereDate('cases.date', '>=', $nocInfo['period_start_date'])
							->whereDate('cases.date', '<=', $nocInfo['period_end_date'])
							->get();

						if ($payment_pending_tickets_prev_qrts->count() > 0) {
							if (date("Y-m-d", strtotime($case->date)) >= $nocInfo['present_period_start_date']) {
								$response = ['success' => false, 'errors' => ["Kindly complete the payment for the previous quarter tickets, so that you can continue with current quarter tickets."]];
								return response()->json($response);
							}
						}
						if (date("Y-m-d", strtotime($case->date)) >= $nocInfo['present_period_start_date']) {
							$response = ['success' => false, 'errors' => ["Kindly generate NOC for the previous quarter tickets, so that you can continue with current quarter tickets."]];
							return response()->json($response);
						}
					}

					$activity = Activity::join('cases', 'cases.id', 'activities.case_id')
						->where([
							['activities.asp_id', Auth::user()->asp->id],
							['activities.case_id', $case->id],
						])
						->whereIn('activities.status_id', [2, 4])
						->select('activities.id as id')
						->first();
					if ($activity) {
						$response = ['success' => true, 'activity_id' => $activity->id];
						return response()->json($response);
					} else {
						$activity_on_hold = Activity::join('cases', 'cases.id', 'activities.case_id')
							->where([
								['activities.asp_id', Auth::user()->asp->id],
								['activities.case_id', $case->id],
							])
							->where('activities.status_id', 17) //ON HOLD
							->first();

						if ($activity_on_hold) {
							$response = ['success' => false, 'errors' => ["Activity On Hold"]];
							return response()->json($response);
						}

						$activity_already_completed = Activity::join('cases', 'cases.id', 'activities.case_id')
							->where([
								['activities.asp_id', Auth::user()->asp->id],
								['activities.case_id', $case->id],
							])
							->whereIn('activities.status_id', [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16])
							->first();

						if ($activity_already_completed) {
							$response = ['success' => false, 'errors' => ["Ticket already submitted."]];
							return response()->json($response);
						} else {
							$response = ['success' => false, 'errors' => ["Activity Not Found"]];
							return response()->json($response);
						}
					}
				} else {
					$response = ['success' => false, 'errors' => ["Ticket is not attended by " . Auth::user()->asp->asp_code . " as per CRM"]];
					return response()->json($response);
				}
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

			$range_limit = "";

			if (!empty($request->update_attach_map_id)) {
				$update_attach_km_map_ids = json_decode($request->update_attach_km_map_id, true);
				Attachment::whereIn('id', $update_attach_km_map_ids)->delete();
			}
			if (!empty($request->update_attach_other_id)) {
				$update_attach_other_ids = json_decode($request->update_attach_other_id, true);
				Attachment::whereIn('id', $update_attach_other_ids)->delete();
			}

			$aspServiceType = AspServiceType::where('asp_id', $activity->asp_id)->where('service_type_id', $activity->service_type_id)->first();
			if ($aspServiceType) {
				$range_limit = $aspServiceType->range_limit;
			}

			if (!empty($request->comments)) {
				// $activity->comments = $request->comments;
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
				$this->data['activities'][$config->name] = $detail->value ? $detail->value : 0;

			}
			$mis_km = $this->data['activities']['cc_total_km'];
			$not_collect_charges = $this->data['activities']['cc_not_collected_amount'];
			$asp_km = empty($request->km_travelled) ? 0 : $request->km_travelled;
			$asp_other = empty($request->other_charge) ? 0 : $request->other_charge;

			$is_bulk = true;

			//1. checking MIS and ASP Service
			if ($request->asp_service_type_id && $activity->service_type_id != $request->asp_service_type_id) {
				$is_bulk = false;

			}

			//2. checking MIS and ASP KMs
			$allowed_variation = 0.5;
			$five_percentage_difference = $mis_km * $allowed_variation / 100;
			if ($asp_km > $range_limit || $range_limit == "") {
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
			if ($mis_km == 0) {
				$is_bulk = false;

			}
			//ASP DATA ENTRY - NEW
			if ($request->data_reentry == '1') {
				if ($is_bulk) {
					$activity->status_id = 8;
				} else {
					$activity->status_id = 9;
				}
			} else {
				//ASP DATA RE-ENTRY - DEFERRED
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

			$activity->save();

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
			$sms_message = 'ASP_DATA_ENTRY_DONE';
			$array = [$activity->case->number];
			// sendSMS2($sms_message, $mobile_number, $array);

			//sending notification to all BO users
			//$bo_users = User::where('users.role_id', 6)->pluck('users.id'); //6 - Bo User role ID
			$state_id = Auth::user()->asp->state_id;
			$bo_users = StateUser::where('state_id', $state_id)->pluck('user_id');

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
				$nocInfo = checkNocInfo();
				//NOC NOT GENERATED FOR PREV QUARTERS
				if ($nocInfo['noc']) {
					$payment_pending_tickets_prev_qrts = Activity::join('cases', 'cases.id', 'activities.case_id')
						->where('activities.asp_id', Auth::user()->asp->id)
						->whereNotIn('activities.status_id', [2, 4, 7, 14])
						->whereDate('cases.date', '>=', $nocInfo['period_start_date'])
						->whereDate('cases.date', '<=', $nocInfo['period_end_date'])
						->get();

					if ($payment_pending_tickets_prev_qrts->count() > 0) {
						if (date("Y-m-d", strtotime($activities->case_date)) >= $nocInfo['present_period_start_date']) {
							return '';
						} else {
							return '<input type="checkbox" class="ticket_id no-link child_select_all" name="invoice_ids[]" value="' . $activities->id . '">';
						}
					} else if (date("Y-m-d", strtotime($activities->case_date)) >= $nocInfo['present_period_start_date']) {
						return '';
					} else {
						return '<input type="checkbox" class="ticket_id no-link child_select_all" name="invoice_ids[]" value="' . $activities->id . '">';
					}
				} else {
					return '<input type="checkbox" class="ticket_id no-link child_select_all" name="invoice_ids[]" value="' . $activities->id . '">';
				}

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
				'total_tax_amount.value as total_tax_amount_value'
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

				//CHECK INVOICE NUMBER EXIST
				$is_invoice_no_exist = Invoices::where('invoice_no', $request->invoice_no)->first();
				if ($is_invoice_no_exist) {
					return response()->json([
						'success' => false,
						'error' => 'Invoice number already exist',
					]);
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
					'message' => $invoice_c['message'],
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

	public function exportActivities(Request $request) {
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
		$activities = Activity::join('cases', 'activities.case_id', '=', 'cases.id')->join('asps', 'activities.asp_id', '=', 'asps.id')->whereIn('activities.status_id', $status_ids)
			->whereDate('activities.created_at', '>=', $range1)
			->whereDate('activities.created_at', '<=', $range2)
			->select('asps.*', 'activities.*', 'activities.id as id');
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
				->select(DB::raw('COUNT(activities.id) as activity_count'), 'activity_portal_statuses.id', 'activity_portal_statuses.name')

				->where('activity_portal_statuses.id', $status_id)
				->whereDate('activities.created_at', '>=', $range1)
				->whereDate('activities.created_at', '<=', $range2)
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
			'Case Number',
			'Case Date',
			'Activity Number',
			'Activity Date',
			'Customer Name',
			'Customer Contact Number',
			'ASP Name',
			'ASP Code',
			'ASP Contact Number',
			'ASP EMail',
			'ASP has GST',
			'ASP Type',
			'Auto Invoice',
			'Workshop Name',
			'Location',
			'District',
			'State',
			'Vehicle Registration Number',
			'Vehicle Model',
			'Vehicle Make',
			'Case Status',
			'Finance Status',
			'ASP Service Type',
			'ASP Activity Rejected Reason',
			'ASP PO Accepted',
			'ASP PO Rejected Reason',
			'Portal Status',
			'Activity Status',
			'Activity Description',
			'Remarks',
			'Invoice Number',
			'Invoice Date',
			'Invoice Amount',
			'Invoice Status',
			'Payment Date',
			'Payment Mode',
			'Paid Amount',
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
		];
		$activity_details_data = [];
		//dd($activities);
		$activity_details_header = array_merge($activity_details_header, $status_headers);
		//dd($activity_details_header );

		foreach ($activities->get() as $activity_key => $activity) {
			$activity_details_data[] = [
				$activity->case->number,
				date('d-m-Y H:i:s', strtotime($activity->case->date)),
				$activity->number,
				date('d-m-Y H:i:s', strtotime($activity->created_at)),
				$activity->case->customer_name,
				$activity->case->customer_contact_number,
				$activity->asp->name,
				$activity->asp->axpta_code,
				$activity->asp->contact_number1,
				$activity->asp->email,
				$activity->asp->has_gst ? 'Yes' : 'No',
				$activity->asp->is_self == 1 ? 'Self' : 'Non Self',
				$activity->asp->is_auto_invoice == 1 ? 'Yes' : 'No',
				$activity->asp->workshop_name,
				$activity->asp->location->name,
				$activity->asp->district->name,
				$activity->asp->state->name,
				$activity->case->vehicle_registration_number,
				$activity->case->vehicleModel->name,
				$activity->case->vehicleModel->vehiclemake->name,
				$activity->case->status->name,
				$activity->financeStatus->name,
				$activity->serviceType->name,
				$activity->aspActivityRejectedReason ? $activity->aspActivityRejectedReason->name : '',
				$activity->asp_po_accepted != NULL ? ($activity->asp_po_accepted == 1 ? 'Yes' : 'No') : '',
				$activity->aspPoRejectedReason ? $activity->aspPoRejectedReason->name : '',
				$activity->status ? $activity->status->name : '',
				$activity->activityStatus ? $activity->activityStatus->name : '',
				$activity->description != NULL ? $activity->description : '',
				$activity->remarks != NULL ? $activity->remarks : '',
				// $activity->invoice ? ($activity->asp->has_gst == 1 && $activity->asp->is_auto_invoice == 0 ? ($activity->invoice->invoice_no) : ($activity->invoice->invoice_no . '-' . $activity->invoice->id)) : '',
				$activity->invoice ? $activity->invoice->invoice_no : '',
				$activity->invoice ? date('d-m-Y', strtotime($activity->invoice->start_date)) : '',
				$activity->invoice ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($activity->invoice->invoice_amount, 2))) : '',
				$activity->invoice ? ($activity->invoice->invoiceStatus ? $activity->invoice->invoiceStatus->name : '') : '',
				'',
				'',
				'',
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
				$sheet->fromArray($activity_details_data, NULL, 'A1');
				$sheet->row(1, $activity_details_header);
				$sheet->cells('A1:CU1', function ($cells) {
					$cells->setFont(array(
						'size' => '10',
						'bold' => true,
					))->setBackground('#CCC9C9');
				});
			});
		})->export('xls');

		return redirect()->back()->with(['success' => 'exported!']);
	}
	public static function findDifference($date1, $date2) {
		$date1 = date_create(date('Y-m-d', strtotime($date1)));
		$date2 = date_create(date('Y-m-d', strtotime($date2)));
		$diff_date = date_diff($date1, $date2);
		$duration = ($diff_date->format("%d") > 1) ? $diff_date->format("%d") : $diff_date->format("%d");
		return $duration;
	}
}
