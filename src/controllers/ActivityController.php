<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use App\Http\Controllers\Controller;
use App\StateUser;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ActivityController extends Controller {

	public function getList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			'asps.asp_code',
			'service_types.name as sub_service',
			'activity_asp_statuses.name as asp_status',
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
			->leftjoin('activity_asp_statuses', 'activity_asp_statuses.id', 'activities.asp_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->orderBy('cases.date', 'DESC')
			->groupBy('activities.id')
		;

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
				$action = '<div class="dataTable-actions">';
				if (Entrust::can('view-activities')) {
					$action .= '<a href="#!/rsa-case-pkg/activity-status/view/' . $activity->id . '">
					                <i class="fa fa-eye dataTable-icon--view" aria-hidden="true"></i>
					            </a>';
				}
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

}
