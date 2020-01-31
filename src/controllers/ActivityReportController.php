<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityPortalStatus;
use App\Http\Controllers\Controller;
use App\StateUser;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use App\StateUser;
use Illuminate\Support\Facades\Response;
use Yajra\Datatables\Datatables;

class ActivityReportController extends Controller {

	public function getExceptionalReportFilterData() {
		$this->data['extras'] = [
			'status_list' => collect(ActivityPortalStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Portal Status']),
		];
		return response()->json($this->data);
	}

	public function getExceptionalReportList(Request $request) {
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

				$action = '<div class="dataTable-actions wid-100">
				<a href="#!/rsa-case-pkg/activity-status/' . $status_id . '/view/' . $activity->id . '">
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

}
