<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use App\CallCenter;
use App\District;
use App\Http\Controllers\Controller;
use App\ServiceType;
use App\State;
use Auth;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Validator;
use Yajra\Datatables\Datatables;

class ActivityReportController extends Controller {

	public function getExceptionalReportFilterData() {
		$this->data['extras'] = [
			'finance_status_list' => collect(ActivityFinanceStatus::select('name', 'id')->where('company_id', 1)->get())->prepend(['id' => '', 'name' => 'Select Finance Status Type']),
			'call_center_list' => collect(CallCenter::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Call Center Name']),
			'service_type_list' => collect(ServiceType::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Service Type']),
		];
		return response()->json($this->data);
	}

	public function getExceptionalReportList(Request $request) {
		$activities = Activity::select(
			'activities.id',
			DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as case_date'),
			'cases.number',
			'asps.asp_code',
			'asps.name as asp_name',
			'asps.is_self',
			'service_types.name as sub_service',
			'bo_km_travelled.value as bo_km',
			DB::raw('COALESCE(activities.exceptional_reason,"--") as deviation_reason'),
			'bo_paid_amt.value as bo_paid_amount'
		)
			->join('cases', 'cases.id', 'activities.case_id')
			->join('asps', 'asps.id', 'activities.asp_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->leftJoin('activity_details as bo_km_travelled', function ($join) {
				$join->on('bo_km_travelled.activity_id', 'activities.id')
					->where('bo_km_travelled.key_id', 158); //BO KM Travelled
			})
			->leftJoin('activity_details as bo_paid_amt', function ($join) {
				$join->on('bo_paid_amt.activity_id', 'activities.id')
					->where('bo_paid_amt.key_id', 182); //BO AMOUNT
			})
			->join('Invoices', function ($join) {
				$join->on('Invoices.id', 'activities.invoice_id')
					->where('Invoices.status_id', 2); //PAID
			})
			->where('activities.is_exceptional_check', 1) // IS EXCEPTIONAL
			->where('cases.company_id', Auth::user()->company_id)
			->orderBy('cases.date', 'DESC')
		;

		if ($request->get('ticket_date')) {
			$activities->whereRaw('DATE_FORMAT(cases.date,"%d-%m-%Y") =  "' . $request->get('ticket_date') . '"');
		}
		if ($request->get('case_number')) {
			$activities->where('cases.number', 'LIKE', '%' . $request->get('case_number') . '%');
		}
		if ($request->get('call_center_id')) {
			$activities->where('cases.call_center_id', $request->get('call_center_id'));
		}
		if ($request->get('service_type_id')) {
			$activities->where('activities.service_type_id', $request->get('service_type_id'));
		}
		if ($request->get('finance_status_id')) {
			$activities->where('activities.finance_status_id', $request->get('finance_status_id'));
		}

		return Datatables::of($activities)
			->addColumn('asp_type', function ($activity) {
				return ($activity->is_self) ? 'Self' : 'Non Self';
			})
			->setRowAttr([
				'id' => function ($activity) {
					return route('angular') . '/#!/rsa-case-pkg/activity-status/3/view/' . $activity->id;
				},
			])
			->make(true);
	}

	public function getReconciliationReport() {
		$user_id = Auth::user()->id;

		$total_amount_paid_in_year = Activity::join('activity_details', function ($join) {
			$join->on('activity_details.activity_id', 'activities.id')
				->where('activity_details.key_id', 182); //BO AMOUNT
		})
			->where('activities.status_id', 14) //PAID
			->whereYear('activities.updated_at', date('Y'))
			->sum('activity_details.value')
		;

		$amount_of_tickets_submitted = Activity::join('activity_details', function ($join) {
			$join->on('activity_details.activity_id', 'activities.id')
				->where('activity_details.key_id', 182); //BO AMOUNT
		})
			->where('activities.status_id', 12) //INVOICED-WAITING FOR PAYMENT
			->orWhere('activities.status_id', 13) //PAYMENT INPROGRESS
			->whereYear('activities.updated_at', date('Y'))
			->sum('activity_details.value')
		;

		$total_amount_submit_in_year = Activity::select(
			'activities.id',
			DB::raw('sum(activity_details.value) as total'),
			DB::raw('DATE_FORMAT(activities.updated_at,"%b") as month')
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 12) //INVOICED-WAITING FOR PAYMENT
			->orWhere('activities.status_id', 13) //PAYMENT INPROGRESS
			->groupby('month')
			->orderBy('activities.updated_at', 'ASC')
			->pluck('total', 'month')
			->toArray()
		;

		$amount_of_bills_yet_to_receive = Activity::join('activity_details', function ($join) {
			$join->on('activity_details.activity_id', 'activities.id')
				->where('activity_details.key_id', 182); //BO AMOUNT
		})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 1) //CASE CLOSED WAITING FOR ASP GENERATE INVOICE
			->orWhere('activities.status_id', 11) //BP APPROVED WAITING FOR ASP INVOICE GENERATION
			->sum('activity_details.value')
		;

		$amount_bills_yet_to_receive = Activity::select(
			'activities.id',
			DB::raw('sum(activity_details.value) as total'),
			DB::raw('DATE_FORMAT(activities.updated_at,"%b") as month')
		)
			->join('activity_details', function ($join) {
				$join->on('activities.id', 'activity_details.activity_id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->where(function ($query) {
				$query->whereYear('activities.updated_at', date('Y'))
					->where('activities.status_id', 1) //CASE CLOSED WAITING FOR ASP GENERATE INVOICE
					->orWhere('activities.status_id', 11); //BP APPROVED WAITING FOR ASP INVOICE GENERATION
			})
			->groupBy('month')
			->pluck('total', 'month')
			->toArray()
		;

		$total_count_of_tickets_in_year = Activity::where('status_id', 14) //PAID
			->whereYear('updated_at', date('Y'))
			->count();

		$total_count_of_tickets_submitted = Activity::where('status_id', 12) //INVOICED-WAITING FOR PAYMENT
			->orWhere('status_id', 13) //PAYMENT INPROGRESS
			->whereYear('updated_at', date('Y'))
			->pluck('id')
			->toArray()
		;

		$bills_yet_to_receive = Activity::where('activities.status_id', 1) //CASE CLOSED WAITING FOR ASP GENERATE INVOICE
			->orWhere('activities.status_id', 11) //BP APPROVED WAITING FOR ASP INVOICE GENERATION
			->whereYear('activities.updated_at', date('Y'))
			->count()
		;

		$total_count_submit_in_year = Activity::select(
			'activities.id',
			DB::raw('count(activity_details.value) as total'),
			DB::raw('DATE_FORMAT(activities.updated_at,"%b") as month')
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 12) //INVOICED-WAITING FOR PAYMENT
			->orWhere('activities.status_id', 13) //PAYMENT INPROGRESS
			->orderBy('activities.updated_at', 'ASC')
			->groupby('month')
			->pluck('total', 'month')
			->toArray()
		;

		$count_bills_yet_to_receive = Activity::select(
			DB::raw('count(id) as total'),
			DB::raw('DATE_FORMAT(updated_at,"%b") month')
		)
			->whereYear('updated_at', date('Y'))
			->where('activities.status_id', 1) //CASE CLOSED WAITING FOR ASP GENERATE INVOICE
			->orWhere('activities.status_id', 11) //BP APPROVED WAITING FOR ASP INVOICE GENERATION
			->groupBy('month')
			->pluck('total', 'month')
			->toArray()
		;

		$this->data['extras'] = [
			'total_amount_submit_in_year_chart' => $total_amount_submit_in_year,
			'amount_of_bills_yet_to_receive_chart' => $amount_bills_yet_to_receive,
			'total_count_yet_to_receive_in_year_chart' => $count_bills_yet_to_receive,
			'total_count_submit_in_year_chart' => $total_count_submit_in_year,
			'total_amount_paid_in_year' => number_format($total_amount_paid_in_year, 2),
			'amount_of_tickets_submitted' => number_format($amount_of_tickets_submitted, 2),
			'amount_of_bills_yet_to_receive' => number_format($amount_of_bills_yet_to_receive, 2),
			'total_count_of_tickets_in_year' => $total_count_of_tickets_in_year,
			'total_count_of_tickets_submitted' => count($total_count_of_tickets_submitted),
			'bills_yet_to_receive' => $bills_yet_to_receive,
		];

		$Total_amount_paid = Activity::select(
			DB::raw('FORMAT(sum(activity_details.value), 2) as total'),
			DB::raw('DATE_FORMAT(activities.updated_at,"%b") as month')
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->groupBy('month')
			->pluck('total', 'month')
			->toArray()
		;

		$Total_count_of_tickets = Activity::select(
			DB::raw('count(id) as total'),
			DB::raw('DATE_FORMAT(updated_at,"%b") month'))
			->whereYear('updated_at', date('Y'))
			->where('status_id', 14) //PAID
			->groupby('month')
			->pluck('total', 'month')
			->toArray();

		for ($i = 1; $i <= 12; $i++) {
			if (date('m') >= $i) {
				$months[] = $i;
				$monthes[] = date('M', strtotime('01-' . $i . '-' . date('Y')));
			}
		}
		$this->data['monthes'] = $monthes;

		//MONTHLY INFORMATION TABLE DATA
		foreach ($months as $month) {
			$month = date('M', strtotime('01-' . $month . '-' . date('Y')));
			$this->data['month_wise_data']['Total_amount_paids'][$month] = array_key_exists($month, $Total_amount_paid) ? $Total_amount_paid[$month] : 0;
			$this->data['month_wise_data']['Total_count_of_tickets'][$month] = array_key_exists($month, $Total_count_of_tickets) ? $Total_count_of_tickets[$month] : 0;
			$this->data['month_wise_data']['total_amount_submit_in_year'][$month] = array_key_exists($month, $total_amount_submit_in_year) ? $total_amount_submit_in_year[$month] : 0;
			$this->data['month_wise_data']['total_count_submit_in_year'][$month] = array_key_exists($month, $total_count_submit_in_year) ? $total_count_submit_in_year[$month] : 0;
			$this->data['month_wise_data']['amount_bills_yet_to_receive'][$month] = array_key_exists($month, $amount_bills_yet_to_receive) ? $amount_bills_yet_to_receive[$month] : 0;
			$this->data['month_wise_data']['count_bills_yet_to_receive'][$month] = array_key_exists($month, $count_bills_yet_to_receive) ? $count_bills_yet_to_receive[$month] : 0;
		}

		return response()->json($this->data);
	}

	public function getProvisionalReport(Request $request) {

		$before_bo_validation_ticket_count = Activity::join('asps', 'asps.id', 'activities.asp_id')
			->join('cases', 'cases.id', 'activities.case_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('asps.workshop_type', "!=", 1)
			->where('activities.finance_status_id', "!=", 3)
			->where('activities.status_id', 2)
			->count('activities.id')
		;

		$before_bo_validation_net_amount = Activity::join('asps', 'asps.id', 'activities.asp_id')
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 174);
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('activities.finance_status_id', "!=", 3)
			->where('asps.workshop_type', "!=", 1)
			->where('activities.status_id', 2)
			->sum('activity_details.value')
		;

		$before_bo_validation_tax = Activity::join('asps', 'asps.id', 'activities.asp_id')
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 177);
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('activities.finance_status_id', "!=", 3)
			->where('asps.workshop_type', "!=", 1)
			->where('activities.status_id', 2)
			->sum('activity_details.value')
		;

		$before_bo_validation_invoice_amount = Activity::join('asps', 'asps.id', 'activities.asp_id')
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 180); //CC AMOUNT
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('activities.finance_status_id', "!=", 3)
			->where('asps.workshop_type', "!=", 1)
			->where('activities.status_id', 2)
			->sum('activity_details.value')
		;

		$this->data['report'] = [
			'date' => date('M Y'),
			'date_from' => date('01/m/Y'),
			'date_to' => date('t/m/Y'),
			'before_bo_validation_ticket_count' => $before_bo_validation_ticket_count,
			'before_bo_validation_net_amount' => $before_bo_validation_net_amount,
			'before_bo_validation_tax' => $before_bo_validation_tax ? $before_bo_validation_tax : 0,
			'before_bo_validation_invoice_amount' => $before_bo_validation_invoice_amount,
		];

		$report_summary = Activity::select(
			'call_centers.name as call_center_name',
			'service_types.name as service_name',
			DB::raw('Round(sum(activity_details.value),2) as total'),
			DB::raw('min(day(cases.date)) as min_day'),
			DB::raw('max(day(cases.date)) as max_day'),
			DB::raw('Count(activities.id) as ticket_count')
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 180); //CC AMOUNT
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('asps', 'asps.id', 'activities.asp_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('activities.status_id', 2) //ASP DATA ENTRY
			->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
			->where('asps.workshop_type', "!=", 1)
			->groupBy('service_types.id')
			->get()
			->toArray()
		;

		$report_summary_total_amount = Activity::join('activity_details', function ($join) {
			$join->on('activity_details.activity_id', 'activities.id')
				->where('activity_details.key_id', 180); //CC AMOUNT
		})
			->join('cases', 'cases.id', 'activities.case_id')
			->join('clients', 'clients.id', 'cases.client_id')
			->join('asps', 'asps.id', 'activities.asp_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('activities.status_id', 2) //ASP DATA ENTRY
			->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
			->where('asps.workshop_type', "!=", 1)
			->sum('activity_details.value')
		;

		$count_of_tickets = Activity::join('cases', 'cases.id', 'activities.case_id')
			->join('asps', 'asps.id', 'activities.asp_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->whereDate('cases.date', '>=', date('Y-m-d'))
			->whereDate('cases.date', '<=', date('Y-m-d'))
			->where('activities.status_id', 2) //ASP DATA ENTRY
			->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
			->where('asps.workshop_type', "!=", 1)
			->get()
			->count()
		;

		$this->data['summary'] = [
			'date' => date('M Y'),
			'date_from' => date('01/m/Y'),
			'date_to' => date('t/m/Y'),
			'report_summary' => $report_summary,
			'report_summary_total_amount' => $report_summary_total_amount,
			'count_of_tickets' => $count_of_tickets,
		];

		$this->data['services_type_list'] = ServiceType::select('id', 'name')->orderBy('name')->get();
		$this->data['check_new_update'] = 0;

		return response()->json($this->data);
	}

	public function getReportBasedDate(Request $request) {

		$date_from_to = explode('-', $request->daterange);
		$date1 = strtr(trim($date_from_to[0]), '/', '-');
		$date_from = date("Y-m-d", strtotime($date1));
		$date2 = strtr(trim($date_from_to[1]), '/', '-');
		$date_to = date('Y-m-d', strtotime($date2));

		$before_bo_validation_invoice_amount = Activity::join('asps', 'asps.id', 'activities.asp_id')
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 180); //CC AMOUNT
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->whereBetween('cases.date', [$date_from, $date_to])
			->where('activities.finance_status_id', "!=", 3)
			->where('asps.workshop_type', "!=", 1)
			->where('activities.status_id', 2)
			->sum('activity_details.value')
		;

		if ($request->id == 1) {
			$before_bo_validation_ticket_count = Activity::join('asps', 'asps.id', 'activities.asp_id')
				->join('cases', 'cases.id', 'activities.case_id')
				->whereBetween('cases.date', [$date_from, $date_to])
				->where('asps.workshop_type', "!=", 1)
				->where('activities.finance_status_id', "!=", 3)
				->where('activities.status_id', 2)
				->count('activities.id')
			;

			$before_bo_validation_net_amount = Activity::join('asps', 'asps.id', 'activities.asp_id')
				->join('activity_details', function ($join) {
					$join->on('activity_details.activity_id', 'activities.id')
						->where('activity_details.key_id', 174); // CC AMOUNT
				})
				->join('cases', 'cases.id', 'activities.case_id')
				->whereBetween('cases.date', [$date_from, $date_to])
				->where('activities.finance_status_id', "!=", 3)
				->where('asps.workshop_type', "!=", 1)
				->where('activities.status_id', 2)
				->sum('activity_details.value')
			;

			$before_bo_validation_tax = Activity::join('asps', 'asps.id', 'activities.asp_id')
				->join('activity_details', function ($join) {
					$join->on('activity_details.activity_id', 'activities.id')
						->where('activity_details.key_id', 177); // CC TAX AMOUNT
				})
				->join('cases', 'cases.id', 'activities.case_id')
				->whereBetween('cases.date', [$date_from, $date_to])
				->where('activities.finance_status_id', "!=", 3)
				->where('asps.workshop_type', "!=", 1)
				->where('activities.status_id', 2)
				->sum('activity_details.value')
			;

			$this->data['report'] = [
				'date' => date('M Y'),
				'date_from' => trim($date_from_to[0]),
				'date_to' => trim($date_from_to[1]),
				'before_bo_validation_ticket_count' => $before_bo_validation_ticket_count,
				'before_bo_validation_invoice_amount' => $before_bo_validation_invoice_amount,
				'before_bo_validation_net_amount' => $before_bo_validation_net_amount,
				'before_bo_validation_tax' => $before_bo_validation_tax ? $before_bo_validation_tax : 0,
			];
		}
		if ($request->id == 2) {
			$report_summary = Activity::select(
				'call_centers.name as call_center_name',
				'service_types.name as service_name',
				DB::raw('Round(sum(activity_details.value),2) as total'),
				DB::raw('min(day(cases.date)) as min_day'),
				DB::raw('max(day(cases.date)) as max_day'),
				DB::raw('Count(activities.id) as ticket_count')
			)
				->join('activity_details', function ($join) {
					$join->on('activity_details.activity_id', 'activities.id')
						->where('activity_details.key_id', 180); //CC AMOUNT
				})
				->join('cases', 'cases.id', 'activities.case_id')
				->join('call_centers', 'call_centers.id', 'cases.call_center_id')
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('service_types', 'service_types.id', 'activities.service_type_id')
				->whereBetween('cases.date', [$date_from, $date_to])
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('asps.workshop_type', "!=", 1)
				->groupBy('service_types.id')
				->get()
				->toArray()
			;

			$report_summary_total_amount = Activity::join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 180); //CC AMOUNT
			})
				->join('cases', 'cases.id', 'activities.case_id')
				->join('clients', 'clients.id', 'cases.client_id')
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('call_centers', 'call_centers.id', 'cases.call_center_id')
				->whereBetween('cases.date', [$date_from, $date_to])
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('asps.workshop_type', "!=", 1)
				->sum('activity_details.value')
			;

			$count_of_tickets = Activity::join('cases', 'cases.id', 'activities.case_id')
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('service_types', 'service_types.id', 'activities.service_type_id')
				->join('call_centers', 'call_centers.id', 'cases.call_center_id')
				->whereBetween('cases.date', [$date_from, $date_to])
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('asps.workshop_type', "!=", 1)
				->get()
				->count()
			;

			$this->data['summary'] = [
				'date' => date('M Y'),
				'date_from' => trim($date_from_to[0]),
				'date_to' => trim($date_from_to[1]),
				'report_summary' => $report_summary,
				'before_bo_validation_invoice_amount' => $before_bo_validation_invoice_amount,
				'report_summary_total_amount' => $report_summary_total_amount,
				'count_of_tickets' => $count_of_tickets,
			];
		}
		$this->data['services_type_list'] = ServiceType::select('id', 'name')->orderBy('name')->get();
		$this->data['check_new_update'] = 0;

		return response()->json($this->data);
	}

	public function exportProvisionalReport(Request $request) {
		// dd($request->all());
		$error_messages = [
			'services_type_id.required' => "Please Select services Types",
		];

		$validator = Validator::make($request->all(), [
			'services_type_id' => [
				'required:true',
			],
		], $error_messages);

		if (empty($request->services_type_id)) {
			return redirect('/#!/rsa-case-pkg/provisional-report/view')->with(['errors' => $validator->errors()->all()]);
		}
		try {
			ini_set('max_execution_time', 0);
			ini_set('display_errors', 1);
			ini_set("memory_limit", "10000M");
			ob_end_clean();
			ob_start();

			$date = explode("-", $request->period);
			$range1 = date("Y-m-d", strtotime(str_replace('/', '-', $date[0])));
			$range2 = date("Y-m-d", strtotime(str_replace('/', '-', $date[1])));
			// dump($range1, $range2, json_decode($request->services_type_id));

			$services_type_ids = json_decode($request->services_type_id);

			$activity_infos = Activity::whereIn('activities.service_type_id', $services_type_ids)
				->join('cases', 'cases.id', 'activities.case_id')
				->join('asps', 'asps.id', 'activities.asp_id')
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->whereBetween('cases.date', [$range1, $range2])
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
			;

			$activity_infos_summary_cal = Activity::whereIn('activities.service_type_id', $services_type_ids)
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('cases', 'cases.id', 'activities.case_id')
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->whereBetween('cases.date', [$range1, $range2])
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('asps.workshop_type', "!=", 1)
			;

			$total_count = $activity_infos->count('activities.id');

			$total_activity_net_amount = $activity_infos_summary_cal->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 174); //CC AMOUNT
			})
				->sum('activity_details.value')
			;
			// dd($activity_infos_summary_cal);
			$total_activity_tax = Activity::whereIn('activities.service_type_id', $services_type_ids)
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('cases', 'cases.id', 'activities.case_id')
				->join('activity_details', function ($join) {
					$join->on('activity_details.activity_id', 'activities.id')
						->where('activity_details.key_id', 177); //CC TAX AMOUNT
				})
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->whereBetween('cases.date', [$range1, $range2])
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('asps.workshop_type', "!=", 1)
				->sum('activity_details.value')
			;

			$total_activity_invoice_amount = Activity::whereIn('activities.service_type_id', $services_type_ids)
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('cases', 'cases.id', 'activities.case_id')
				->join('activity_details', function ($join) {
					$join->on('activity_details.activity_id', 'activities.id')
						->where('activity_details.key_id', 180); //CC AMOUNT
				})
				->where('activities.status_id', 2) //ASP DATA ENTRY
				->whereBetween('cases.date', [$range1, $range2])
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('asps.workshop_type', "!=", 1)
				->sum('activity_details.value')
			;

			$count_splitup = Activity::select(
				DB::raw('COUNT(activities.id) as ticket_count'),
				'activities.service_type_id as service_type_id',
				'service_types.name as service_name'
			)
				->join('cases', 'cases.id', 'activities.case_id')
				->join('asps', 'asps.id', 'activities.asp_id')
				->join('service_types', 'service_types.id', 'activities.service_type_id')
				->whereIn('activities.service_type_id', $services_type_ids)
				->whereBetween('cases.date', [$range1, $range2])
				->where('activities.finance_status_id', "!=", 3) //NO PAYOUT
				->where('activities.status_id', 2)
				->groupBy('activities.service_type_id')
				->get()
				->keyBy('service_type_id')
				->toArray()
			;

			if ($total_count == 0) {
				return redirect()->back()->with(['error' => 'No Tickets found for given period & service types']);
			}

			$selected_statuses = $request->services_type_id;
			// dd($selected_statuses);

			//SUMMARY REPORT
			$summary_period = ['Period', date('d/M/Y', strtotime($range1)) . ' to ' . date('d/M/Y', strtotime($range2))];
			$summary[] = ['Services', 'Count'];
			foreach (json_decode($request->services_type_id) as $k => $services_type_id) {
				if (isset($count_splitup[$services_type_id]['ticket_count'])) {
					$count = $count_splitup[$services_type_id]['ticket_count'];
					$service_name = $count_splitup[$services_type_id]['service_name'];
				} else {
					$count = 0;
					$service_name = ServiceType::where('id', $services_type_id)->pluck('name')->first();
				}
				$summary[] = [$service_name, $count];
			}

			$summary[] = ['Total Tickets', $total_count];
			$summary[] = ['Net Total Before approved of BO', $total_activity_net_amount];
			$summary[] = ['Tax Total Before approved of BO', $total_activity_tax];
			$summary[] = ['Grand Total Before approved of BO', $total_activity_invoice_amount];

			//FOR TICKET INFORMATION
			$tickets = $activity_infos
				->select(
					'asps.asp_code',
					'asps.axpta_code',
					'asps.workshop_name',
					// 'asps.asp_status_id',
					'asps.name',
					'locations.name as location',
					'districts.name as district',
					'call_centers.name as call_centers_name',
					'clients.name as client_name',
					'service_types.name as asp_service_type',
					'service_types.name as bo_service_type',
					DB::raw('DATE_FORMAT(Invoices.created_at,"%d/%m/%Y") as invoice_date'),
					'Invoices.invoice_no as invoice_no',
					'service_types.name as activity_service_type',
					'asps.workshop_type',
					'cases.customer_name',
					'cases.customer_contact_number',
					'cases.contact_number',
					'cases.km_during_breakdown',
					'asps.location_type',
					'states.name as state',
					'activity_portal_statuses.name as flow_current_status',
					DB::raw('(CASE WHEN asps.has_gst=1 THEN "Yes" ELSE "No" END) as has_gst'),
					DB::raw('(CASE WHEN asps.is_self=1 THEN "Self" ELSE "Non Self" END) as is_self'),
					DB::raw('(CASE WHEN asps.is_auto_invoice=1 THEN "Yes" ELSE "No" END) as is_auto_invoice'),
					'vehicle_models.name as vehicle_model',
					'vehicle_makes.name as vehicle_makes',
					'cases.vehicle_registration_number',
					'cases.vin_no',
					'cases.membership_type',
					'cases.bd_lat',
					'cases.bd_long',
					'cases.bd_location',
					'subjects.name as service_type',
					'activities.*'

					// $ticket->asp->has_gst ? 'Yes' : 'No';
					// $ticket->asp->is_self == 1 ? 'Self' : 'Non Self';
					// $ticket->asp->is_auto_invoice == 1 ? 'Yes' : 'No';
					// DB::raw('IF(mis_informations.claim_status=0,"NEW","DEFERED") as claim_status')
				)
				->join('service_types', 'service_types.id', 'activities.service_type_id')
				->join('call_centers', 'call_centers.id', 'cases.call_center_id')
				->join('subjects', 'subjects.id', 'cases.subject_id')

				->join('clients', 'clients.id', 'cases.client_id')
				->join('vehicle_models', 'vehicle_models.id', 'cases.vehicle_model_id')
				->join('vehicle_makes', 'vehicle_makes.id', 'vehicle_models.vehicle_make_id')
				->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
				->join('locations', 'asps.location_id', 'locations.id')
				->leftJoin('Invoices', 'Invoices.id', 'activities.invoice_id')
				->join('districts', 'districts.id', 'asps.district_id')
				->join('states', 'states.id', 'asps.state_id')
				->where('activity_portal_statuses.company_id', Auth::user()->company_id)
				->orderBy('asps.asp_code')
				->orderBy('cases.date')
				->get()
				// ->toArray()
			;

			foreach ($tickets as $ticket) {
				$mis_km = $ticket->detail(280) ? $ticket->detail(280) : NULL; //CC TOTAL KM
				$non_member_charges = $ticket->detail(303) ? $ticket->detail(303) : NULL; //MEMBERSHIP CHARGES
				$service_charges = $ticket->detail(302) ? $ticket->detail(302) : NULL; //SERVICE CHARGES
				$bo_net_amount = $ticket->detail(176) ? $ticket->detail(176) : NULL; //BO NET AMOUNT
				$deduction = $ticket->detail(173) ? $ticket->detail(173) : NULL; //DETECTION
				$mis_km_charge = $ticket->detail(150) ? $ticket->detail(150) : NULL; //CC TOTAL KM CHARGES
				$mis_not_collect = $ticket->detail(282) ? $ticket->detail(282) : NULL; //CC NOT COLLECTED AMOUNT
				$mis_collect = $ticket->detail(281) ? $ticket->detail(281) : NULL; //CC COLLECTED AMOUNT
				$mis_paid_amount = $ticket->detail(174) ? $ticket->detail(174) : NULL; //CC NET AMOUNT
				$mis_tax = $ticket->detail(177) ? $ticket->detail(177) : NULL; //CC TAX AMOUNT

				$mis_invoice_amount = $ticket->detail(182) ? $ticket->detail(182) : NULL; //BO AMOUNT
				$payout_amount = $ticket->detail(340) ? $ticket->detail(340) : NULL; //NORMAL PAYOUT
				$onward_google_km = $ticket->detail(286) ? $ticket->detail(286) : NULL; //onward_google_km
				$dealer_google_km = $ticket->detail(287) ? $ticket->detail(287) : NULL; //dealer_google_km
				$return_google_km = $ticket->detail(288) ? $ticket->detail(288) : NULL; //return_google_km
				$asp_collected_charges = $ticket->detail(155) ? $ticket->detail(155) : NULL; //ASP COLLECTED AMOUNT
				$bo_collected_charges = $ticket->detail(159) ? $ticket->detail(159) : NULL; //BO COLLECTED AMOUNT
				$bo_km = $ticket->detail(158) ? $ticket->detail(158) : NULL; //BO KM TRAVELLED
				$asp_km = $ticket->detail(154) ? $ticket->detail(154) : NULL; //ASP KM TRAVELLED

				// $asp_status = config('rsa.asp_statuses_label')[$ticket['asp_status_id']];
				$asp_status = DB::table('activity_finance_statuses')->select('name')->where('id', $ticket['status_id'])->first();
				$location_type = config('constants.asp_filter_types_label')[$ticket->location_type];
				$workshop_type = config('constants.workshop_types_label')[$ticket->workshop_type];
				// dd($asp_status->name, $location_type, $workshop_type);

				$created_at = $ticket->created_at;
				$updated_at = $ticket->updated_at;
				$collection = collect($ticket);

				$collection->forget(['created_at', 'updated_at']);

				$mis_km_charge = ($ticket->workshop_type == 1) ? 0 : (float) $mis_km_charge->value;
				$mis_not_collect = ($ticket->workshop_type == 1) ? 0 : (float) $mis_not_collect->value;
				$mis_collect = ($ticket->workshop_type == 1) ? 0 : (float) $mis_collect->value;
				$mis_paid_amount = ($ticket->workshop_type == 1) ? 0 : (float) $mis_paid_amount->value;
				$mis_tax = ($ticket->workshop_type == 1) ? 0 : !empty($mis_tax) ? (float) $mis_tax : 0;
				$mis_invoice_amount = ($ticket->workshop_type == 1) ? 0 : (float) $mis_invoice_amount->value;

				$collection->put('mis_km_charge', $mis_km_charge);
				$collection->put('mis_not_collect', $mis_not_collect);
				$collection->put('mis_collect', $mis_collect);
				$collection->put('mis_paid_amount', $mis_paid_amount);
				$collection->put('mis_tax', $mis_tax);
				$collection->put('mis_invoice_amount', $mis_invoice_amount);

				$collection->put('km_during_breakdown', $ticket->km_during_breakdown ? (int) $ticket->km_during_breakdown : 0);
				// $collection->put('excess_km_charges', (float) $ticket['excess_km_charges']);
				$collection->put('non_member_charges', !empty($non_member_charges) ? (float) $non_member_charges->value : 0);

				$collection->put('service_charges', !empty($service_charges) ? (float) $service_charges->value : 0);

				$collection->put('bo_net_amount', !empty($bo_net_amount) ? (float) $bo_net_amount->value : 0);
				$collection->put('deduction', !empty($deduction) ? (float) $deduction->value : 0);
				$collection->put('tax', (float) $mis_tax);
				$collection->put('payout_amount', !empty($payout_amount) ? (float) $payout_amount : 0);
				$collection->put('bo_invoice_amount', !empty($mis_invoice_amount) ? (float) $mis_invoice_amount : 0);
				$collection->put('onward_google_km', !empty($onward_google_km) ? (float) $onward_google_km->value : 0);
				$collection->put('dealer_google_km', !empty($dealer_google_km) ? (float) $onward_google_km->value : 0);

				$collection->put('return_google_km', $return_google_km ? (float) $return_google_km->value : 0);
				// $collection->put('excess_km', (float) $ticket['excess_km']);
				$collection->put('asp_collected_charges', !empty($asp_collected_charges) ? (float) $asp_collected_charges->value : 0);
				$collection->put('bo_collected_charges', !empty($bo_collected_charges) ? (float) $bo_collected_charges->value : 0);
				// $collection->put('bo_other_charges', (float) $ticket['bo_other_charges']);
				$collection->put('bo_km', !empty($bo_km) ? (float) $bo_km->value : 0);
				// $collection->put('asp_other_charges', (float) $ticket['asp_other_charges']);
				$collection->put('asp_km', !empty($asp_km) ? (float) $asp_km->value : 0);

				$collection->put('asp_status', $asp_status->name);
				$collection->put('location_type', $location_type);
				$collection->put('workshop_type', $workshop_type);
				$collection->put('created_at', $created_at);
				$collection->put('updated_at', $updated_at);
				$result[] = $collection->toArray();
			}

			Excel::create('Ticket_Provisional_Report', function ($excel) use ($summary, $selected_statuses, $summary_period, $result) {

				$excel->sheet('Summary', function ($sheet) use ($summary, $selected_statuses, $summary_period) {

					$sheet->fromArray($summary, NULL, 'A1', false, false);
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
					$cell_number = count(json_decode($selected_statuses)) + 3;
					$sheet->cells('A' . $cell_number . ':B' . $cell_number, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});

					$cell_number1 = count(json_decode($selected_statuses)) + 5;
					$sheet->cells('A' . $cell_number1 . ':B' . $cell_number1, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});

					$cell_number2 = count(json_decode($selected_statuses)) + 6;
					$sheet->cells('A' . $cell_number2 . ':B' . $cell_number2, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});

					$cell_number3 = count(json_decode($selected_statuses)) + 7;
					$sheet->cells('A' . $cell_number3 . ':B' . $cell_number3, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});
				});
				//setAutoHeadingGeneration

				$excel->sheet('Ticket_Informations', function ($sheet) use ($result) {
					$sheet->fromArray($result);

					$sheet->cells('A1:CT1', function ($cells) {
						$cells->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#CCC9C9');
					});
				});
			})->download('xls');

		} catch (\Exception $e) {
			$message = ['error' => $e->getline()];
		}
	}

	public function getGeneralReport() {

		$general_report_ticket_count = Activity::select(
			DB::raw('COUNT(id) as total'),
			DB::raw('DATE_FORMAT(updated_at,"%b") month'))
			->whereYear('updated_at', date('Y'))
			->where('status_id', 14) //PAID
			->groupby('month')
			->pluck('total', 'month')
			->toArray()
		;

		//OVERALL AMOUNT PAID IN CURRENT YEAR
		$general_report_ticket_count_year = Activity::whereYear('updated_at', date('Y'))
			->where('status_id', 14) //PAID
			->count('id');

		//TOP ASP LIST
		$top_ASPs = Activity::select(
			'asps.workshop_name as workshop_name',
			'asps.asp_code as asp_code',
			'states.name as states_name',
			'districts.name as city_name',
			'users.image as image',
			'users.image_type as image_type',
			DB::raw('SUM(activity_details.value) as total'),
			DB::raw('COUNT(activities.id) as count')
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->join('asps', 'activities.asp_id', 'asps.id')
			->join('states', 'asps.state_id', 'states.id')
			->join('districts', 'asps.district_id', 'districts.id')
			->join('users', 'asps.user_id', 'users.id')
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->groupBy('asps.id')
			->orderBy('total', 'desc')
			->take(10)
			->get()
			->toArray()
		;
		// dd($top_ASPs);

		//CITY WISE COUNT
		$city_wise_list = Activity::select(
			DB::raw('COUNT(activities.id) as ticket_count'),
			DB::raw('COUNT(clients.id) as client_count'),
			'cases.bd_city as city_name'
		)
			->join('cases', 'cases.id', 'activities.case_id')
			->join('clients', 'clients.id', 'cases.client_id')
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->groupBy('cases.bd_city')
			->get()
			->toArray()
		;

		foreach ($city_wise_list as $city) {
			// dd($city['city_name']);
			$state_ids = District::where('name', 'LIKE', '%' . $city['city_name'] . '%')
				->pluck('state_id')
				->toArray()
			;
		}
		// dump(array_unique($state_ids));

		// STATE WISE COUNT
		$state_wise_list = State::select(
			DB::raw('COUNT(activities.id) as ticket_count'),
			DB::raw('COUNT(clients.id) as client_count'),
			'states.name as state_name'
		)
			->join('districts', 'districts.state_id', 'states.id')
			->leftJoin('cases', 'cases.bd_city', 'districts.name')
			->join('clients', 'clients.id', 'cases.client_id')
			->join('activities', 'activities.case_id', 'cases.id')
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->whereNotNull('cases.id')
			->groupby('states.name')
			->get()
			->toArray()
		;

		// dd($state_wise_list);

		// STATE WISE COUNT
		// $state_wise_list = Activity::select(
		// 	DB::raw('COUNT(activities.id) as ticket_count'),
		// 	DB::raw('COUNT(clients.id) as client_count'),
		// 	'states.name as state_name'
		// )
		// 	->join('cases', 'cases.id', 'activities.case_id')
		// 	->join('clients', 'clients.id', 'cases.client_id')
		// 	->join('users', 'users.id', 'clients.user_id')
		// 	->join('state_user', 'state_user.user_id', 'users.id')
		// 	->join('states', 'states.id', 'state_user.state_id')
		// 	->whereYear('activities.updated_at', date('Y'))
		// 	->where('activities.status_id', 14) //PAID
		// 	->get()
		// 	->toArray()
		;
		// dd($state_wise_list);

		$this->data['general'] = [
			'general_report_ticket_count' => $general_report_ticket_count,
			'general_report_ticket_count_year' => $general_report_ticket_count_year,
			'top_ASPs' => $top_ASPs,
			'city_wise_list' => $city_wise_list,
			'state_wise_list' => $state_wise_list,
		];

		// dd($state_wise_list);

		return response()->json($this->data);
	}

	public function getAspPaymentList(Request $request) {

		$ticket = Activity::select(
			'asps.asp_code',
			'asps.name as asp_name',
			'asps.is_self',
			DB::raw('FORMAT(asp_collected_amt.value, 2) as collected_from_customer'),
			DB::raw('FORMAT(SUM(activity_details.value), 2) as invoice_amount'),
			DB::raw('COUNT(activities.id) as ticket_count'),
			'states.name as state_name',
			'districts.name as city_name'
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182) //BO AMOUNT
				;
			})
			->join('activity_details as asp_collected_amt', function ($join) {
				$join->on('asp_collected_amt.activity_id', 'activities.id')
					->where('asp_collected_amt.key_id', 155) //ASP COLLECTED AMOUNT
				;
			})
			->join('asps', 'asps.id', 'activities.asp_id')
			->join('districts', 'districts.id', 'asps.district_id')
			->join('states', 'states.id', 'districts.state_id')
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->groupBy('asps.id')
		// ->get()
		;
		// dd($ticket);

		return Datatables::of($ticket)
		// ->setRowAttr([
		// 	'id' => function ($ticket) {
		// 	},
		// ])
			->addColumn('asp_type', function ($ticket) {
				return ($ticket->is_self) ? 'Self' : 'Non Self';
			})
			->make(true);
	}

	public function getCityPaymentList() {
		// dd($request->all());
		$all_city_wise = Activity::select(
			DB::raw('FORMAT(SUM(activity_details.value), 2) as amount'),
			DB::raw('Count(activities.id) as ticket_count'),
			'cases.bd_city as city_name',
			'service_types.name as service_name',
			'service_types.id as service_id',
			'call_centers.name as call_center_name',
			'states.name as state_name'
		)
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->leftJoin('districts', 'districts.name', 'cases.bd_city')
			->join('states', 'states.id', 'districts.state_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
		// ->where('cases.bd_city', $request->name)
			->groupBy('cases.bd_city', 'activities.service_type_id')
			->orderBy('activity_details.value', 'Desc')
			->get()
			->toArray()
		;

		$city_wise = [];
		$overall_city = [];
		foreach ($all_city_wise as $result) {
			$overall_city[$result['city_name']] = $result['city_name'];
			$city_wise[$result['city_name']]['city_name'] = $result['city_name'];
			$city_wise[$result['city_name']]['state_name'] = $result['state_name'];
			$city_wise[$result['city_name']]['callcenter'] = $result['call_center_name'];
			$city_wise[$result['city_name']][$result['service_id']]['amount'] = $result['amount'];
			$city_wise[$result['city_name']][$result['service_id']]['count'] = $result['ticket_count'];
			$city_wise[$result['city_name']][$result['service_id']]['service_name'] = $result['service_name'];
		}

		$all_city_wise_total = Activity::select(
			DB::raw('SUM(activity_details.value) as amount'),
			DB::raw('COUNT(activities.id) as ticket_count'),
			'cases.bd_city as city_name'
		)
			->join('activity_details', function ($join) {
				$join->on('activities.id', 'activity_details.activity_id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->join('cases', 'cases.id', 'activities.case_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
		// ->where('cases.bd_city', $request->name)
			->groupBy('cases.bd_city')
			->orderBy('activity_details.value', 'Desc')
			->get()
			->toArray()
		;

		$total_paid_ticket_count = Activity::whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->count()
		;
		$total_paid_ticket_amount = Activity::join('activity_details', function ($join) {
			$join->on('activities.id', 'activity_details.activity_id')
				->where('activity_details.key_id', 182); //BO AMOUNT
		})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->sum('activity_details.value')
		;
		// dd($total_paind_ticket_amount);

		$city_wise_total = [];
		foreach ($all_city_wise_total as $result) {
			$city_wise_total[$result['city_name']]['amount'] = $result['amount'];
			$city_wise_total[$result['city_name']]['count'] = $result['ticket_count'];
		}
		// dd(count($city_wise_total));
		// $services = ServiceType::pluck('name', 'id');

		$this->data['city'] = [
			'city_wise' => $city_wise,
			'total_count' => $total_paid_ticket_count,
			'total_amount' => $total_paid_ticket_amount,
			'city_wise_total' => $city_wise_total,
		];
		return response()->json($this->data);
	}

	public function getStatePaymentList() {
		// dd($request->all());
		$all_state_wise = State::select(
			DB::raw('SUM(activity_details.value) as amount'),
			DB::raw('Count(activities.id) as ticket_count'),
			// 'cases.bd_city as city_name',
			'service_types.name as service_name',
			'service_types.id as service_id',
			'call_centers.name as call_center_name',
			'states.name as state_name'
		)
			->join('districts', 'districts.state_id', 'states.id')
			->leftJoin('cases', 'cases.bd_city', 'districts.name')
			->join('activities', 'activities.case_id', 'cases.id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->whereNotNull('cases.id')
			->groupby('states.name', 'activities.service_type_id')
			->get()
			->toArray()
		;
		// dd($all_state_wise);

		$state_wise = [];
		$overall_state = [];
		foreach ($all_state_wise as $result) {
			$overall_state[$result['state_name']] = $result['state_name'];
			$state_wise[$result['state_name']]['state_name'] = $result['state_name'];
			$state_wise[$result['state_name']]['callcenter'] = $result['call_center_name'];
			$state_wise[$result['state_name']][$result['service_id']]['amount'] = $result['amount'];
			$state_wise[$result['state_name']][$result['service_id']]['count'] = $result['ticket_count'];
			$state_wise[$result['state_name']][$result['service_id']]['service_name'] = $result['service_name'];
		}

		$all_state_wise_total = State::select(
			DB::raw('SUM(activity_details.value) as amount'),
			DB::raw('COUNT(activities.id) as ticket_count'),
			'states.name as state_name'
		)
			->join('districts', 'districts.state_id', 'states.id')
			->leftJoin('cases', 'cases.bd_city', 'districts.name')
			->join('activities', 'activities.case_id', 'cases.id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('activity_details', function ($join) {
				$join->on('activity_details.activity_id', 'activities.id')
					->where('activity_details.key_id', 182); //BO AMOUNT
			})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->whereNotNull('cases.id')
			->groupby('states.name')
			->orderBy('activity_details.value', 'Desc')
			->get()
			->toArray()
		;

		// dd($all_state_wise_total);

		$total_paid_ticket_count = Activity::whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->count()
		;
		$total_paid_ticket_amount = Activity::join('activity_details', function ($join) {
			$join->on('activities.id', 'activity_details.activity_id')
				->where('activity_details.key_id', 182); //BO AMOUNT
		})
			->whereYear('activities.updated_at', date('Y'))
			->where('activities.status_id', 14) //PAID
			->sum('activity_details.value')
		;
		// dd($total_paind_ticket_amount);

		$state_wise_total = [];
		foreach ($all_state_wise_total as $result) {
			$state_wise_total[$result['state_name']]['amount'] = $result['amount'];
			$state_wise_total[$result['state_name']]['count'] = $result['ticket_count'];
		}
		$this->data['state'] = [
			'state_wise' => $state_wise,
			'total_count' => $total_paid_ticket_count,
			'total_amount' => $total_paid_ticket_amount,
			'state_wise_total' => $state_wise_total,
		];
		return response()->json($this->data);
	}

}
