<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityDetail;
use Abs\RsaCasePkg\ActivityFinanceStatus;
use Abs\RsaCasePkg\ActivityLog;
use Abs\RsaCasePkg\ActivityPortalStatus;
use Abs\RsaCasePkg\ActivityRatecard;
use Abs\RsaCasePkg\ActivityReport;
use Abs\RsaCasePkg\ActivityStatus;
use App\Asp;
use App\AspServiceType;
use App\Attachment;
use App\CallCenter;
use App\Client;
use App\Config;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MapMyIndiaController;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;
use Validator;
use Yajra\Datatables\Datatables;

class ActivityController extends Controller {

	public function getFilterData() {
		if (Entrust::can('export-own-activities') || Entrust::can('export-own-rm-asp-activities') || Entrust::can('export-own-zm-asp-activities')) {
			$activityPortalStatusList = ActivityPortalStatus::select('name', 'id')->whereNotIn('id', [2, 4, 15, 16, 17, 25, 27])->get();
		} else {
			$activityPortalStatusList = ActivityPortalStatus::select('name', 'id')->get();
		}

		$this->data['extras'] = [
			'call_center_list' => collect(CallCenter::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Call Center']),
			'service_type_list' => collect(ServiceType::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Sub Service']),
			'finance_status_list' => collect(ActivityFinanceStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Finance Status']),
			'portal_status_list' => collect($activityPortalStatusList),
			'status_list' => collect($activityPortalStatusList)->prepend(['id' => '', 'name' => 'Select Portal Status']),
			'activity_status_list' => collect(ActivityStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Activity Status']),
			'client_list' => collect(Client::withTrashed()->select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Client']),
			'export_client_list' => collect(Client::withTrashed()->select([
				'id',
				'name',
				DB::raw('IF(deleted_at IS NULL,"Active","Inactive") as status'),
			])
					->get()),
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
		$isAspRole = false;
		if (Entrust::hasRole('asp')) {
			$isAspRole = true;
		}
		$this->data['isAspRole'] = $isAspRole;
		return response()->json($this->data);
	}

	public function getList(Request $request) {
		// dd($request->all());
		$periods = getStartDateAndEndDate($request->date_range_period);
		$from_date = $periods['start_date'];
		$end_date = $periods['end_date'];

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
				// ASP FINANCE ADMIN
				if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
					$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
					$aspIds[] = Auth::user()->asp->id;
					$activities->whereIn('asps.id', $aspIds)->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
				} else {
					$activities->where('users.id', Auth::id())->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
				}
			}
			if (Entrust::can('own-rm-asp-activities')) {
				$aspIds = Asp::where('regional_manager_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereIn('asps.id', $aspIds)
					->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
			}
			if (Entrust::can('own-zm-asp-activities')) {
				$aspIds = Asp::where('zm_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereIn('asps.id', $aspIds)
					->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
			}
			if (Entrust::can('own-nm-asp-activities')) {
				$aspIds = Asp::where('nm_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereIn('asps.id', $aspIds);
			}
		}
		return Datatables::of($activities)
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.workshop_name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->addColumn('action', function ($activity) {
				$status_id = 1;
				$return_status_ids = [5, 6, 8, 9, 11, 1, 7, 18, 19, 20, 21, 22, 23, 24, 25, 26];

				$action = '<div class="dataTable-actions" style="min-width: 125px;">
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

				//MOVE CASE TO NOT ELIGIBLE FOR PAYOUT
				if (Entrust::can('move-activity-to-not-eligible-payout')) {
					$notEligibleIcon = asset('public/img/content/table/noteligible.svg');
					if ($activity->status_id != 15 && $activity->status_id != 16 && $activity->status_id != 12 && $activity->status_id != 13 && $activity->status_id != 14) {
						$action .= '<a href="javascript:;" onclick="angular.element(this).scope().moveToNotEligibleForPayout(' . $activity->id . ')" title="Move To Not Eligible">
                						<img src="' . $notEligibleIcon . '" alt="Move To Not Eligible" class="img-responsive">
                					</a>';
					}
				}

				//RELEASE ON HOLD / ASP COMPLETED DATA ENTRY - WAITING FOR CALL CENTER DATA ENTRY CASES
				if (Entrust::can('release-onhold-case')) {
					$onholdCaseReleaseIcon = asset('public/img/content/table/release.svg');
					if ($activity->status_id == 17 || $activity->status_id == 26) {
						$action .= '<a href="javascript:;" onclick="angular.element(this).scope().releaseOnHoldCase(' . $activity->id . ')" title="Release On Hold Case">
                						<img src="' . $onholdCaseReleaseIcon . '" alt="Release On Hold Case" class="img-responsive">
                					</a>';
					}
				}

				$action .= '</div>';
				return $action;
			})
			->make(true);
	}

	public function activityBackAsp(Request $request) {
		// dd($request->all());
		try {
			$activity = Activity::findOrFail($request->activty_id);
			$return_status_ids = [5, 6, 8, 9, 11, 1, 7, 18, 19, 20, 21, 22, 23, 24, 25, 26];

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

			if (!isset($request->backstep_reason) || (isset($request->backstep_reason) && empty($request->backstep_reason))) {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'error' => 'Reason is required',
				]);
			}

			// Check if the first character is a special character
			if (isset($request->backstep_reason) && !empty($request->backstep_reason) && !preg_match('/^[^=]/', $request->backstep_reason)) {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'error' => 'Equal symbol (=) is not allowed as the first character for reason!',
				]);
			}

			//ASP Rejected CC Details - Waiting for ASP Data Entry
			if ($request->ticket_status_id == '1') {
				$activity->status_id = 2;
				$activity->is_asp_data_entry_done = NULL;
				$activity->backstep_reason = makeUrltoLinkInString($request->backstep_reason);
				$activity->backstepped_at = Carbon::now();
				$activity->backstep_by_id = Auth::user()->id;
				$activity->updated_at = Carbon::now();
				$activity->updated_by_id = Auth::user()->id;
				$activity->save();

				//SAVE ACTIVITY REPORT FOR DASHBOARD
				ActivityReport::saveReport($activity->id);

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
				$activity->backstep_reason = makeUrltoLinkInString($request->backstep_reason);
				$activity->backstepped_at = Carbon::now();
				$activity->backstep_by_id = Auth::user()->id;
				$activity->updated_at = Carbon::now();
				$activity->updated_by_id = Auth::user()->id;
				$activity->save();

				//SAVE ACTIVITY REPORT FOR DASHBOARD
				ActivityReport::saveReport($activity->id);

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
			} elseif ($request->ticket_status_id == '3') {
				$activity->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
				$activity->is_asp_data_entry_done = 1;
				$activity->backstep_reason = makeUrltoLinkInString($request->backstep_reason);
				$activity->backstepped_at = Carbon::now();
				$activity->backstep_by_id = Auth::user()->id;
				$activity->updated_at = Carbon::now();
				$activity->updated_by_id = Auth::user()->id;
				$activity->save();

				//SAVE ACTIVITY REPORT FOR DASHBOARD
				ActivityReport::saveReport($activity->id);

				if ($activity) {
					//log message
					$log_status = config('rsa.LOG_STATUES_TEMPLATES.ASP_DATA_ENTRY_DONE');
					$log_waiting = config('rsa.LOG_WAITING_FOR_TEMPLATES.ASP_DATA_ENTRY_DONE');
					logActivity3(config('constants.entity_types.ticket'), $activity->id, [
						'Status' => $log_status,
						'Waiting for' => $log_waiting,
					], 361);

					$sendApprovalNotification = config('rsa.SEND_APPROVAL_NOTIFICATION');
					if ($sendApprovalNotification) {
						$noty_message_template = 'ASP_DATA_ENTRY_DONE_DEFFERED';
						$ticket_number = [$activity->case->number];
						$state_id = $activity->asp->state_id;
						$bo_users = DB::table('state_user')
							->join('users', 'users.id', 'state_user.user_id')
							->where('state_user.state_id', $state_id)
							->where('users.role_id', 6) //BO
							->where('users.activity_approval_level_id', 1) //L1
							->pluck('state_user.user_id');
						if (!empty($bo_users)) {
							foreach ($bo_users as $bo_user_id) {
								notify2($noty_message_template, $bo_user_id, config('constants.alert_type.blue'), $ticket_number);
							}
						}
					}
					return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
						'success' => 'Activity status moved to L1 individual verification',
					]);
				} else {
					return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
						'error' => 'Activity status not moved to L1 individual verification',
					]);
				}
			}
		} catch (\Exception $e) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
				'error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
			]);
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

		//SAVE ACTIVITY REPORT FOR DASHBOARD
		ActivityReport::saveReport($id);

		return response()->json(['success' => true]);
	}

	public function getBulkVerificationList(Request $request) {
		$activities = Activity::select([
			'activities.id',
			'activities.crm_activity_id',
			'activities.number as activity_number',
			// DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			DB::raw('DATE_FORMAT(cases.date,"%Y-%m-%d %H:%i:%s") as case_date'),
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
			'call_centers.name as call_center',
			DB::raw('COALESCE(bo_km_travelled.value, "--") as boKmTravelled'),
			DB::raw('COALESCE(bo_payout_amount.value, "--") as boPayoutAmount'),
		])
			->leftJoin('activity_details as bo_km_travelled', function ($join) {
				$join->on('bo_km_travelled.activity_id', 'activities.id')
					->where('bo_km_travelled.key_id', 158); //BO KM TRAVELLED
			})
			->leftJoin('activity_details as bo_payout_amount', function ($join) {
				$join->on('bo_payout_amount.activity_id', 'activities.id')
					->where('bo_payout_amount.key_id', 182); //BO PAYOUT AMOUNT
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
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
		// ->where('activities.asp_accepted_cc_details', '!=', 1)
		// ->orderBy('cases.date', 'DESC')
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
				} elseif (Auth::user()->activity_approval_level_id == 4) {
					// L4
					$activities->where('activities.status_id', 23); //Waiting for L4 Bulk Verification
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
		$activities = Activity::select([
			'activities.id',
			'activities.crm_activity_id',
			'activities.number as activity_number',
			// DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			DB::raw('DATE_FORMAT(cases.date,"%Y-%m-%d %H:%i:%s") as case_date'),
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
			'call_centers.name as call_center',
			DB::raw('COALESCE(bo_km_travelled.value, "--") as boKmTravelled'),
			DB::raw('COALESCE(bo_payout_amount.value, "--") as boPayoutAmount'),
		])
			->leftJoin('activity_details as bo_km_travelled', function ($join) {
				$join->on('bo_km_travelled.activity_id', 'activities.id')
					->where('bo_km_travelled.key_id', 158); //BO KM TRAVELLED
			})
			->leftJoin('activity_details as bo_payout_amount', function ($join) {
				$join->on('bo_payout_amount.activity_id', 'activities.id')
					->where('bo_payout_amount.key_id', 182); //BO PAYOUT AMOUNT
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
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
		// ->where('activities.asp_accepted_cc_details', '!=', 1)
		// ->orderBy('cases.date', 'DESC')
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
				} elseif (Auth::user()->activity_approval_level_id == 4) {
					// L4
					$activities->where('activities.status_id', 24); //Waiting for L4 Individual Verification
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
				if (!$activity_data || ($activity_data && $activity_data->status_id != 5 && $activity_data->status_id != 6 && $activity_data->status_id != 8 && $activity_data->status_id != 9 && $activity_data->status_id != 18 && $activity_data->status_id != 19 && $activity_data->status_id != 20 && $activity_data->status_id != 21 && $activity_data->status_id != 22 && $activity_data->status_id != 23 && $activity_data->status_id != 24)) {
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
			$this->data['activities'] = $activity = Activity::with([
				'invoice',
				'invoice.asp' => function ($q) {
					$q->select([
						'id',
						'has_gst',
						'is_auto_invoice',
						'tax_calculation_method',
						'workshop_name',
						'bank_account_number',
						'bank_name',
						'bank_branch_name',
						'bank_ifsc_code',
					]);
				},
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
					DB::raw('COALESCE(cases.membership_type, "--") as membership_type'),
					'cases.customer_name as customer_name',
					'cases.vin_no',
					'cases.km_during_breakdown',
					'cases.customer_contact_number',
					'cases.bd_lat',
					'cases.bd_long',
					'cases.bd_location',
					'cases.bd_city',
					'cases.bd_state',
					'cases.csr',
					'cases.pickup_lat',
					'cases.pickup_long',
					'cases.pickup_dealer_name',
					'cases.pickup_dealer_location',
					'cases.pickup_dealer_state',
					'cases.pickup_dealer_city',
					'cases.pickup_location_pincode',
					'cases.drop_dealer_name',
					'cases.drop_dealer_location',
					'cases.drop_dealer_state',
					'cases.drop_dealer_city',
					'cases.drop_location_pincode',
					'cases.contact_name_at_pickup',
					'cases.contact_number_at_pickup',
					'cases.contact_name_at_drop',
					'cases.contact_number_at_drop',
					DB::raw('DATE_FORMAT(cases.delivery_request_pickup_date,"%d-%m-%Y") as delivery_request_pickup_date'),
					'cases.delivery_request_pickup_time',
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
					'activities.backstep_reason',
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
					DB::raw('IF(activities.remarks IS NULL OR activities.remarks="","",activities.remarks) as remarks'),
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
					DB::raw('IF(Invoices.created_at IS NULL,"NA",DATE_FORMAT(Invoices.created_at,"%d/%m/%Y")) as invoice_date'),
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
				'entity_id' => $activity_status_id,
				'entity_type' => 18,
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
				'entity_id' => $activity_status_id,
				'entity_type' => 19,
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
				'entity_id' => $activity_status_id,
				'entity_type' => 20,
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

			$otherAttachmentOne = Attachment::where([
				'entity_id' => $activity_status_id,
				'entity_type' => 24,
			])
				->first();
			$otherAttachmentOneUrl = '';
			if ($otherAttachmentOne) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $otherAttachmentOne->attachment_file_name)) {
						$otherAttachmentOneUrl = aspTicketAttachmentImage($otherAttachmentOne->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}
				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $otherAttachmentOne->attachment_file_name)) {
						$otherAttachmentOneUrl = aspTicketAttachmentImage($otherAttachmentOne->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
			}
			$this->data['activities']['otherAttachmentOne'] = $otherAttachmentOne;
			$this->data['activities']['otherAttachmentOneUrl'] = $otherAttachmentOneUrl;

			$otherAttachmentTwo = Attachment::where([
				'entity_id' => $activity_status_id,
				'entity_type' => 25,
			])
				->first();
			$otherAttachmentTwoUrl = '';
			if ($otherAttachmentTwo) {
				if ($hasccServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $ccServiceType->id . '/' . $otherAttachmentTwo->attachment_file_name)) {
						$otherAttachmentTwoUrl = aspTicketAttachmentImage($otherAttachmentTwo->attachment_file_name, $activity_status_id, $activity->asp->id, $ccServiceType->id);
					}
				}
				if ($hasaspServiceType) {
					if (Storage::disk('asp-data-entry-attachment-folder')->exists('/attachments/ticket/asp/ticket-' . $activity_status_id . '/asp-' . $activity->asp->id . '/service-' . $aspServiceType->id . '/' . $otherAttachmentTwo->attachment_file_name)) {
						$otherAttachmentTwoUrl = aspTicketAttachmentImage($otherAttachmentTwo->attachment_file_name, $activity_status_id, $activity->asp->id, $aspServiceType->id);
					}
				}
			}
			$this->data['activities']['otherAttachmentTwo'] = $otherAttachmentTwo;
			$this->data['activities']['otherAttachmentTwoUrl'] = $otherAttachmentTwoUrl;

			$key_list = [153, 157, 161, 158, 159, 160, 154, 155, 156, 170, 174, 180, 298, 179, 176, 172, 173, 182, 171, 175, 181];
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

				$this->data['activities']['invoice_activities'] = $invoice_activities = Activity::select([
					'cases.number',
					'activities.id',
					// 'activities.asp_id',
					'Invoices.asp_id',
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
					'total_tax_amount.value as total_tax_amount_value',
				])
					->join('Invoices', 'Invoices.id', 'activities.invoice_id')
					->join('cases', 'cases.id', 'activities.case_id')
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

				$companyDetailBasedOnInvoiceResponse = getCompanyDetailsBasedOnInvoice($inv_created);
				$this->data['activities']['company_invoice_address'] = $companyDetailBasedOnInvoiceResponse['invoiceAddress'];

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

				$this->data['activities']['signature_attachment'] = Attachment::where('entity_id', $invoice_activities[0]->asp_id)
					->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))
					->first();

				$this->data['activities']['signature_attachment_path'] = url('storage/' . config('rsa.asp_attachment_path_view'));

			}

			$isMobile = 0; //WEB
			//MOBILE APP
			if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
				$isMobile = 1;
			}

			$caseDate = date('Y-m-d H:i:s', strtotime($activity->case_date));
			$asp_service_type_data = Activity::getAspServiceRateCardByAmendment($activity->asp_id, $caseDate, $activity->service_type_id, $isMobile);
			if (!$asp_service_type_data) {
				return response()->json([
					'success' => false,
					'errors' => [
						'ASP rate card not found',
					],
				]);
			}

			$casewiseRatecardEffectDatetime = config('rsa.CASEWISE_RATECARD_EFFECT_DATETIME');
			//Activity creation datetime greater than effective datetime
			if (date('Y-m-d H:i:s', strtotime($activity->activity_date)) > $casewiseRatecardEffectDatetime) {
				//Activity that is initiated for payment process & not eligible
				if ($activity->activity_portal_status_id == 1 || $activity->activity_portal_status_id == 10 || $activity->activity_portal_status_id == 11 || $activity->activity_portal_status_id == 12 || $activity->activity_portal_status_id == 13 || $activity->activity_portal_status_id == 14 || $activity->activity_portal_status_id == 15 || $activity->activity_portal_status_id == 16 || $activity->activity_portal_status_id == 17 || $activity->activity_portal_status_id == 25) {
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

					$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", str_replace(",", "", number_format($detail->value, 2))) : '0.00') : '0.00';
					$raw_key_name = 'raw_' . $config->name;
					$this->data['activities'][$raw_key_name] = $detail ? (!empty($detail->value) ? $detail->value : '0.00') : '0.00';
				} elseif (strpos($config->name, '_time')) {
					$this->data['activities'][$config->name] = $detail ? (!empty($detail->value) ? $detail->value : '0.00') : '0.00';
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
					$aspServiceType = Activity::getAspServiceRateCardByAmendment($activity->asp_id, $caseDate, $service_type->id, $isMobile);
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
			$l4DefferedAt = "";
			$l4DefferedBy = "";
			$l4ApprovedAt = "";
			$l4ApprovedBy = "";
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
				if (!empty($activity_data->log->l4_deffered_at)) {
					$l4DefferedAt = $activity_data->log->l4_deffered_at;
				}
				if (!empty($activity_data->log->l4_deffered_by_id)) {
					$l4DefferedBy = $activity_data->log->l4DefferedBy ? ($activity_data->log->l4DefferedBy->name . ' - ' . $activity_data->log->l4DefferedBy->username) : '';
				}
				if (!empty($activity_data->log->l4_approved_at)) {
					$l4ApprovedAt = $activity_data->log->l4_approved_at;
				}
				if (!empty($activity_data->log->l4_approved_by_id)) {
					$l4ApprovedBy = $activity_data->log->l4ApprovedBy ? ($activity_data->log->l4ApprovedBy->name . ' - ' . $activity_data->log->l4ApprovedBy->username) : '';
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

			$serviceTypes = Activity::getAspServiceTypesByAmendment($activity->asp_id, $caseDate, $isMobile);
			$boServiceTypeId = '';
			$boServiceTypeData = ActivityDetail::where('activity_id', $activity_status_id)->where('key_id', 161)->first();
			if ($boServiceTypeData) {
				$boServiceType = ServiceType::where('name', $boServiceTypeData->value)->first();
				if ($boServiceType) {
					$boServiceTypeId = $boServiceType->id;
				}
			}
			$eligibleBackstepStatusIds = [5, 6, 8, 9, 11, 1, 7, 18, 19, 20, 21, 22, 23, 24, 25, 26];
			$eligibleForBackstep = false;
			if (Entrust::can('backstep-activity') && in_array($activity->activity_portal_status_id, $eligibleBackstepStatusIds)) {
				$eligibleForBackstep = true;
			}

			// OTHER CHARGES SPLIT UP ELIGIBLE
			$eligibleForOthersplitupCharges = true;
			$otherChargesSplitupEffectDatetime = config('rsa.OTHER_CHARGES_SPLITUP_EFFECT_DATETIME');
			if (!empty($aspDataFilledAt) && ($otherChargesSplitupEffectDatetime > $aspDataFilledAt)) {
				$eligibleForOthersplitupCharges = false;
			}

			$this->data['activities']['eligibleForBackstep'] = $eligibleForBackstep;
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
			$this->data['activities']['l4DefferedAt'] = $l4DefferedAt;
			$this->data['activities']['l4DefferedBy'] = $l4DefferedBy;
			$this->data['activities']['l4ApprovedAt'] = $l4ApprovedAt;
			$this->data['activities']['l4ApprovedBy'] = $l4ApprovedBy;
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
			$this->data['activities']['eligibleForOthersplitupCharges'] = $eligibleForOthersplitupCharges;

			// Google Map Link for ASP KM travelled view
			$aspStartEndLocation = '';
			if (!empty($activity->asp->lat) && !empty($activity->asp->long)) {
				$aspStartEndLocation = $activity->asp->lat . ',' . $activity->asp->long;
			} elseif (isset($activity->asp->full_address) && !empty($activity->asp->full_address)) {
				$aspStartEndLocation = $activity->asp->full_address;
			}

			$bdLocation = '';
			if (!empty($this->data['activities']->bd_lat) && !empty($this->data['activities']->bd_long) && $this->data['activities']->bd_lat != '-' && $this->data['activities']->bd_long != '-') {
				$bdLocation = $this->data['activities']->bd_lat . ',' . $this->data['activities']->bd_long;
			} elseif (!empty($this->data['activities']->bd_location) && $this->data['activities']->bd_location != '-') {
				$bdLocation = $this->data['activities']->bd_location;
			}

			$pickupLocation = '';
			if (!empty($this->data['activities']->pickup_lat) && !empty($this->data['activities']->pickup_long) && $this->data['activities']->pickup_lat != '-' && $this->data['activities']->pickup_long != '-') {
				$pickupLocation = $this->data['activities']->pickup_lat . ',' . $this->data['activities']->pickup_long;
			}

			$dropLocation = '';
			//ONLY TOW SERVICES
			if ($this->data['activities']->serviceType->service_group_id == 3) {
				if (isset($this->data['activities']->drop_location_lat) && isset($this->data['activities']->drop_location_long) && !empty($this->data['activities']->drop_location_lat) && !empty($this->data['activities']->drop_location_long) && $this->data['activities']->drop_location_lat != "-" && $this->data['activities']->drop_location_long != "-") {
					$dropLocation = $this->data['activities']->drop_location_lat . ',' . $this->data['activities']->drop_location_long;
				} elseif (isset($this->data['activities']->drop_location) && !empty($this->data['activities']->drop_location) && $this->data['activities']->drop_location != '-') {
					$dropLocation = $this->data['activities']->drop_location;
				}
			}

			$locationUrl = "https://www.google.com/maps/dir/" . $aspStartEndLocation;

			if (!empty($bdLocation)) {
				$locationUrl .= "/" . $bdLocation;
			}

			if (!empty($pickupLocation)) {
				$locationUrl .= "/" . $pickupLocation;
			}

			if (!empty($dropLocation)) {
				$locationUrl .= "/" . $dropLocation;
			}

			$locationUrl .= "/" . $aspStartEndLocation;
			$this->data['activities']['asp_km_travelled_map_url'] = $locationUrl;

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

	// ON CHANGE SERVICE TYPE RATE CARD ON APPROVAL FORM
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

			$isMobile = 0; //WEB
			//MOBILE APP
			if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
				$isMobile = 1;
			}

			$asp_service_type_data = Activity::getAspServiceRateCardByAmendment($request->asp_id, $activity->case->date, $request->service_type_id, $isMobile);
			if (!$asp_service_type_data) {
				return response()->json([
					'success' => false,
					'errors' => [
						'ASP rate card not found',
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

			// Check if the first character is a special character
			if (isset($request->bo_comments) && !empty($request->bo_comments) && !preg_match('/^[^=]/', $request->bo_comments)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Equal symbol (=) is not allowed as the first character for comments!',
					],
				]);
			}

			// Check if the first character is a special character
			if (isset($request->deduction_reason) && !empty($request->deduction_reason) && !preg_match('/^[^=]/', $request->deduction_reason)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Equal symbol (=) is not allowed as the first character for deduction reason!',
					],
				]);
			}

			// Check if the first character is a special character
			if (isset($request->exceptional_reason) && !empty($request->exceptional_reason) && !preg_match('/^[^=]/', $request->exceptional_reason)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Equal symbol (=) is not allowed as the first character for exceptional reason!',
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

			if ($request->bo_border_charges !== 0 && $request->bo_border_charges === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Border Charges is required',
					],
				]);
			}

			if ($request->bo_green_tax_charges !== 0 && $request->bo_green_tax_charges === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Green Tax Charges is required',
					],
				]);
			}

			if ($request->bo_toll_charges !== 0 && $request->bo_toll_charges === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Toll Charges is required',
					],
				]);
			}

			if ($request->bo_eatable_items_charges !== 0 && $request->bo_eatable_items_charges === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Eatable Items Charges is required',
					],
				]);
			}

			if ($request->bo_fuel_charges !== 0 && $request->bo_fuel_charges === '') {
				return response()->json([
					'success' => false,
					'errors' => [
						'Fuel Charges is required',
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

			$activity = Activity::whereIn('status_id', [6, 9, 19, 21, 22, 24, 5, 8, 18, 20, 23])
				->where('id', $request->activity_id)
				->first();
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
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

			$key_list = [158, 159, 160, 161, 176, 172, 173, 179, 182, 325, 324, 323, 322, 328, 330, 333];
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
					//L1
					if (Auth::user()->activity_approval_level_id == 1) {
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("<hr> L1 Approver : " . makeUrltoLinkInString($request->exceptional_reason));
						} else {
							$exceptionalReason = 'L1 Approver : ' . makeUrltoLinkInString($request->exceptional_reason);
						}
					} elseif (Auth::user()->activity_approval_level_id == 2) {
						//L2
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("<hr> L2 Approver : " . makeUrltoLinkInString($request->exceptional_reason));
						} else {
							$exceptionalReason = 'L2 Approver : ' . makeUrltoLinkInString($request->exceptional_reason);
						}
					} elseif (Auth::user()->activity_approval_level_id == 3) {
						//L3
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("<hr> L3 Approver : " . makeUrltoLinkInString($request->exceptional_reason));
						} else {
							$exceptionalReason = 'L3 Approver : ' . makeUrltoLinkInString($request->exceptional_reason);
						}
					} elseif (Auth::user()->activity_approval_level_id == 4) {
						//L4
						if (!empty($exceptionalReason)) {
							$exceptionalReason .= nl2br("<hr> L4 Approver : " . makeUrltoLinkInString($request->exceptional_reason));
						} else {
							$exceptionalReason = 'L4 Approver : ' . makeUrltoLinkInString($request->exceptional_reason);
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

			$sendBreakdownOrEmptyreturnChargesWhatsappSms = false;
			//L2, L3, and L4 approver flow should be effective from April 2022 cases not for all the cases - By Sundhar / Hyder
			if (date('Y-m-d', strtotime($activity->case->date)) >= "2022-04-01") {
				$l2Approvers = User::where('activity_approval_level_id', 2)->pluck('id');
				$l3Approvers = User::where('activity_approval_level_id', 3)->pluck('id');
				$l4Approvers = User::where('activity_approval_level_id', 4)->pluck('id');

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
							$activity->l1_changed_service_type_id = $request->boServiceTypeId;
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
							$activity->l2_changed_service_type_id = $request->boServiceTypeId;
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
						if ($isActivityBulk) {
							$activityStatusId = 23; //Waiting for L4 Bulk Verification
						} else {
							$activityStatusId = 24; //Waiting for L4 Individual Verification
						}
						$approver = '3';
						if ($isServiceTypeChanged) {
							$activity->service_type_changed_on_level = 3;
							$activity->l3_changed_service_type_id = $request->boServiceTypeId;
						}
						if ($isKmTravelledChanged) {
							$activity->km_changed_on_level = 3;
						}
						if ($isNotCollectedChanged) {
							$activity->not_collected_amount_changed_on_level = 3;
						}
						if ($isCollectedChanged) {
							$activity->collected_amount_changed_on_level = 3;
						}
						$this->sendApprovalNoty($l4Approvers, $activity->case->number, "L4_APPROVAL");
					} elseif (Auth::user()->activity_approval_level_id == 4) {
						// L4
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '4';
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					}
				} elseif (floatval($request->bo_net_amount) > 6000 && floatval($request->bo_net_amount) <= 10000) {
					//GREATER THAN 6000 AND LESSER THAN OR EQUAL TO 10000
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
							$activity->l1_changed_service_type_id = $request->boServiceTypeId;
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
							$activity->l2_changed_service_type_id = $request->boServiceTypeId;
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
						if ($isServiceTypeChanged) {
							$activity->service_type_changed_on_level = 3;
							$activity->l3_changed_service_type_id = $request->boServiceTypeId;
						}
						if ($isKmTravelledChanged) {
							$activity->km_changed_on_level = 3;
						}
						if ($isNotCollectedChanged) {
							$activity->not_collected_amount_changed_on_level = 3;
						}
						if ($isCollectedChanged) {
							$activity->collected_amount_changed_on_level = 3;
						}
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					} elseif (Auth::user()->activity_approval_level_id == 4) {
						// L4
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '4';
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					}
				} elseif (floatval($request->bo_net_amount) > 4000 && floatval($request->bo_net_amount) <= 6000) {
					//GREATER THAN 4000 AND LESSER THAN OR EQUAL TO 6000
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
							$activity->l1_changed_service_type_id = $request->boServiceTypeId;
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
							$activity->l2_changed_service_type_id = $request->boServiceTypeId;
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
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					} elseif (Auth::user()->activity_approval_level_id == 3) {
						// L3
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '3';
						if ($isServiceTypeChanged) {
							$activity->service_type_changed_on_level = 3;
							$activity->l3_changed_service_type_id = $request->boServiceTypeId;
						}
						if ($isKmTravelledChanged) {
							$activity->km_changed_on_level = 3;
						}
						if ($isNotCollectedChanged) {
							$activity->not_collected_amount_changed_on_level = 3;
						}
						if ($isCollectedChanged) {
							$activity->collected_amount_changed_on_level = 3;
						}
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					} elseif (Auth::user()->activity_approval_level_id == 4) {
						// L4
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '4';
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
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
							$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
						}
						$approver = '1';
						if ($isServiceTypeChanged) {
							$activity->service_type_changed_on_level = 1;
							$activity->l1_changed_service_type_id = $request->boServiceTypeId;
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
							$activity->l2_changed_service_type_id = $request->boServiceTypeId;
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
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					} elseif (Auth::user()->activity_approval_level_id == 3) {
						// L3
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '3';
						if ($isServiceTypeChanged) {
							$activity->service_type_changed_on_level = 3;
							$activity->l3_changed_service_type_id = $request->boServiceTypeId;
						}
						if ($isKmTravelledChanged) {
							$activity->km_changed_on_level = 3;
						}
						if ($isNotCollectedChanged) {
							$activity->not_collected_amount_changed_on_level = 3;
						}
						if ($isCollectedChanged) {
							$activity->collected_amount_changed_on_level = 3;
						}
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					} elseif (Auth::user()->activity_approval_level_id == 4) {
						// L4
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '4';
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					}
				}
			} else {
				$activityStatusId = 11; //Waiting for Invoice Generation by ASP
				$isApproved = true;
				$approver = '1';
				if (Auth::user()->activity_approval_level_id == 1) {
					$approver = '1';
				} elseif (Auth::user()->activity_approval_level_id == 2) {
					$approver = '2';
				} elseif (Auth::user()->activity_approval_level_id == 3) {
					$approver = '3';
				} elseif (Auth::user()->activity_approval_level_id == 4) {
					$approver = '4';
				}
				$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
			}

			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
			$breakdownAlertSent = Activity::breakdownAlertSent($activity->id);
			$disableWhatsappAutoApproval = config('rsa')['DISABLE_WHATSAPP_AUTO_APPROVAL'];

			// WHATSAPP FLOW (TOW SERVICE || ROS SERVICE WITH DISABLE AUTO APPROVAL)
			if ($breakdownAlertSent && $sendBreakdownOrEmptyreturnChargesWhatsappSms && $activity->asp && !empty($activity->asp->whatsapp_number) && ($activity->data_src_id == 260 || $activity->data_src_id == 261) && $activity->serviceType && !empty($activity->serviceType->service_group_id) && ($activity->serviceType->service_group_id == 3 || ($activity->serviceType->service_group_id != 3 && $disableWhatsappAutoApproval)) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {

				$activityStatusId = 25; // Waiting for Charges Acceptance by ASP

				// SEND BREAKDOWN OR EMPTY RETURN CHARGES WHATSAPP SMS TO ASP (TOWING SERVICE ONLY)
				$chargesSmsAlreadySent = ActivityWhatsappLog::where('activity_id', $activity->id)
					->whereIn('type_id', [1193, 1194])
					->first();
				if ($chargesSmsAlreadySent) {
					// SEND REVISED BREAKDOWN OR EMPTY RETURN CHARGES
					$activity->sendRevisedBreakdownOrEmptyreturnChargesWhatsappSms();
				} else {
					// SEND BREAKDOWN OR EMPTY RETURN CHARGES
					$activity->sendBreakdownOrEmptyreturnChargesWhatsappSms();
				}
			} else {
				//NORMAL FLOW

				if ($isApproved) {
					$this->updateActivityApprovalLog($activity, $request->case_number, 1);
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
			} elseif ($approver == '4') {
				//L4
				$activityLog->l4_approved_at = Carbon::now();
				$activityLog->l4_approved_by_id = Auth::id();
			}
			$activityLog->updated_by_id = Auth::id();
			$activityLog->updated_at = Carbon::now();
			$activityLog->save();

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

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
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function sendApprovalNoty($approvers, $caseNumber, $notyMessageTemplate) {
		$sendApprovalNotification = config('rsa.SEND_APPROVAL_NOTIFICATION');
		if (!empty($approvers) && $sendApprovalNotification) {
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

		$aspRateCard = Activity::getAspServiceRateCardByAmendment($activity->asp_id, $activity->case->date, $activity->service_type_id, $isMobile);
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

			$activities = Activity::whereIn('id', $request->activity_ids)
				->whereIn('status_id', [5, 8, 18, 20, 23])
				->get();
			if ($activities->isEmpty()) {
				return response()->json([
					'success' => false,
					'error' => 'Activities not found',
				]);
			}

			$l2Approvers = User::where('activity_approval_level_id', 2)->pluck('id');
			$l3Approvers = User::where('activity_approval_level_id', 3)->pluck('id');
			$l4Approvers = User::where('activity_approval_level_id', 4)->pluck('id');
			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
			$disableWhatsappAutoApproval = config('rsa')['DISABLE_WHATSAPP_AUTO_APPROVAL'];

			foreach ($activities as $key => $activity) {

				$saveActivityRatecardResponse = $activity->saveActivityRatecard();
				if (!$saveActivityRatecardResponse['success']) {
					return response()->json([
						'success' => false,
						'error' => $saveActivityRatecardResponse['error'],
					]);
				}

				$isMobile = 0; //WEB
				//MOBILE APP
				if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
					$isMobile = 1;
				}

				$aspServiceType = Activity::getAspServiceRateCardByAmendment($activity->asp_id, $activity->case->date, $activity->service_type_id, $isMobile);

				if ($aspServiceType) {
					// $bo_km_charge = $activity->detail(172) ? $activity->detail(172)->value : 0;
					$bo_km_travelled = $activity->detail(158) ? numberFormatToDecimalConversion(floatval($activity->detail(158)->value)) : 0;
					$bo_km_collected = $activity->detail(159) ? numberFormatToDecimalConversion(floatval($activity->detail(159)->value)) : 0;
					$bo_km_not_collected = $activity->detail(160) ? numberFormatToDecimalConversion(floatval($activity->detail(160)->value)) : 0;

					$boWaitingTime = 0;
					if ($activity->detail(330) && !empty($activity->detail(330)->value)) {
						$boWaitingTime = floatval($activity->detail(330)->value);
					} elseif ($activity->detail(279) && !empty($activity->detail(279)->value)) {
						$boWaitingTime = floatval($activity->detail(279)->value);
					}

					if (floatval($bo_km_travelled) <= 0) {
						return response()->json([
							'success' => false,
							'error' => 'KM travelled should be greater than zero for the case - ' . $activity->case->number,
						]);
					}

					$response = getActivityKMPrices($activity->serviceType, $activity->asp, $activity->data_src_id, $activity->case->date);
					if (!$response['success']) {
						return response()->json([
							'success' => false,
							'error' => $response['error'] . '. Case - ' . $activity->case->number,
						]);
					}

					$price = $response['asp_service_price'];

					$boWaitingCharge = 0;
					if (!empty($price->waiting_charge_per_hour) && !empty($boWaitingTime)) {
						$boWaitingCharge = numberFormatToDecimalConversion(floatval($boWaitingTime / 60) * floatval($price->waiting_charge_per_hour));
					}

					if ($activity->financeStatus->po_eligibility_type_id == 341) {
						// Empty Return Payout
						$below_range_price = $bo_km_travelled == 0 ? 0 : $price->empty_return_range_price;
					} else {
						$below_range_price = $bo_km_travelled == 0 ? 0 : $price->below_range_price;
					}

					$above_range_price = ($bo_km_travelled > $price->range_limit) ? ($bo_km_travelled - $price->range_limit) * $price->above_range_price : 0;
					$km_charge = numberFormatToDecimalConversion(floatval($below_range_price + $above_range_price));

					$boDeduction = 0;
					//DISABLED AS THERE IS NO ADJUSTMENT TYPE IN FUTURE
					// if ($aspServiceType->adjustment_type == 2) {
					// 	$boDeduction = floatval($aspServiceType->adjustment);
					// } else if ($aspServiceType->adjustment_type == 1) {
					// 	$boDeduction = floatval($km_charge) * floatval($aspServiceType->adjustment / 100);
					// }

					$invoiceAmount = numberFormatToDecimalConversion(floatval(($km_charge + $bo_km_not_collected + $boWaitingCharge) - ($boDeduction + $bo_km_collected)));

					if (floatval($invoiceAmount) <= 0) {
						return response()->json([
							'success' => false,
							'error' => 'Payout amount should be greater than zero for the case - ' . $activity->case->number,
						]);
					}

					$bo_waiting_time = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 330,
					]);
					$bo_waiting_time->value = $boWaitingTime;
					$bo_waiting_time->save();

					$bo_waiting_charge = ActivityDetail::firstOrNew([
						'company_id' => 1,
						'activity_id' => $activity->id,
						'key_id' => 333,
					]);
					$bo_waiting_charge->value = $boWaitingCharge;
					$bo_waiting_charge->save();

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

					$sendBreakdownOrEmptyreturnChargesWhatsappSms = false;
					//L2, L3, and L4 approver flow should be effective from April 2022 cases not for all the cases - By Sundhar / Hyder
					if (date('Y-m-d', strtotime($activity->case->date)) >= "2022-04-01") {
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
								$activityStatusId = 23; //Waiting for L4 Bulk Verification
								$approver = '3';
								$this->sendApprovalNoty($l4Approvers, $activity->case->number, "L4_APPROVAL");
							} elseif (Auth::user()->activity_approval_level_id == 4) {
								// L4
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '4';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							}
						} elseif (floatval($invoiceAmount) > 6000 && floatval($invoiceAmount) <= 10000) {
							//GREATER THAN 6000 AND LESSER THAN OR EQUAL TO 10000
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
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							} elseif (Auth::user()->activity_approval_level_id == 4) {
								// L4
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '4';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							}
						} elseif (floatval($invoiceAmount) > 4000 && floatval($invoiceAmount) <= 6000) {
							//GREATER THAN 4000 AND LESSER THAN OR EQUAL TO 6000
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
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							} elseif (Auth::user()->activity_approval_level_id == 3) {
								// L3
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '3';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							} elseif (Auth::user()->activity_approval_level_id == 4) {
								// L4
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '4';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
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
									$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
								}
								$approver = '1';
							} elseif (Auth::user()->activity_approval_level_id == 2) {
								// L2
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '2';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							} elseif (Auth::user()->activity_approval_level_id == 3) {
								// L3
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '3';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							} elseif (Auth::user()->activity_approval_level_id == 4) {
								// L4
								$activityStatusId = 11; //Waiting for Invoice Generation by ASP
								$isApproved = true;
								$approver = '4';
								$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
							}
						}
					} else {
						$activityStatusId = 11; //Waiting for Invoice Generation by ASP
						$isApproved = true;
						$approver = '1';
						if (Auth::user()->activity_approval_level_id == 1) {
							$approver = '1';
						} elseif (Auth::user()->activity_approval_level_id == 2) {
							$approver = '2';
						} elseif (Auth::user()->activity_approval_level_id == 3) {
							$approver = '3';
						} elseif (Auth::user()->activity_approval_level_id == 4) {
							$approver = '4';
						}
						$sendBreakdownOrEmptyreturnChargesWhatsappSms = true;
					}

					$activityBreakdownAlertSent = Activity::breakdownAlertSent($activity->id);

					// WHATSAPP FLOW (TOW SERVICE || ROS SERVICE WITH DISABLE AUTO APPROVAL)
					if ($activityBreakdownAlertSent && $sendBreakdownOrEmptyreturnChargesWhatsappSms && $activity->asp && !empty($activity->asp->whatsapp_number) && ($activity->data_src_id == 260 || $activity->data_src_id == 261) && $activity->serviceType && !empty($activity->serviceType->service_group_id) && ($activity->serviceType->service_group_id == 3 || ($activity->serviceType->service_group_id != 3 && $disableWhatsappAutoApproval)) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {

						$activityStatusId = 25; // Waiting for Charges Acceptance by ASP

						//SEND BREAKDOWN OR EMPTY RETURN CHARGES WHATSAPP SMS TO ASP (TOWING SERVICE ONLY)
						$chargesSmsAlreadySent = ActivityWhatsappLog::where('activity_id', $activity->id)
							->whereIn('type_id', [1193, 1194])
							->first();
						if ($chargesSmsAlreadySent) {
							// SEND REVISED BREAKDOWN OR EMPTY RETURN CHARGES
							$activity->sendRevisedBreakdownOrEmptyreturnChargesWhatsappSms();
						} else {
							// SEND BREAKDOWN OR EMPTY RETURN CHARGES
							$activity->sendBreakdownOrEmptyreturnChargesWhatsappSms();
						}
					} else {
						// NORMAL FLOW

						if ($isApproved) {
							$this->updateActivityApprovalLog($activity, $activity->case->number, 2);
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
					} elseif ($approver == '4') {
						//L4
						$activityLog->l4_approved_at = Carbon::now();
						$activityLog->l4_approved_by_id = Auth::id();
					}
					$activityLog->updated_by_id = Auth::id();
					$activityLog->updated_at = Carbon::now();
					$activityLog->save();

					//SAVE ACTIVITY REPORT FOR DASHBOARD
					ActivityReport::saveReport($activity->id);

				} else {
					return response()->json([
						'success' => false,
						'error' => "ASP Rate card not available for the case : " . $activity->case->number,
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
				'error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
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

			$errorMessages = [
				'defer_reason.regex' => "Equal symbol (=) is not allowed as the first character for defer reason!",
			];

			$validator = Validator::make($request->all(), [
				'activity_id' => [
					'required',
					'integer',
					'exists:activities,id',
				],
				'defer_reason' => [
					'required',
					'string',
					'regex:/^[^=]/',
				],
			], $errorMessages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$activity = Activity::whereIn('status_id', [6, 9, 19, 21, 22, 24, 5, 8, 18, 20, 23])
				->where('id', $request->activity_id)
				->first();

			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
					],
				]);
			}

			$eligleForAspReEntry = false;
			$deferReason = $activity->defer_reason;
			//L1
			if (Auth::user()->activity_approval_level_id == 1) {
				$activityStatusId = 7; //BO Rejected - Waiting for ASP Data Re-Entry
				$eligleForAspReEntry = true;
				if (!empty($deferReason)) {
					$deferReason .= nl2br("<hr> L1 Approver : " . makeUrltoLinkInString($request->defer_reason));
				} else {
					$deferReason = "L1 Approver : " . makeUrltoLinkInString($request->defer_reason);
				}
				$activity->service_type_changed_on_level = NULL;
				$activity->l1_changed_service_type_id = NULL;
				$activity->l2_changed_service_type_id = NULL;
				$activity->l3_changed_service_type_id = NULL;
				$activity->km_changed_on_level = NULL;
				$activity->not_collected_amount_changed_on_level = NULL;
				$activity->collected_amount_changed_on_level = NULL;
			} elseif (Auth::user()->activity_approval_level_id == 2) {
				// L2
				$activityStatusId = 22; //BO Rejected - Waiting for L1 Individual Verification
				if (!empty($deferReason)) {
					$deferReason .= nl2br("<hr> L2 Approver : " . makeUrltoLinkInString($request->defer_reason));
				} else {
					$deferReason = "L2 Approver : " . makeUrltoLinkInString($request->defer_reason);
				}
				$activity->l2_changed_service_type_id = NULL;
			} elseif (Auth::user()->activity_approval_level_id == 3) {
				// L3
				$activityStatusId = 22; //BO Rejected - Waiting for L1 Individual Verification
				if (!empty($deferReason)) {
					$deferReason .= nl2br("<hr> L3 Approver : " . makeUrltoLinkInString($request->defer_reason));
				} else {
					$deferReason = "L3 Approver : " . makeUrltoLinkInString($request->defer_reason);
				}
				$activity->l3_changed_service_type_id = NULL;
			} elseif (Auth::user()->activity_approval_level_id == 4) {
				// L4
				$activityStatusId = 22; //BO Rejected - Waiting for L1 Individual Verification
				if (!empty($deferReason)) {
					$deferReason .= nl2br("<hr> L4 Approver : " . makeUrltoLinkInString($request->defer_reason));
				} else {
					$deferReason = "L4 Approver : " . makeUrltoLinkInString($request->defer_reason);
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
			} elseif (Auth::user()->activity_approval_level_id == 4) {
				// L4
				$activityLog->l4_deffered_at = date('Y-m-d H:i:s');
				$activityLog->l4_deffered_by_id = Auth::id();
			}
			$activityLog->updated_by_id = Auth::id();
			$activityLog->updated_at = Carbon::now();
			$activityLog->save();

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

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
		//dd($request->all());
		$number = str_replace(' ', '', $request->number);
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

		$aspIds = [];
		//ASP FINANCE ADMIN
		if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
			$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
			$aspIds[] = Auth::user()->asp->id;
		} else {
			$aspIds[] = Auth::user()->asp->id;
		}

		//CHECK TICKET EXIST WITH DATA ENTRY STATUS & DATE FOR ASP
		$query = Activity::select([
			'activities.id as id',
			'activities.activity_status_id',
			'activities.status_id',
			'cases.created_at as case_created_at',
			// 'cases.date as case_date',
			DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y") as case_date'),
			'cases.number as case_number',
			'cases.submission_closing_date',
		])
			->join('cases', 'cases.id', 'activities.case_id')
			->where(function ($q) use ($number) {
				$q->where('cases.number', $number)
					->orWhere('cases.vehicle_registration_number', $number)
					->orWhere('activities.crm_activity_id', $number);
			});

		$caseExistQuery = clone $query;
		$case = $caseExistQuery->whereIn('activities.asp_id', $aspIds)
			->orderBy('activities.id', 'ASC')
			->first();
		if ($case && !empty($case->submission_closing_date)) {
			$submission_closing_extended = true;
		}

		$query1 = clone $query;
		$tickets = $query1->whereIn('activities.status_id', [2, 4, 17]) // WAITING FOR ASP DATA ENTRY AND ON HOLD
			->whereIn('activities.asp_id', $aspIds)
			->whereNull('activities.is_asp_data_entry_done') //FOR ONHOLD STATUS PURPOSE
			->orderBy('activities.id', 'ASC')
			->get();
		if ($tickets->isNotEmpty()) {
			foreach ($tickets as $key => $ticketValue) {
				//CASE WITH EXTENSION
				if (!empty($ticketValue->submission_closing_date)) {
					if ($ticketValue->submission_closing_date >= date('Y-m-d H:i:s')) {
						return response()->json([
							'success' => true,
							'activity_id' => $ticketValue->id,
						]);
					}
				} else {
					if ($ticketValue->case_created_at >= $threeMonthsBefore) {
						return response()->json([
							'success' => true,
							'activity_id' => $ticketValue->id,
						]);
					}
				}
			}
		}

		//CHECK TICKET EXIST
		$query2 = clone $query;
		$ticket_exist = $query2->first();

		if ($ticket_exist) {

			$query3 = clone $query;

			//CHECK TICKET IS BELONGS TO ASP
			$asp_has_activity = $query3->whereIn('activities.asp_id', $aspIds)->first();
			if (!$asp_has_activity) {
				//ASP FINANCE ADMIN
				if (Auth::user()->asp->is_finance_admin == 1) {
					$errorMessage = "The ticket is not attended by your ASP as per CRM";
				} else {
					$errorMessage = "The ticket is not attended by " . Auth::user()->asp->asp_code . " as per CRM";
				}
				return response()->json([
					'success' => false,
					'errors' => [
						$errorMessage,
					],
				]);
			} else {

				//Restriction disable - temporarily for June 2020 & July 2020 tickets
				$sub_query = clone $query;
				$tickets = $sub_query->addSelect([
					'cases.created_at',
				])
					->whereIn('activities.status_id', [2, 4])
					->whereIn('activities.asp_id', $aspIds)
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
					->whereIn('activities.asp_id', $aspIds)
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
					->whereNotNull('activities.is_asp_data_entry_done')
					->whereIn('activities.asp_id', $aspIds)
					->first();
				if ($activity_on_hold) {
					// $activityOnHoldError = "Ticket On Hold";
					// if ($activity_on_hold->activityStatus) {
					// 	$activityOnHoldError = "Ticket On Hold. Activity status : " . $activity_on_hold->activityStatus->name;
					// }
					$activityOnHoldError = "Ticket already submitted. Case : " . $activity_on_hold->case_number . "(" . $activity_on_hold->case_date . ")";
					if ($activity_on_hold->activityStatus) {
						$activityOnHoldError = "Ticket already submitted. Case : " . $activity_on_hold->case_number . "(" . $activity_on_hold->case_date . "), Activity status : " . $activity_on_hold->activityStatus->name;
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
					->whereIn('activities.asp_id', $aspIds)
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
					->whereIn('activities.status_id', [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 18, 19, 20, 21, 22, 26])
					->whereIn('activities.asp_id', $aspIds)
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
					->whereIn('activities.asp_id', $aspIds)
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
					->whereIn('activities.asp_id', $aspIds)
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
	public function activityNewGetFormData($id = NULL) {
		$for_deffer_activity = 0;
		$this->data = Activity::getFormData($id, $for_deffer_activity);
		$this->data['case_details'] = $this->data['activity']->case;

		//CHECK IF TICKET DATE IS GREATER THAN 3 MONTHS OLDER ----------------------------------------------

		$today = date('Y-m-d H:i:s');
		$threeMonthsBefore = date('Y-m-d H:i:s', strtotime("-3 months", strtotime($today)));
		$errorMessage = "Please contact administrator.";
		if ($this->data['activity']->activityStatus) {
			$errorMessage = "Please contact administrator. Activity status : " . $this->data['activity']->activityStatus->name;
		}

		if ($this->data['activity']->case && !empty($this->data['activity']->case->submission_closing_date)) {
			if (Carbon::parse($this->data['activity']->case->submission_closing_date)->format('Y-m-d H:i:s') < $today) {
				return response()->json([
					'success' => false,
					'error' => $errorMessage,
				]);
			}
		} else {
			if ($this->data['activity']->case && Carbon::parse($this->data['activity']->case->created_at)->format('Y-m-d H:i:s') < $threeMonthsBefore) {
				return response()->json([
					'success' => false,
					'error' => $errorMessage,
				]);
			}
		}

		if (date('Y-m-d') >= "2022-04-01") {
			$towingAttachmentsMandatoryLabel = '';
		} elseif (date('Y-m-d') > "2022-02-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st April onwards)';
		} elseif (date('Y-m-d') > "2022-01-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st February onwards)';
		} else {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st January onwards)';
		}
		$this->data['towingAttachmentsMandatoryLabel'] = $towingAttachmentsMandatoryLabel;
		return response()->json($this->data);
	}

	//GET SERVICE TYPE DETAIL BASED ON SERVICE TYPE CHANGE IN ASP DATA ENTRY
	public function activityNewGetServiceTypeDetail($id, $activityId) {
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
						'Service not found',
					],
				]);
			}

			$activity = Activity::find($activityId);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Ticket not found',
					],
				]);
			}

			$isMobile = 0; //WEB
			//MOBILE APP
			if ($activity->data_src_id == 260 || $activity->data_src_id == 263) {
				$isMobile = 1;
			}

			$range_limit = 0;
			$aspServiceType = Activity::getAspServiceRateCardByAmendment($activity->asp_id, $activity->case->date, $id, $isMobile);
			if ($aspServiceType) {
				$range_limit = $aspServiceType->range_limit;
			}

			// UPDATE TOWING ATTACHMENT AS MANDATORY IF TOWING SERVICE AND MATURED FINANCE STATUS
			if ($serviceType->service_group_id == 3 && $activity->financeStatus && $activity->financeStatus->po_eligibility_type_id == 340) {
				$activity->is_towing_attachments_mandatory = 1;
				$activity->towing_attachments_mandatory_by_id = Auth::user()->id;
				$activity->save();
			} else {
				$activity->is_towing_attachments_mandatory = 0;
				$activity->towing_attachments_mandatory_by_id = NULL;
				$activity->save();
			}

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

			return response()->json([
				'success' => true,
				'range_limit' => $range_limit,
				'serviceType' => $serviceType,
				'activity' => $activity->load([
					'case',
					'financeStatus',
				]),
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
			$activity = Activity::whereIn('status_id', [2, 4, 7, 17])
				->where('id', $request->activity_id)
				->first();
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Activity not found',
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

			// Check if the first character is a special character
			if (isset($request->remarks_not_collected) && !empty($request->remarks_not_collected) && !preg_match('/^[^=]/', $request->remarks_not_collected)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Equal symbol (=) is not allowed as the first character for remarks for charges not collected!',
					],
				]);
			}

			if (isset($request->general_remarks) && !empty($request->general_remarks) && !preg_match('/^[^=]/', $request->general_remarks)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Equal symbol (=) is not allowed as the first character for general remarks!',
					],
				]);
			}

			if (isset($request->comments) && !empty($request->comments) && !preg_match('/^[^=]/', $request->comments)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Equal symbol (=) is not allowed as the first character for resolve comments!',
					],
				]);
			}

			$enteredServiceType = ServiceType::select([
				'id',
				'service_group_id',
			])
				->where('id', $request->asp_service_type_id)
				->first();
			if (!$enteredServiceType) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Service not found',
					],
				]);
			}
			$checkTowingAttachmentMandatory = false;
			//TOWING GROUP
			if ($enteredServiceType->service_group_id == 3 && $activity->is_towing_attachments_mandatory == 1 && $activity->financeStatus && $activity->financeStatus->po_eligibility_type_id == 340) {
				$towingImagesMandatoryEffectiveDate = config('rsa.TOWING_IMAGES_MANDATORY_EFFECTIVE_DATE');
				if (date('Y-m-d', strtotime($activity->case->date)) >= $towingImagesMandatoryEffectiveDate) {
					$checkTowingAttachmentMandatory = true;
				}
			}
			if ($checkTowingAttachmentMandatory) {
				// Vehicle Pickup image
				if (!isset($request->vehiclePickupAttachExist) && (!isset($request->vehicle_pickup_attachment) || (isset($request->vehicle_pickup_attachment) && empty($request->vehicle_pickup_attachment)))) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please Upload Vehicle Pickup image',
						],
					]);
				}
				// Vehicle Pickup image
				if (!isset($request->vehicleDropAttachExist) && (!isset($request->vehicle_drop_attachment) || (isset($request->vehicle_drop_attachment) && empty($request->vehicle_drop_attachment)))) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please Upload Vehicle Drop image',
						],
					]);
				}
				// Vehicle Pickup image
				if (!isset($request->inventoryJobSheetAttachExist) && (!isset($request->inventory_job_sheet_attachment) || (isset($request->inventory_job_sheet_attachment) && empty($request->inventory_job_sheet_attachment)))) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please Upload Inventory Job Sheet image',
						],
					]);
				}
			}

			if (isset($request->vehicle_pickup_attachment) && !empty($request->vehicle_pickup_attachment)) {
				$extension = $request->file("vehicle_pickup_attachment")->getClientOriginalExtension();
				if ($extension != 'jpeg' && $extension != 'jpg' && $extension != 'png') {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please Upload Vehicle Pickup image in jpeg, png, jpg formats',
						],
					]);
				}
			}

			if (isset($request->vehicle_drop_attachment) && !empty($request->vehicle_drop_attachment)) {
				$extension = $request->file("vehicle_drop_attachment")->getClientOriginalExtension();
				if ($extension != 'jpeg' && $extension != 'jpg' && $extension != 'png') {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please Upload Vehicle Drop image in jpeg, png, jpg formats',
						],
					]);
				}
			}

			if (isset($request->inventory_job_sheet_attachment) && !empty($request->inventory_job_sheet_attachment)) {
				$extension = $request->file("inventory_job_sheet_attachment")->getClientOriginalExtension();
				if ($extension != 'jpeg' && $extension != 'jpg' && $extension != 'png') {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please Upload Inventory Job Sheet image in jpeg, png, jpg formats',
						],
					]);
				}
			}

			$range_limit = $waiting_charge_per_hour = 0;
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

			$aspServiceType = Activity::getAspServiceRateCardByAmendment($activity->asp_id, $activity->case->date, $request->asp_service_type_id, $isMobile);
			if ($aspServiceType) {
				$range_limit = $aspServiceType->range_limit;
				$waiting_charge_per_hour = $aspServiceType->waiting_charge_per_hour;
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
				//$status = $request->file("vehicle_pickup_attachment")->storeAs($destination, $filename . '.' . $extension);
				$img = Image::make($request->file("vehicle_pickup_attachment")->getRealPath());
				$status = $img->resize(1500, 788, function ($constraint) {
					$constraint->aspectRatio();
				})->save(\storage_path('app/uploads/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $filename . '.' . $extension));
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
				//$status = $request->file("vehicle_drop_attachment")->storeAs($destination, $filename . '.' . $extension);
				$img = Image::make($request->file("vehicle_drop_attachment")->getRealPath());
				$status = $img->resize(1500, 788, function ($constraint) {
					$constraint->aspectRatio();
				})->save(\storage_path('app/uploads/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $filename . '.' . $extension));
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
				//$status = $request->file("inventory_job_sheet_attachment")->storeAs($destination, $filename . '.' . $extension);
				$img = Image::make($request->file("inventory_job_sheet_attachment")->getRealPath());
				$status = $img->resize(1500, 788, function ($constraint) {
					$constraint->aspectRatio();
				})->save(\storage_path('app/uploads/attachments/ticket/asp/ticket-' . $activity->id . '/asp-' . $activity->asp_id . '/service-' . $activity->service_type_id . '/' . $filename . '.' . $extension));
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
								'errors' => [
									'Please attach google map screenshot',
								],
							]);
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

						$is_bulk = false;

					}
				}
			}

			//LOGIC SAID BY CLIENT
			if (floatval($asp_other) >= 31) {
				if (!isset($request->other_attachment_exist) && empty($request->other_attachment)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please attach other Attachment',
						],
					]);
				}
				if (empty($request->remarks_not_collected)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Please enter remarks comments for not collected',
						],
					]);
				}
			}

			//checking ASP KMs exceed ASP service type range limit
			if ($asp_km > $range_limit) {
				$is_bulk = false;
			}

			//checking MIS and ASP not collected
			if ($asp_other > $not_collect_charges) {
				$is_bulk = false;
			}

			//checking MIS and ASP collected
			$asp_collected_charges = empty($request->asp_collected_charges) ? 0 : $request->asp_collected_charges;
			if ($asp_collected_charges < $this->data['activities']['cc_colleced_amount']) {
				$is_bulk = false;
			}

			if (floatval($mis_km == 0)) {
				$is_bulk = false;
			}

			$sendNoty = false;
			//NOT ONHOLD TICKETS
			if ($activity->status_id != 17) {
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
				$sendNoty = true;
			} else {
				// ONHOLD TICKETS
				$activity->status_id = 26; //ASP Completed Data Entry - Waiting for Call Center Data Entry
			}

			$activity->is_asp_data_entry_done = 1;
			$activity->service_type_id = $request->asp_service_type_id;

			if (!empty($request->comments)) {
				//$activity->comments = $request->comments;
				$activity->asp_resolve_comments = makeUrltoLinkInString($request->comments);
			}

			if (floatval($asp_other) >= 31) {
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

				if (!empty($request->remarks_not_collected)) {
					$activity->remarks = makeUrltoLinkInString(strip_tags($request->remarks_not_collected));
				}
			}

			if (!empty($request->general_remarks)) {
				$activity->general_remarks = makeUrltoLinkInString($request->general_remarks);
			}
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

			$waitingCharge = 0;
			$waitingTimeInMin = 0;
			if (!empty($request->waiting_time)) {
				[$hours, $minutes] = explode(':', $request->waiting_time);
				$waitingTimeInMin = intval(($hours * 60) + $minutes);
				$waitingCharge = numberFormatToDecimalConversion(floatval($waitingTimeInMin / 60) * floatval($waiting_charge_per_hour));
			}

			$kmTravelled = numberFormatToDecimalConversion(floatval($request->km_travelled)); //ASP ENTERED KM
			$collected = numberFormatToDecimalConversion(floatval($request->asp_collected_charges)); //ASP COLLECTED
			$not_collected = numberFormatToDecimalConversion(floatval($request->other_charge)); //ASP NOT COLLECTED
			$aspBorderCharge = numberFormatToDecimalConversion(floatval($request->border_charge));
			$aspGreenTaxCharge = numberFormatToDecimalConversion(floatval($request->green_tax_charge));
			$aspTollCharge = numberFormatToDecimalConversion(floatval($request->toll_charge));
			$aspEatableItemCharge = numberFormatToDecimalConversion(floatval($request->eatable_item_charge));
			$aspFuelCharge = numberFormatToDecimalConversion(floatval($request->fuel_charge));
			$aspWaitingTime = floatval($waitingTimeInMin);

			//UPDATE ASP ACTIVITY DETAILS & CALCULATE INVOICE AMOUNT FOR ASP & BO BASED ON ASP ENTERTED DETAILS
			$asp_key_ids = [
				//ASP
				157 => $activity->serviceType->name,
				154 => $kmTravelled,
				156 => $not_collected,
				155 => $collected,

				//ASP OTHER CHARGES (SPLIT UPs)
				316 => $aspBorderCharge,
				315 => $aspGreenTaxCharge,
				314 => $aspTollCharge,
				313 => $aspEatableItemCharge,
				319 => $aspFuelCharge,
				329 => $aspWaitingTime,
				332 => $waitingCharge,

				//BO
				161 => $activity->serviceType->name,
				158 => $kmTravelled,
				160 => $not_collected,
				159 => $collected,

				//BO OTHER CHARGES (SPLIT UPs)
				325 => $aspBorderCharge,
				324 => $aspGreenTaxCharge,
				323 => $aspTollCharge,
				322 => $aspEatableItemCharge,
				328 => $aspFuelCharge,
				330 => $aspWaitingTime,
				333 => $waitingCharge,

			];
			foreach ($asp_key_ids as $key_id => $value) {
				$var_key_val = DB::table('activity_details')->updateOrInsert(['activity_id' => $activity->id, 'key_id' => $key_id, 'company_id' => 1], ['value' => $value]);
			}

			$response = getActivityKMPrices($activity->serviceType, $activity->asp, $activity->data_src_id, $activity->case->date);
			if (!$response['success']) {
				return response()->json([
					'success' => false,
					'errors' => [
						$response['error'],
					],
				]);
			}

			$price = $response['asp_service_price'];
			//INV AMOUNT FORMULA
			if ($activity->financeStatus->po_eligibility_type_id == 341) {
				// Empty Return Payout
				$below_range_price = $kmTravelled == 0 ? 0 : $price->empty_return_range_price;
			} else {
				$below_range_price = $kmTravelled == 0 ? 0 : $price->below_range_price;
			}

			$above_range_price = ($kmTravelled > $price->range_limit) ? ($kmTravelled - $price->range_limit) * $price->above_range_price : 0;
			$km_charge = numberFormatToDecimalConversion(floatval($below_range_price + $above_range_price));

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
			$net_amount = numberFormatToDecimalConversion(floatval(($payout_amount + $not_collected + $waitingCharge) - $collected));
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

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

			$sendApprovalNotification = config('rsa.SEND_APPROVAL_NOTIFICATION');
			if ($sendNoty && $sendApprovalNotification) {
				//sending confirmation SMS to ASP
				$mobile_number = $activity->asp->contact_number1;
				$sms_message = 'Tkt uptd successfully';
				$array = [$activity->case->number];
				// sendSMS2($sms_message, $mobile_number, $array, NULL);

				//sending notification to all ASP STATE MAPPED BO users
				//$bo_users = User::where('users.role_id', 6)->pluck('users.id'); //6 - Bo User role ID
				$state_id = $activity->asp->state_id;
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
			}
			DB::commit();
			return response()->json(['success' => true]);
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
			->where(function ($q) {
				// FINANCE ADMIN
				if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
					$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
					$aspIds[] = Auth::user()->asp->id;
					$q->whereIn('asps.id', $aspIds);
				} else {
					$q->where('users.id', Auth::id());
				}
			})
			->where('activities.status_id', 7) //BO Rejected - Waiting for ASP Data Re-Entry
			->groupBy('activities.id')
			->orderBy('cases.date', 'DESC')
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
		if (date('Y-m-d') >= "2022-04-01") {
			$towingAttachmentsMandatoryLabel = '';
		} elseif (date('Y-m-d') > "2022-02-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st April onwards)';
		} elseif (date('Y-m-d') > "2022-01-01") {
			$towingAttachmentsMandatoryLabel = '(This field is mandatory from 1st February onwards)';
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
			->where(function ($q) {
				// FINANCE ADMIN
				if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
					$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
					$aspIds[] = Auth::user()->asp->id;
					$q->whereIn('asps.id', $aspIds);
				} else {
					$q->where('users.id', Auth::id());
				}
			})
			->whereIn('activities.status_id', [11, 1]) //Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
			->groupBy('activities.id')
			->orderBy('cases.date', 'ASC')
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
		return Activity::getEncryptionKey($request);
	}

	public function getActivityApprovedDetails($encryption_key = '') {
		return Activity::getApprovedDetails($encryption_key);
	}

	public function generateInvoice(Request $request) {
		//dd($request->all());
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
			$asp = ASP::where('id', Auth::user()->asp->id)->first();
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
				->whereIn('status_id', [11, 1]) //Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
				->get();

			//CUSTOM VALIDATION SAID BY BUSINESS TEAM
			$aug21ToNov21caseExist = false;
			$afterDec21caseExist = false;

			if ($activities->isNotEmpty()) {
				foreach ($activities as $key => $activity) {
					//FINANCE ADMIN
					if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
						//CHECK ASP MATCHES WITH ACTIVITY ASP
						$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
						$aspIds[] = Auth::user()->asp->id;
						if (!in_array($activity->asp_id, $aspIds)) {
							return response()->json([
								'success' => false,
								'error' => 'ASP not matched for activity ID ' . $activity->crm_activity_id,
							]);
						}
					} else {
						//CHECK ASP MATCHES WITH ACTIVITY ASP
						if ($activity->asp_id != $asp->id) {
							return response()->json([
								'success' => false,
								'error' => 'ASP not matched for activity ID ' . $activity->crm_activity_id,
							]);
						}
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

				//SPECIAL CHARACTERS NOT ALLOWED AT PREFIX
				if (!preg_match("/^[A-Za-z0-9]{1}/", $request->invoice_no)) {
					return response()->json([
						'success' => false,
						'error' => 'Special characters are not allowed at the beginning of the invoice number',
					]);
				}

				//SPECIAL CHARACTERS NOT ALLOWED AT SUFFIX
				if (!preg_match("/[A-Za-z0-9]{1}$/", $request->invoice_no)) {
					return response()->json([
						'success' => false,
						'error' => 'Special characters are not allowed at the end of the invoice number',
					]);
				}

				if (isset($request->irn) && !empty($request->irn) && strlen($request->irn) != '64') {
					return response()->json([
						'success' => false,
						'error' => 'Please enter at least 64 characters for IRN',
					]);
				}

				$invoice_no = $request->invoice_no;
				$irn = (isset($request->irn) && !empty($request->irn)) ? $request->irn : NULL;
				$invoice_date = date('Y-m-d H:i:s', strtotime($request->inv_date));
			} else {
				//SYSTEM
				//GENERATE INVOICE NUMBER
				$invoice_no = generateInvoiceNumber();
				$invoice_date = new Carbon();
				$irn = NULL;
			}

			$invoice_c = Invoices::createInvoice($asp, $request->crm_activity_ids, $invoice_no, $irn, $invoice_date, $value, false);
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
				'remarks.regex' => "Equal symbol (=) is not allowed as the first character for remarks!",
			];
			$validator = Validator::make($r->all(), [
				'closing_date' => [
					'required:true',
				],
				'remarks' => [
					'required:true',
					'regex:/^[^=]/',
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

			//IF ACTIVITY PORTAL STATUS IS LAPSED THEN CHANGE STATUS
			if ($activity->status_id == 27) {
				//LAPSED
				$activity->status_id = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
				$activity->save();
			}

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

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

	public function moveToNotEligibleForPayout(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$errorMessages = [
				'not_eligible_reason.regex' => "Equal symbol (=) is not allowed as the first character for reason!",
			];
			$validator = Validator::make($request->all(), [
				'activity_id' => [
					'required',
					'integer',
					'exists:activities,id',
				],
				'not_eligible_reason' => [
					'required',
					'string',
					'regex:/^[^=]/',
				],
			], $errorMessages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$activity = Activity::withTrashed()->whereNotIn('status_id', [12, 13, 14, 15, 16])
				->where('id', $request->activity_id)
				->first();
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Ticket not found',
					],
				]);
			}
			$activity->not_eligible_moved_by_id = Auth::user()->id;
			$activity->not_eligible_moved_at = Carbon::now();
			$exceptionalReason = $activity->exceptional_reason;
			if (!empty($exceptionalReason)) {
				$exceptionalReason .= nl2br("<hr> Not eligible Reason : " . makeUrltoLinkInString($request->not_eligible_reason) . ". Done by: " . Auth::user()->name . " at " . date('d-m-Y g:i A', strtotime($activity->not_eligible_moved_at)));
			} else {
				$exceptionalReason = 'Not eligible Reason : ' . makeUrltoLinkInString($request->not_eligible_reason) . ". Done by: " . Auth::user()->name . " at " . date('d-m-Y g:i A', strtotime($activity->not_eligible_moved_at));
			}
			$activity->exceptional_reason = $exceptionalReason;
			$activity->status_id = 15; //Not Eligible for Payout
			$activity->save();

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Activity moved to not eligible for payout',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function towingImagesRequiredUpdated(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
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

			$activity = Activity::find($request->activity_id);
			$activity->is_towing_attachments_mandatory = $request->isTowingAttachmentsMandatory;
			$activity->towing_attachments_mandatory_by_id = Auth::user()->id;
			$activity->save();

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

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
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function exportActivities(Request $request) {
		// dd($request->all());
		try {
			ini_set('max_execution_time', 0);
			ini_set('display_errors', 1);
			ini_set('memory_limit', '-1');
			ob_end_clean();
			ob_start();

			$errorMessages = [
				'status_ids.required' => "Please Select Activity Status",
			];

			$validator = Validator::make($request->all(), [
				'status_ids' => [
					'required:true',
				],
			], $errorMessages);

			if ($validator->fails()) {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'errors' => $validator->errors()->all(),
				]);
			}

			$periods = getStartDateAndEndDate($request->period);
			$range1 = $periods['start_date'];
			$range2 = $periods['end_date'];

			$statusIds = !empty($request->status_ids) ? explode(',', $request->status_ids) : null;
			$aspAxaptaCodes = !empty($request->aspAxaptaCodes) ? json_decode($request->aspAxaptaCodes) : [];
			$clientIds = !empty($request->client_ids) ? explode(',', $request->client_ids) : null;

			$activityReports = ActivityReport::join('activities', 'activities.id', 'activity_reports.activity_id')
				->join('cases', 'cases.id', 'activities.case_id')
				->join('asps', 'asps.id', 'activities.asp_id');
			if (!empty($statusIds)) {
				$activityReports->whereIn('activities.status_id', $statusIds);
			}

			if ($request->filter_by == 'general') {
				$activityReports->where(function ($q) use ($range1, $range2) {
					$q->whereDate('cases.date', '>=', $range1)
						->whereDate('cases.date', '<=', $range2);
				});
			} elseif ($request->filter_by == 'activity') {
				$activityReports->where(function ($q) use ($range1, $range2) {
					$q->where(function ($query) use ($range1, $range2) {
						$query->whereRaw('DATE(activity_reports.imported_date) between "' . $range1 . '" and "' . $range2 . '"');
					})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.asp_data_filled_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l1_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l1_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l2_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l2_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l3_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l3_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l4_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.l4_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.invoice_generated_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.axapta_generated_date) between "' . $range1 . '" and "' . $range2 . '"');
						})
						->orwhere(function ($query) use ($range1, $range2) {
							$query->whereRaw('DATE(activity_reports.payment_completed_date) between "' . $range1 . '" and "' . $range2 . '"');
						});
				});
			} elseif ($request->filter_by == 'invoiceDate') {
				$activityReports->whereRaw('DATE(activity_reports.invoice_date) between "' . $range1 . '" and "' . $range2 . '"');
			} elseif ($request->filter_by == 'transactionDate') {
				$activityReports->whereRaw('DATE(activity_reports.transaction_date) between "' . $range1 . '" and "' . $range2 . '"');
			}

			$activityReports->select([
				'activity_reports.id',
				'activity_reports.activity_id as activityId',
				DB::raw('COALESCE(activity_reports.case_number, "--") as caseNumber'),
				DB::raw('DATE_FORMAT(activity_reports.case_date, "%d-%m-%Y %H:%i:%s") as caseDate'),
				DB::raw('DATE_FORMAT(activity_reports.case_submission_closing_date, "%d-%m-%Y %H:%i:%s") as caseSubmissionClosingDate'),
				DB::raw('COALESCE(activity_reports.case_submission_closing_date_remarks, "--") as caseSubmissionClosingDateRemarks'),
				DB::raw('COALESCE(activity_reports.crm_activity_id, "--") as crmActivityId'),
				DB::raw('COALESCE(activity_reports.activity_number, "--") as activityNumber'),
				DB::raw('DATE_FORMAT(activity_reports.activity_created_date, "%d-%m-%Y %H:%i:%s") as activityCreatedDate'),
				DB::raw('COALESCE(activity_reports.client, "--") as client'),
				DB::raw('COALESCE(activity_reports.customer_name, "--") as customerName'),
				DB::raw('COALESCE(activity_reports.customer_contact_number, "--") as customerContactNumber'),
				DB::raw('COALESCE(activity_reports.asp_name, "--") as aspName'),
				DB::raw('COALESCE(activity_reports.axapta_code, "--") as axaptaCode'),
				DB::raw('COALESCE(activity_reports.asp_code, "--") as aspCode'),
				'activity_reports.asp_contact_number as aspContactNumber',
				DB::raw('COALESCE(activity_reports.asp_email, "--") as aspEmail'),
				DB::raw('COALESCE(activity_reports.asp_has_gst, "--") as aspHasGst'),
				DB::raw('COALESCE(activity_reports.asp_type, "--") as aspType'),
				DB::raw('COALESCE(activity_reports.auto_invoice, "--") as autoInvoice'),
				DB::raw('COALESCE(activity_reports.workshop_name, "--") as workshopName'),
				DB::raw('COALESCE(activity_reports.workshop_type, "--") as workshopType'),
				DB::raw('COALESCE(activity_reports.rm_name, "--") as rmName'),
				DB::raw('COALESCE(activity_reports.zm_name, "--") as zmName'),
				DB::raw('COALESCE(activity_reports.nm_name, "--") as nmName'),
				DB::raw('COALESCE(activity_reports.location, "--") as location'),
				DB::raw('COALESCE(activity_reports.district, "--") as district'),
				DB::raw('COALESCE(activity_reports.state, "--") as state'),
				DB::raw('COALESCE(activity_reports.vehicle_registration_number, "--") as vehicleRegistrationNumber'),
				DB::raw('COALESCE(activity_reports.membership_type, "--") as membershipType'),
				DB::raw('COALESCE(activity_reports.vehicle_model, "--") as vehicleModel'),
				DB::raw('COALESCE(activity_reports.vehicle_make, "--") as vehicleMake'),
				DB::raw('COALESCE(activity_reports.case_status, "--") as caseStatus'),
				DB::raw('COALESCE(activity_reports.finance_status, "--") as financeStatus'),
				DB::raw('COALESCE(activity_reports.final_approved_bo_service_type, "--") as finalApprovedBoServiceType'),
				DB::raw('COALESCE(activity_reports.asp_activity_rejected_reason, "--") as aspActivityRejectedReason'),
				DB::raw('COALESCE(activity_reports.asp_po_accepted, "--") as aspPoAccepted'),
				DB::raw('COALESCE(activity_reports.asp_po_rejected_reason, "--") as aspPoRejectedReason'),
				DB::raw('COALESCE(activity_reports.portal_status, "--") as portalStatus'),
				DB::raw('COALESCE(activity_reports.activity_status, "--") as activityStatus'),
				DB::raw('COALESCE(activity_reports.activity_description, "--") as activityDescription'),
				DB::raw('COALESCE(activity_reports.is_towing_attachment_mandatory, "--") as isTowingAttachmentMandatory'),
				DB::raw('COALESCE(activity_reports.towing_attachment_mandatory_by, "--") as towingAttachmentMandatoryBy'),
				DB::raw('COALESCE(activity_reports.remarks, "--") as remarks'),
				DB::raw('COALESCE(activity_reports.manual_uploading_remarks, "--") as manualUploadingRemarks'),
				DB::raw('COALESCE(activity_reports.general_remarks, "--") as generalRemarks'),
				DB::raw('COALESCE(activity_reports.bo_comments, "--") as boComments'),
				DB::raw('COALESCE(activity_reports.deduction_reason, "--") as deductionReason'),
				DB::raw('COALESCE(activity_reports.defer_reason, "--") as deferReason'),
				DB::raw('COALESCE(activity_reports.asp_resolve_comments, "--") as aspResolveComments'),
				DB::raw('COALESCE(activity_reports.is_exceptional, "--") as isExceptional'),
				DB::raw('COALESCE(activity_reports.exceptional_reason, "--") as exceptionalReason'),
				DB::raw('COALESCE(activity_reports.invoice_number, "--") as invoiceNumber'),
				DB::raw('DATE_FORMAT(activity_reports.invoice_date, "%d-%m-%Y") as invoiceDate'),
				DB::raw('COALESCE(activity_reports.invoice_amount, "--") as invoiceAmount'),
				DB::raw('COALESCE(activity_reports.invoice_status, "--") as invoiceStatus'),
				DB::raw('DATE_FORMAT(activity_reports.transaction_date, "%d-%m-%Y") as transactionDate'),
				DB::raw('COALESCE(activity_reports.voucher, "--") as voucher'),
				DB::raw('COALESCE(activity_reports.tds_amount, "--") as tdsAmount'),
				DB::raw('COALESCE(activity_reports.paid_amount, "--") as paidAmount'),
				DB::raw('COALESCE(activity_reports.bd_lat, "--") as bdLat'),
				DB::raw('COALESCE(activity_reports.bd_long, "--") as bdLong'),
				DB::raw('COALESCE(activity_reports.bd_location, "--") as bdLocation'),
				DB::raw('COALESCE(activity_reports.bd_city, "--") as bdCity'),
				DB::raw('COALESCE(activity_reports.bd_state, "--") as bdState'),

				DB::raw('COALESCE(activity_reports.pickup_lat, "--") as pickupLat'),
				DB::raw('COALESCE(activity_reports.pickup_long, "--") as pickupLong'),
				DB::raw('COALESCE(activity_reports.pickup_dealer_name, "--") as pickupDealerName'),
				DB::raw('COALESCE(activity_reports.pickup_dealer_location, "--") as pickupDealerLocation'),
				DB::raw('COALESCE(activity_reports.pickup_dealer_state, "--") as pickupDealerState'),
				DB::raw('COALESCE(activity_reports.pickup_dealer_city, "--") as pickupDealerCity'),
				DB::raw('COALESCE(activity_reports.pickup_location_pincode, "--") as pickupLocationPincode'),
				DB::raw('COALESCE(DATE_FORMAT(activity_reports.delivery_request_pickup_date,"%d-%m-%Y"), "--") as deliveryRequestPickupDate'),
				DB::raw('COALESCE(activity_reports.delivery_request_pickup_time, "--") as deliveryRequestPickupTime'),

				DB::raw('COALESCE(activity_reports.csr, "--") as csr'),
				DB::raw('COALESCE(activity_reports.location_type, "--") as locationType'),
				DB::raw('COALESCE(activity_reports.location_category, "--") as locationCategory'),
				DB::raw('COALESCE(activity_reports.sla_achieved_delayed, "--") as slaAchievedDelayed'),
				DB::raw('COALESCE(activity_reports.cc_waiting_time, "--") as ccWaitingTime'),
				DB::raw('COALESCE(activity_reports.cc_total_km, "--") as ccTotalKm'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_collected_amount, 2), "--") as ccCollectedAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_not_collected_amount, 2), "--") as ccNotCollectedAmount'),
				DB::raw('DATE_FORMAT(activity_reports.asp_reached_date, "%d-%m-%Y %H:%i:%s") as aspReachedDate'),
				DB::raw('COALESCE(activity_reports.asp_start_location, "--") as aspStartLocation'),
				DB::raw('COALESCE(activity_reports.asp_end_location, "--") as aspEndLocation'),
				DB::raw('COALESCE(activity_reports.onward_google_km, "--") as onwardGoogleKm'),
				DB::raw('COALESCE(activity_reports.dealer_google_km, "--") as dealerGoogleKm'),
				DB::raw('COALESCE(activity_reports.return_google_km, "--") as returnGoogleKm'),
				DB::raw('COALESCE(activity_reports.onward_km, "--") as onwardKm'),
				DB::raw('COALESCE(activity_reports.dealer_km, "--") as dealerKm'),
				DB::raw('COALESCE(activity_reports.return_km, "--") as returnKm'),
				DB::raw('COALESCE(activity_reports.drop_location_type, "--") as dropLocationType'),
				DB::raw('COALESCE(activity_reports.drop_dealer, "--") as dropDealer'),
				DB::raw('COALESCE(activity_reports.drop_location, "--") as dropLocation'),

				DB::raw('COALESCE(activity_reports.drop_dealer_state, "--") as dropDealerState'),
				DB::raw('COALESCE(activity_reports.drop_dealer_city, "--") as dropDealerCity'),
				DB::raw('COALESCE(activity_reports.drop_location_pincode, "--") as dropLocationPincode'),

				DB::raw('COALESCE(activity_reports.drop_location_lat, "--") as dropLocationLat'),
				DB::raw('COALESCE(activity_reports.drop_location_long, "--") as dropLocationLong'),
				DB::raw('COALESCE(FORMAT(activity_reports.amount, 2), "--") as amount'),
				DB::raw('COALESCE(activity_reports.paid_to, "--") as paidTo'),
				DB::raw('COALESCE(activity_reports.payment_mode, "--") as paymentMode'),
				DB::raw('COALESCE(activity_reports.payment_receipt_no, "--") as paymentReceiptNo'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_service_charges, 2), "--") as ccServiceCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_membership_charges, 2), "--") as ccMembershipCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_eatable_items_charges, 2), "--") as ccEatableItemsCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_toll_charges, 2), "--") as ccTollCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_green_tax_charges, 2), "--") as ccGreenTaxCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_border_charges, 2), "--") as ccBorderCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_octroi_charges, 2), "--") as ccOctroiCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_excess_charges, 2), "--") as ccExcessCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_fuel_charges, 2), "--") as ccFuelCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_service_charges, 2), "--") as aspServiceCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_membership_charges, 2), "--") as aspMembershipCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_eatable_items_charges, 2), "--") as aspEatableItemsCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_toll_charges, 2), "--") as aspTollCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_green_tax_charges, 2), "--") as aspGreenTaxCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_border_charges, 2), "--") as aspBorderCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_octroi_charges, 2), "--") as aspOctroiCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_excess_charges, 2), "--") as aspExcessCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_fuel_charges, 2), "--") as aspFuelCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_service_charges, 2), "--") as boServiceCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_membership_charges, 2), "--") as boMembershipCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_eatable_items_charges, 2), "--") as boEatableItemsCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_toll_charges, 2), "--") as boTollCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_green_tax_charges, 2), "--") as boGreenTaxCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_border_charges, 2), "--") as boBorderCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_octroi_charges, 2), "--") as boOctroiCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_excess_charges, 2), "--") as boExcessCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_fuel_charges, 2), "--") as boFuelCharges'),
				DB::raw('COALESCE(activity_reports.asp_waiting_time, "--") as aspWaitingTime'),
				DB::raw('COALESCE(activity_reports.bo_waiting_time, "--") as boWaitingTime'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_waiting_charges, 2), "--") as ccWaitingCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_waiting_charges, 2), "--") as aspWaitingCharges'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_waiting_charges, 2), "--") as boWaitingCharges'),
				DB::raw('COALESCE(activity_reports.cc_service_type, "--") as ccServiceType'),
				DB::raw('COALESCE(activity_reports.asp_service_type, "--") as aspServiceType'),
				DB::raw('COALESCE(activity_reports.bo_service_type, "--") as boServiceType'),
				DB::raw('COALESCE(activity_reports.bo_km_travelled, "--") as boKmTravelled'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_collected, 2), "--") as boCollected'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_not_collected, 2), "--") as boNotCollected'),
				DB::raw('COALESCE(activity_reports.asp_km_travelled, "--") as aspKmTravelled'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_collected, 2), "--") as aspCollected'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_not_collected, 2), "--") as aspNotCollected'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_po_amount, 2), "--") as ccPoAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_net_amount, 2), "--") as ccNetAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.cc_amount, 2), "--") as ccAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_tax_amount, 2), "--") as boTaxAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_net_amount, 2), "--") as boNetAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_po_amount, 2), "--") as boPoAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_deduction, 2), "--") as boDeduction'),
				DB::raw('COALESCE(FORMAT(activity_reports.bo_amount, 2), "--") as boAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_po_amount, 2), "--") as aspPoAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_net_amount, 2), "--") as aspNetAmount'),
				DB::raw('COALESCE(FORMAT(activity_reports.asp_amount, 2), "--") as aspAmount'),
				DB::raw('DATE_FORMAT(activity_reports.imported_date, "%d-%m-%Y %H:%i:%s") as importedDate'),
				DB::raw('COALESCE(activity_reports.imported_by, "--") as importedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_import_and_asp_data_filled, "--") as durationBetweenImportAndAspDataFilled'),
				DB::raw('DATE_FORMAT(activity_reports.asp_data_filled_date, "%d-%m-%Y %H:%i:%s") as aspDataFilledDate'),
				DB::raw('COALESCE(activity_reports.asp_data_filled_by, "--") as aspDataFilledBy'),
				DB::raw('COALESCE(activity_reports.duration_between_asp_data_filled_and_l1_deffered, "--") as durationBetweenAspDataFilledAndL1Deffered'),
				DB::raw('DATE_FORMAT(activity_reports.l1_deffered_date, "%d-%m-%Y %H:%i:%s") as l1DefferedDate'),
				DB::raw('COALESCE(activity_reports.l1_deffered_by, "--") as l1DefferedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_asp_data_filled_and_l1_approved, "--") as durationBetweenAspDataFilledAndL1Approved'),
				DB::raw('DATE_FORMAT(activity_reports.l1_approved_date, "%d-%m-%Y %H:%i:%s") as l1ApprovedDate'),
				DB::raw('COALESCE(activity_reports.l1_approved_by, "--") as l1ApprovedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l1_approved_and_invoice_generated, "--") as durationBetweenL1ApprovedAndInvoiceGenerated'),
				DB::raw('COALESCE(activity_reports.duration_between_l1_approved_and_l2_deffered, "--") as durationBetweenL1ApprovedAndL2Deffered'),
				DB::raw('DATE_FORMAT(activity_reports.l2_deffered_date, "%d-%m-%Y %H:%i:%s") as l2DefferedDate'),
				DB::raw('COALESCE(activity_reports.l2_deffered_by, "--") as l2DefferedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l1_approved_and_l2_approved, "--") as durationBetweenL1ApprovedAndL2Approved'),
				DB::raw('DATE_FORMAT(activity_reports.l2_approved_date, "%d-%m-%Y %H:%i:%s") as l2ApprovedDate'),
				DB::raw('COALESCE(activity_reports.l2_approved_by, "--") as l2ApprovedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l2_approved_and_invoice_generated, "--") as durationBetweenL2ApprovedAndInvoiceGenerated'),
				DB::raw('COALESCE(activity_reports.duration_between_l1_approved_and_l3_deffered, "--") as durationBetweenL1ApprovedAndL3Deffered'),
				DB::raw('COALESCE(activity_reports.duration_between_l2_approved_and_l3_deffered, "--") as durationBetweenL2ApprovedAndL3Deffered'),
				DB::raw('DATE_FORMAT(activity_reports.l3_deffered_date, "%d-%m-%Y %H:%i:%s") as l3DefferedDate'),
				DB::raw('COALESCE(activity_reports.l3_deffered_by, "--") as l3DefferedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l2_approved_and_l3_approved, "--") as durationBetweenL2ApprovedAndL3Approved'),
				DB::raw('DATE_FORMAT(activity_reports.l3_approved_date, "%d-%m-%Y %H:%i:%s") as l3ApprovedDate'),
				DB::raw('COALESCE(activity_reports.l3_approved_by, "--") as l3ApprovedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l3_approved_and_invoice_generated, "--") as durationBetweenL3ApprovedAndInvoiceGenerated'),
				DB::raw('COALESCE(activity_reports.duration_between_l1_approved_and_l4_deffered, "--") as durationBetweenL1ApprovedAndL4Deffered'),
				DB::raw('COALESCE(activity_reports.duration_between_l2_approved_and_l4_deffered, "--") as durationBetweenL2ApprovedAndL4Deffered'),
				DB::raw('COALESCE(activity_reports.duration_between_l3_approved_and_l4_deffered, "--") as durationBetweenL3ApprovedAndL4Deffered'),
				DB::raw('DATE_FORMAT(activity_reports.l4_deffered_date, "%d-%m-%Y %H:%i:%s") as l4DefferedDate'),
				DB::raw('COALESCE(activity_reports.l4_deffered_by, "--") as l4DefferedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l3_approved_and_l4_approved, "--") as durationBetweenL3ApprovedAndL4Approved'),
				DB::raw('DATE_FORMAT(activity_reports.l4_approved_date, "%d-%m-%Y %H:%i:%s") as l4ApprovedDate'),
				DB::raw('COALESCE(activity_reports.l4_approved_by, "--") as l4ApprovedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_l4_approved_and_invoice_generated, "--") as durationBetweenL4ApprovedAndInvoiceGenerated'),
				DB::raw('DATE_FORMAT(activity_reports.invoice_generated_date, "%d-%m-%Y %H:%i:%s") as invoiceGeneratedDate'),
				DB::raw('COALESCE(activity_reports.invoice_generated_by, "--") as invoiceGeneratedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_invoice_generated_and_axapta_generated, "--") as durationBetweenInvoiceGeneratedAndAxaptaGenerated'),
				DB::raw('DATE_FORMAT(activity_reports.axapta_generated_date, "%d-%m-%Y %H:%i:%s") as axaptaGeneratedDate'),
				DB::raw('COALESCE(activity_reports.axapta_generated_by, "--") as axaptaGeneratedBy'),
				DB::raw('COALESCE(activity_reports.duration_between_axapta_generated_and_payment_completed, "--") as durationBetweenAxaptaGeneratedAndPaymentCompleted'),
				DB::raw('DATE_FORMAT(activity_reports.payment_completed_date, "%d-%m-%Y %H:%i:%s") as paymentCompletedDate'),
				DB::raw('COALESCE(activity_reports.total_no_of_days, "--") as totalNoOfDays'),
				DB::raw('COALESCE(activity_reports.source, "--") as source'),
				DB::raw('COALESCE(activity_reports.range_limit, "--") as rangeLimit'),
				DB::raw('COALESCE(FORMAT(activity_reports.below_range_price, 2), "--") as belowRangePrice'),
				DB::raw('COALESCE(FORMAT(activity_reports.above_range_price, 2), "--") as aboveRangePrice'),
				DB::raw('COALESCE(FORMAT(activity_reports.waiting_charge_per_hour, 2), "--") as waitingChargePerHour'),
				DB::raw('COALESCE(FORMAT(activity_reports.empty_return_range_price, 2), "--") as emptyReturnRangePrice'),
				DB::raw('COALESCE(activity_reports.adjustment_type, "--") as adjustmentType'),
				DB::raw('COALESCE(activity_reports.adjustment, "--") as adjustment'),
			]);

			// if (!empty($request->get('asp_id'))) {
			// 	if (Entrust::can('export-own-activities')) {
			// 		// ASP FINANCE ADMIN
			// 		if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
			// 			$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
			// 			$aspIds[] = Auth::user()->asp->id;
			// 			$activityReports = $activityReports->whereIn('activities.asp_id', $aspIds);
			// 		} else {
			// 			$activityReports = $activityReports->where('activities.asp_id', $request->get('asp_id'));
			// 		}
			// 	} else {
			// 		$activityReports = $activityReports->where('activities.asp_id', $request->get('asp_id'));
			// 	}
			// }

			if (Entrust::can('export-own-activities') && Auth::user()->asp) {
				// ASP FINANCE ADMIN
				if (Auth::user()->asp->is_finance_admin == 1) {
					$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
					$aspIds[] = Auth::user()->asp->id;
					$activityReports = $activityReports->whereIn('activities.asp_id', $aspIds);
				} else {
					$activityReports = $activityReports->where('activities.asp_id', Auth::user()->asp->id);
				}
			} else if (!empty($aspAxaptaCodes)) {
				$aspIds = Asp::whereIn('axpta_code', $aspAxaptaCodes)->pluck('id')->toArray();
				$activityReports = $activityReports->whereIn('activities.asp_id', $aspIds);
			}

			if (!empty($clientIds)) {
				$activityReports = $activityReports->whereIn('cases.client_id', $clientIds);
			}
			if (!empty($request->get('ticket'))) {
				$activityReports = $activityReports->where('cases.number', $request->get('ticket'));
			}

			if (!Entrust::can('view-all-activities')) {
				if (Entrust::can('view-mapped-state-activities')) {
					$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
					$activityReports = $activityReports->whereIn('asps.state_id', $states);
				}
				if (Entrust::can('export-own-activities')) {
					$activityReports = $activityReports->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
				}
				if (Entrust::can('export-own-rm-asp-activities')) {
					$aspIds = Asp::where('regional_manager_id', Auth::user()->id)->pluck('id')->toArray();
					$activityReports = $activityReports->whereIn('asps.id', $aspIds)
						->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
				}
				if (Entrust::can('export-own-zm-asp-activities')) {
					$aspIds = Asp::where('zm_id', Auth::user()->id)->pluck('id')->toArray();
					$activityReports = $activityReports->whereIn('asps.id', $aspIds)
						->whereNotIn('activities.status_id', [2, 4, 15, 16, 17, 25, 27]);
				}
				if (Entrust::can('export-own-nm-asp-activities')) {
					$aspIds = Asp::where('nm_id', Auth::user()->id)->pluck('id')->toArray();
					$activityReports = $activityReports->whereIn('asps.id', $aspIds);
				}
			}
			$activitesTotalCount = $activityReports;
			$totalCount = $activitesTotalCount->groupBy('activity_reports.id')->get()->count();
			if ($totalCount == 0) {
				return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
					'errors' => [
						'No activities found for given period & statuses',
					],
				]);
			}

			$selected_statuses = $statusIds;
			$summaryPeriod = ['Period', date('d/M/Y', strtotime($range1)) . ' to ' . date('d/M/Y', strtotime($range2))];
			$summary[] = ['Status', 'Count'];

			if (!empty($statusIds)) {
				$activityPortalStatuses = ActivityPortalStatus::select([
					'id',
					'name',
				])
					->get();
				foreach ($statusIds as $statusKey => $statusId) {
					$activityPortalStatus = $activityPortalStatuses->where('id', $statusId)->first();
					if ($activityPortalStatus) {
						$activitiesSummaryCountQuery = ActivityReport::select([
							'activity_reports.id',
						])
							->join('activities', 'activities.id', 'activity_reports.activity_id')
							->join('cases', 'cases.id', 'activities.case_id')
							->where('activities.status_id', $statusId);

						if ($request->filter_by == 'general') {
							$activitiesSummaryCountQuery->where(function ($q) use ($range1, $range2) {
								$q->whereDate('cases.date', '>=', $range1)
									->whereDate('cases.date', '<=', $range2);
							});
						} elseif ($request->filter_by == 'activity') {
							$activitiesSummaryCountQuery->where(function ($q) use ($range1, $range2) {
								$q->where(function ($query) use ($range1, $range2) {
									$query->whereRaw('DATE(activity_reports.imported_date) between "' . $range1 . '" and "' . $range2 . '"');
								})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.asp_data_filled_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l1_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l1_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l2_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l2_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l3_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l3_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l4_deffered_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.l4_approved_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.invoice_generated_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.axapta_generated_date) between "' . $range1 . '" and "' . $range2 . '"');
									})
									->orwhere(function ($query) use ($range1, $range2) {
										$query->whereRaw('DATE(activity_reports.payment_completed_date) between "' . $range1 . '" and "' . $range2 . '"');
									});
							});
						} elseif ($request->filter_by == 'invoiceDate') {
							$activitiesSummaryCountQuery->whereRaw('DATE(activity_reports.invoice_date) between "' . $range1 . '" and "' . $range2 . '"');
						} elseif ($request->filter_by == 'transactionDate') {
							$activitiesSummaryCountQuery->whereRaw('DATE(activity_reports.transaction_date) between "' . $range1 . '" and "' . $range2 . '"');
						}

						// if (!empty($request->get('asp_id'))) {
						// 	if (Entrust::can('export-own-activities')) {
						// 		// ASP FINANCE ADMIN
						// 		if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
						// 			$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
						// 			$aspIds[] = Auth::user()->asp->id;
						// 			$activitiesSummaryCountQuery->whereIn('activities.asp_id', $aspIds);
						// 		} else {
						// 			$activitiesSummaryCountQuery->where('activities.asp_id', $request->get('asp_id'));
						// 		}
						// 	} else {
						// 		$activitiesSummaryCountQuery->where('activities.asp_id', $request->get('asp_id'));
						// 	}
						// }

						if (Entrust::can('export-own-activities') && Auth::user()->asp) {
							// ASP FINANCE ADMIN
							if (Auth::user()->asp->is_finance_admin == 1) {
								$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
								$aspIds[] = Auth::user()->asp->id;
								$activitiesSummaryCountQuery->whereIn('activities.asp_id', $aspIds);
							} else {
								$activitiesSummaryCountQuery->where('activities.asp_id', Auth::user()->asp->id);
							}
						} else if (!empty($aspAxaptaCodes)) {
							$aspIds = Asp::whereIn('axpta_code', $aspAxaptaCodes)->pluck('id')->toArray();
							$activitiesSummaryCountQuery->whereIn('activities.asp_id', $aspIds);
						}

						if (!empty($clientIds)) {
							$activitiesSummaryCountQuery->whereIn('cases.client_id', $clientIds);
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
							if (Entrust::can('export-own-rm-asp-activities')) {
								$aspIds = Asp::where('regional_manager_id', Auth::user()->id)->pluck('id')->toArray();
								$activitiesSummaryCountQuery->join('asps', 'activities.asp_id', '=', 'asps.id')
									->whereIn('asps.id', $aspIds);
							}
							if (Entrust::can('export-own-zm-asp-activities')) {
								$aspIds = Asp::where('zm_id', Auth::user()->id)->pluck('id')->toArray();
								$activitiesSummaryCountQuery->join('asps', 'activities.asp_id', '=', 'asps.id')
									->whereIn('asps.id', $aspIds);
							}
							if (Entrust::can('export-own-nm-asp-activities')) {
								$aspIds = Asp::where('nm_id', Auth::user()->id)->pluck('id')->toArray();
								$activitiesSummaryCountQuery->join('asps', 'activities.asp_id', '=', 'asps.id')
									->whereIn('asps.id', $aspIds);
							}
						}

						$activitySummaryCount = $activitiesSummaryCountQuery->groupBy('activity_reports.id')->get()->count();
						$summary[] = [
							$activityPortalStatus->name,
							$activitySummaryCount,
						];
					}
				}
			}

			$summary[] = ['Total', $totalCount];

			if (Entrust::can('export-own-activities') || Entrust::can('export-own-rm-asp-activities') || Entrust::can('export-own-zm-asp-activities')) {
				$activityReportHeaders = [
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

					'Pickup Latitude',
					'Pickup Longitude',
					'Pickup Dealer Name',
					'Pickup Dealer Location',
					'Pickup Dealer State',
					'Pickup Dealer City',
					'Pickup Location Pincode',
					'Delivery Request Pickup Date',
					'Delivery Request Pickup Time',

					'CSR',
					'Drop Dealer',
					'Drop Location',

					'Drop Dealer State',
					'Drop Dealer City',
					'Drop Location Pincode',

					'Drop Location Latitude',
					'Drop Location Longitude',
					'BO KM Travelled',
					'BO Collected',
					'BO Not Collected',
					'BO Net Amount',
					'BO Deduction',
					'BO Amount',
				];
			} else {
				$activityReportHeaders = [
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
					'Is Towing Attachment Mandatory',
					'Towing Attachment Mandatory By',
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

					'Pickup Latitude',
					'Pickup Longitude',
					'Pickup Dealer Name',
					'Pickup Dealer Location',
					'Pickup Dealer State',
					'Pickup Dealer City',
					'Pickup Location Pincode',
					'Delivery Request Pickup Date',
					'Delivery Request Pickup Time',

					'CSR',
					'Location Type',
					'Location Category',
					'SLA Achieved Delayed',
					'CC Waiting Time',
					'CC Total KM',
					'CC Collected Amount',
					'CC Not Collected Amount',
					'ASP Reached Date',
					'ASP Start Location',
					'ASP End Location',
					'Onward Google KM',
					'Dealer Google KM',
					'Return Google KM',
					'Onward KM',
					'Dealer KM',
					'Return KM',
					'Drop Location Type',
					'Drop Dealer',
					'Drop Location',

					'Drop Dealer State',
					'Drop Dealer City',
					'Drop Location Pincode',

					'Drop Location Latitude',
					'Drop Location Longitude',
					'Amount',
					'Paid To',
					'Payment Mode',
					'Payment Receipt No',
					'CC Service Charges',
					'CC Membership Charges',
					'CC Eatable Items Charges',
					'CC Toll Charges',
					'CC Green Tax Charges',
					'CC Border Charges',
					'CC Octroi Charges',
					'CC Excess Charges',
					'CC Fuel Charges',
					'ASP Service Charges',
					'ASP Membership Charges',
					'ASP Eatable Items Charges',
					'ASP Toll Charges',
					'ASP Green Tax Charges',
					'ASP Border Charges',
					'ASP Octroi Charges',
					'ASP Excess Charges',
					'ASP Fuel Charges',
					'BO Service Charges',
					'BO Membership Charges',
					'BO Eatable Items Charges',
					'BO Toll Charges',
					'BO Green Tax Charges',
					'BO Border Charges',
					'BO Octroi Charges',
					'BO Excess Charges',
					'BO Fuel Charges',
					'ASP Waiting Time',
					'BO Waiting Time',
					'CC Waiting Charges',
					'ASP Waiting Charges',
					'BO Waiting Charges',
					'CC Service Type',
					'ASP Service Type',
					'BO Service Type',
					'BO KM Travelled',
					'BO Collected',
					'BO Not Collected',
					'ASP KM Travelled',
					'ASP Collected',
					'ASP Not Collected',
					'CC PO Amount',
					'CC Net Amount',
					'CC Amount',
					'BO Tax Amount',
					'BO Net Amount',
					'BO PO Amount',
					'BO Deduction',
					'BO Amount',
					'ASP PO Amount',
					'ASP Net Amount',
					'ASP Amount',
				];
			}

			if (!Entrust::can('export-own-activities') && !Entrust::can('export-own-rm-asp-activities') && !Entrust::can('export-own-zm-asp-activities')) {
				$logHeaders = [
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
					'Duration Between L1 approved and L4 deffered',
					'Duration Between L2 approved and L4 deffered',
					'Duration Between L3 approved and L4 deffered',
					'L4 Deferred',
					'L4 Deferred By',
					'Duration Between L3 approved and L4 approved',
					'L4 Approved',
					'L4 Approved By',
					'Duration Between L4 approved and Invoice generated',
					'Invoice Generated',
					'Invoice Generated By',
					'Duration Between Invoice generated and Axapta Generated',
					'Axapta Generated',
					'Axapta Generated By',
					'Duration Between Axapta Generated and Payment Completed',
					'Payment Completed',
					'Total No. Of Days',
					'Source',
				];
				$activityReportHeaders = array_merge($activityReportHeaders, $logHeaders);
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
			$activityReportHeaders = array_merge($activityReportHeaders, $rateCardHeaders);

			$activityReportAllData = [];
			$activityReports->groupBy('activity_reports.id')->chunk(5000, function ($activityReportValues) use (&$activityReportAllData) {
				$activityReportDetails = [];
				foreach ($activityReportValues as $activityReportKey => $activityReportVal) {

					if (!empty($activityReportVal->aspContactNumber)) {
						if (Entrust::can('display-asp-number-in-activities')) {
							$aspContactNumber = $activityReportVal->aspContactNumber;
						} else {
							$aspContactNumber = maskPhoneNumber($activityReportVal->aspContactNumber);
						}
					} else {
						$aspContactNumber = "--";
					}

					if (Entrust::can('export-own-activities') || Entrust::can('export-own-rm-asp-activities') || Entrust::can('export-own-zm-asp-activities')) {
						$activityReportDetails[] = [
							$activityReportVal->activityId,
							$activityReportVal->caseNumber,
							$activityReportVal->caseDate,
							$activityReportVal->crmActivityId,
							$activityReportVal->activityNumber,
							$activityReportVal->activityCreatedDate,
							$activityReportVal->client,
							$activityReportVal->aspName,
							$activityReportVal->axaptaCode,
							$activityReportVal->aspCode,
							$aspContactNumber,
							$activityReportVal->aspEmail,
							$activityReportVal->aspHasGst,
							$activityReportVal->workshopName,
							$activityReportVal->rmName,
							$activityReportVal->location,
							$activityReportVal->district,
							$activityReportVal->state,
							$activityReportVal->vehicleRegistrationNumber,
							$activityReportVal->membershipType,
							$activityReportVal->vehicleModel,
							$activityReportVal->vehicleMake,
							$activityReportVal->caseStatus,
							$activityReportVal->financeStatus,
							$activityReportVal->finalApprovedBoServiceType,
							$activityReportVal->portalStatus,
							$activityReportVal->activityStatus,
							$activityReportVal->remarks,
							$activityReportVal->generalRemarks,
							$activityReportVal->boComments,
							$activityReportVal->deductionReason,
							$activityReportVal->deferReason,
							$activityReportVal->aspResolveComments,
							$activityReportVal->invoiceNumber,
							$activityReportVal->invoiceDate,
							$activityReportVal->invoiceStatus,
							$activityReportVal->transactionDate,
							$activityReportVal->voucher,
							$activityReportVal->tdsAmount,
							$activityReportVal->paidAmount,
							$activityReportVal->bdLat,
							$activityReportVal->bdLong,
							$activityReportVal->bdLocation,
							$activityReportVal->bdCity,
							$activityReportVal->bdState,

							$activityReportVal->pickupLat,
							$activityReportVal->pickupLong,
							$activityReportVal->pickupDealerName,
							$activityReportVal->pickupDealerLocation,
							$activityReportVal->pickupDealerState,
							$activityReportVal->pickupDealerCity,
							$activityReportVal->pickupLocationPincode,
							$activityReportVal->deliveryRequestPickupDate,
							$activityReportVal->deliveryRequestPickupTime,

							$activityReportVal->csr,
							$activityReportVal->dropDealer,
							$activityReportVal->dropLocation,

							$activityReportVal->dropDealerState,
							$activityReportVal->dropDealerCity,
							$activityReportVal->dropLocationPincode,

							$activityReportVal->dropLocationLat,
							$activityReportVal->dropLocationLong,
							$activityReportVal->boKmTravelled,
							$activityReportVal->boCollected,
							$activityReportVal->boNotCollected,
							$activityReportVal->boNetAmount,
							$activityReportVal->boDeduction,
							$activityReportVal->boAmount,
						];
					} else {
						$activityReportDetails[] = [
							$activityReportVal->activityId,
							$activityReportVal->caseNumber,
							$activityReportVal->caseDate,
							$activityReportVal->caseSubmissionClosingDate,
							$activityReportVal->caseSubmissionClosingDateRemarks,
							$activityReportVal->crmActivityId,
							$activityReportVal->activityNumber,
							$activityReportVal->activityCreatedDate,
							$activityReportVal->client,
							$activityReportVal->customerName,
							$activityReportVal->customerContactNumber,
							$activityReportVal->aspName,
							$activityReportVal->axaptaCode,
							$activityReportVal->aspCode,
							$aspContactNumber,
							$activityReportVal->aspEmail,
							$activityReportVal->aspHasGst,
							$activityReportVal->aspType,
							$activityReportVal->autoInvoice,
							$activityReportVal->workshopName,
							$activityReportVal->workshopType,
							$activityReportVal->rmName,
							$activityReportVal->location,
							$activityReportVal->district,
							$activityReportVal->state,
							$activityReportVal->vehicleRegistrationNumber,
							$activityReportVal->membershipType,
							$activityReportVal->vehicleModel,
							$activityReportVal->vehicleMake,
							$activityReportVal->caseStatus,
							$activityReportVal->financeStatus,
							$activityReportVal->finalApprovedBoServiceType,
							$activityReportVal->aspActivityRejectedReason,
							$activityReportVal->aspPoAccepted,
							$activityReportVal->aspPoRejectedReason,
							$activityReportVal->portalStatus,
							$activityReportVal->activityStatus,
							$activityReportVal->activityDescription,
							$activityReportVal->isTowingAttachmentMandatory,
							$activityReportVal->towingAttachmentMandatoryBy,
							$activityReportVal->remarks,
							$activityReportVal->manualUploadingRemarks,
							$activityReportVal->generalRemarks,
							$activityReportVal->boComments,
							$activityReportVal->deductionReason,
							$activityReportVal->deferReason,
							$activityReportVal->aspResolveComments,
							$activityReportVal->isExceptional,
							$activityReportVal->exceptionalReason,
							$activityReportVal->invoiceNumber,
							$activityReportVal->invoiceDate,
							$activityReportVal->invoiceAmount,
							$activityReportVal->invoiceStatus,
							$activityReportVal->transactionDate,
							$activityReportVal->voucher,
							$activityReportVal->tdsAmount,
							$activityReportVal->paidAmount,
							$activityReportVal->bdLat,
							$activityReportVal->bdLong,
							$activityReportVal->bdLocation,
							$activityReportVal->bdCity,
							$activityReportVal->bdState,

							$activityReportVal->pickupLat,
							$activityReportVal->pickupLong,
							$activityReportVal->pickupDealerName,
							$activityReportVal->pickupDealerLocation,
							$activityReportVal->pickupDealerState,
							$activityReportVal->pickupDealerCity,
							$activityReportVal->pickupLocationPincode,
							$activityReportVal->deliveryRequestPickupDate,
							$activityReportVal->deliveryRequestPickupTime,

							$activityReportVal->csr,
							$activityReportVal->locationType,
							$activityReportVal->locationCategory,
							$activityReportVal->slaAchievedDelayed,
							$activityReportVal->ccWaitingTime,
							$activityReportVal->ccTotalKm,
							$activityReportVal->ccCollectedAmount,
							$activityReportVal->ccNotCollectedAmount,
							$activityReportVal->aspReachedDate,
							$activityReportVal->aspStartLocation,
							$activityReportVal->aspEndLocation,
							$activityReportVal->onwardGoogleKm,
							$activityReportVal->dealerGoogleKm,
							$activityReportVal->returnGoogleKm,
							$activityReportVal->onwardKm,
							$activityReportVal->dealerKm,
							$activityReportVal->returnKm,
							$activityReportVal->dropLocationType,
							$activityReportVal->dropDealer,
							$activityReportVal->dropLocation,

							$activityReportVal->dropDealerState,
							$activityReportVal->dropDealerCity,
							$activityReportVal->dropLocationPincode,

							$activityReportVal->dropLocationLat,
							$activityReportVal->dropLocationLong,
							$activityReportVal->amount,
							$activityReportVal->paidTo,
							$activityReportVal->paymentMode,
							$activityReportVal->paymentReceiptNo,
							$activityReportVal->ccServiceCharges,
							$activityReportVal->ccMembershipCharges,
							$activityReportVal->ccEatableItemsCharges,
							$activityReportVal->ccTollCharges,
							$activityReportVal->ccGreenTaxCharges,
							$activityReportVal->ccBorderCharges,
							$activityReportVal->ccOctroiCharges,
							$activityReportVal->ccExcessCharges,
							$activityReportVal->ccFuelCharges,
							$activityReportVal->aspServiceCharges,
							$activityReportVal->aspMembershipCharges,
							$activityReportVal->aspEatableItemsCharges,
							$activityReportVal->aspTollCharges,
							$activityReportVal->aspGreenTaxCharges,
							$activityReportVal->aspBorderCharges,
							$activityReportVal->aspOctroiCharges,
							$activityReportVal->aspExcessCharges,
							$activityReportVal->aspFuelCharges,
							$activityReportVal->boServiceCharges,
							$activityReportVal->boMembershipCharges,
							$activityReportVal->boEatableItemsCharges,
							$activityReportVal->boTollCharges,
							$activityReportVal->boGreenTaxCharges,
							$activityReportVal->boBorderCharges,
							$activityReportVal->boOctroiCharges,
							$activityReportVal->boExcessCharges,
							$activityReportVal->boFuelCharges,
							$activityReportVal->aspWaitingTime,
							$activityReportVal->boWaitingTime,
							$activityReportVal->ccWaitingCharges,
							$activityReportVal->aspWaitingCharges,
							$activityReportVal->boWaitingCharges,
							$activityReportVal->ccServiceType,
							$activityReportVal->aspServiceType,
							$activityReportVal->boServiceType,
							$activityReportVal->boKmTravelled,
							$activityReportVal->boCollected,
							$activityReportVal->boNotCollected,
							$activityReportVal->aspKmTravelled,
							$activityReportVal->aspCollected,
							$activityReportVal->aspNotCollected,
							$activityReportVal->ccPoAmount,
							$activityReportVal->ccNetAmount,
							$activityReportVal->ccAmount,
							$activityReportVal->boTaxAmount,
							$activityReportVal->boNetAmount,
							$activityReportVal->boPoAmount,
							$activityReportVal->boDeduction,
							$activityReportVal->boAmount,
							$activityReportVal->aspPoAmount,
							$activityReportVal->aspNetAmount,
							$activityReportVal->aspAmount,
						];
					}

					if (!Entrust::can('export-own-activities') && !Entrust::can('export-own-rm-asp-activities') && !Entrust::can('export-own-zm-asp-activities')) {

						$activityReportDetails[$activityReportKey][] = $activityReportVal->importedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->importedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenImportAndAspDataFilled;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->aspDataFilledDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->aspDataFilledBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenAspDataFilledAndL1Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l1DefferedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l1DefferedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenAspDataFilledAndL1Approved;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l1ApprovedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l1ApprovedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL1ApprovedAndInvoiceGenerated;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL1ApprovedAndL2Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l2DefferedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l2DefferedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL1ApprovedAndL2Approved;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l2ApprovedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l2ApprovedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL2ApprovedAndInvoiceGenerated;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL1ApprovedAndL3Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL2ApprovedAndL3Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l3DefferedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l3DefferedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL2ApprovedAndL3Approved;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l3ApprovedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l3ApprovedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL3ApprovedAndInvoiceGenerated;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL1ApprovedAndL4Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL2ApprovedAndL4Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL3ApprovedAndL4Deffered;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l4DefferedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l4DefferedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL3ApprovedAndL4Approved;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l4ApprovedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->l4ApprovedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenL4ApprovedAndInvoiceGenerated;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->invoiceGeneratedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->invoiceGeneratedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenInvoiceGeneratedAndAxaptaGenerated;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->axaptaGeneratedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->axaptaGeneratedBy;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->durationBetweenAxaptaGeneratedAndPaymentCompleted;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->paymentCompletedDate;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->totalNoOfDays;
						$activityReportDetails[$activityReportKey][] = $activityReportVal->source;
					}
					$activityReportDetails[$activityReportKey][] = $activityReportVal->rangeLimit;
					$activityReportDetails[$activityReportKey][] = $activityReportVal->belowRangePrice;
					$activityReportDetails[$activityReportKey][] = $activityReportVal->aboveRangePrice;
					$activityReportDetails[$activityReportKey][] = $activityReportVal->waitingChargePerHour;
					$activityReportDetails[$activityReportKey][] = $activityReportVal->emptyReturnRangePrice;
					$activityReportDetails[$activityReportKey][] = $activityReportVal->adjustmentType;
					$activityReportDetails[$activityReportKey][] = $activityReportVal->adjustment;
				}
				$activityReportAllData = array_merge($activityReportAllData, $activityReportDetails);
			});

			Excel::create('Activity Status Report', function ($excel) use ($summary, $activityReportHeaders, $activityReportAllData, $statusIds, $summaryPeriod) {
				$excel->sheet('Summary', function ($sheet) use ($summary, $statusIds, $summaryPeriod) {
					$sheet->fromArray($summary, NULL, 'A1');
					$sheet->row(1, $summaryPeriod);
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
					$cell_number = count($statusIds) + 3;
					$sheet->cells('A' . $cell_number . ':B' . $cell_number, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});
				});

				$excel->sheet('Activity Informations', function ($sheet) use ($activityReportHeaders, $activityReportAllData) {
					$sheet->fromArray($activityReportAllData, NULL, 'A1');
					$sheet->row(1, $activityReportHeaders);
					$sheet->row(1, function ($row) {
						$row->setBackground('#CCC9C9');
						$row->setFontSize(10);
						$row->setFontWeight('bold');
					});
					$sheet->setAutoSize(true);
				});
			})->export('xlsx');

			return redirect()->back()->with(['success' => 'exported!']);
		} catch (\Exception $e) {
			return redirect('/#!/rsa-case-pkg/activity-status/list')->with([
				'errors' => [
					$e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	// NOT USED NOW
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
				//MECHANICAL SERVICE GROUP
				if ($activity->serviceType && $activity->serviceType->service_group_id == 2) {
					$cc_total_km = $activity->detail(280) ? $activity->detail(280)->value : 0;
					$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $activity->case->date);
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
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function releaseOnHoldActivity($activityId) {
		// dd($activityId);
		DB::beginTransaction();
		try {
			$activity = Activity::withTrashed()->whereIn('status_id', [17, 26]) // ONHOLD / ASP COMPLETED DATA ENTRY - WAITING FOR CALL CENTER DATA ENTRY
				->find($activityId);
			if (!$activity) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Ticket not found',
					],
				]);
			}

			$checkAspHasWhatsappFlow = config('rsa')['CHECK_ASP_HAS_WHATSAPP_FLOW'];
			$breakdownAlertSent = Activity::breakdownAlertSent($activity->id);
			$disableWhatsappAutoApproval = config('rsa')['DISABLE_WHATSAPP_AUTO_APPROVAL'];
			$cc_total_km = $activity->detail(280) ? $activity->detail(280)->value : 0;

			//WHATSAPP FLOW
			if ($breakdownAlertSent && $activity->asp && !empty($activity->asp->whatsapp_number) && (!$checkAspHasWhatsappFlow || ($checkAspHasWhatsappFlow && $activity->asp->has_whatsapp_flow == 1))) {
				// ROS SERVICE
				if ($activity->serviceType && $activity->serviceType->service_group_id != 3) {

					if (!$disableWhatsappAutoApproval) {
						$autoApprovalProcessResponse = $activity->autoApprovalProcess();
						if (!$autoApprovalProcessResponse['success']) {
							//SAVE CASE API LOG
							DB::rollBack();
							return response()->json([
								'success' => false,
								'errors' => [
									$autoApprovalProcessResponse['error'],
								],
							]);
						}
						$statusId = 25; // Waiting for Charges Acceptance by ASP
					} else {
						//MECHANICAL SERVICE GROUP
						if ($activity->serviceType && $activity->serviceType->service_group_id == 2) {
							$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $activity->case->date);
							if ($is_bulk) {
								$statusId = 5; //ASP Completed Data Entry - Waiting for L1 Bulk Verification
							} else {
								$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
							}
						} else {
							if (($activity->asp && $activity->asp->is_corporate == 1) || $activity->is_asp_data_entry_done == 1) {
								$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
							} else {
								$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
							}
						}

						//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
						if (floatval($cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
							$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
						}

					}

				} else {
					// TOW SERVICE
					if ($activity->asp->is_corporate == 1 || $activity->towing_attachments_uploaded_on_whatsapp == 1 || $activity->is_asp_data_entry_done == 1) {
						$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
					} else {
						$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
					}

					//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
					if (floatval($cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
						$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
					}

				}
			} else {
				// NORMAL FLOW

				//MECHANICAL SERVICE GROUP
				if ($activity->serviceType && $activity->serviceType->service_group_id == 2) {
					$is_bulk = Activity::checkTicketIsBulk($activity->asp_id, $activity->serviceType->id, $cc_total_km, $activity->data_src_id, $activity->case->date);
					if ($is_bulk) {
						$statusId = 5; //ASP Completed Data Entry - Waiting for L1 Bulk Verification
					} else {
						$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
					}
				} else {
					if (($activity->asp && $activity->asp->is_corporate == 1) || $activity->is_asp_data_entry_done == 1) {
						$statusId = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
					} else {
						$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
					}
				}

				//IF CC TOTAL KM IS LESS THAN 2 KM THEN MOVE ACTIVITY TO ASP DATA ENTRY TO AVOID VERIFICATION DEFER
				if (floatval($cc_total_km) <= 2 && $activity->is_asp_data_entry_done != 1) {
					$statusId = 2; //ASP Rejected CC Details - Waiting for ASP Data Entry
				}

			}

			$activity->update([
				'status_id' => $statusId,
				'onhold_released_by_id' => Auth::user()->id,
				'onhold_released_at' => Carbon::now(),
				'updated_by_id' => Auth::user()->id,
			]);

			//SAVE ACTIVITY REPORT FOR DASHBOARD
			ActivityReport::saveReport($activity->id);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Activity released successfully',
			]);

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
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

	public function searchAllAspsByAxaptaCode(Request $request) {
		return Asp::searchAllAspsByAxaptaCode($request);
	}

	public function searchClients(Request $request) {
		return Client::searchClient($request);
	}

	public function getSearchListBk(Request $request) {

		if (empty($request->searchQuery)) {
			return Datatables::of([])->make(true);
		}

		if (preg_match("/^[0-9]{10}+$/", $request->searchQuery)) {
			$search_type = 'mobile_number';
		} else {
			$search_type = 'normal';
		}

		$activities = Activity::select([
			'activities.id',
			'Invoices.id as invoiceId',
			'activities.crm_activity_id as crm_activity_id',
			'activities.status_id as status_id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number as case_number',
			DB::raw('COALESCE(cases.csr, "--") as csr'),
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			DB::raw('COALESCE(cases.vin_no, "--") as vin'),
			DB::raw('CONCAT(asps.asp_code," / ",asps.workshop_name) as asp'),
			DB::raw('COALESCE(service_types.name, "--") as sub_service'),
			DB::raw('COALESCE(activity_finance_statuses.name, "--") as finance_status'),
			DB::raw('COALESCE(activity_portal_statuses.name, "--") as status'),
			DB::raw('COALESCE(activity_statuses.name, "--") as activity_status'),
			DB::raw('COALESCE(clients.name, "--") as client'),
			DB::raw('COALESCE(call_centers.name, "--") as call_center'),
			'cases.created_at as caseCreatedAt',
			'cases.submission_closing_date as caseSubmissionClosingDate',
		])
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->leftjoin('Invoices', 'Invoices.id', 'activities.invoice_id');
		if (!empty($search_type) && $search_type == 'mobile_number') {
			$activities->where('cases.customer_contact_number', $request->searchQuery);
		} else {
			$activities->where(function ($q) use ($request) {
				$q->where('cases.number', $request->searchQuery)
					->orWhere('cases.vehicle_registration_number', $request->searchQuery)
					->orWhere('cases.vin_no', $request->searchQuery)
					->orWhere('activities.crm_activity_id', $request->searchQuery)
					->orWhere('cases.csr', $request->searchQuery);
			});
		}

		if (!Entrust::can('all-asp-activity-search')) {

			//BACK OFFICE
			if (Entrust::can('mapped-state-asp-activity-search')) {
				$stateIds = StateUser::where('user_id', '=', Auth::user()->id)->pluck('state_id')->toArray();
				$activities->whereIn('asps.state_id', $stateIds);
			} elseif (Entrust::can('own-asp-activity-search')) {
				// ASP || ASP FINANCE ADMIN
				if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
					$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
					$aspIds[] = Auth::user()->asp->id;
					$activities->whereIn('asps.id', $aspIds);
				} else {
					$activities->where('users.id', Auth::user()->id); // OWN ASP USER ID
				}
			} elseif (Entrust::can('own-rm-asp-activity-search')) {
				// REGIONAL MANAGER
				$aspIds = Asp::where('regional_manager_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereIn('asps.id', $aspIds);
			} elseif (Entrust::can('own-zm-asp-activity-search')) {
				// ZONAL MANAGER
				$aspIds = Asp::where('zm_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereIn('asps.id', $aspIds);
			} elseif (Entrust::can('own-nm-asp-activity-search')) {
				// NATIONAL MANAGER
				$aspIds = Asp::where('nm_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereIn('asps.id', $aspIds);
			} else {
				$activities->whereNull('activities.asp_id');
			}
		}

		$activities->orderBy('cases.date', 'DESC')->groupBy('activities.id');
		return Datatables::of($activities)
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.workshop_name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->addColumn('action', function ($activity) {
				// VIEW PAGE FOR OTHER STATUSES
				$url = '#!/rsa-case-pkg/activity-status/1/view/' . $activity->id;

				$today = date('Y-m-d H:i:s');
				$threeMonthsBefore = date('Y-m-d H:i:s', strtotime("-3 months", strtotime($today)));

				// ASP || ASP FINANCE ADMIN
				if (Entrust::can('own-asp-activity-search')) {
					//ASP Rejected CC Details - Waiting for ASP Data Entry || On Hold
					if ($activity->status_id == 2 || $activity->status_id == 4 || $activity->status_id == 17) {
						$url = '';
						//CASE WITH EXTENSION
						if (!empty($activity->caseSubmissionClosingDate) && Carbon::parse($activity->caseSubmissionClosingDate)->format('Y-m-d H:i:s') >= $today) {
							$url = '#!/rsa-case-pkg/new-activity/update-details/' . $activity->id;
						} else if (Carbon::parse($activity->caseCreatedAt)->format('Y-m-d H:i:s') >= $threeMonthsBefore) {
							$url = '#!/rsa-case-pkg/new-activity/update-details/' . $activity->id;
						}
					} elseif ($activity->status_id == 7) {
						//BO Rejected - Waiting for ASP Data Re-Entry
						$url = '#!/rsa-case-pkg/deferred-activity/update/' . $activity->id;
					} elseif ($activity->status_id == 11) {
						//Waiting for Invoice Generation by ASP
						$url = '#!/rsa-case-pkg/approved-activity/list';
					} elseif ($activity->status_id == 15 || $activity->status_id == 16 || $activity->status_id == 27) {
						//Not Eligible for Payout || Own Patrol Activity - Not Eligible for Payout || LAPSED
						$url = '';
					}
				}

				// RM || ZM
				if (Entrust::can('own-rm-asp-activity-search') || Entrust::can('own-zm-asp-activity-search')) {
					//ASP Rejected CC Details - Waiting for ASP Data Entry || On Hold
					if ($activity->status_id == 2 || $activity->status_id == 4 || $activity->status_id == 17) {
						$url = '';
						//CASE WITH EXTENSION - DISABLED FOR NOW SAID BY HYDER 04 APRIL 2023
						// if (!empty($activity->caseSubmissionClosingDate) && Carbon::parse($activity->caseSubmissionClosingDate)->format('Y-m-d H:i:s') >= $today) {
						// 	$url = '#!/rsa-case-pkg/activity-status/1/view/' . $activity->id;
						// } else if (Carbon::parse($activity->caseCreatedAt)->format('Y-m-d H:i:s') >= $threeMonthsBefore) {
						// 	$url = '#!/rsa-case-pkg/activity-status/1/view/' . $activity->id;
						// }
					} elseif ($activity->status_id == 15 || $activity->status_id == 16 || $activity->status_id == 27) {
						//Not Eligible for Payout || Own Patrol Activity - Not Eligible for Payout || LAPSED
						$url = '';
					}
				}

				// APPROVER
				if (Auth::check()) {
					if (!empty(Auth::user()->activity_approval_level_id)) {
						//L1 AND ASP Completed Data Entry - Waiting for L1 Bulk / Individual Verification AND ASP Data Re-Entry Completed - Waiting for L1 Bulk / Individual Verification AND BO Rejected - Waiting for L1 Bulk / Individual Verification
						if (Auth::user()->activity_approval_level_id == 1 && ($activity->status_id == 5 || $activity->status_id == 6 || $activity->status_id == 8 || $activity->status_id == 9 || $activity->status_id == 22)) {
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						} elseif (Auth::user()->activity_approval_level_id == 2 && ($activity->status_id == 18 || $activity->status_id == 19)) {
							// L2 AND Waiting for L2 Bulk / Individual Verification
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						} elseif (Auth::user()->activity_approval_level_id == 3 && ($activity->status_id == 20 || $activity->status_id == 21)) {
							// L3 AND Waiting for L3 Bulk / Individual Verification
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						} elseif (Auth::user()->activity_approval_level_id == 4 && ($activity->status_id == 23 || $activity->status_id == 24)) {
							// L4 AND Waiting for L4 Bulk / Individual Verification
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						}
					}
				}

				//Invoiced - Waiting for Payment
				if ($activity->status_id == 12) {
					$url = '#!/rsa-case-pkg/invoice/view/' . $activity->invoiceId . '/1';
				} elseif ($activity->status_id == 13) {
					//Payment Inprogress
					$url = '#!/rsa-case-pkg/invoice/view/' . $activity->invoiceId . '/2';
				} elseif ($activity->status_id == 14) {
					//Paid
					$url = '#!/rsa-case-pkg/invoice/view/' . $activity->invoiceId . '/3';
				}

				$action = '';
				if (!empty($url)) {
					$action = '<div class="dataTable-actions" style="min-width: 125px;">
									<a href="' . $url . '" target="_blank">
					                	<i class="fa fa-external-link-square" aria-hidden="true"></i>
					            	</a>
			            		</div>';
				}
				return $action;
			})
			->make(true);
	}

	public function getSearchList(Request $request) {

		if (empty($request->caseNumber) && empty($request->vehicleRegistrationNumber) && empty($request->vin) && empty($request->mobileNumber) && empty($request->crmActivityId) && empty($request->csr)) {
			return Datatables::of([])->make(true);
		}

		$activities = Activity::with([
			'case' => function ($query) {
				$query->select([
					'id',
					'client_id',
					'call_center_id',
					DB::raw('DATE_FORMAT(date,"%d-%m-%Y %H:%i:%s") as case_date'),
					'number as case_number',
					DB::raw('COALESCE(csr, "--") as csr'),
					DB::raw('COALESCE(vehicle_registration_number, "--") as vehicle_registration_number'),
					DB::raw('COALESCE(vin_no, "--") as vin'),
					'created_at',
					'submission_closing_date',
				]);
			},
			'case.clientWithTrashed' => function ($query) {
				$query->select([
					'id',
					DB::raw('COALESCE(name, "--") as name'),
				]);
			},
			'case.callcenter' => function ($query) {
				$query->select([
					'id',
					DB::raw('COALESCE(name, "--") as name'),
				]);
			},
			'asp' => function ($query) {
				$query->select([
					'id',
					DB::raw('CONCAT(asp_code," / ",workshop_name) as name'),
					'user_id',
				]);
			},
			'asp.user' => function ($query) {
				$query->select([
					'id',
				]);
			},
			'serviceType' => function ($query) {
				$query->select([
					'id',
					DB::raw('COALESCE(name, "--") as name'),
				]);
			},
			'financeStatus' => function ($query) {
				$query->select([
					'id',
					DB::raw('COALESCE(name, "--") as name'),
				]);
			},
			'status' => function ($query) {
				$query->select([
					'id',
					DB::raw('COALESCE(name, "--") as name'),
				]);
			},
			'activityStatus' => function ($query) {
				$query->select([
					'id',
					DB::raw('COALESCE(name, "--") as name'),
				]);
			},
		])
			->select([
				'id',
				'invoice_id as invoiceId',
				'crm_activity_id',
				'case_id',
				'status_id',
				'asp_id',
				'service_type_id',
				'finance_status_id',
				'activity_status_id',
				'created_at',
			])
		;

		if (!empty($request->mobileNumber)) {
			$activities->whereHas('case', function ($query) use ($request) {
				$query->where('customer_contact_number', $request->mobileNumber);
			});
		} else if (!empty($request->caseNumber)) {
			$activities->whereHas('case', function ($query) use ($request) {
				$query->where('number', $request->caseNumber);
			});
		} else if (!empty($request->vehicleRegistrationNumber)) {
			$activities->whereHas('case', function ($query) use ($request) {
				$query->where('vehicle_registration_number', $request->vehicleRegistrationNumber);
			});
		} else if (!empty($request->vin)) {
			$activities->whereHas('case', function ($query) use ($request) {
				$query->where('vin_no', $request->vin);
			});
		} else if (!empty($request->csr)) {
			$activities->whereHas('case', function ($query) use ($request) {
				$query->where('csr', $request->csr);
			});
		} else if (!empty($request->crmActivityId)) {
			$activities->where('crm_activity_id', $request->crmActivityId);
		}

		if (!Entrust::can('all-asp-activity-search')) {

			//BACK OFFICE
			if (Entrust::can('mapped-state-asp-activity-search')) {
				$stateIds = StateUser::where('user_id', '=', Auth::user()->id)->pluck('state_id')->toArray();
				$activities->whereHas('asp', function ($query) use ($stateIds) {
					$query->whereIn('state_id', $stateIds);
				});
			} elseif (Entrust::can('own-asp-activity-search')) {
				// ASP || ASP FINANCE ADMIN
				if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
					$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
					$aspIds[] = Auth::user()->asp->id;
					$activities->whereHas('asp', function ($query) use ($aspIds) {
						$query->whereIn('id', $aspIds);
					});
				} else {
					$authUserId = Auth::user()->id;
					$activities->whereHas('asp.user', function ($query) use ($authUserId) {
						$query->where('id', $authUserId); // OWN ASP USER ID
					});
				}
			} elseif (Entrust::can('own-rm-asp-activity-search')) {
				// REGIONAL MANAGER
				$aspIds = Asp::where('regional_manager_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereHas('asp', function ($query) use ($aspIds) {
					$query->whereIn('id', $aspIds);
				});
			} elseif (Entrust::can('own-zm-asp-activity-search')) {
				// ZONAL MANAGER
				$aspIds = Asp::where('zm_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereHas('asp', function ($query) use ($aspIds) {
					$query->whereIn('id', $aspIds);
				});
			} elseif (Entrust::can('own-nm-asp-activity-search')) {
				// NATIONAL MANAGER
				$aspIds = Asp::where('nm_id', Auth::user()->id)->pluck('id')->toArray();
				$activities->whereHas('asp', function ($query) use ($aspIds) {
					$query->whereIn('id', $aspIds);
				});
			} else {
				$activities->whereNull('asp_id');
			}
		}

		$activities->orderBy('created_at', 'DESC');

		return Datatables::of($activities)
			->addColumn('action', function ($activity) {
				// VIEW PAGE FOR OTHER STATUSES
				$url = '#!/rsa-case-pkg/activity-status/1/view/' . $activity->id;

				$today = date('Y-m-d H:i:s');
				$threeMonthsBefore = date('Y-m-d H:i:s', strtotime("-3 months", strtotime($today)));

				// ASP || ASP FINANCE ADMIN
				if (Entrust::can('own-asp-activity-search')) {
					//ASP Rejected CC Details - Waiting for ASP Data Entry || On Hold
					if ($activity->status_id == 2 || $activity->status_id == 4 || $activity->status_id == 17) {
						$url = '';
						//CASE WITH EXTENSION
						if (!empty($activity->case->submission_closing_date) && Carbon::parse($activity->case->submission_closing_date)->format('Y-m-d H:i:s') >= $today) {
							$url = '#!/rsa-case-pkg/new-activity/update-details/' . $activity->id;
						} else if (Carbon::parse($activity->case->created_at)->format('Y-m-d H:i:s') >= $threeMonthsBefore) {
							$url = '#!/rsa-case-pkg/new-activity/update-details/' . $activity->id;
						}
					} elseif ($activity->status_id == 7) {
						//BO Rejected - Waiting for ASP Data Re-Entry
						$url = '#!/rsa-case-pkg/deferred-activity/update/' . $activity->id;
					} elseif ($activity->status_id == 11) {
						//Waiting for Invoice Generation by ASP
						$url = '#!/rsa-case-pkg/approved-activity/list';
					} elseif ($activity->status_id == 15 || $activity->status_id == 16 || $activity->status_id == 27) {
						//Not Eligible for Payout || Own Patrol Activity - Not Eligible for Payout || LAPSED
						$url = '';
					}
				}

				// RM || ZM
				if (Entrust::can('own-rm-asp-activity-search') || Entrust::can('own-zm-asp-activity-search')) {
					//ASP Rejected CC Details - Waiting for ASP Data Entry || On Hold
					if ($activity->status_id == 2 || $activity->status_id == 4 || $activity->status_id == 17) {
						$url = '';
						//CASE WITH EXTENSION - DISABLED FOR NOW SAID BY HYDER 04 APRIL 2023
						// if (!empty($activity->caseSubmissionClosingDate) && Carbon::parse($activity->caseSubmissionClosingDate)->format('Y-m-d H:i:s') >= $today) {
						// 	$url = '#!/rsa-case-pkg/activity-status/1/view/' . $activity->id;
						// } else if (Carbon::parse($activity->caseCreatedAt)->format('Y-m-d H:i:s') >= $threeMonthsBefore) {
						// 	$url = '#!/rsa-case-pkg/activity-status/1/view/' . $activity->id;
						// }
					} elseif ($activity->status_id == 15 || $activity->status_id == 16 || $activity->status_id == 27) {
						//Not Eligible for Payout || Own Patrol Activity - Not Eligible for Payout || LAPSED
						$url = '';
					}
				}

				// APPROVER
				if (Auth::check()) {
					if (!empty(Auth::user()->activity_approval_level_id)) {
						//L1 AND ASP Completed Data Entry - Waiting for L1 Bulk / Individual Verification AND ASP Data Re-Entry Completed - Waiting for L1 Bulk / Individual Verification AND BO Rejected - Waiting for L1 Bulk / Individual Verification
						if (Auth::user()->activity_approval_level_id == 1 && ($activity->status_id == 5 || $activity->status_id == 6 || $activity->status_id == 8 || $activity->status_id == 9 || $activity->status_id == 22)) {
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						} elseif (Auth::user()->activity_approval_level_id == 2 && ($activity->status_id == 18 || $activity->status_id == 19)) {
							// L2 AND Waiting for L2 Bulk / Individual Verification
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						} elseif (Auth::user()->activity_approval_level_id == 3 && ($activity->status_id == 20 || $activity->status_id == 21)) {
							// L3 AND Waiting for L3 Bulk / Individual Verification
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						} elseif (Auth::user()->activity_approval_level_id == 4 && ($activity->status_id == 23 || $activity->status_id == 24)) {
							// L4 AND Waiting for L4 Bulk / Individual Verification
							$url = '#!/rsa-case-pkg/activity-verification/2/view/' . $activity->id;
						}
					}
				}

				//Invoiced - Waiting for Payment
				if ($activity->status_id == 12) {
					$url = '#!/rsa-case-pkg/invoice/view/' . $activity->invoiceId . '/1';
				} elseif ($activity->status_id == 13) {
					//Payment Inprogress
					$url = '#!/rsa-case-pkg/invoice/view/' . $activity->invoiceId . '/2';
				} elseif ($activity->status_id == 14) {
					//Paid
					$url = '#!/rsa-case-pkg/invoice/view/' . $activity->invoiceId . '/3';
				}

				$action = '';
				if (!empty($url)) {
					$action = '<div class="dataTable-actions" style="min-width: 125px;">
								<a href="' . $url . '" target="_blank">
				                	<i class="fa fa-external-link-square" aria-hidden="true"></i>
				            	</a>
		            		</div>';
				}
				return $action;
			})
			->make(true);
	}

	public function getLatLongBasedOnLocation($location) {
		$mapMyIndiaController = new MapMyIndiaController;
		$get_eloc = $mapMyIndiaController->customTextPlaceDetailApi($location);
		if ($get_eloc['success'] && isset($get_eloc['data']->suggestedLocations[0]) && $get_eloc['data']->suggestedLocations[0]->eLoc) {
			$get_lat_lon = $mapMyIndiaController->elocPlaceDetailApi($get_eloc['data']->suggestedLocations[0]->eLoc);
			if ($get_lat_lon['success'] && !empty($get_lat_lon['data']->latitude) && !empty($get_lat_lon['data']->longitude)) {
				return $get_lat_lon['data']->latitude . ',' . $get_lat_lon['data']->longitude;
			} else {
				return $get_lat_lon;
			}

		} else {
			return $get_eloc;
		}
	}

	public function getUnclaimedTicketFilterData() {
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
		return response()->json($this->data);
	}

	public function getUnclaimedTicketList(Request $request) {

		if (Auth::user()->asp && !Auth::user()->asp->enable_unclaimed_tickets) {
			return Datatables::of([])->make(true);
		}

		$periods = getStartDateAndEndDate($request->date_range_period);
		$startDate = $periods['start_date'];
		$endDate = $periods['end_date'];

		$today = date('Y-m-d H:i:s');
		$threeMonthsBefore = date('Y-m-d H:i:s', strtotime("-3 months", strtotime($today)));

		$activities = Activity::select([
			'activities.id',
			'activities.crm_activity_id',
			'activities.is_towing_attachments_mandatory',
			'activities.status_id as status_id',
			'activities.number as activity_number',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			DB::raw('COALESCE(cases.vehicle_registration_number, "--") as vehicle_registration_number'),
			DB::raw('CONCAT(asps.asp_code," / ",asps.workshop_name) as asp'),
			'service_types.name as sub_service',
			'service_types.service_group_id',
			'activity_finance_statuses.name as finance_status',
			'activity_portal_statuses.name as status',
			'activity_statuses.name as activity_status',
			'clients.name as client',
			'configs.name as source',
			'call_centers.name as call_center',
		])
			->where('activities.status_id', 2) //ASP Rejected CC Details - Waiting for ASP Data Entry
			->where(function ($query) use ($startDate, $endDate) {
				if (!empty($startDate) && !empty($endDate)) {
					$query->whereRaw('DATE(cases.date) between "' . $startDate . '" and "' . $endDate . '"');
				}
			})
			->where(function ($query) use ($threeMonthsBefore) {
				$query->where(function ($q) {
					$q->whereNotNull('cases.submission_closing_date')
						->where('cases.submission_closing_date', '>=', date('Y-m-d H:i:s'));
				})
					->orWhere(function ($q) use ($threeMonthsBefore) {
						$q->whereNull('cases.submission_closing_date')
							->where('cases.created_at', '>=', $threeMonthsBefore);
					});
			})
			->leftjoin('asps', 'asps.id', 'activities.asp_id')
			->leftjoin('users', 'users.id', 'asps.user_id')
			->leftjoin('cases', 'cases.id', 'activities.case_id')
			->leftjoin('clients', 'clients.id', 'cases.client_id')
			->leftjoin('call_centers', 'call_centers.id', 'cases.call_center_id')
			->leftjoin('service_types', 'service_types.id', 'activities.service_type_id')
			->leftjoin('configs', 'configs.id', 'activities.data_src_id')
			->leftjoin('activity_finance_statuses', 'activity_finance_statuses.id', 'activities.finance_status_id')
			->leftjoin('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftjoin('activity_statuses', 'activity_statuses.id', 'activities.activity_status_id')
			->orderBy('cases.date', 'DESC')
			->groupBy('activities.id')
		;

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
		if ($request->get('activity_status_id')) {
			$activities->where('activities.activity_status_id', $request->get('activity_status_id'));
		}
		if ($request->get('client_id')) {
			$activities->where('cases.client_id', $request->get('client_id'));
		}

		// ASP FINANCE ADMIN
		if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
			$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
			$aspIds[] = Auth::user()->asp->id;
			$activities->whereIn('asps.id', $aspIds);
		} else {
			$activities->where('users.id', Auth::id());
		}

		return Datatables::of($activities)
			->filterColumn('asp', function ($query, $keyword) {
				$sql = "CONCAT(asps.asp_code,' / ',asps.workshop_name)  like ?";
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->addColumn('action', function ($activity) {
				$url = '#!/rsa-case-pkg/new-activity/update-details/' . $activity->id;
				$action = '<div class="dataTable-actions" style="min-width: 125px;">
					<a href="' . $url . '" target="_blank">
	                	<i class="fa fa-external-link-square" aria-hidden="true"></i>
	            	</a>
        		</div>';
				return $action;
			})
			->make(true);
	}

}
