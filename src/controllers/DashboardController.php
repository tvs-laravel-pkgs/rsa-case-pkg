<?php
//All roles dashboard in this controller (super-admin,rm,bo,approval-1,approval-2,finance )
namespace Abs\RsaCasePkg;
use App\Batch;
use App\Http\Controllers\Controller;
use Abs\RsaCasePkg\Actitivty;
use App\StateUser;
use Auth;
use Carbon\Carbon;
use DB;
use Session;

class DashboardController extends Controller {
	public function __construct() {
	}

	public function dashboardData() {
		/* super admin - selection */
		if (Auth::user()->hasRole('super-admin')) {
			if (!Session::has('portal_selection')) {
				//if not having session then redirect to selection page
				return redirect()->route('admin_selection');
			}
		}
		$today = date('Y-m-d');
		$previous_month = Carbon::now()->subMonth();
		
		if (Auth::user()->hasRole('asp')) {
			//by default asp login comes to  change password page
			return redirect()->route('changePassword');
		} else {
			//for other roles
			$primary_route = Auth::user()->role->primary_route_permission->route;
			if (($primary_route == 'dashboard') || ($primary_route == '')) {
				//if primary page is dashboard
				$user_id = Auth::user()->id;
				if ((Auth::user()->hasRole('super-admin')) && (Session::get('portal_selection') == 1)) {
					//New Ticket
					$this->data['role'] = 'super-admin';
					$this->data['new_ticket_count'] = Activity::where('status_id',2)->count();

					$this->data['today_new_ticket_count'] = Activity::where('status_id',2)
						->where('created_at', $today)
						->count();

					$this->data['this_month_new_ticket_count'] = Activity::join('cases','activities.case_id','=','cases.id')->where('activities.status_id',2)
						->whereMonth('cases.created_at', date('m', strtotime($today)))
						->whereYear('cases.created_at', date('Y', strtotime($today)))
						->count();

					$this->data['prev_month_new_ticket_count'] =Activity::join('cases','activities.case_id','=','cases.id')->where('activities.status_id',2)
						->whereMonth('cases.created_at', date('m', strtotime($previous_month)))
						->whereYear('cases.created_at',date('Y', strtotime($previous_month)))
						->count();

					//Ticket in approval
					$this->data['tickets_in_approval'] = Activity::whereIn('status_id',[8,9,5,6])
						->count();

					$this->data['today_tickets_in_approval'] = Activity::join('cases','activities.case_id','=','cases.id')
						->whereIn('activities.status_id',[8,9,5,6])
						->where('cases.created_at', $today)
						->count();

					$this->data['this_month_tickets_in_approval'] = Activity::join('cases','activities.case_id','=','cases.id')
						->whereIn('activities.status_id',[8,9,5,6])
						->whereMonth('cases.created_at', date('m', strtotime($today)))
						->whereYear('cases.created_at', date('Y', strtotime($today)))
						->count();

					$this->data['prev_month_tickets_in_approval'] = Activity::join('cases','activities.case_id','=','cases.id')
						->whereIn('activities.status_id',[8,9,5,6])
						->whereMonth('cases.created_at', date('m', strtotime($previous_month)))
						->whereYear('cases.created_at', date('Y', strtotime($previous_month)))
						->count();

					//tickets_approved
						$this->data['today_tickets_in_approved'] = Activity::join('cases','activities.case_id','=','cases.id')
						->where('activities.status_id',11)
						->where('cases.created_at', $today)
						->count();

					$this->data['this_month_tickets_in_approved'] = Activity::join('cases','activities.case_id','=','cases.id')
						->where('activities.status_id',11)
						->whereMonth('cases.created_at', date('m', strtotime($today)))
						->whereYear('cases.created_at', date('Y', strtotime($today)))
						->count();

					$this->data['prev_month_tickets_in_approved'] = Activity::join('cases','activities.case_id','=','cases.id')
						->where('activities.status_id',11)
						->whereMonth('cases.created_at', date('m', strtotime($previous_month)))
						->whereYear('cases.created_at', date('Y', strtotime($previous_month)))
						->count();

					//Invoiced
					$this->data['invoiced'] = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
						->whereIn('Invoices.status_id',[1,3])
						->count();

					$this->data['today_tickets_invoiced'] = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
						->whereIn('Invoices.status_id',[1,3])
						->where('Invoices.created_at', $today)
						->count();

					$this->data['tickets_invoiced_this_month'] = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
						->whereIn('Invoices.status_id',[1,3])
						->whereMonth('Invoices.created_at', date('m', strtotime($today)))
						->whereYear('Invoices.created_at', date('Y', strtotime($today)))
						->count();

					$this->data['prev_month_tickets_invoiced'] = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
						->whereIn('Invoices.status_id',[1,3])
						->whereMonth('Invoices.created_at', date('m', strtotime($previous_month)))
						->whereYear('Invoices.created_at', date('Y', strtotime($previous_month)))
						->count();

					//Completed Ticket
					$this->data['total_ticket_complete'] = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
						->where('Invoices.status_id',2)
						->count();

					/*$this->data['today_total_ticket_complete'] = Activity::where('flow_current_status', 'Payment Confirmed')
						->whereDay('updated_at', Carbon::now()->format('d'))
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->count();*/


					/*//payment chart(monthwise)
					$payment_year = Batch::select(DB::raw('IF(sum(paid_amount) IS NULL OR sum(paid_amount) = "", 0, sum(paid_amount)) as `total`'), DB::raw('DATE_FORMAT(updated_at,"%b") month'))
						->whereYear('updated_at', date('Y'))
						->where('status', 'Payment Confirmed')
						->groupby('month')
						->pluck('total', 'month')->toArray();
					$this->data['payment'] = $payment_year;

					//payment chart(daywise)
					$payment_month = Batch::select(DB::raw('IF(sum(paid_amount) IS NULL or sum(paid_amount) = "", 0, sum(paid_amount)) as `total`'), DB::raw('day(updated_at) day'))
						->wheremonth('updated_at', date('m'))
						->whereYear('updated_at', date('Y'))
						->where('status', 'Payment Confirmed')
						->groupby('day')
						->pluck('total', 'day')->toArray();
					$this->data['payment_day'] = $payment_month;*/

					//completed ticket count (chart)
					/*$this->data['completed_ticket_count'] = Actitivty::select(DB::raw('IF(count(id) IS NULL or count(id) = "", 0, count(id)) as `total`'), DB::raw('DATE_FORMAT(updated_at,"%b") month'))
						->whereYear('updated_at', date('Y'))
						->where('flow_current_status', "Payment Confirmed")
						->groupby('month')
						->pluck('total', 'month')->toArray();

					


					

					
					
					//tickets_in_approval
					

					

					

					$this->data['prev_month_tickets_in_approval'] = DB::table('mis_informations')
						->where(function ($query) {
							$query->where('flow_current_status', "Waiting for BO - Bulk Approval")
								->orWhere('flow_current_status', "Waiting for BO - Deferred Approval");
						})
						->whereMonth('updated_at', Carbon::now()->submonth()->month)
						->whereYear('updated_at', date('Y'))
						->count();
					//

					//tickets_in_invoiced
					

					

					
					//

					//total_ticket_complete
					

					

					$this->data['this_month_total_ticket_complete'] = Activity::where('flow_current_status', 'Payment Confirmed')
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->count();

					$this->data['prev_month_total_ticket_complete'] = Activity::where('flow_current_status', 'Payment Confirmed')
						->whereMonth('updated_at', Carbon::now()->submonth()->month)
						->whereYear('updated_at', date('Y'))
						->count();
					//Invoiced
					

					$this->data['prev_month_invoiced'] = Activity::where('flow_current_status', 'Waiting for Batch Generation')
						->whereMonth('updated_at', Carbon::now()->submonth()->month)
						->whereYear('updated_at', date('Y'))
						->count();

					//total_amounts
					$this->data['total_amounts'] = Batch::where('status', "Payment Confirmed")
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->sum('paid_amount');

					$this->data['prev_month_total_amounts'] = Batch::where('status', "Payment Confirmed")
						->whereMonth('updated_at', Carbon::now()->submonth()->month)
						->whereYear('updated_at', date('Y'))
						->sum('paid_amount');*/
					//
						//dd($this->data);
					//End has role superadmin
				} elseif ((Auth::user()->hasRole('super-admin')) && (Session::get('portal_selection') == 2)) {
					return redirect()->route('sales_dashboard');
				} elseif (Auth::user()->hasRole('bo')) {

					$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id');
					$statesid = $states->toArray();
					$this->data['current_new_ticket'] = 0;
					$this->data['this_month_new_ticket'] = 0;
					$this->data['previous_new_ticket'] = 0;
					$this->data['today_new_ticket'] = 0;
					$this->data['current_waiting_approval'] = 0;
					$this->data['this_month_waiting_approval'] = 0;
					$this->data['previous_waiting_approval'] = 0;
					$this->data['today_waiting_approval'] = 0;
					$this->data['current_paid'] = 0;
					$this->data['this_month_current_paid'] = 0;
					$this->data['previous_paid'] = 0;
					$this->data['today_paid'] = 0;
					$this->data['Approved_ticket_count'] = [];
					$this->data['current_waiting_batch'] = 0;
					$this->data['previous_waiting_batch'] = 0;
					$this->data['today_waiting_batch'] = 0;
					$this->data['this_month_waiting_batch'] = 0;

					/*
						$this->data['current_new_ticket'] = DB::table('mis_informations')
							->where('flow_current_status', 'Waiting for ASP Data Entry')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->whereIn('asps.state_id', $statesid)
							->count();

						$this->data['this_month_new_ticket'] = DB::table('mis_informations')
							->where('flow_current_status', 'Waiting for ASP Data Entry')
							->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
							->whereYear('mis_informations.updated_at', date('Y'))
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->whereIn('asps.state_id', $statesid)
							->count();

						$this->data['previous_new_ticket'] = DB::table('mis_informations')
							->where('flow_current_status', 'Waiting for ASP Data Entry')
							->whereMonth('mis_informations.updated_at', Carbon::now()->submonth()->format('m'))
							->whereYear('mis_informations.updated_at', date('Y'))
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->whereIn('asps.state_id', $statesid)
							->count();

						$this->data['today_new_ticket'] = DB::table('mis_informations')
							->where('flow_current_status', 'Waiting for ASP Data Entry')
							->whereDay('mis_informations.updated_at', date('d'))
							->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
							->whereYear('mis_informations.updated_at', date('Y'))
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->whereIn('asps.state_id', $statesid)
							->count();

						$this->data['current_waiting_approval'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereIn('asps.state_id', $statesid);
							})
							->where(function ($query) {
								$query->where('flow_current_status', 'Waiting for BO - Bulk Approval')
									->orWhere('flow_current_status', 'Waiting for BO - Deferred Approval');
							})
							->count();

						$this->data['this_month_waiting_approval'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid);
							})
							->where(function ($query) {
								$query->where('flow_current_status', 'Waiting for BO - Bulk Approval')
									->orWhere('flow_current_status', 'Waiting for BO - Deferred Approval');
							})
							->count();

						$this->data['previous_waiting_approval'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->submonth()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid);
							})
							->where(function ($query) {
								$query->where('flow_current_status', 'Waiting for BO - Bulk Approval')
									->orWhere('flow_current_status', 'Waiting for BO - Deferred Approval');
							})
							->count();

						$this->data['today_waiting_approval'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereDay('mis_informations.updated_at', date('d'))
									->whereIn('asps.state_id', $statesid);
							})
							->where(function ($query) {
								$query->where('flow_current_status', 'Waiting for BO - Bulk Approval')
									->orWhere('flow_current_status', 'Waiting for BO - Deferred Approval');
							})
							->count();

						$this->data['current_waiting_batch'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Waiting for Batch Generation');
							})->count();

						$this->data['this_month_waiting_batch'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Waiting for Batch Generation');
							})->count();

						$this->data['previous_waiting_batch'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->submonth()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Waiting for Batch Generation');
							})->count();

						$this->data['today_waiting_batch'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
									->whereDay('mis_informations.updated_at', date('d'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Waiting for Batch Generation');
							})->count();

						$this->data['current_paid'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Payment Confirmed');
							})->count();

						$this->data['this_month_current_paid'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Payment Confirmed');
							})->count();

						$this->data['previous_paid'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->submonth()->format('m'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Payment Confirmed');
							})->count();

						$this->data['today_paid'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->where(function ($query) use ($statesid) {
								$query->whereMonth('mis_informations.updated_at', Carbon::now()->format('m'))
									->whereDay('mis_informations.updated_at', date('d'))
									->whereYear('mis_informations.updated_at', date('Y'))
									->whereIn('asps.state_id', $statesid)
									->where('flow_current_status', 'Payment Confirmed');
							})->count();

						$this->data['Approved_ticket_count'] = DB::table('mis_informations')
							->join('asps', 'asps.id', '=', 'mis_informations.asp_id')
							->select(DB::raw('IF(count(mis_informations.id) IS NULL or count(mis_informations.id) = "", 0, count(mis_informations.id)) as `total`'), DB::raw('DATE_FORMAT(mis_informations.ticket_date_time,"%b") month'))
							->where('flow_current_status', 'Waiting for Invoice Generation')
							->whereYear('mis_informations.ticket_date_time', date('Y'))
							->whereIn('asps.state_id', $statesid)
							->groupby('month')->pluck('total', 'month')->toArray();
					*/

				} elseif (Auth::user()->hasRole('finance')) {
					//payment completed count (chart)
					$this->data['completed_payment_count'] = Batch::select(DB::raw('IF(count(id) IS NULL or count(id) = "", 0, count(id)) as `total`'), DB::raw('DATE_FORMAT(updated_at,"%b") month'))
						->whereYear('updated_at', date('Y'))
						->where('status', "Payment Confirmed")
						->groupby('month')
						->whereYear('updated_at', date('Y'))
						->pluck('total', 'month')->toArray();
					//

					//new_batches_count
					$this->data['today_new_batch_count'] = Batch::where(function ($query) {
						$query->where('status', 'Waiting for Payment')
							->orWhere('status', 'Payment Inprogress');
					})
						->whereDay('updated_at', Carbon::now()->format('d'))
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->count();

					$this->data['new_batch_count'] = Batch::where('status', "Waiting for Payment")->orWhere('status', "Payment Inprogress")
						->count();

					$this->data['this_month_new_batch_count'] = Batch::where(function ($query) {
						$query->where('status', 'Waiting for Payment')
							->orWhere('status', 'Payment Inprogress');
					})
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->count();

					$this->data['prev_month_new_batch_count'] = Batch::where(function ($query) {
						$query->where('status', 'Waiting for Payment')
							->orWhere('status', 'Payment Inprogress');
					})
						->whereMonth('updated_at', Carbon::now()->submonth()->month)
						->whereYear('updated_at', date('Y'))
						->count();
					//
					//payment completed
					$this->data['today_completed_payment_count'] = Batch::where('status', "Payment Confirmed")
						->whereDay('updated_at', Carbon::now()->format('d'))
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->count();

					$this->data['month_completed_payment_count'] = Batch::where('status', "Payment Confirmed")
						->count();

					$this->data['this_month_completed_payment_count'] = Batch::where('status', "Payment Confirmed")
						->whereMonth('updated_at', Carbon::now()->format('m'))
						->whereYear('updated_at', date('Y'))
						->count();

					$this->data['prev_month_completed_payment_count'] = Batch::where('status', "Payment Confirmed")
						->whereMonth('updated_at', Carbon::now()->submonth()->month)
						->whereYear('updated_at', date('Y'))
						->count();
					//
				} else {
					$this->data['no_access'] = [];
				}
				//dd('a',$this->data);
				//return view('dashboard/dashboards', $this->data);
				return response()->json(['success' => true,'data' => $this->data]);
			} else {
					//dd('asd',$primary_route);

				//the primary route is not a dashboard then it will redirects to what what it is in else case
				return redirect()->route($primary_route);
			}
		} // else for other roles

		/*if (Auth::user()->hasRole('approval-1')) {

						$this->data['Approved_ticket_count'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->select(DB::raw('
			                        IF(count(tickets.id) IS NULL or count(tickets.id) = "", 0, count(tickets.id)) as `total`'), DB::raw('DATE_FORMAT(logs.updated_at,"%b") month'))
							->where('logs.action', config('constants.ticket_statuses.A1_COMPLETED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereYear('logs.updated_at', date('Y'))
							->groupby('month')
							->pluck('total', 'month')->toArray();

			//new_ticket_count
						$this->data['today_new_ticket_count'] = Ticket::where('flow_current_status', config('constants.ticket_statuses.WAITING_FOR_APPROVAL1'))
							->whereDay('updated_at', Carbon::now()->format('d'))
							->whereMonth('updated_at', Carbon::now()->format('m'))
							->whereYear('updated_at', date('Y'))
							->count();

						$this->data['new_ticket_count'] = Ticket::where('flow_current_status', config('constants.ticket_statuses.WAITING_FOR_APPROVAL1'))
							->whereMonth('updated_at', Carbon::now()->format('m'))
							->whereYear('updated_at', date('Y'))
							->count();

						$this->data['prev_month_new_ticket_count'] = Ticket::where('flow_current_status', config('constants.ticket_statuses.WAITING_FOR_APPROVAL1'))
							->whereMonth('updated_at', Carbon::now()->submonth()->month)
							->whereYear('updated_at', date('Y'))
							->count();
			//
						//tickets_approved
						$this->data['today_tickets_in_approved'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A1_COMPLETED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereDay('logs.updated_at', Carbon::now()->format('d'))
							->whereMonth('logs.updated_at', Carbon::now()->format('m'))
							->whereYear('logs.updated_at', date('Y'))
							->count();

						$this->data['tickets_in_approved'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A1_COMPLETED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereMonth('logs.updated_at', Carbon::now()->format('m'))
							->whereYear('logs.updated_at', date('Y'))
							->count();

						$this->data['prev_month_tickets_in_approved'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A1_COMPLETED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereMonth('logs.updated_at', Carbon::now()->submonth()->month)
							->whereYear('logs.updated_at', date('Y'))
							->count();
			//

			//ticket rejected
						$this->data['today_ticket_rejected'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A1_REJECTED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereDay('logs.updated_at', Carbon::now()->format('d'))
							->whereMonth('logs.updated_at', Carbon::now()->format('m'))
							->whereYear('logs.updated_at', date('Y'))
							->count();

						$this->data['month_ticket_rejected'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A1_REJECTED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereMonth('logs.updated_at', Carbon::now()->format('m'))
							->whereYear('logs.updated_at', date('Y'))
							->count();

						$this->data['prev_month_ticket_rejected'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A1_REJECTED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereMonth('logs.updated_at', Carbon::now()->submonth()->month)
							->whereYear('logs.updated_at', date('Y'))
							->count();
			//
					}

					if (Auth::user()->hasRole('approval-2')) {

						$this->data['Approved_batch_count'] = DB::table('batches')
							->join('asps', 'batches.asp_id', 'asps.id')
							->join('logs', 'batches.id', 'entity_id')
							->select(DB::raw('IF(count(batches.id) IS NULL or count(batches.id) = "", 0, count(batches.id)) as `total`'), DB::raw('DATE_FORMAT(logs.updated_at,"%b") month'))
							->where(function ($query1) use ($user_id) {
								$query1->where('logs.action', config('constants.ticket_statuses.A2_COMPLETED'))
									->where('logs.entity_type', config('constants.entity_types.batch'))
									->where('logs.created_by', $user_id)
									->whereYear('batches.updated_at', date('Y'));
							})
							->groupby('month')->pluck('total', 'month')->toArray();

			//dd($this->data['Approved_batch_count']);
						//new_batches_count
						$this->data['today_new_batch_count'] = Batch::where('status', config('constants.ticket_statuses.WAITING_FOR_APPROVAL2'))
							->whereDay('updated_at', Carbon::now()->format('d'))
							->whereMonth('updated_at', Carbon::now()->format('m'))
							->whereYear('updated_at', date('Y'))
							->count();

						$this->data['new_batch_count'] = Batch::where('status', config('constants.ticket_statuses.WAITING_FOR_APPROVAL2'))
							->whereMonth('updated_at', Carbon::now()->format('m'))
							->whereYear('updated_at', date('Y'))
							->count();

						$this->data['prev_month_new_batch_count'] = Batch::where('status', config('constants.ticket_statuses.WAITING_FOR_APPROVAL2'))
							->whereMonth('updated_at', Carbon::now()->submonth()->month)
							->whereYear('updated_at', date('Y'))
							->count();
			//

			//ticket Approved
						$this->data['today_ticket_Approved'] = DB::table('batches')
							->join('logs', 'batches.id', 'entity_id')
							->where(function ($query1) use ($user_id) {
								$query1->where('logs.action', config('constants.ticket_statuses.A2_COMPLETED'))
									->where('logs.entity_type', config('constants.entity_types.batch'))
									->where('logs.created_by', $user_id)
									->whereDay('logs.updated_at', Carbon::now()->format('d'))
									->whereMonth('logs.updated_at', Carbon::now()->format('m'))
									->whereYear('logs.updated_at', date('Y'));
							})
							->count();
			//dd($this->data['today_ticket_Approved']);
						$this->data['month_ticket_Approved'] = DB::table('batches')
							->join('logs', 'batches.id', 'entity_id')
							->where(function ($query1) use ($user_id) {
								$query1->where('logs.action', config('constants.ticket_statuses.A2_COMPLETED'))
									->where('logs.entity_type', config('constants.entity_types.batch'))
									->where('logs.created_by', $user_id)
									->whereMonth('logs.updated_at', Carbon::now()->format('m'))
									->whereYear('logs.updated_at', date('Y'));
							})
							->count();

						$this->data['prev_month_ticket_Approved'] = DB::table('batches')
							->join('logs', 'batches.id', 'entity_id')
							->where(function ($query1) use ($user_id) {
								$query1->where('logs.action', config('constants.ticket_statuses.A2_COMPLETED'))
									->where('logs.entity_type', config('constants.entity_types.batch'))
									->where('logs.created_by', $user_id)
									->whereMonth('logs.updated_at', Carbon::now()->submonth()->month)
									->whereYear('logs.updated_at', date('Y'));
							})
							->count();

			//dd($this->data['prev_month_ticket_Approved']);

			//
						//ticket rejected

						$this->data['today_ticket_rejected'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A2_REJECTED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereDay('logs.updated_at', Carbon::now()->format('d'))
							->whereMonth('logs.updated_at', Carbon::now()->format('m'))
							->whereYear('logs.updated_at', date('Y'))
							->count();

						$this->data['month_ticket_rejected'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A2_REJECTED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereMonth('logs.updated_at', Carbon::now()->format('m'))
							->whereYear('logs.updated_at', date('Y'))
							->count();

						$this->data['prev_month_ticket_rejected'] = DB::table('tickets')
							->join('logs', 'tickets.id', 'entity_id')
							->where('logs.action', config('constants.ticket_statuses.A2_REJECTED'))
							->where('logs.entity_type', config('constants.entity_types.ticket'))
							->where('logs.created_by', $user_id)
							->whereMonth('logs.updated_at', Carbon::now()->submonth()->month)
							->whereYear('logs.updated_at', date('Y'))
							->count();
			//
					}
		*/
	}
}
