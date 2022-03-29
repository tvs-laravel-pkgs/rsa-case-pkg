<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityPortalStatus;
use Abs\RsaCasePkg\ActivityRatecard;
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
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
			'exportFilterByList' => [
				['id' => '', 'name' => 'Select Filter By'],
				['id' => 'general', 'name' => 'General'],
				['id' => 'activity', 'name' => 'Activity'],
				['id' => 'invoiceDate', 'name' => 'Invoice Date'],
				['id' => 'transactionDate', 'name' => 'Transaction Date'],
			],
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

		$activities = Activity::select([
			'activities.id',
			'activities.crm_activity_id',
			'activities.is_towing_attachments_mandatory',
			'activities.status_id as status_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			// 'asps.asp_code',
			DB::raw('CONCAT(asps.asp_code," / ",asps.workshop_name) as asp'),
			'service_types.name as sub_service',
			'service_types.service_group_id',
			// 'activity_asp_statuses.name as asp_status',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'configs.name as source',
			'call_centers.name as call_center',
		])
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
				$sql = "CONCAT(asps.asp_code,' / ',asps.workshop_name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->addColumn('action', function ($activity) {
				$status_id = 1;
				$return_status_ids = [5, 6, 8, 9, 11, 1, 7, 18, 19, 20, 21, 22];

				$action = '<div class="dataTable-actions">
				<a href="#!/rsa-case-pkg/activity-status/' . $status_id . '/view/' . $activity->id . '">
					                <i class="fa fa-eye dataTable-icon--view" aria-hidden="true"></i>
					            </a>';
				if (($activity->status_id == 2 || $activity->status_id == 4 || $activity->status_id == 15 || $activity->status_id == 16 || $activity->status_id == 17) && Entrust::can('delete-activities')) {
					$action .= '<a onclick="angular.element(this).scope().deleteConfirm(' . $activity->id . ')" href="javascript:void(0)">
						                <i class="fa fa-trash dataTable-icon--trash cl-delete" data-cl-id =' . $activity->id . ' aria-hidden="true"></i>
						            </a>';
				}

				if (Entrust::can('backstep-activity') && in_array($activity->status_id, $return_status_ids)) {
					$activityDetail = new Activity;
					$activityDetail->id = $activity->id;
					$activityDetail->status_id = $activity->status_id;
					$activityDetail->activity_number = $activity->activity_number;

					$action .= "<a href='javascript:void(0)' onclick='angular.element(this).scope().backConfirm(" . $activityDetail . ")' class='ticket_back_button'><i class='fa fa-arrow-left dataTable-icon--edit-1' data-cl-id =" . $activity->id . " aria-hidden='true'></i></a>";
				}

				//IF ASP DATA ENTRY OR REENTRY & TOWING SERVICE GROUP
				if (($activity->status_id == 2 || $activity->status_id == 7) && $activity->service_group_id == 3 && Entrust::can('towing-images-required-for-activities')) {
					$action .= '<a onclick="angular.element(this).scope().towingImageRequiredBtn(' . $activity->id . ',' . $activity->is_towing_attachments_mandatory . ')" href="javascript:void(0)">
										<i class="dataTable-icon--edit-1" data-cl-id =' . $activity->id . ' aria-hidden="true"><img class="" src="resources/assets/images/edit-note.svg"></i>
						            </a>';
				}

				$action .= '</div>';
				return $action;
			})
			->make(true);
	}

	public function activityBackAsp(Request $request) {
		//	dd($request->all());
		$activity = Activity::findOrFail($request->activty_id);
		$return_status_ids = [5, 6, 8, 9, 11, 1, 7, 18, 19, 20, 21, 22];

		if (!$activity) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
				'error' => 'Activity not found',
			]);
		}

		if (!in_array($activity->status_id, $return_status_ids)) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
				'error' => 'Activity not eligible for back step',
			]);
		}

		if (!Entrust::can('backstep-activity')) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
				'error' => 'User not eligible to back step',
			]);
		}

		//ASP Rejected CC Details - Waiting for ASP Data Entry
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

				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'success' => 'Activity status moved to ASP data entry',
				]);
			} else {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'error' => 'Activity status not moved to ASP data entry',
				]);
			}
		} elseif ($request->ticket_status_id == '2') {
			//BO Rejected - Waiting for ASP Data Re-Entry
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

				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'success' => 'Activity status moved to ASP Data Re-Entry',
				]);
			} else {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'error' => 'Activity status not moved to ASP Data Re-Entry',
				]);
			}
		}
	}

	public function delete($id) {
		$deleteActivityBaseQuery = Activity::where('id', $id);
		if (Auth::check()) {
			$deleteActivityUpdatedByQuery = clone $deleteActivityBaseQuery;
			$deleteActivityUpdatedByQuery->update([
				'updated_by_id' => Auth::user()->id,
			]);
		}
		$deleteActivityQuery = clone $deleteActivityBaseQuery;
		$deleteActivityQuery->delete();

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
			DB::raw('CONCAT(asps.asp_code," / ",asps.workshop_name) as asp'),
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
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
		// ->where('activities.asp_accepted_cc_details', '!=', 1)
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

		if (Auth::check()) {
			if (!empty(Auth::user()->activity_approval_level_id)) {
				//L1
				if (Auth::user()->activity_approval_level_id == 1) {
					$activities->whereIn('activities.status_id', [5, 8]); //ASP Completed Data Entry - Waiting for L1 Bulk Verification AND ASP Data Re-Entry Completed - Waiting for L1 Bulk Verification
				} elseif (Auth::user()->activity_approval_level_id == 2) {
					// L2
					$activities->where('activities.status_id', 18); //Waiting for L2 Bulk Verification
				} elseif (Auth::user()->activity_approval_level_id == 3) {
					// L3
					$activities->where('activities.status_id', 20); //Waiting for L3 Bulk Verification
				} else {
					$activities->whereNull('activities.status_id');
				}
			} else {
				$activities->whereNull('activities.status_id');
			}
		} else {
			$activities->whereNull('activities.status_id');
		}

		return Datatables::of($activities)
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.workshop_name)  like ?";
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
			DB::raw('CONCAT(asps.asp_code," / ",asps.workshop_name) as asp'),
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
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
		// ->where('activities.asp_accepted_cc_details', '!=', 1)
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

		if (Auth::check()) {
			if (!empty(Auth::user()->activity_approval_level_id)) {
				//L1
				if (Auth::user()->activity_approval_level_id == 1) {
					$activities->whereIn('activities.status_id', [6, 9, 22]); //ASP Completed Data Entry - Waiting for L1 Individual Verification AND ASP Data Re-Entry Completed - Waiting for L1 Individual Verification AND BO Rejected - Waiting for L1 Individual Verification
				} elseif (Auth::user()->activity_approval_level_id == 2) {
					// L2
					$activities->where('activities.status_id', 19); //Waiting for L2 Individual Verification
				} elseif (Auth::user()->activity_approval_level_id == 3) {
					// L3
					$activities->where('activities.status_id', 21); //Waiting for L3 Individual Verification
				} else {
					$activities->whereNull('activities.status_id');
				}
			} else {
				$activities->whereNull('activities.status_id');
			}
		} else {
			$activities->whereNull('activities.status_id');
		}

		return Datatables::of($activities)
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.workshop_name)  like ?";
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
		try {
			$activityApprovalLevel = '';
			$activity_data = Activity::findOrFail($activity_status_id);
			if ($view_type_id == 2) {
				if (!$activity_data || ($activity_data && $activity_data->status_id != 5 && $activity_data->status_id != 6 && $activity_data->status_id != 8 && $activity_data->status_id != 9 && $activity_data->status_id != 18 && $activity_data->status_id != 19 && $activity_data->status_id != 20 && $activity_data->status_id != 21 && $activity_data->status_id != 22)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Activity is not valid for Verification',
						],
					]);
				}

				if (Auth::check()) {
					if (empty(Auth::user()->activity_approval_level_id)) {
						return response()->json([
							'success' => false,
							'errors' => [
								'User is not valid for Verification',
							],
						]);
					} else {
						$activityApprovalLevel = Auth::user()->activity_approval_level_id;
					}
				} else {
					return response()->json([
						'success' => false,
						'errors' => [
							'User is not valid for Verification',
						],
					]);
				}
			}

			if (!$activity_data->case->vehicleModel) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Vehicle model is required',
					],
				]);
			}

			if (!$activity_data->case->vehicleModel->vehiclecategory) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Vehicle category not mapped for the vehicle model',
					],
				]);
			}

			$isMobile = 0; //WEB
			//MOBILE APP
			if ($activity_data->data_src_id == 260 || $activity_data->data_src_id == 263) {
				$isMobile = 1;
			}

			$asp_service_type_data = AspServiceType::where('asp_id', $activity_data->asp_id)
				->where('service_type_id', $activity_data->service_type_id)
				->where('vehicle_category_id', $activity_data->case->vehicleModel->vehiclecategory->id)
				->where('is_mobile', $isMobile)
				->first();

			if (!$asp_service_type_data) {
				return response()->json([
					'success' => false,
					'errors' => [
						"Service (" . $activity_data->serviceType->name . ") not enabled for the ASP (" . $activity_data->asp->asp_code . ")",
					],
				]);
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
			])
				->select([
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
					'cases.customer_contact_number',
					'cases.bd_lat',
					'cases.bd_long',
					'cases.bd_location',
					'cases.bd_city',
					'cases.bd_state',
					'activities.number as activity_number',
					'activities.asp_po_accepted as asp_po_accepted',
					'activities.defer_reason as defer_reason',
					'activities.general_remarks',
					'activities.asp_resolve_comments',
					'activities.is_exceptional_check as is_exceptional_check',
					'activities.service_type_changed_on_level',
					'activities.km_changed_on_level',
					'activities.not_collected_amount_changed_on_level',
					'activities.collected_amount_changed_on_level',
					'activities.exceptional_reason',
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
					'bd_location_category.name as location_category',
					'activities.data_src_id',
				])
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
			$this->data['activities']['activityApprovalLevel'] = $activityApprovalLevel;

			$vehiclePickupAttachment = Attachment::where([
				['entity_id', '=', $activity_status_id],
				['entity_type', '=', 18],
			])
				->first();
			$vehiclePickupAttachmentUrl = '';
			if ($vehiclePickupAttachment) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $vehiclePickupAttachment->attachment_file_name)) {
						$vehiclePickupAttachmentUrl = aspTicketAttachmentImage($vehiclePickupAttachment->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}

				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $vehiclePickupAttachment->attachment_file_name)) {
						$vehiclePickupAttachmentUrl = aspTicketAttachmentImage($vehiclePickupAttachment->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
			}
			$this->data['activities']['vehiclePickupAttachment'] = $vehiclePickupAttachment;
			$this->data['activities']['vehiclePickupAttachmentUrl'] = $vehiclePickupAttachmentUrl;

			$vehicleDropAttachment = Attachment::where([
				['entity_id', '=', $activity_status_id],
				['entity_type', '=', 19],
			])
				->first();
			$vehicleDropAttachmentUrl = '';
			if ($vehicleDropAttachment) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $vehicleDropAttachment->attachment_file_name)) {
						$vehicleDropAttachmentUrl = aspTicketAttachmentImage($vehicleDropAttachment->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}
				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $vehicleDropAttachment->attachment_file_name)) {
						$vehicleDropAttachmentUrl = aspTicketAttachmentImage($vehicleDropAttachment->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
			}
			$this->data['activities']['vehicleDropAttachment'] = $vehicleDropAttachment;
			$this->data['activities']['vehicleDropAttachmentUrl'] = $vehicleDropAttachmentUrl;

			$inventoryJobSheetAttachment = Attachment::where([
				['entity_id', '=', $activity_status_id],
				['entity_type', '=', 20],
			])
				->first();
			$inventoryJobSheetAttachmentUrl = '';
			if ($inventoryJobSheetAttachment) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $inventoryJobSheetAttachment->attachment_file_name)) {
						$inventoryJobSheetAttachmentUrl = aspTicketAttachmentImage($inventoryJobSheetAttachment->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}
				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $inventoryJobSheetAttachment->attachment_file_name)) {
						$inventoryJobSheetAttachmentUrl = aspTicketAttachmentImage($inventoryJobSheetAttachment->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
			}
			$this->data['activities']['inventoryJobSheetAttachment'] = $inventoryJobSheetAttachment;
			$this->data['activities']['inventoryJobSheetAttachmentUrl'] = $inventoryJobSheetAttachmentUrl;

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

			$casewiseRatecardEffectDatetime = config('rsa.CASEWISE_RATECARD_EFFECT_DATETIME');
			//Activity creation datetime greater than effective datetime
			if (date('Y-m-d H:i:s', strtotime($activity->activity_date)) > $casewiseRatecardEffectDatetime) {
				//Activity that is initiated for payment process & not eligible
				if ($activity->activity_portal_status_id == 1 || $activity->activity_portal_status_id == 10 || $activity->activity_portal_status_id == 11 || $activity->activity_portal_status_id == 12 || $activity->activity_portal_status_id == 13 || $activity->activity_portal_status_id == 14 || $activity->activity_portal_status_id == 15 || $activity->activity_portal_status_id == 16 || $activity->activity_portal_status_id == 17) {
					$activityRatecard = ActivityRatecard::select([
						'range_limit',
						'below_range_price',
						'above_range_price',
						'waiting_charge_per_hour',
						'empty_return_range_price',
						'adjustment_type',
						'adjustment',
					])
						->where('activity_id', $activity_status_id)
						->first();
					if ($activityRatecard) {
						$asp_service_type_data = $activityRatecard;
					}
				}
			}

			$this->data['activities']['asp_service_type_data'] = $asp_service_type_data;

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
						->where('vehicle_category_id', $activity_data->case->vehicleModel->vehiclecategory->id)
						->where('is_mobile', $isMobile)
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

			$importedAt = "";
			$importedBy = "";
			$aspDataFilledAt = "";
			$aspDataFilledBy = "";
			$boDefferedAt = "";
			$boDefferedBy = "";
			$boApprovedAt = "";
			$boApprovedBy = "";
			$l2DefferedAt = "";
			$l2DefferedBy = "";
			$l2ApprovedAt = "";
			$l2ApprovedBy = "";
			$l3DefferedAt = "";
			$l3DefferedBy = "";
			$l3ApprovedAt = "";
			$l3ApprovedBy = "";
			$invoiceGeneratedAt = "";
			$invoiceGeneratedBy = "";
			$axaptaGeneratedAt = "";
			$axaptaGeneratedBy = "";
			$paymentCompletedAt = "";

			if ($activity_data->log) {
				if (!empty($activity_data->log->imported_at)) {
					$importedAt = $activity_data->log->imported_at;
				}
				if (!empty($activity_data->log->imported_by_id)) {
					$importedBy = $activity_data->log->importedBy ? ($activity_data->log->importedBy->name . ' - ' . $activity_data->log->importedBy->username) : '';
				}
				if (!empty($activity_data->log->asp_data_filled_at)) {
					$aspDataFilledAt = $activity_data->log->asp_data_filled_at;
				}
				if (!empty($activity_data->log->asp_data_filled_by_id)) {
					$aspDataFilledBy = $activity_data->log->aspDataFilledBy ? ($activity_data->log->aspDataFilledBy->name . ' - ' . $activity_data->log->aspDataFilledBy->username) : '';
				}
				if (!empty($activity_data->log->bo_deffered_at)) {
					$boDefferedAt = $activity_data->log->bo_deffered_at;
				}
				if (!empty($activity_data->log->bo_deffered_by_id)) {
					$boDefferedBy = $activity_data->log->boDefferedBy ? ($activity_data->log->boDefferedBy->name . ' - ' . $activity_data->log->boDefferedBy->username) : '';
				}
				if (!empty($activity_data->log->bo_approved_at)) {
					$boApprovedAt = $activity_data->log->bo_approved_at;
				}
				if (!empty($activity_data->log->bo_approved_by_id)) {
					$boApprovedBy = $activity_data->log->boApprovedBy ? ($activity_data->log->boApprovedBy->name . ' - ' . $activity_data->log->boApprovedBy->username) : '';
				}
				if (!empty($activity_data->log->l2_deffered_at)) {
					$l2DefferedAt = $activity_data->log->l2_deffered_at;
				}
				if (!empty($activity_data->log->l2_deffered_by_id)) {
					$l2DefferedBy = $activity_data->log->l2DefferedBy ? ($activity_data->log->l2DefferedBy->name . ' - ' . $activity_data->log->l2DefferedBy->username) : '';
				}
				if (!empty($activity_data->log->l2_approved_at)) {
					$l2ApprovedAt = $activity_data->log->l2_approved_at;
				}
				if (!empty($activity_data->log->l2_approved_by_id)) {
					$l2ApprovedBy = $activity_data->log->l2ApprovedBy ? ($activity_data->log->l2ApprovedBy->name . ' - ' . $activity_data->log->l2ApprovedBy->username) : '';
				}
				if (!empty($activity_data->log->l3_deffered_at)) {
					$l3DefferedAt = $activity_data->log->l3_deffered_at;
				}
				if (!empty($activity_data->log->l3_deffered_by_id)) {
					$l3DefferedBy = $activity_data->log->l3DefferedBy ? ($activity_data->log->l3DefferedBy->name . ' - ' . $activity_data->log->l3DefferedBy->username) : '';
				}
				if (!empty($activity_data->log->l3_approved_at)) {
					$l3ApprovedAt = $activity_data->log->l3_approved_at;
				}
				if (!empty($activity_data->log->l3_approved_by_id)) {
					$l3ApprovedBy = $activity_data->log->l3ApprovedBy ? ($activity_data->log->l3ApprovedBy->name . ' - ' . $activity_data->log->l3ApprovedBy->username) : '';
				}
				if (!empty($activity_data->log->invoice_generated_at)) {
					$invoiceGeneratedAt = $activity_data->log->invoice_generated_at;
				}
				if (!empty($activity_data->log->invoice_generated_by_id)) {
					$invoiceGeneratedBy = $activity_data->log->invoiceGeneratedBy ? ($activity_data->log->invoiceGeneratedBy->name . ' - ' . $activity_data->log->invoiceGeneratedBy->username) : '';
				}
				if (!empty($activity_data->log->axapta_generated_at)) {
					$axaptaGeneratedAt = $activity_data->log->axapta_generated_at;
				}
				if (!empty($activity_data->log->axapta_generated_by_id)) {
					$axaptaGeneratedBy = $activity_data->log->axaptaGeneratedBy ? ($activity_data->log->axaptaGeneratedBy->name . ' - ' . $activity_data->log->axaptaGeneratedBy->username) : '';
				}
				if (!empty($activity_data->log->payment_completed_at)) {
					$paymentCompletedAt = $activity_data->log->payment_completed_at;
				}
			}

			$serviceTypes = AspServiceType::select([
				'service_types.id',
				'service_types.name',
			])
				->join('service_types', 'service_types.id', 'asp_service_types.service_type_id')
				->where('asp_service_types.asp_id', $activity->asp_id)
				->groupBy('asp_service_types.service_type_id')
				->get();
			$boServiceTypeId = '';
			$boServiceTypeData = ActivityDetail::where('activity_id', $activity_status_id)->where('key_id', 161)->first();
			if ($boServiceTypeData) {
				$boServiceType = ServiceType::where('name', $boServiceTypeData->value)->first();
				if ($boServiceType) {
					$boServiceTypeId = $boServiceType->id;
				}
			}

			$this->data['activities']['serviceTypes'] = $serviceTypes;
			$this->data['activities']['boServiceTypeId'] = $boServiceTypeId;
			$this->data['activities']['importedAt'] = $importedAt;
			$this->data['activities']['importedBy'] = $importedBy;
			$this->data['activities']['aspDataFilledAt'] = $aspDataFilledAt;
			$this->data['activities']['aspDataFilledBy'] = $aspDataFilledBy;
			$this->data['activities']['boDefferedAt'] = $boDefferedAt;
			$this->data['activities']['boDefferedBy'] = $boDefferedBy;
			$this->data['activities']['boApprovedAt'] = $boApprovedAt;
			$this->data['activities']['boApprovedBy'] = $boApprovedBy;
			$this->data['activities']['l2DefferedAt'] = $l2DefferedAt;
			$this->data['activities']['l2DefferedBy'] = $l2DefferedBy;
			$this->data['activities']['l2ApprovedAt'] = $l2ApprovedAt;
			$this->data['activities']['l2ApprovedBy'] = $l2ApprovedBy;
			$this->data['activities']['l3DefferedAt'] = $l3DefferedAt;
			$this->data['activities']['l3DefferedBy'] = $l3DefferedBy;
			$this->data['activities']['l3ApprovedAt'] = $l3ApprovedAt;
			$this->data['activities']['l3ApprovedBy'] = $l3ApprovedBy;
			$this->data['activities']['invoiceGeneratedAt'] = $invoiceGeneratedAt;
			$this->data['activities']['invoiceGeneratedBy'] = $invoiceGeneratedBy;
			$this->data['activities']['axaptaGeneratedAt'] = $axaptaGeneratedAt;
			$this->data['activities']['axaptaGeneratedBy'] = $axaptaGeneratedBy;
			$this->data['activities']['paymentCompletedAt'] = $paymentCompletedAt;

			$this->data['activities']['is_service_type_eligible'] = $is_service_type_eligible;
			$this->data['activities']['is_km_travelled_eligible'] = $is_km_travelled_eligible;
			$this->data['activities']['is_not_collected_eligible'] = $is_not_collected_eligible;
			$this->data['activities']['is_collected_eligible'] = $is_collected_eligible;
			$this->data['activities']['is_case_lapsed'] = $is_case_lapsed;
			$this->data['activities']['submission_closing_date'] = $submission_closing_date;

			return response()->json(['success' => true, 'data' => $this->data]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function getServiceTypeRateCardDetail(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'activity_id' => [
					'required:true',
					'integer',
					'exists:activities,id',
				],
				'service_type_id' => [
					'required:true',
					'integer',
					'exists:service_types,id',
				],
				'asp_id' => [
					'required:true',
					'integer',
					'exists:asps,id',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			$serviceType = ServiceType::find($request->service_type_id);

			$activity = Activity::find($request->activity_id);

			if (!$activity->case->vehicleModel) {
				return response()->json([
					'success' => false,
					'errors' => [
						"Vehicle model is required",
					],
				]);
			}

			if (!$activity->case->vehicleModel->vehiclecategory) {
				return response()->json([
					'success' => false,
					'errors' => [
						"Vehicle category not mapped for the vehicle model",
					],
				]);
			}

			$isMobile = 0; //WEB
			//MOBILE APP
			if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
				$isMobile = 1;
			}

			$asp_service_type_data = AspServiceType::where('asp_id', $request->asp_id)
				->where('service_type_id', $request->service_type_id)
				->where('vehicle_category_id', $activity->case->vehicleModel->vehiclecategory->id)
				->where('is_mobile', $isMobile)
				->first();

			if (!$asp_service_type_data) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Service (' . $serviceType->name . ') not enabled for the ASP (' . $activity->asp->asp_code . ')',
					],
				]);
			}

			return response()->json([
				'success' => true,
				'service' => $serviceType->name,
				'asp_service_type_data' => $asp_service_type_data,
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function approveActivity(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			if (Auth::check()) {
				if (empty(Auth::user()->activity_approval_level_id)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'User is not valid for Verification',
						],
					]);
				}
			} else {
				return response()->json([
					'success' => false,
					'errors' => [
						'User is not valid for Verification',
					],
				]);
			}

			if (empty($request->exceptional_reason)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Exceptional reason is required',
					],
				]);
			}

			if ($request->boServiceTypeId == '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Service is required',
					],
				]);
			}

			if ($request->bo_km_travelled !== '' && $request->bo_km_travelled <= 0) {
				return response()->json([
					'success' => false,
					'errors' => [
						'KM Travelled should be greater than zero',
					],
				]);
			}

			if ($request->bo_km_travelled !== 0 && $request->bo_km_travelled === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'KM Travelled is required',
					],
				]);
			}

			if ($request->bo_not_collected !== 0 && $request->bo_not_collected === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Charges not collected is required',
					],
				]);
			}

			if ($request->bo_collected !== 0 && $request->bo_collected === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Charges collected is required',
					],
				]);
			}

			if ($request->bo_net_amount <= 0) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Payout amount should be greater than zero',
					],
				]);
			}

			$activity = Activity::findOrFail($request->activity_id);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
					],
				]);
			}

			if (!$activity->case->vehicleModel) {
				return response()->json([
					'success' => false,
					'errors' => [
						"Vehicle model is required",
					],
				]);
			}

			if (!$activity->case->vehicleModel->vehiclecategory) {
				return response()->json([
					'success' => false,
					'errors' => [
						"Vehicle category not mapped for the vehicle model",
					],
				]);
			}

			$asp_km_travelled = ActivityDetail::where([['activity_id', '=', $activity->id], ['key_id', '=', 154]])->first();
			if (!$asp_km_travelled) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity ASP KM not found',
					],
				]);
			}

			//CHECK BO KM > ASP KM
			if ($request->bo_km_travelled > $asp_km_travelled->value) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Final KM should be less than or equal to ASP KM',
					],
				]);
			}

			$isServiceTypeChanged = false;
			$isKmTravelledChanged = false;
			$isNotCollectedChanged = false;
			$isCollectedChanged = false;

			$kmTravelled = $activity->detail(158) ? floatval($activity->detail(158)->value) : 0;
			$notCollected = $activity->detail(160) ? floatval($activity->detail(160)->value) : 0;
			$collected = $activity->detail(159) ? floatval($activity->detail(159)->value) : 0;

			if ($request->boServiceTypeId != $activity->service_type_id) {
				$isServiceTypeChanged = true;
			}
			if (floatval($request->bo_km_travelled) != $kmTravelled) {
				$isKmTravelledChanged = true;
			}
			if (floatval($request->bo_not_collected) != $notCollected) {
				$isNotCollectedChanged = true;
			}
			if (floatval($request->bo_collected) != $collected) {
				$isCollectedChanged = true;
			}

			$key_list = [158, 159, 160, 161, 176, 172, 173, 179, 182];
			foreach ($key_list as $keyw) {
				$var_key = Config::where('id', $keyw)->first();
				$key_name = str_replace(" ", "_", strtolower($var_key->name));
				$value = $request->$key_name ? str_replace(",", "", $request->$key_name) : 0;
				//NEW
				$activityDetail = ActivityDetail::firstOrNew([
					'company_id' => 1,
					'activity_id' => $request->activity_id,
					'key_id' => $keyw,
				]);
				$activityDetail->value = $value;
				$activityDetail->save();
			}
			if (isset($request->is_exceptional_check)) {
				$activity->is_exceptional_check = $request->is_exceptional_check;
				if (!empty($request->exceptional_reason)) {
					$exceptionalReason = $activity->exceptional_reason;
					if (Auth::user()->activity_approval_level_id == 1) {
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("\n L1 Approver : " . $request->exceptional_reason);
						} else {
							$exceptionalReason = 'L1 Approver : ' . $request->exceptional_reason;
						}
					} elseif (Auth::user()->activity_approval_level_id == 2) {
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("\n L2 Approver : " . $request->exceptional_reason);
						} else {
							$exceptionalReason = 'L2 Approver : ' . $request->exceptional_reason;
						}
					} elseif (Auth::user()->activity_approval_level_id == 3) {
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("\n L3 Approver : " . $request->exceptional_reason);
						} else {
							$exceptionalReason = 'L3 Approver : ' . $request->exceptional_reason;
						}
					}
					$activity->exceptional_reason = $exceptionalReason;
				}
			}

			$activity->bo_comments = isset($request->bo_comments) ? $request->bo_comments : NULL;
			$activity->deduction_reason = isset($request->deduction_reason) ? $request->deduction_reason : NULL;
			$activity->service_type_id = $request->boServiceTypeId;
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();

			$saveActivityRatecardResponse = $activity->saveActivityRatecard();
			if (!$saveActivityRatecardResponse['success']) {
				return response()->json([
					'success' => false,
					'errors' => [
						$saveActivityRatecardResponse['error'],
					],
				]);
			}

			$l2Approvers = User::where('activity_approval_level_id', 2)->pluck('id');
			$l3Approvers = User::where('activity_approval_level_id', 3)->pluck('id');

			$isActivityBulk = $this->isActivityBulkOnApproval($activity);
			$isApproved = false;
			$approver = '';
			//GREATER THAN 10000
			if (floatval($request->bo_net_amount) > 10000) {
				//L1
				if (Auth::user()->activity_approval_level_id == 1) {
					if ($isActivityBulk) {
						$activityStatusId = 18; //Waiting for L2 Bulk Verification
					} else {
						$activityStatusId = 19; //Waiting for L2 Individual Verification
					}
					$approver = '1';
					if ($isServiceTypeChanged) {
						$activity->service_type_changed_on_level = 1;
					}
					if ($isKmTravelledChanged) {
						$activity->km_changed_on_level = 1;
					}
					if ($isNotCollectedChanged) {
						$activity->not_collected_amount_changed_on_level = 1;
					}
					if ($isCollectedChanged) {
						$activity->collected_amount_changed_on_level = 1;
					}
					$this->sendApprovalNoty($l2Approvers, $activity->case->number, "L2_APPROVAL");
				} elseif (Auth::user()->activity_approval_level_id == 2) {
					// L2
					if ($isActivityBulk) {
						$activityStatusId = 20; //Waiting for L3 Bulk Verification
					} else {
						$activityStatusId = 21; //Waiting for L3 Individual Verification
					}
					$approver = '2';
					if ($isServiceTypeChanged) {
						$activity->service_type_changed_on_level = 2;
					}
					if ($isKmTravelledChanged) {
						$activity->km_changed_on_level = 2;
					}
					if ($isNotCollectedChanged) {
						$activity->not_collected_amount_changed_on_level = 2;
					}
					if ($isCollectedChanged) {
						$activity->collected_amount_changed_on_level = 2;
					}
					$this->sendApprovalNoty($l3Approvers, $activity->case->number, "L3_APPROVAL");
				} elseif (Auth::user()->activity_approval_level_id == 3) {
					// L3
					$activityStatusId = 11; //Waiting for Invoice Generation by ASP
					$isApproved = true;
					$approver = '3';
				}
			} elseif (floatval($request->bo_net_amount) > 4000 && floatval($request->bo_net_amount) <= 10000) {
				//GREATER THAN 4000 AND LESSER THAN OR EQUAL TO 10000
				//L1
				if (Auth::user()->activity_approval_level_id == 1) {
					if ($isActivityBulk) {
						$activityStatusId = 18; //Waiting for L2 Bulk Verification
					} else {
						$activityStatusId = 19; //Waiting for L2 Individual Verification
					}
					$approver = '1';
					if ($isServiceTypeChanged) {
						$activity->service_type_changed_on_level = 1;
					}
					if ($isKmTravelledChanged) {
						$activity->km_changed_on_level = 1;
					}
					if ($isNotCollectedChanged) {
						$activity->not_collected_amount_changed_on_level = 1;
					}
					if ($isCollectedChanged) {
						$activity->collected_amount_changed_on_level = 1;
					}
					$this->sendApprovalNoty($l2Approvers, $activity->case->number, "L2_APPROVAL");
				} elseif (Auth::user()->activity_approval_level_id == 2) {
					// L2
					$activityStatusId = 11; //Waiting for Invoice Generation by ASP
					$isApproved = true;
					$approver = '2';
					if ($isServiceTypeChanged) {
						$activity->service_type_changed_on_level = 2;
					}
					if ($isKmTravelledChanged) {
						$activity->km_changed_on_level = 2;
					}
					if ($isNotCollectedChanged) {
						$activity->not_collected_amount_changed_on_level = 2;
					}
					if ($isCollectedChanged) {
						$activity->collected_amount_changed_on_level = 2;
					}
				} elseif (Auth::user()->activity_approval_level_id == 3) {
					// L3
					$activityStatusId = 11; //Waiting for Invoice Generation by ASP
					$isApproved = true;
					$approver = '3';
				}
			} else {
				//LESSER THAN OR EQUAL TO 4000
				//L1
				if (Auth::user()->activity_approval_level_id == 1) {
					$isL2ApprovalRequired = $this->isL2ApprovalRequired($activity);
					if ($isL2ApprovalRequired) {
						if ($isActivityBulk) {
							$activityStatusId = 18; //Waiting for L2 Bulk Verification
						} else {
							$activityStatusId = 19; //Waiting for L2 Individual Verification
						}
						$this->sendApprovalNoty($l2Approvers, $activity->case->number, "L2_APPROVAL");
					} else {
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
					}
					$approver = '1';
					if ($isServiceTypeChanged) {
						$activity->service_type_changed_on_level = 1;
					}
					if ($isKmTravelledChanged) {
						$activity->km_changed_on_level = 1;
					}
					if ($isNotCollectedChanged) {
						$activity->not_collected_amount_changed_on_level = 1;
					}
					if ($isCollectedChanged) {
						$activity->collected_amount_changed_on_level = 1;
					}
				} elseif (Auth::user()->activity_approval_level_id == 2) {
					// L2
					$activityStatusId = 11; //Waiting for Invoice Generation by ASP
					$isApproved = true;
					$approver = '2';
					if ($isServiceTypeChanged) {
						$activity->service_type_changed_on_level = 2;
					}
					if ($isKmTravelledChanged) {
						$activity->km_changed_on_level = 2;
					}
					if ($isNotCollectedChanged) {
						$activity->not_collected_amount_changed_on_level = 2;
					}
					if ($isCollectedChanged) {
						$activity->collected_amount_changed_on_level = 2;
					}
				} elseif (Auth::user()->activity_approval_level_id == 3) {
					// L3
					$activityStatusId = 11; //Waiting for Invoice Generation by ASP
					$isApproved = true;
					$approver = '3';
				}
			}

			if (isset($activityStatusId)) {
				$activity->status_id = $activityStatusId;
			}
			$activity->updated_by_id = Auth::user()->id;
			$activity->updated_at = Carbon::now();
			$activity->save();

			//LOG SAVE
			$activityLog = ActivityLog::firstOrNew([
				'activity_id' => $activity->id,
			]);
			//L1
			if ($approver == '1') {
				$activityLog->bo_approved_at = Carbon::now();
				$activityLog->bo_approved_by_id = Auth::id();
			} elseif ($approver == '2') {
				//L2
				$activityLog->l2_approved_at = Carbon::now();
				$activityLog->l2_approved_by_id = Auth::id();
			} elseif ($approver == '3') {
				//L3
				$activityLog->l3_approved_at = Carbon::now();
				$activityLog->l3_approved_by_id = Auth::id();
			}
			$activityLog->updated_by_id = Auth::id();
			$activityLog->updated_at = Carbon::now();
			$activityLog->save();

			if ($isApproved) {
				$this->updateActivityApprovalLog($activity, $request->case_number, 1);
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity approved successfully.',
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

	public function sendApprovalNoty($approvers, $caseNumber, $notyMessageTemplate) {
		if (!empty($approvers)) {
			foreach ($approvers as $approverId) {
				notify2($notyMessageTemplate, $approverId, config('constants.alert_type.blue'), $caseNumber);
			}
		}
	}

	public function isL2ApprovalRequired($activity) {
		$ccKmTravelled = $activity->detail(280) ? floatval($activity->detail(280)->value) : 0;
		$ccServiceTypeVal = $activity->detail(153) ? $activity->detail(153)->value : '';

		$boKmTravelled = $activity->detail(158) ? floatval($activity->detail(158)->value) : 0;
		$boServiceTypeVal = $activity->detail(161) ? $activity->detail(161)->value : '';

		$l2ApprovalRequired = false;
		if (!empty($ccServiceTypeVal) && !empty($boServiceTypeVal)) {
			$ccServiceType = ServiceType::where('name', $ccServiceTypeVal)->first();
			$boServiceType = ServiceType::where('name', $boServiceTypeVal)->first();
			if ($ccServiceType && $boServiceType && ($ccServiceType->id != $boServiceType->id)) {
				$l2ApprovalRequired = true;
			}
		}

		if ($boKmTravelled > $ccKmTravelled) {
			$l2ApprovalRequired = true;
		}

		return $l2ApprovalRequired;
	}

	public function isActivityBulkOnApproval($activity) {
		$isMobile = 0; //WEB
		//MOBILE APP
		if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
			$isMobile = 1;
		}

		$aspRateCard = AspServiceType::where('asp_id', $activity->asp_id)
			->where('service_type_id', $activity->service_type_id)
			->where('vehicle_category_id', $activity->case->vehicleModel->vehiclecategory->id)
			->where('is_mobile', $isMobile)
			->first();
		if ($aspRateCard) {
			$rangeLimit = floatval($aspRateCard->range_limit);
		}

		$ccKmTravelled = $activity->detail(280) ? floatval($activity->detail(280)->value) : 0;
		$ccCollected = $activity->detail(281) ? floatval($activity->detail(281)->value) : 0;
		$ccNotCollected = $activity->detail(282) ? floatval($activity->detail(282)->value) : 0;
		$ccServiceTypeVal = $activity->detail(153) ? $activity->detail(153)->value : '';

		$boKmTravelled = $activity->detail(158) ? floatval($activity->detail(158)->value) : 0;
		$boCollected = $activity->detail(159) ? floatval($activity->detail(159)->value) : 0;
		$boNotCollected = $activity->detail(160) ? floatval($activity->detail(160)->value) : 0;
		$boServiceTypeVal = $activity->detail(161) ? $activity->detail(161)->value : '';

		$isBulk = true;
		//1. checking CC and BO Service
		if (!empty($ccServiceTypeVal) && !empty($boServiceTypeVal)) {
			$ccServiceType = ServiceType::where('name', $ccServiceTypeVal)->first();
			$boServiceType = ServiceType::where('name', $boServiceTypeVal)->first();
			if ($ccServiceType && $boServiceType && ($ccServiceType->id != $boServiceType->id)) {
				$isBulk = false;
			}
		}

		//2. checking CC and BO KMs
		$allowed_variation = 0.5;
		$five_percentage_difference = $ccKmTravelled * $allowed_variation / 100;
		if (($boKmTravelled > $rangeLimit) || floatval($rangeLimit) == 0) {
			if ($boKmTravelled > $ccKmTravelled) {
				$kmDifference = $boKmTravelled - $ccKmTravelled;
				if ($kmDifference > $five_percentage_difference) {
					$isBulk = false;
				}
			}
		}

		//checking BO KMs exceed ASP rate card range limit
		if ($boKmTravelled > $rangeLimit) {
			$isBulk = false;
		}

		//checking CC and BO not collected
		if ($boNotCollected > $ccNotCollected) {
			$isBulk = false;
		}

		//checking CC and BO collected
		if ($boCollected < $ccCollected) {
			$isBulk = false;
		}

		if ($boKmTravelled == 0) {
			$isBulk = false;
		}
		return $isBulk;
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
			if ($activities->isEmpty()) {
				return response()->json([
					'success' => false,
					'error' => 'Activities not found',
				]);
			}

			$l2Approvers = User::where('activity_approval_level_id', 2)->pluck('id');
			$l3Approvers = User::where('activity_approval_level_id', 3)->pluck('id');

			foreach ($activities as $key => $activity) {

				$saveActivityRatecardResponse = $activity->saveActivityRatecard();
				if (!$saveActivityRatecardResponse['success']) {
					return response()->json([
						'success' => false,
						'error' => $saveActivityRatecardResponse['error'] . ' Case Number: ' . $activity->case->number,
					]);
				}

				$isMobile = 0; //WEB
				//MOBILE APP
				if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
					$isMobile = 1;
				}

				$aspServiceType = AspServiceType::where('asp_id', $activity->asp_id)
					->where('service_type_id', $activity->service_type_id)
					->where('vehicle_category_id', $activity->case->vehicleModel->vehiclecategory->id)
					->where('is_mobile', $isMobile)
					->first();

				if ($aspServiceType) {
					// $bo_km_charge = $activity->detail(172) ? $activity->detail(172)->value : 0;
					$bo_km_travelled = $activity->detail(158) ? $activity->detail(158)->value : 0;
					$bo_km_collected = $activity->detail(159) ? $activity->detail(159)->value : 0;
					$bo_km_not_collected = $activity->detail(160) ? $activity->detail(160)->value : 0;

					if (floatval($bo_km_travelled) <= 0) {
						return response()->json([
							'success' => false,
							'error' => 'KM travelled should be greater than zero for the case - ' . $activity->case->number,
						]);
					}

					$response = getActivityKMPrices($activity->serviceType, $activity->asp, $activity->data_src_id, $activity->case);
					if (!$response['success']) {
						return response()->json([
							'success' => false,
							'error' => $response['error'],
						]);
					}
					$price = $response['asp_service_price'];

					if ($activity->financeStatus->po_eligibility_type_id == 341) {
						// Empty Return Payout
						$below_range_price = $bo_km_travelled == 0 ? 0 : $price->empty_return_range_price;
					} else {
						$below_range_price = $bo_km_travelled == 0 ? 0 : $price->below_range_price;
					}

					$above_range_price = ($bo_km_travelled > $price->range_limit) ? ($bo_km_travelled - $price->range_limit) * $price->above_range_price : 0;
					$km_charge = $below_range_price + $above_range_price;

					$boDeduction = 0;
					//DISABLED AS THERE IS NO ADJUSTMENT TYPE IN FUTURE
					// if ($aspServiceType->adjustment_type == 2) {
					// 	$boDeduction = floatval($aspServiceType->adjustment);
					// } else if ($aspServiceType->adjustment_type == 1) {
					// 	$boDeduction = floatval($km_charge) * floatval($aspServiceType->adjustment / 100);
					// }

					$invoiceAmount = (floatval($km_charge) + floatval($bo_km_not_collected)) - floatval($boDeduction) - floatval($bo_km_collected);

					if (floatval($invoiceAmount) <= 0) {
						return response()->json([
							'success' => false,
							'error' => 'Payout amount should be greater than zero for the case - ' . $activity->case->number,
						]);
					}

					$bo_km_charge = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 172,
					]);
					$bo_km_charge->value = $km_charge;
					$bo_km_charge->save();

					$bo_deduction = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 173,
					]);
					$bo_deduction->value = $boDeduction;
					$bo_deduction->save();

					$bo_net_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 176,
					]);
					$bo_net_amount->value = $invoiceAmount;
					$bo_net_amount->save();

					$bo_invoice_amount = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 182,
					]);
					$bo_invoice_amount->value = $invoiceAmount;
					$bo_invoice_amount->save();

					$isApproved = false;
					$approver = '';
					//GREATER THAN 10000
					if (floatval($invoiceAmount) > 10000) {
						//L1
						if (Auth::user()->activity_approval_level_id == 1) {
							$activityStatusId = 18; //Waiting for L2 Bulk Verification
							$approver = '1';
							$this->sendApprovalNoty($l2Approvers, $activity->case->number, "L2_APPROVAL");
						} elseif (Auth::user()->activity_approval_level_id == 2) {
							// L2
							$activityStatusId = 20; //Waiting for L3 Bulk Verification
							$approver = '2';
							$this->sendApprovalNoty($l3Approvers, $activity->case->number, "L3_APPROVAL");
						} elseif (Auth::user()->activity_approval_level_id == 3) {
							// L3
							$activityStatusId = 11; //Waiting for Invoice Generation by ASP
							$isApproved = true;
							$approver = '3';
						}
					} elseif (floatval($invoiceAmount) > 4000 && floatval($invoiceAmount) <= 10000) {
						//GREATER THAN 4000 AND LESSER THAN OR EQUAL TO 10000
						//L1
						if (Auth::user()->activity_approval_level_id == 1) {
							$activityStatusId = 18; //Waiting for L2 Bulk Verification
							$approver = '1';
							$this->sendApprovalNoty($l2Approvers, $activity->case->number, "L2_APPROVAL");
						} elseif (Auth::user()->activity_approval_level_id == 2) {
							// L2
							$activityStatusId = 11; //Waiting for Invoice Generation by ASP
							$isApproved = true;
							$approver = '2';
						} elseif (Auth::user()->activity_approval_level_id == 3) {
							// L3
							$activityStatusId = 11; //Waiting for Invoice Generation by ASP
							$isApproved = true;
							$approver = '3';
						}
					} else {
						//LESSER THAN OR EQUAL TO 4000
						//L1
						if (Auth::user()->activity_approval_level_id == 1) {
							$isL2ApprovalRequired = $this->isL2ApprovalRequired($activity);
							if ($isL2ApprovalRequired) {
								$activityStatusId = 18; //Waiting for L2 Bulk Verification
								$this->sendApprovalNoty($l2Approvers, $activity->case->number, "L2_APPROVAL");
							} else {
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
							}
							$approver = '1';
						} elseif (Auth::user()->activity_approval_level_id == 2) {
							// L2
							$activityStatusId = 11; //Waiting for Invoice Generation by ASP
							$isApproved = true;
							$approver = '2';
						} elseif (Auth::user()->activity_approval_level_id == 3) {
							// L3
							$activityStatusId = 11; //Waiting for Invoice Generation by ASP
							$isApproved = true;
							$approver = '3';
						}
					}

					if (isset($activityStatusId)) {
						$activity->status_id = $activityStatusId;
					}
					$activity->updated_by_id = Auth::user()->id;
					$activity->updated_at = Carbon::now();
					$activity->save();

					//LOG SAVE
					$activityLog = ActivityLog::firstOrNew([
						'activity_id' => $activity->id,
					]);
					//L1
					if ($approver == '1') {
						$activityLog->bo_approved_at = Carbon::now();
						$activityLog->bo_approved_by_id = Auth::id();
					} elseif ($approver == '2') {
						//L2
						$activityLog->l2_approved_at = Carbon::now();
						$activityLog->l2_approved_by_id = Auth::id();
					} elseif ($approver == '3') {
						//L3
						$activityLog->l3_approved_at = Carbon::now();
						$activityLog->l3_approved_by_id = Auth::id();
					}
					$activityLog->updated_by_id = Auth::id();
					$activityLog->updated_at = Carbon::now();
					$activityLog->save();

					if ($isApproved) {
						$this->updateActivityApprovalLog($activity, $activity->case->number, 2);
					}
				} else {
					return response()->json([
						'success' => false,
						'error' => "Service (" . $activity->serviceType->name . ") not enabled for the ASP (" . $activity->asp->asp_code . ")  Case Number: " . $activity->case->number,
					]);
				}
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activities approved successfully.',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function updateActivityApprovalLog($activity, $caseNumber, $type) {

		//INDIVIDUAL
		if ($type == 1) {
			$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_APPROVED_DEFERRED');
		} else {
			//BULK
			$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_APPROVED_BULK');
		}
		$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_APPROVED');
		logActivity3(config('constants.entity_types.ticket'), $activity->id, [
			'Status' => $log_status,
			'Waiting for' => $log_waiting,
		], 361);

		if ($activity->asp && !empty($activity->asp->contact_number1)) {
			sendSMS2("Tkt waiting for Invoice", $activity->asp->contact_number1, [$caseNumber], NULL);
		}

		//sending notification to all BO users
		if ($activity->asp && !empty($activity->asp->user_id)) {
			notify2('BO_APPROVED', $activity->asp->user_id, config('constants.alert_type.blue'), [$caseNumber]);
		}
	}

	public function saveActivityDiffer(Request $request) {
		DB::beginTransaction();
		try {

			if (Auth::check()) {
				if (empty(Auth::user()->activity_approval_level_id)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'User is not valid for Verification',
						],
					]);
				}
			} else {
				return response()->json([
					'success' => false,
					'errors' => [
						'User is not valid for Verification',
					],
				]);
			}

			$activity = Activity::findOrFail($request->activity_id);

			$eligleForAspReEntry = false;
			$deferReason = $activity->defer_reason;
			//L1
			if (Auth::user()->activity_approval_level_id == 1) {
				$activityStatusId = 7; //BO Rejected - Waiting for ASP Data Re-Entry
				$eligleForAspReEntry = true;
				if (!empty($deferReason)) {
					$deferReason .= nl2br("\n L1 Approver : " . $request->defer_reason);
				} else {
					$deferReason = "L1 Approver : " . $request->defer_reason;
				}
				$activity->service_type_changed_on_level = NULL;
				$activity->km_changed_on_level = NULL;
				$activity->not_collected_amount_changed_on_level = NULL;
				$activity->collected_amount_changed_on_level = NULL;
			} elseif (Auth::user()->activity_approval_level_id == 2) {
				// L2
				$activityStatusId = 22; //BO Rejected - Waiting for L1 Individual Verification
				if (!empty($deferReason)) {
					$deferReason .= nl2br("\n L2 Approver : " . $request->defer_reason);
				} else {
					$deferReason = "L2 Approver : " . $request->defer_reason;
				}
			} elseif (Auth::user()->activity_approval_level_id == 3) {
				// L3
				$activityStatusId = 22; //BO Rejected - Waiting for L1 Individual Verification
				if (!empty($deferReason)) {
					$deferReason .= nl2br("\n L3 Approver : " . $request->defer_reason);
				} else {
					$deferReason = "L3 Approver : " . $request->defer_reason;
				}
			}

			$activity->defer_reason = $deferReason;
			$activity->bo_comments = isset($request->bo_comments) ? $request->bo_comments : NULL;
			$activity->deduction_reason = isset($request->deduction_reason) ? $request->deduction_reason : NULL;
			if (isset($activityStatusId)) {
				$activity->status_id = $activityStatusId;
			}
			$activity->updated_at = Carbon::now();
			$activity->updated_by_id = Auth::user()->id;
			$activity->save();

			$activityLog = ActivityLog::firstOrNew([
				'activity_id' => $activity->id,
			]);
			//L1
			if (Auth::user()->activity_approval_level_id == 1) {
				$activityLog->bo_deffered_at = date('Y-m-d H:i:s');
				$activityLog->bo_deffered_by_id = Auth::id();
			} elseif (Auth::user()->activity_approval_level_id == 2) {
				// L2
				$activityLog->l2_deffered_at = date('Y-m-d H:i:s');
				$activityLog->l2_deffered_by_id = Auth::id();
			} elseif (Auth::user()->activity_approval_level_id == 3) {
				// L3
				$activityLog->l3_deffered_at = date('Y-m-d H:i:s');
				$activityLog->l3_deffered_by_id = Auth::id();
			}
			$activityLog->updated_by_id = Auth::id();
			$activityLog->updated_at = Carbon::now();
			$activityLog->save();

			//Saving log record
			if ($eligleForAspReEntry) {
				$log_status = config('rsa.LOG_STATUES_TEMPLATES.BO_DEFERED_DONE');
				$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.BO_DEFERRED');
				logActivity3(config('constants.entity_types.ticket'), $activity->id, [
					'Status' => $log_status,
					'Waiting for' => $log_waiting,
				], 361);

				//SMS record
				$mobile_number = $activity->asp->contact_number1;
				$sms_message = 'Deferred Tkt re-entry';
				$array = [$request->case_number];
				sendSMS2($sms_message, $mobile_number, $array, NULL);

				//sending notification to all BO users
				$asp_user = $activity->asp->user_id;
				$noty_message_template = 'BO_DEFERRED';
				$number = [$request->case_number];
				notify2($noty_message_template, $asp_user, config('constants.alert_type.red'), $number);
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity deferred successfully.',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
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
			'activities.activity_status_id',
			'cases.created_at as case_created_at',
			// 'cases.date as case_date',
			DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y") as case_date'),
			'cases.number as case_number',
		])
			->join('cases', 'cases.id', 'activities.case_id')
			->where(function ($q) use ($number) {
				$q->where('cases.number', $number)
					->orWhere('cases.vehicle_registration_number', $number)
					->orWhere('activities.crm_activity_id', $number);
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
						$checkTicketDateError = "Please contact administrator.";
						if ($check_ticket_date->activityStatus) {
							$checkTicketDateError = "Please contact administrator. Activity status : " . $check_ticket_date->activityStatus->name;
						}
						return response()->json([
							'success' => false,
							'errors' => [
								$checkTicketDateError,
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
						$activityOnHoldError = "Ticket On Hold";
						if ($activity_on_hold->activityStatus) {
							$activityOnHoldError = "Ticket On Hold. Activity status : " . $activity_on_hold->activityStatus->name;
						}
						return response()->json([
							'success' => false,
							'errors' => [
								$activityOnHoldError,
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
						$activityNotEligibleForPaymentError = 'Ticket not found';
						if ($activity_not_eligible_for_payment->activityStatus) {
							$activityNotEligibleForPaymentError = "Ticket not found. Activity status : " . $activity_not_eligible_for_payment->activityStatus->name;
						}
						return response()->json([
							'success' => false,
							'errors' => [
								$activityNotEligibleForPaymentError,
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
						->whereIn('activities.status_id', [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 18, 19, 20, 21, 22])
						->where('activities.asp_id', Auth::user()->asp->id)
						->first();
					if ($activity_already_completed) {
						$activityAlreadyCompletedError = "Ticket already submitted. Case : " . $activity_already_completed->case_number . "(" . $activity_already_completed->case_date . ")";
						if ($activity_already_completed->activityStatus) {
							$activityAlreadyCompletedError = "Ticket already submitted. Case : " . $activity_already_completed->case_number . "(" . $activity_already_completed->case_date . "), Activity status : " . $activity_already_completed->activityStatus->name;
						}
						return response()->json([
							'success' => false,
							'errors' => [
								$activityAlreadyCompletedError,
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
						$caseWithCancelledStatusError = "Ticket is cancelled";
						if ($case_with_cancelled_status->activityStatus) {
							$caseWithCancelledStatusError = "Ticket is cancelled. Activity status : " . $case_with_cancelled_status->activityStatus->name;
						}
						return response()->json([
							'success' => false,
							'errors' => [
								$caseWithCancelledStatusError,
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
					if ($case_with_closed_status) {
						$caseWithClosedStatusError = "Ticket is closed";
						if ($case_with_closed_status->activityStatus) {
							$caseWithClosedStatusError = "Ticket is closed. Activity status : " . $case_with_closed_status->activityStatus->name;
						}
						return response()->json([
							'success' => false,
							'errors' => [
								$caseWithClosedStatusError,
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
		if (!$this->data['success']) {
			return response()->json([
				'success' => false,
				'error' => $this->data['error'],
			]);
		}
		$this->data['case_details'] = $this->data['activity']->case;
		if (date('Y-m-d') > "2022-01-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st February onwards)';
		} elseif (date('Y-m-d') > "2022-02-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st April onwards)';
		} elseif (date('Y-m-d') >= "2022-04-01") {
			$towingAttachmentsMandatoryLabel = '';
		} else {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st January onwards)';
		}
		$this->data['towingAttachmentsMandatoryLabel'] = $towingAttachmentsMandatoryLabel;
		return response()->json($this->data);
	}

	public function activityNewGetServiceTypeDetail($id) {
		try {
			$serviceType = ServiceType::select([
				'id',
				'service_group_id',
			])
				->where('id', $id)
				->first();
			if (!$serviceType) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Ticket not found',
					],
				]);
			}
			return response()->json([
				'success' => true,
				'serviceType' => $serviceType,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function updateActivity(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$activity = Activity::findOrFail($request->activity_id);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
					],
				]);
			}

			if (!$activity->case->vehicleModel) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Vehicle model is required',
					],
				]);
			}

			if (!$activity->case->vehicleModel->vehiclecategory) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Vehicle category not mapped for the vehicle model',
					],
				]);
			}

			if (floatval($request->km_travelled) <= 0) {
				return response()->json([
					'success' => false,
					'errors' => [
						'KM travelled should be greater than zero',
					],
				]);
			}

			$range_limit = 0;
			$destination = aspTicketAttachmentPath($activity->id, $activity->asp_id, $activity->service_type_id);
			Storage::makeDirectory($destination, 0777);

			//MAP ATTACHMENTS REMOVAL
			if (isset($request->update_attach_km_map_id) && !empty($request->update_attach_km_map_id)) {
				$update_attach_km_map_ids = json_decode($request->update_attach_km_map_id, true);
				$removeMapAttachments = Attachment::whereIn('id', $update_attach_km_map_ids)
					->get();
				if ($removeMapAttachments->isNotEmpty()) {
					foreach ($removeMapAttachments as $removeMapAttachmentKey => $removeMapAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $removeMapAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $removeMapAttachment->attachment_file_name));
						}
						$removeMapAttachment->delete();
					}
				}
			}

			//OTHER ATTACHMENTS REMOVAL
			if (isset($request->update_attach_other_id) && !empty($request->update_attach_other_id)) {
				$update_attach_other_ids = json_decode($request->update_attach_other_id, true);
				$removeOtherAttachments = Attachment::whereIn('id', $update_attach_other_ids)
					->get();
				if ($removeOtherAttachments->isNotEmpty()) {
					foreach ($removeOtherAttachments as $removeOtherAttachmentKey => $removeOtherAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $removeOtherAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $removeOtherAttachment->attachment_file_name));
						}
						$removeOtherAttachment->delete();
					}
				}
			}

			//VEHICLE PICKUP ATTACHMENTS REMOVAL
			if (isset($request->vehiclePickupAttachRemovelIds) && !empty($request->vehiclePickupAttachRemovelIds)) {
				$vehiclePickupAttachRemovelIds = json_decode($request->vehiclePickupAttachRemovelIds, true);
				$removeVehiclePickupAttachments = Attachment::whereIn('id', $vehiclePickupAttachRemovelIds)
					->get();
				if ($removeVehiclePickupAttachments->isNotEmpty()) {
					foreach ($removeVehiclePickupAttachments as $removeVehiclePickupAttachmentKey => $removeVehiclePickupAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $removeVehiclePickupAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $removeVehiclePickupAttachment->attachment_file_name));
						}
						$removeVehiclePickupAttachment->delete();
					}
				}
			}

			//VEHICLE DROP ATTACHMENTS REMOVAL
			if (isset($request->vehicleDropAttachRemovelIds) && !empty($request->vehicleDropAttachRemovelIds)) {
				$vehicleDropAttachRemovelIds = json_decode($request->vehicleDropAttachRemovelIds, true);
				$removeVehicleDropAttachments = Attachment::whereIn('id', $vehicleDropAttachRemovelIds)
					->get();
				if ($removeVehicleDropAttachments->isNotEmpty()) {
					foreach ($removeVehicleDropAttachments as $removeVehicleDropAttachmentKey => $removeVehicleDropAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $removeVehicleDropAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $removeVehicleDropAttachment->attachment_file_name));
						}
						$removeVehicleDropAttachment->delete();
					}
				}
			}

			//INVENTORY JOB SHEET ATTACHMENTS REMOVAL
			if (isset($request->inventoryJobSheetAttachRemovelIds) && !empty($request->inventoryJobSheetAttachRemovelIds)) {
				$inventoryJobSheetAttachRemovelIds = json_decode($request->inventoryJobSheetAttachRemovelIds, true);
				$removeInventoryJobSheetAttachments = Attachment::whereIn('id', $inventoryJobSheetAttachRemovelIds)
					->get();
				if ($removeInventoryJobSheetAttachments->isNotEmpty()) {
					foreach ($removeInventoryJobSheetAttachments as $removeInventoryJobSheetAttachmentKey => $removeInventoryJobSheetAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $removeInventoryJobSheetAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $removeInventoryJobSheetAttachment->attachment_file_name));
						}
						$removeInventoryJobSheetAttachment->delete();
					}
				}
			}

			$cc_service_type_exist = ActivityDetail::where('activity_id', $activity->id)
				->where('key_id', 153)
				->first();
			$cc_service_type = ServiceType::where('name', $cc_service_type_exist->value)->first();

			$isMobile = 0; //WEB
			//MOBILE APP
			if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
				$isMobile = 1;
			}

			$aspServiceType = AspServiceType::where('asp_id', $activity->asp_id)
				->where('service_type_id', $cc_service_type->id)
				->where('vehicle_category_id', $activity->case->vehicleModel->vehiclecategory->id)
				->where('is_mobile', $isMobile)
				->first();

			if (!$aspServiceType) {
				return response()->json([
					'success' => false,
					'errors' => [
						"Service (" . $cc_service_type->name . ") not enabled for ASP (" . $activity->asp->asp_code . ")",
					],
				]);
			}

			$range_limit = $aspServiceType->range_limit;

			if (!empty($request->comments)) {
				$activity->asp_resolve_comments = $request->comments;
			}

			if (!empty($request->other_attachment)) {
				//REMOVE EXISTING ATTACHMENT
				$getOtherAttachments = Attachment::where('entity_id', $activity->id)
					->where('entity_type', 17)
					->get();
				if ($getOtherAttachments->isNotEmpty()) {
					foreach ($getOtherAttachments as $getOtherAttachmentKey => $getOtherAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $getOtherAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $getOtherAttachment->attachment_file_name));
						}
						$getOtherAttachment->delete();
					}
				}
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
			}

			if (!empty($request->map_attachment)) {
				//REMOVE EXISTING ATTACHMENT
				$getMapAttachments = Attachment::where('entity_id', $activity->id)
					->where('entity_type', 16)
					->get();
				if ($getMapAttachments->isNotEmpty()) {
					foreach ($getMapAttachments as $getMapAttachmentKey => $getMapAttachment) {
						if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $getMapAttachment->attachment_file_name)) {
							unlink(storage_path('app/' . $destination . '/' . $getMapAttachment->attachment_file_name));
						}
						$getMapAttachment->delete();
					}
				}
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
			}

			//VEHICLE PICKUP ATTACHMENT
			if (isset($request->vehicle_pickup_attachment) && $request->hasFile("vehicle_pickup_attachment")) {
				//REMOVE EXISTING ATTACHMENT
				$getVehiclePickupAttach = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.VEHICLE_PICKUP_ATTACHMENT'))
					->first();
				if ($getVehiclePickupAttach) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $getVehiclePickupAttach->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $getVehiclePickupAttach->attachment_file_name));
					}
					$getVehiclePickupAttach->delete();
				}

				$filename = "vehicle_pickup_attachment";
				$extension = $request->file("vehicle_pickup_attachment")->getClientOriginalExtension();
				$status = $request->file("vehicle_pickup_attachment")->storeAs($destination, $filename . '.' . $extension);
				$attachmentFileName = $filename . '.' . $extension;
				$attachment = $Attachment = Attachment::create([
					'entity_type' => config('constants.entity_types.VEHICLE_PICKUP_ATTACHMENT'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $attachmentFileName,
				]);
			}

			//VEHICLE DROP ATTACHMENT
			if (isset($request->vehicle_drop_attachment) && $request->hasFile("vehicle_drop_attachment")) {
				//REMOVE EXISTING ATTACHMENT
				$getVehicleDropAttach = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.VEHICLE_DROP_ATTACHMENT'))
					->first();
				if ($getVehicleDropAttach) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $getVehicleDropAttach->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $getVehicleDropAttach->attachment_file_name));
					}
					$getVehicleDropAttach->delete();
				}

				$filename = "vehicle_drop_attachment";
				$extension = $request->file("vehicle_drop_attachment")->getClientOriginalExtension();
				$status = $request->file("vehicle_drop_attachment")->storeAs($destination, $filename . '.' . $extension);
				$attachmentFileName = $filename . '.' . $extension;
				$attachment = $Attachment = Attachment::create([
					'entity_type' => config('constants.entity_types.VEHICLE_DROP_ATTACHMENT'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $attachmentFileName,
				]);
			}

			//INVENTORY JOB SHEET ATTACHMENT
			if (isset($request->inventory_job_sheet_attachment) && $request->hasFile("inventory_job_sheet_attachment")) {
				//REMOVE EXISTING ATTACHMENT
				$getInventoryJobSheetAttach = Attachment::where('entity_id', $activity->id)
					->where('entity_type', config('constants.entity_types.INVENTORY_JOB_SHEET_ATTACHMENT'))
					->first();
				if ($getInventoryJobSheetAttach) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $getInventoryJobSheetAttach->attachment_file_name)) {
						unlink(storage_path('app/' . $destination . '/' . $getInventoryJobSheetAttach->attachment_file_name));
					}
					$getInventoryJobSheetAttach->delete();
				}

				$filename = "inventory_job_sheet_attachment";
				$extension = $request->file("inventory_job_sheet_attachment")->getClientOriginalExtension();
				$status = $request->file("inventory_job_sheet_attachment")->storeAs($destination, $filename . '.' . $extension);
				$attachmentFileName = $filename . '.' . $extension;
				$attachment = $Attachment = Attachment::create([
					'entity_type' => config('constants.entity_types.INVENTORY_JOB_SHEET_ATTACHMENT'),
					'entity_id' => $activity->id,
					'attachment_file_name' => $attachmentFileName,
				]);
			}

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

			$saveActivityRatecardResponse = $activity->saveActivityRatecard();
			if (!$saveActivityRatecardResponse['success']) {
				return [
					'success' => false,
					'errors' => [
						$saveActivityRatecardResponse['error'],
					],
				];
			}

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

			$response = getActivityKMPrices($activity->serviceType, $activity->asp, $activity->data_src_id, $activity->case);
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
			$activity_log->asp_data_filled_by_id = Auth::id();
			$activity_log->updated_by_id = Auth::id();
			$activity_log->save();

			//sending confirmation SMS to ASP
			$mobile_number = $activity->asp->contact_number1;
			$sms_message = 'Tkt uptd successfully';
			$array = [$activity->case->number];
			// sendSMS2($sms_message, $mobile_number, $array, NULL);

			//sending notification to all ASP STATE MAPPED BO users
			//$bo_users = User::where('users.role_id', 6)->pluck('users.id'); //6 - Bo User role ID
			$state_id = Auth::user()->asp->state_id;
			// $bo_users = StateUser::where('state_id', $state_id)->pluck('user_id');
			$bo_users = DB::table('state_user')
				->join('users', 'users.id', 'state_user.user_id')
				->where('state_user.state_id', $state_id)
				->where('users.role_id', 6) //BO
				->where('users.activity_approval_level_id', 1) //L1
				->pluck('state_user.user_id');

			if ($activity->status_id == 5) {
				$noty_message_template = 'ASP_DATA_ENTRY_DONE_BULK';
			} else {
				$noty_message_template = 'ASP_DATA_ENTRY_DONE_DEFFERED';
			}
			$ticket_number = [$activity->case->number];
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
		if (!$this->data['success']) {
			return response()->json([
				'success' => false,
				'error' => $this->data['error'],
			]);
		}
		$this->data['case'] = $this->data['activity']->case;
		if (date('Y-m-d') > "2022-01-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st February onwards)';
		} elseif (date('Y-m-d') > "2022-02-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st April onwards)';
		} elseif (date('Y-m-d') >= "2022-04-01") {
			$towingAttachmentsMandatoryLabel = '';
		} else {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st January onwards)';
		}
		$this->data['towingAttachmentsMandatoryLabel'] = $towingAttachmentsMandatoryLabel;
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
			->orderBy('cases.date', 'ASC')
			->groupBy('activities.id')
			->where('users.id', Auth::id())
			->whereIn('activities.status_id', [11, 1]) //Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
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
		DB::beginTransaction();
		try {
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
			DB::commit();
			return response()->json($this->data);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
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
			$activities = Activity::select([
				'invoice_id',
				'crm_activity_id',
				'number',
				'asp_id',
				'case_id',
				'status_id',
			])
				->whereIn('crm_activity_id', $request->crm_activity_ids)
				->get();

			//CUSTOM VALIDATION SAID BY BUSINESS TEAM
			$aug21ToNov21caseExist = false;
			$afterDec21caseExist = false;

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

					//EXCEPT(Case Closed - Waiting for ASP to Generate Invoice AND Waiting for Invoice Generation by ASP)
					if ($activity->status_id != 1 && $activity->status_id != 11) {
						return response()->json([
							'success' => false,
							'error' => 'ASP not accepted / case not closed for activity ID ' . $activity->crm_activity_id,
						]);
					}

					//CUSTOM VALIDATION SAID BY BUSINESS TEAM
					if (!$aug21ToNov21caseExist) {
						if ($activity->case && ((date('Y-m-d', strtotime($activity->case->date)) >= "2021-08-01") && (date('Y-m-d', strtotime($activity->case->date)) <= "2021-11-31"))) {
							$aug21ToNov21caseExist = true;
						}
					}

					if (!$afterDec21caseExist) {
						if ($activity->case && (date('Y-m-d', strtotime($activity->case->date)) >= "2021-12-01")) {
							$afterDec21caseExist = true;
						}
					}
				}
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Activity not found',
				]);
			}

			if ($aug21ToNov21caseExist && $afterDec21caseExist) {
				return response()->json([
					'success' => false,
					'error' => "August'21 to November'21 cases should be separately invoiced. Cases done from 1st December 2021 should be invoiced separately for INP Payment",
				]);
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

				if (Str::length($request->invoice_no) > 20) {
					return response()->json([
						'success' => false,
						'error' => 'The invoice number may not be greater than 20 characters',
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
			return response()->json([
				'success' => false,
				'error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
			]);
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
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function towingImagesRequiredUpdated(Request $r) {
		// dd($r->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($r->all(), [
				'activity_id' => [
					'required:true',
					'integer',
					'exists:activities,id',
				],
				'isTowingAttachmentsMandatory' => [
					'required:true',
					'integer',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$activity = Activity::find($r->activity_id);
			$activity->is_towing_attachments_mandatory = $r->isTowingAttachmentsMandatory;
			$activity->save();
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity updated successfully',
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
			->join('users as regionalManager', 'regionalManager.id', '=', 'asps.regional_manager_id')
			->join('clients', 'cases.client_id', '=', 'clients.id')
			->join('activity_finance_statuses', 'activity_finance_statuses.id', '=', 'activities.finance_status_id')
			->join('service_types', 'service_types.id', '=', 'activities.service_type_id')
			->join('configs as data_source', 'data_source.id', '=', 'activities.data_src_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', '=', 'activities.status_id')
			->leftjoin('asp_activity_rejected_reasons', 'asp_activity_rejected_reasons.id', '=', 'activities.asp_activity_rejected_reason_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', '=', 'activities.activity_status_id')
			->leftjoin('case_statuses', 'case_statuses.id', '=', 'cases.status_id')
			->leftjoin('locations', 'locations.id', '=', 'asps.location_id')
			->leftjoin('districts', 'districts.id', '=', 'asps.district_id')
			->leftjoin('states', 'states.id', '=', 'asps.state_id')
			->leftjoin('vehicle_models', 'vehicle_models.id', '=', 'cases.vehicle_model_id')
			->leftjoin('vehicle_makes', 'vehicle_makes.id', '=', 'vehicle_models.vehicle_make_id')
			->leftjoin('configs as bd_location_type', 'bd_location_type.id', '=', 'cases.bd_location_type_id')
			->leftjoin('configs as bd_location_category', 'bd_location_category.id', '=', 'cases.bd_location_category_id')
			->leftjoin('activity_ratecards', 'activity_ratecards.activity_id', 'activities.id')
			->whereIn('activities.status_id', $status_ids);

		if ($request->filter_by == 'general') {
			$activities->leftjoin('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
				->leftjoin('invoice_statuses', 'invoice_statuses.id', '=', 'Invoices.status_id')
				->leftjoin('invoice_vouchers', 'invoice_vouchers.invoice_id', 'Invoices.id')
				->where(function ($q) use ($range1, $range2) {
					$q->whereDate('cases.date', '>=', $range1)
						->whereDate('cases.date', '<=', $range2);
				});
		} elseif ($request->filter_by == 'activity') {
			$activities->leftjoin('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
				->leftjoin('invoice_statuses', 'invoice_statuses.id', '=', 'Invoices.status_id')
				->leftjoin('invoice_vouchers', 'invoice_vouchers.invoice_id', 'Invoices.id')
				->join('activity_logs', 'activities.id', '=', 'activity_logs.activity_id')
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
							$query->whereRaw('DATE(activity_logs.l2_deffered_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.l2_approved_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.l3_deffered_at) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_logs.l3_approved_at) between "' . $range1 . '" and "' . $range2 . '"');
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
		} elseif ($request->filter_by == 'invoiceDate') {
			$activities->join('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
				->leftjoin('invoice_statuses', 'invoice_statuses.id', '=', 'Invoices.status_id')
				->leftjoin('invoice_vouchers', 'invoice_vouchers.invoice_id', 'Invoices.id')
				->where(function ($q) use ($range1, $range2) {
					$q->whereRaw('DATE(Invoices.created_at) between "' . $range1 . '" and "' . $range2 . '"');
				});
		} elseif ($request->filter_by == 'transactionDate') {
			$activities->join('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
				->leftjoin('invoice_statuses', 'invoice_statuses.id', '=', 'Invoices.status_id')
				->join('invoice_vouchers', 'invoice_vouchers.invoice_id', 'Invoices.id')
				->where(function ($q) use ($range1, $range2) {
					$q->whereRaw('DATE(invoice_vouchers.date) between "' . $range1 . '" and "' . $range2 . '"');
				});
		}

		$activities->select([
			'activities.id as id',
			'activities.crm_activity_id',
			'activities.number',
			DB::raw('DATE_FORMAT(activities.created_at, "%d-%m-%Y %H:%i:%s") as activity_created_at'),
			'activities.created_at',
			'activities.asp_activity_rejected_reason_id',
			'activities.asp_po_accepted',
			'activities.asp_po_rejected_reason',
			'activities.status_id',
			'activities.activity_status_id',
			'activities.description',
			'activities.remarks',
			'activities.manual_uploading_remarks',
			'activities.general_remarks',
			'activities.bo_comments',
			'activities.deduction_reason',
			'activities.defer_reason',
			'activities.asp_resolve_comments',
			DB::raw('IF(activities.is_exceptional_check = 1, "Yes", "No") as is_exceptional_check'),
			'activities.exceptional_reason',
			'activity_finance_statuses.name as activity_finance_status',
			'service_types.name as service_type',
			'activity_portal_statuses.name as activity_portal_status',
			'activity_statuses.name as activity_status',
			'asp_activity_rejected_reasons.name as asp_activity_rejected_reason',
			'asps.name as asp_name',
			'asps.axpta_code as asp_axpta_code',
			'asps.asp_code as asp_code',
			'asps.contact_number1 as asp_contact_number1',
			'asps.email as asp_email',
			DB::raw('IF(asps.has_gst = 1, "Yes", "No") as asp_has_gst'),
			DB::raw('IF(asps.is_self = 1, "Self", "Non Self") as asp_is_self'),
			DB::raw('IF(asps.is_auto_invoice = 1, "Yes", "No") as asp_is_auto_invoice'),
			'asps.workshop_name as asp_workshop_name',
			'asps.workshop_type as asp_workshop_type',
			'regionalManager.name as asp_rm_name',
			'locations.name as asp_location_name',
			'districts.name as asp_district_name',
			'states.name as asp_state_name',
			'vehicle_models.name as vehicle_model',
			'vehicle_makes.name as vehicle_make',
			'case_statuses.name as case_status',
			'clients.name as client_name',
			'Invoices.created_at as invoice_created_at',
			'Invoices.invoice_no',
			'Invoices.invoice_amount',
			'invoice_statuses.name as invoice_status',
			DB::raw('COALESCE(invoice_vouchers.date, "--") as transactionDate'),
			DB::raw('COALESCE(invoice_vouchers.number, "--") as voucher'),
			DB::raw('COALESCE(invoice_vouchers.tds, "--") as tdsAmount'),
			DB::raw('COALESCE(invoice_vouchers.paid_amount, "--") as paidAmount'),
			'cases.number as case_number',
			DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y %H:%i:%s") as case_date'),
			DB::raw('DATE_FORMAT(cases.submission_closing_date, "%d-%m-%Y %H:%i:%s") as case_submission_closing_date'),
			'cases.created_at as case_created_at',
			'cases.vehicle_registration_number as case_vehicle_registration_number',
			'cases.membership_type as case_membership_type',
			'cases.customer_name as case_customer_name',
			'cases.customer_contact_number as case_customer_contact_number',
			'cases.submission_closing_date_remarks as case_submission_closing_date_remarks',
			'cases.bd_lat',
			'cases.bd_long',
			'cases.bd_location',
			'cases.bd_city',
			'cases.bd_state',
			DB::raw('COALESCE(bd_location_type.name, "--") as location_type'),
			DB::raw('COALESCE(data_source.name, "--") as data_source'),
			DB::raw('COALESCE(bd_location_category.name, "--") as location_category'),
			DB::raw('DATE_FORMAT(activities.updated_at, "%d-%m-%Y %H:%i:%s") as latest_updation_date'),
			DB::raw('COALESCE(activity_ratecards.range_limit, "--") as range_limit'),
			DB::raw('COALESCE(activity_ratecards.below_range_price, "--") as below_range_price'),
			DB::raw('COALESCE(activity_ratecards.above_range_price, "--") as above_range_price'),
			DB::raw('COALESCE(activity_ratecards.waiting_charge_per_hour, "--") as waiting_charge_per_hour'),
			DB::raw('COALESCE(activity_ratecards.empty_return_range_price, "--") as empty_return_range_price'),
			// DB::raw('COALESCE(IF(activity_ratecards.adjustment_type = 1, "Percentage", "Amount"), "--") as adjustment_type'),
			'activity_ratecards.adjustment_type',
			DB::raw('COALESCE(activity_ratecards.adjustment, "--") as adjustment'),
		]);

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
			if (Entrust::can('view-own-activities')) {
				$activities = $activities->whereNotIn('activities.status_id', [2, 4, 15, 16, 17]);
			}
		}
		$activitesTotalCount = $activities;
		$total_count = $activitesTotalCount->groupBy('activities.id')->get()->count();
		if ($total_count == 0) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
				'errors' => [
					'No activities found for given period & statuses',
				],
			]);
		}

		$selected_statuses = $status_ids;
		$summary_period = ['Period', date('d/M/Y', strtotime($range1)) . ' to ' . date('d/M/Y', strtotime($range2))];
		$summary[] = ['Status', 'Count'];

		if (!empty($status_ids)) {
			$activityPortalStatuses = ActivityPortalStatus::select([
				'id',
				'name',
			])
				->get();
			foreach ($status_ids as $key => $status_id) {
				$activityPortalStatus = $activityPortalStatuses->where('id', $status_id)->first();
				if ($activityPortalStatus) {
					$activitiesSummaryCountQuery = Activity::select([
						'activities.id',
					])
						->join('cases', 'cases.id', 'activities.case_id')
						->where('activities.status_id', $status_id);

					if ($request->filter_by == 'general') {
						$activitiesSummaryCountQuery->where(function ($q) use ($range1, $range2) {
							$q->whereDate('cases.date', '>=', $range1)
								->whereDate('cases.date', '<=', $range2);
						});
					} elseif ($request->filter_by == 'activity') {
						$activitiesSummaryCountQuery->join('activity_logs', 'activities.id', '=', 'activity_logs.activity_id')
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
										$query->whereRaw('DATE(activity_logs.l2_deffered_at) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_logs.l2_approved_at) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_logs.l3_deffered_at) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_logs.l3_approved_at) between "' . $range1 . '" and "' . $range2 . '"');
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
					} elseif ($request->filter_by == 'invoiceDate') {
						$activitiesSummaryCountQuery->join('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
							->where(function ($q) use ($range1, $range2) {
								$q->whereRaw('DATE(Invoices.created_at) between "' . $range1 . '" and "' . $range2 . '"');
							});
					} elseif ($request->filter_by == 'transactionDate') {
						$activitiesSummaryCountQuery->join('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
							->join('invoice_vouchers', 'invoice_vouchers.invoice_id', 'Invoices.id')
							->where(function ($q) use ($range1, $range2) {
								$q->whereRaw('DATE(invoice_vouchers.date) between "' . $range1 . '" and "' . $range2 . '"');
							});
					}

					if (!empty($request->get('asp_id'))) {
						$activitiesSummaryCountQuery->where('activities.asp_id', $request->get('asp_id'));
					}
					if (!empty($request->get('client_id'))) {
						$activitiesSummaryCountQuery->where('cases.client_id', $request->get('client_id'));
					}
					if (!empty($request->get('ticket'))) {
						$activitiesSummaryCountQuery->where('cases.number', $request->get('ticket'));
					}
					if (!Entrust::can('view-all-activities')) {
						if (Entrust::can('view-mapped-state-activities')) {
							$stateIds = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
							$activitiesSummaryCountQuery->join('asps', 'activities.asp_id', '=', 'asps.id')
								->whereIn('asps.state_id', $stateIds);
						}
					}

					$activitySummaryCount = $activitiesSummaryCountQuery->groupBy('activities.id')->get()->count();
					$summary[] = [
						$activityPortalStatus->name,
						$activitySummaryCount,
					];
				}
			}
		}

		$summary[] = ['Total', $total_count];

		if (Entrust::can('export-own-activities')) {
			$activity_details_header = [
				'ID',
				'Case Number',
				'Case Date',
				'CRM Activity ID',
				'Activity Number',
				'Activity Date',
				'Client Name',
				'ASP Name',
				'Axapta Code',
				'ASP Code',
				'ASP Contact Number',
				'ASP EMail',
				'ASP has GST',
				'Workshop Name',
				'RM Name',
				'Location',
				'District',
				'State',
				'Vehicle Registration Number',
				'Membership Type',
				'Vehicle Model',
				'Vehicle Make',
				'Case Status',
				'Finance Status',
				'Final Approved BO Service Type',
				'Portal Status',
				'Activity Status',
				'Remarks',
				'General Remarks',
				'Comments',
				'Deduction Reason',
				'Deferred Reason',
				'ASP Resolve Comments',
				'Invoice Number',
				'Invoice Date',
				'Invoice Status',
				'Transaction Date',
				'Voucher',
				'TDS Amount',
				'Paid Amount',
				'BD Latitude',
				'BD Longitude',
				'BD Location',
				'BD City',
				'BD State',
			];
			$config_ids = [294, 295, 296, 297, 158, 159, 160, 176, 173, 182];

		} else {
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
				'ASP Email',
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
				'Membership Type',
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
				'Transaction Date',
				'Voucher',
				'TDS Amount',
				'Paid Amount',
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
		}

		foreach ($config_ids as $key => $config_id) {
			$config = Config::where('id', $config_id)->first();
			$activity_details_header[] = str_replace("_", " ", strtolower($config->name));
		}

		if (!Entrust::can('export-own-activities')) {
			$status_headers = [
				'Imported through MIS Import',
				'Imported By',
				'Duration Between Import and ASP Data Filled',
				'ASP Data Filled',
				'ASP Data Filled By',
				'Duration Between ASP Data Filled and L1 deffered',
				'L1 Deferred',
				'L1 Deferred By',
				'Duration Between ASP Data Filled and L1 approved',
				'L1 Approved',
				'L1 Approved By',
				'Duration Between L1 approved and Invoice generated',
				'Duration Between L1 approved and L2 deffered',
				'L2 Deferred',
				'L2 Deferred By',
				'Duration Between L1 approved and L2 approved',
				'L2 Approved',
				'L2 Approved By',
				'Duration Between L2 approved and Invoice generated',
				'Duration Between L1 approved and L3 deffered',
				'Duration Between L2 approved and L3 deffered',
				'L3 Deferred',
				'L3 Deferred By',
				'Duration Between L2 approved and L3 approved',
				'L3 Approved',
				'L3 Approved By',
				'Duration Between L3 approved and Invoice generated',
				'Invoice Generated',
				'Invoice Generated By',
				'Duration Between Invoice generated and Axapta Generated',
				'Axapta Generated',
				'Axapta Generated By',
				'Duration Between Axapta Generated and Payment Completed',
				'Payment Completed',
				'Total No. Of Days',
				'Source',
				// 'Latest Updation Date',
			];
			$activity_details_header = array_merge($activity_details_header, $status_headers);
		}

		$rateCardHeaders = [
			'Range Limit',
			'Below Range Price',
			'Above Range Price',
			'Waiting Charge Per Hour',
			'Empty Return Range Price',
			'Adjustment Type',
			'Adjustment',
		];
		$activity_details_header = array_merge($activity_details_header, $rateCardHeaders);
		//dd($activity_details_header );

		$constants = config('constants');
		$activities = $activities
			->groupBy('activities.id')
			->get();
		$activity_details_data = [];
		foreach ($activities as $activity_key => $activity) {
			if (!empty($activity->case_submission_closing_date)) {
				$submission_closing_date = $activity->case_submission_closing_date;
			} else {
				$submission_closing_date = date('d-m-Y H:i:s', strtotime("+3 months", strtotime($activity->case_created_at)));
			}
			if (!empty($activity->invoice_created_at)) {
				$inv_created_at = date('d-m-Y', strtotime(str_replace('/', '-', $activity->invoice_created_at)));
			} else {
				$inv_created_at = '';
			}

			if (Entrust::can('display-asp-number-in-activities')) {
				$aspContactNumber = $activity->asp_contact_number1;
			} else {
				$aspContactNumber = maskPhoneNumber($activity->asp_contact_number1);
			}
			if (Entrust::can('export-own-activities')) {
				$activity_details_data[] = [
					$activity->id,
					$activity->case_number,
					$activity->case_date,
					$activity->crm_activity_id,
					$activity->number,
					$activity->activity_created_at,
					$activity->client_name,
					$activity->asp_name,
					$activity->asp_axpta_code,
					$activity->asp_code,
					$aspContactNumber,
					$activity->asp_email,
					$activity->asp_has_gst,
					$activity->asp_workshop_name,
					$activity->asp_rm_name,
					$activity->asp_location_name,
					$activity->asp_district_name,
					$activity->asp_state_name,
					$activity->case_vehicle_registration_number,
					$activity->case_membership_type,
					$activity->vehicle_model,
					$activity->vehicle_make,
					$activity->case_status,
					$activity->activity_finance_status,
					$activity->service_type,
					$activity->activity_portal_status,
					$activity->activity_status,
					$activity->remarks != NULL ? $activity->remarks : '',
					$activity->general_remarks != NULL ? $activity->general_remarks : '',
					$activity->bo_comments != NULL ? $activity->bo_comments : '',
					$activity->deduction_reason != NULL ? $activity->deduction_reason : '',
					$activity->defer_reason != NULL ? $activity->defer_reason : '',
					$activity->asp_resolve_comments != NULL ? $activity->asp_resolve_comments : '',
					$activity->invoice_no,
					$inv_created_at,
					$activity->invoice_status,
					$activity->transactionDate,
					$activity->voucher,
					$activity->tdsAmount,
					$activity->paidAmount,
					!empty($activity->bd_lat) ? $activity->bd_lat : '',
					!empty($activity->bd_long) ? $activity->bd_long : '',
					!empty($activity->bd_location) ? $activity->bd_location : '',
					!empty($activity->bd_city) ? $activity->bd_city : '',
					!empty($activity->bd_state) ? $activity->bd_state : '',
				];
			} else {
				$activity_details_data[] = [
					$activity->id,
					$activity->case_number,
					$activity->case_date,
					$submission_closing_date,
					$activity->case_submission_closing_date_remarks,
					$activity->crm_activity_id,
					$activity->number,
					$activity->activity_created_at,
					$activity->client_name,
					$activity->case_customer_name,
					$activity->case_customer_contact_number,
					$activity->asp_name,
					$activity->asp_axpta_code,
					$activity->asp_code,
					$aspContactNumber,
					$activity->asp_email,
					$activity->asp_has_gst,
					$activity->asp_is_self,
					$activity->asp_is_auto_invoice,
					$activity->asp_workshop_name,
					!empty($activity->asp_workshop_type) ? array_flip($constants['workshop_types'])[$activity->asp_workshop_type] : '',
					$activity->asp_rm_name,
					$activity->asp_location_name,
					$activity->asp_district_name,
					$activity->asp_state_name,
					$activity->case_vehicle_registration_number,
					$activity->case_membership_type,
					$activity->vehicle_model,
					$activity->vehicle_make,
					$activity->case_status,
					$activity->activity_finance_status,
					$activity->service_type,
					$activity->asp_activity_rejected_reason,
					$activity->asp_po_accepted != NULL ? ($activity->asp_po_accepted == 1 ? 'Yes' : 'No') : '',
					!empty($activity->asp_po_rejected_reason) ? $activity->asp_po_rejected_reason : '',
					$activity->activity_portal_status,
					$activity->activity_status,
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
					$activity->invoice_no,
					$inv_created_at,
					!empty($activity->invoice_amount) ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($activity->invoice_amount, 2))) : '',
					$activity->invoice_status,
					$activity->transactionDate,
					$activity->voucher,
					$activity->tdsAmount,
					$activity->paidAmount,
					!empty($activity->bd_lat) ? $activity->bd_lat : '',
					!empty($activity->bd_long) ? $activity->bd_long : '',
					!empty($activity->bd_location) ? $activity->bd_location : '',
					!empty($activity->bd_city) ? $activity->bd_city : '',
					!empty($activity->bd_state) ? $activity->bd_state : '',
					$activity->location_type,
					$activity->location_category,
				];
			}

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

			if (!Entrust::can('export-own-activities')) {
				$total_days = 0;
				$activity_log = ActivityLog::where('activity_id', $activity->id)->first();
				if ($activity_log) {
					$activity_details_data[$activity_key][] = $activity_log->imported_at ? date('d-m-Y H:i:s', strtotime($activity_log->imported_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->importedBy ? $activity_log->importedBy->username : '';
					// 'Duration Between Import and ASP Data Filled'
					$tot = ($activity_log->imported_at && $activity_log->asp_data_filled_at) ? $this->findDifference($activity_log->imported_at, $activity_log->asp_data_filled_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->asp_data_filled_at ? date('d-m-Y H:i:s', strtotime($activity_log->asp_data_filled_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->aspDataFilledBy ? $activity_log->aspDataFilledBy->username : '';
					// 'Duration Between ASP Data Filled and L1 deffered'
					$tot = ($activity_log->asp_data_filled_at && $activity_log->bo_deffered_at) ? $this->findDifference($activity_log->asp_data_filled_at, $activity_log->bo_deffered_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->bo_deffered_at ? date('d-m-Y H:i:s', strtotime($activity_log->bo_deffered_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->boDefferedBy ? $activity_log->boDefferedBy->username : '';
					// 'Duration Between ASP Data Filled and L1 approved'
					$tot = ($activity_log->asp_data_filled_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->asp_data_filled_at, $activity_log->bo_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->bo_approved_at ? date('d-m-Y H:i:s', strtotime($activity_log->bo_approved_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->boApprovedBy ? $activity_log->boApprovedBy->username : '';
					// 'Duration Between L1 approved and Invoice generated'
					$tot = ($activity_log->invoice_generated_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->invoice_generated_at, $activity_log->bo_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					// 'Duration Between L1 approved and L2 deffered'
					$tot = ($activity_log->l2_deffered_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->l2_deffered_at, $activity_log->bo_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';
					$activity_details_data[$activity_key][] = $activity_log->l2_deffered_at ? date('d-m-Y H:i:s', strtotime($activity_log->l2_deffered_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->l2DefferedBy ? $activity_log->l2DefferedBy->username : '';

					// 'Duration Between L1 approved and L2 approved'
					$tot = ($activity_log->l2_approved_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->l2_approved_at, $activity_log->bo_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->l2_approved_at ? date('d-m-Y H:i:s', strtotime($activity_log->l2_approved_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->l2ApprovedBy ? $activity_log->l2ApprovedBy->username : '';
					// 'Duration Between L2 approved and Invoice generated'
					$tot = ($activity_log->invoice_generated_at && $activity_log->l2_approved_at) ? $this->findDifference($activity_log->invoice_generated_at, $activity_log->l2_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					// 'Duration Between L1 approved and L3 deffered'
					$tot = ($activity_log->l3_deffered_at && $activity_log->bo_approved_at) ? $this->findDifference($activity_log->l3_deffered_at, $activity_log->bo_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					// 'Duration Between L2 approved and L3 deffered'
					$tot = ($activity_log->l3_deffered_at && $activity_log->l2_approved_at) ? $this->findDifference($activity_log->l3_deffered_at, $activity_log->l2_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->l3_deffered_at ? date('d-m-Y H:i:s', strtotime($activity_log->l3_deffered_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->l3DefferedBy ? $activity_log->l3DefferedBy->username : '';

					// 'Duration Between L2 approved and L3 approved'
					$tot = ($activity_log->l3_approved_at && $activity_log->l2_approved_at) ? $this->findDifference($activity_log->l3_approved_at, $activity_log->l2_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->l3_approved_at ? date('d-m-Y H:i:s', strtotime($activity_log->l3_approved_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->l3ApprovedBy ? $activity_log->l3ApprovedBy->username : '';
					// 'Duration Between L3 approved and Invoice generated'
					$tot = ($activity_log->invoice_generated_at && $activity_log->l3_approved_at) ? $this->findDifference($activity_log->invoice_generated_at, $activity_log->l3_approved_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->invoice_generated_at ? date('d-m-Y H:i:s', strtotime($activity_log->invoice_generated_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->invoiceGeneratedBy ? $activity_log->invoiceGeneratedBy->username : '';
					// 'Duration Between Invoice generated and Axapta Generated'
					$tot = ($activity_log->invoice_generated_at && $activity_log->axapta_generated_at) ? $this->findDifference($activity_log->invoice_generated_at, $activity_log->axapta_generated_at) : '';
					$total_days = is_numeric($tot) ? ($tot + $total_days) : $total_days;
					$activity_details_data[$activity_key][] = is_numeric($tot) ? ($tot > 1 ? ($tot . ' Days') : ($tot . ' Day')) : '';

					$activity_details_data[$activity_key][] = $activity_log->axapta_generated_at ? date('d-m-Y H:i:s', strtotime($activity_log->axapta_generated_at)) : '';
					$activity_details_data[$activity_key][] = $activity_log->axaptaGeneratedBy ? $activity_log->axaptaGeneratedBy->username : '';
					// 'Duration Between Axapta Generated and Payment Completed'
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
			$activity_details_data[$activity_key][] = $activity->range_limit;
			$activity_details_data[$activity_key][] = $activity->below_range_price;
			$activity_details_data[$activity_key][] = $activity->above_range_price;
			$activity_details_data[$activity_key][] = $activity->waiting_charge_per_hour;
			$activity_details_data[$activity_key][] = $activity->empty_return_range_price;
			$activity_details_data[$activity_key][] = !empty($activity->adjustment_type) ? ($activity->adjustment_type == 1 ? "Percentage" : "Amount") : '--';
			$activity_details_data[$activity_key][] = $activity->adjustment;
		}

		Excel::create('Activity Status Report', function ($excel) use ($summary, $activity_details_header, $activity_details_data, $status_ids, $summary_period) {
			$excel->sheet('Summary', function ($sheet) use ($summary, $status_ids, $summary_period) {
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
				$sheet->cells('A1:EZ1', function ($cells) {
					$cells->setFont(array(
						'size' => '10',
						'bold' => true,
					))->setBackground('#CCC9C9');
				});
			});
		})->export('xlsx');

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
			$activities = Activity::select([
				'activities.id',
				'activities.service_type_id',
				'activities.asp_id',
			])
				->join('cases', 'cases.id', 'activities.case_id')
				->where('activities.status_id', 17) //ONHOLD
				->whereIn('cases.status_id', [3, 4]) //CANCELLED/CLOSED
				->whereDate('cases.date', '<=', $case_date)
				->get();

			if ($activities->isEmpty()) {
				return response()->json([
					'success' => false,
					'errors' => [
						'No activities in the selected case date',
					],
				]);
			}

			foreach ($activities as $key => $activity) {

				if (!$activity->case->vehicleModel) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Vehicle model is required. Case Number: ' . $activity->case->number,
						],
					]);
				}

				if (!$activity->case->vehicleModel->vehiclecategory) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Vehicle category not mapped for the vehicle model. Case Number: ' . $activity->case->number,
						],
					]);
				}

				//MECHANICAL SERVICE GROUP
				if ($activity->serviceType && $activity->serviceType->service_group_id == 2) {
					$cc_total_km = $activity->detail(280) ? $activity->detail(280)->value : 0;
					$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $activity->case->vehicleModel->vehiclecategory->id);
					if ($is_bulk) {
						//ASP Completed Data Entry - Waiting for BO Bulk Verification
						$status_id = 5;
					} else {
						//ASP Completed Data Entry - Waiting for BO Individual Verification
						$status_id = 6;
					}
				} else {
					$status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
				}
				$activity->update([
					'status_id' => $status_id,
					'updated_by_id' => Auth::id(),
				]);
			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'On Hold Cases have been released for the selected case date',
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
		return Asp::searchAllAsps($request);
	}

	public function searchClients(Request $request) {
		return Client::searchClient($request);
	}
}
