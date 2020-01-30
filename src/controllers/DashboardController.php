<?php
//All roles dashboard in this controller (super-admin,rm,bo,approval-1,approval-2,finance )
namespace Abs\RsaCasePkg;
use App\Batch;
use App\Http\Controllers\Controller;
use Abs\RsaCasePkg\Actitivty;
use App\StateUser;
use App\Invoices;
use Auth;
use Carbon\Carbon;
use DB;
use Session;

class DashboardController extends Controller {
	public function __construct() {
	}

	public static function boData($col,$states){
		return $col->join('asps','asps.id','activities.asp_id')->whereIn('asps.state_id', $states);
	}

	public static function rmData($col){
		return $col->join('asps','asps.id','activities.asp_id')->where('asps.regional_manager_id',Auth::user()->id);
	}

	public function dashboardData() {
		/* super admin - selection */
		if (Entrust::can('admin-dashboard')) {
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
			$user_id = Auth::user()->id;
			if (((Entrust::can('admin-dashboard')) && (Session::get('portal_selection') == 1)) || Entrust::can('bo-dashboard') || Entrust::can('rm-dashboard')) {
				//New Ticket
				if(Auth::user()->hasRole('super-admin')){
					$this->data['role'] = 'super-admin';
				}elseif(Auth::user()->hasRole('bo')){
					$this->data['role'] = 'bo';
					$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
				}else{
					$this->data['role'] = 'rm';

				}
				$new_ticket_count = Activity::where('status_id',2);
				if(Auth::user()->hasRole('bo')){
					$new_ticket_count = $this->boData($new_ticket_count,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$new_ticket_count = $this->rmData($new_ticket_count);
				}
				$this->data['new_ticket_count'] =  $new_ticket_count->count();


				$today_new_ticket_count = Activity::where('status_id',2)
					->whereDate('activities.created_at', $today);
				if(Auth::user()->hasRole('bo')){
					$today_new_ticket_count = $this->boData($today_new_ticket_count,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$today_new_ticket_count = $this->rmData($today_new_ticket_count);
				}
				$this->data['today_new_ticket_count'] = $today_new_ticket_count->count();

				//
				$this_month_new_ticket_count =  Activity::join('cases','activities.case_id','=','cases.id')->where('activities.status_id',2)
					->whereMonth('cases.created_at', date('m', strtotime($today)))
					->whereYear('cases.created_at', date('Y', strtotime($today)));
				if(Auth::user()->hasRole('bo')){
					$this_month_new_ticket_count = $this->boData($this_month_new_ticket_count,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$this_month_new_ticket_count = $this->rmData($this_month_new_ticket_count);
				}
				$this->data['this_month_new_ticket_count'] = $this_month_new_ticket_count->count();

				//
				$prev_month_new_ticket_count = Activity::join('cases','activities.case_id','=','cases.id')->where('activities.status_id',2)
					->whereMonth('cases.created_at', date('m', strtotime($previous_month)))
					->whereYear('cases.created_at',date('Y', strtotime($previous_month)));
				if(Auth::user()->hasRole('bo')){
					$prev_month_new_ticket_count = $this->boData($prev_month_new_ticket_count,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$prev_month_new_ticket_count = $this->rmData($prev_month_new_ticket_count);
				}
				$this->data['prev_month_new_ticket_count'] = $prev_month_new_ticket_count->count();

				//Ticket in approval
				$tickets_in_approval = Activity::whereIn('status_id',[8,9,5,6]);
				if(Auth::user()->hasRole('bo')){
					$tickets_in_approval = $this->boData($tickets_in_approval,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$tickets_in_approval = $this->rmData($tickets_in_approval);
				}
				$this->data['tickets_in_approval'] = $tickets_in_approval->count();

				//
				$today_tickets_in_approval = Activity::join('cases','activities.case_id','=','cases.id')
					->whereIn('activities.status_id',[8,9,5,6])
					->whereDate('cases.created_at', $today);
				if(Auth::user()->hasRole('bo')){
					$today_tickets_in_approval = $this->boData($today_tickets_in_approval,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$today_tickets_in_approval = $this->rmData($today_tickets_in_approval);
				}
				$this->data['today_tickets_in_approval'] = $today_tickets_in_approval->count();

				//
				$this_month_tickets_in_approval =  Activity::join('cases','activities.case_id','=','cases.id')
					->whereIn('activities.status_id',[8,9,5,6])
					->whereMonth('cases.created_at', date('m', strtotime($today)))
					->whereYear('cases.created_at', date('Y', strtotime($today)));
				if(Auth::user()->hasRole('bo')){
					$this_month_tickets_in_approval = $this->boData($this_month_tickets_in_approval,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$this_month_tickets_in_approval = $this->rmData($this_month_tickets_in_approval);
				}
				$this->data['this_month_tickets_in_approval'] = $this_month_tickets_in_approval->count();

				//
				 $prev_month_tickets_in_approval = Activity::join('cases','activities.case_id','=','cases.id')
					->whereIn('activities.status_id',[8,9,5,6])
					->whereMonth('cases.created_at', date('m', strtotime($previous_month)))
					->whereYear('cases.created_at', date('Y', strtotime($previous_month)));
				if(Auth::user()->hasRole('bo')){
					$prev_month_tickets_in_approval = $this->boData($prev_month_tickets_in_approval,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$prev_month_tickets_in_approval = $this->rmData($prev_month_tickets_in_approval);
				}
				$this->data['prev_month_tickets_in_approval'] = $prev_month_tickets_in_approval->count();
				//

				$current_waiting_batch = Activity::join('cases','activities.case_id','=','cases.id')
				->where('activities.status_id',11);
				if(Auth::user()->hasRole('bo')){
					$current_waiting_batch = $this->boData($current_waiting_batch,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$current_waiting_batch = $this->rmData($current_waiting_batch);
				}
				$this->data['current_waiting_batch'] =  $current_waiting_batch->count();

				//tickets_approved
				$today_tickets_in_approved = Activity::join('cases','activities.case_id','=','cases.id')
				->where('activities.status_id',11)
				->whereDate('cases.created_at', $today);
				if(Auth::user()->hasRole('bo')){
					$today_tickets_in_approved = $this->boData($today_tickets_in_approved,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$today_tickets_in_approved = $this->rmData($today_tickets_in_approved);
				}
				$this->data['today_tickets_in_approved'] =  $today_tickets_in_approved->count();

				$this_month_tickets_in_approved = Activity::join('cases','activities.case_id','=','cases.id')
					->where('activities.status_id',11)
					->whereMonth('cases.created_at', date('m', strtotime($today)))
					->whereYear('cases.created_at', date('Y', strtotime($today)));
				if(Auth::user()->hasRole('bo')){
					$this_month_tickets_in_approved = $this->boData($this_month_tickets_in_approved,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$this_month_tickets_in_approved = $this->rmData($this_month_tickets_in_approved);
				}
				$this->data['this_month_tickets_in_approved'] = $this_month_tickets_in_approved->count();

				//
				$prev_month_tickets_in_approved = Activity::join('cases','activities.case_id','=','cases.id')
					->where('activities.status_id',11)
					->whereMonth('cases.created_at', date('m', strtotime($previous_month)))
					->whereYear('cases.created_at', date('Y', strtotime($previous_month)));
				if(Auth::user()->hasRole('bo')){
					$prev_month_tickets_in_approved = $this->boData($prev_month_tickets_in_approved,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$prev_month_tickets_in_approved = $this->rmData($prev_month_tickets_in_approved);
				}
				$this->data['prev_month_tickets_in_approved'] = $prev_month_tickets_in_approved->count();

				//Invoiced
				$invoiced = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->whereIn('Invoices.status_id',[1,3]);
				if(Auth::user()->hasRole('bo')){
					$invoiced = $this->boData($invoiced,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$invoiced = $this->rmData($invoiced);
				}
				$this->data['invoiced'] = $invoiced->count();

				$today_tickets_invoiced = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->whereIn('Invoices.status_id',[1,3])
					->whereDate('Invoices.created_at', $today);
				if(Auth::user()->hasRole('bo')){
					$today_tickets_invoiced = $this->boData($today_tickets_invoiced,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$today_tickets_invoiced = $this->rmData($today_tickets_invoiced);
				}
				$this->data['today_tickets_invoiced'] = $today_tickets_invoiced->count();

				//
				$tickets_invoiced_this_month =  Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->whereIn('Invoices.status_id',[1,3])
					->whereMonth('Invoices.created_at', date('m', strtotime($today)))
					->whereYear('Invoices.created_at', date('Y', strtotime($today)));
				if(Auth::user()->hasRole('bo')){
					$tickets_invoiced_this_month = $this->boData($tickets_invoiced_this_month,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$tickets_invoiced_this_month = $this->rmData($tickets_invoiced_this_month);
				}
				$this->data['tickets_invoiced_this_month'] = $tickets_invoiced_this_month->count();

				//
				$prev_month_tickets_invoiced = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->whereIn('Invoices.status_id',[1,3])
					->whereMonth('Invoices.created_at', date('m', strtotime($previous_month)))
					->whereYear('Invoices.created_at', date('Y', strtotime($previous_month)));
				if(Auth::user()->hasRole('bo')){
					$prev_month_tickets_invoiced = $this->boData($prev_month_tickets_invoiced,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$prev_month_tickets_invoiced = $this->rmData($prev_month_tickets_invoiced);
				}
				$this->data['prev_month_tickets_invoiced'] = $prev_month_tickets_invoiced->count();


				//Completed Ticket
				$total_ticket_complete = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->where('Invoices.status_id',2);
				if(Auth::user()->hasRole('bo')){
					$total_ticket_complete = $this->boData($total_ticket_complete,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$total_ticket_complete = $this->rmData($total_ticket_complete);
				}
				$this->data['total_ticket_complete'] = 	$total_ticket_complete->count();

				//
				$today_total_ticket_complete = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->where('Invoices.status_id',2)
					->whereDate('Invoices.updated_at', $today);
				if(Auth::user()->hasRole('bo')){
					$today_total_ticket_complete = $this->boData($today_total_ticket_complete,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$today_total_ticket_complete = $this->rmData($today_total_ticket_complete);
				}
				$this->data['today_total_ticket_complete'] = $today_total_ticket_complete->count();
				//dd($today,$this->data['today_total_ticket_complete'],$today_total_ticket_complete);
				//
				$this_month_total_ticket_complete = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->where('Invoices.status_id','=',2)
					->whereMonth('Invoices.updated_at', date('m', strtotime($today)))
					->whereYear('Invoices.updated_at', date('Y', strtotime($today)));
				//dd(date('m', strtotime($today)),date('Y', strtotime($today)),$this_month_total_ticket_complete );
				if(Auth::user()->hasRole('bo')){
					$this_month_total_ticket_complete = $this->boData($this_month_total_ticket_complete,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$this_month_total_ticket_complete = $this->rmData($this_month_total_ticket_complete);
				}
				$this->data['this_month_total_ticket_complete'] = $this_month_total_ticket_complete->count();

				$prev_month_total_ticket_complete = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->where('Invoices.status_id',2)
					->whereMonth('Invoices.updated_at', date('m', strtotime($previous_month)))
					->whereYear('Invoices.updated_at', date('Y', strtotime($previous_month)));
				if(Auth::user()->hasRole('bo')){
					$prev_month_total_ticket_complete = $this->boData($prev_month_total_ticket_complete,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$prev_month_total_ticket_complete = $this->rmData($prev_month_total_ticket_complete);
				}
				$this->data['prev_month_total_ticket_complete'] = $prev_month_total_ticket_complete->count();

				//completed ticket count (chart)
				$completed_ticket_count = Activity::leftJoin('Invoices','activities.invoice_id','=','Invoices.id')
					->select(DB::raw('IF(count(activities.id) IS NULL or count(activities.id) = "", 0, count(activities.id)) as `total`'), DB::raw('DATE_FORMAT(Invoices.updated_at,"%b") month'))
					->whereYear('Invoices.updated_at', date('Y', strtotime($today)))
					->where('Invoices.status_id', 2);
				if(Auth::user()->hasRole('bo')){
					$completed_ticket_count = $this->boData($completed_ticket_count,$states);
				}
				if(Auth::user()->hasRole('rm')){
					$completed_ticket_count = $this->rmData($completed_ticket_count);
				}
				$this->data['completed_ticket_count'] = $completed_ticket_count->groupBy('month')->pluck('total', 'month')->toArray();
				//payment chart(daywise)
				$payment_month = Invoices::select(DB::raw('IF(sum(Invoices.invoice_amount) IS NULL or sum(Invoices.invoice_amount) = "", 0, sum(Invoices.invoice_amount)) as total'), DB::raw('day(Invoices.updated_at) as day'))
					->wheremonth('Invoices.updated_at', date('m', strtotime($today)))
					->whereYear('Invoices.updated_at', date('Y', strtotime($today)))
					->where('Invoices.status_id',2)->groupby('day')
					->pluck('total', 'day')->toArray();
				
				$this->data['payment_day'] = $payment_month;
				//dd($payment_month);
				//payment chart(monthwise)
				$payment_year = Invoices::select(DB::raw('IF(sum(Invoices.invoice_amount) IS NULL OR sum(Invoices.invoice_amount) = "", 0, sum(Invoices.invoice_amount)) as total'), DB::raw('DATE_FORMAT(updated_at,"%b") month'))
					->whereYear('Invoices.updated_at', date('Y'))
					->where('Invoices.status_id',2)
					->groupby('month')
					->pluck('total', 'month')->toArray();
				$this->data['payment'] = $payment_year;

				//dd(Auth::user()->id);

				//Invoiced
				
				/*
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
			} elseif ((Entrust::can('admin-dashboard')) && (Session::get('portal_selection') == 2)) {
				return redirect()->route('sales_dashboard');
			}elseif (Entrust::can('finance-dashboard')) {
				$this->data['role'] = 'finance';

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
