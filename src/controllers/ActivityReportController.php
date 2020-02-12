<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use App\CallCenter;
use App\Http\Controllers\Controller;
use App\ServiceType;
use Auth;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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
			->join('Invoices as invoice', function ($join) {
				$join->on('Invoice.id', 'activities.invoice_id')
					->where('invoice.status_id', 2); //PAID
			})
			->where('activities.is_exceptional_check', 0)
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

		$amount_of_tickets_submitted = Activity::join('activity_details', function ($join) {
			$join->on('activity_details.activity_id', 'activities.id')
				->where('activity_details.key_id', 182); //BO AMOUNT
		})
			->where('activities.status_id', 12) //INVOICED-WAITING FOR PAYMENT
			->orWhere('activities.status_id', 13) //PAYMENT INPROGRESS
			->whereYear('activities.updated_at', date('Y'))
			->sum('activity_details.value')
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

		$this->data['extras'] = [
			'total_amount_submit_in_year_chart' => $total_amount_submit_in_year,
			'amount_of_bills_yet_to_receive_chart' => $amount_bills_yet_to_receive,
			'total_count_yet_to_receive_in_year_chart' => $count_bills_yet_to_receive,
			'total_count_submit_in_year_chart' => $total_count_submit_in_year,
			'total_amount_paid_in_year' => $total_amount_paid_in_year,
			'amount_of_tickets_submitted' => $amount_of_tickets_submitted,
			'amount_of_bills_yet_to_receive' => $amount_of_bills_yet_to_receive,
			'total_count_of_tickets_in_year' => $total_count_of_tickets_in_year,
			'total_count_of_tickets_submitted' => count($total_count_of_tickets_submitted),
			'bills_yet_to_receive' => $bills_yet_to_receive,
		];

		$Total_amount_paid = Activity::select(
			DB::raw('sum(activity_details.value) as total'),
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

		$this->data['before_bo_validation_invoice_amount'] = Activity::join('asps', 'asps.id', 'activities.asp_id')
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
				'date' => date('m Y'),
				'date_from' => trim($date_from_to[0]),
				'date_to' => trim($date_from_to[1]),
				'before_bo_validation_ticket_count' => $before_bo_validation_ticket_count,
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
				'date' => date('m Y'),
				'date_from' => trim($date_from_to[0]),
				'date_to' => trim($date_from_to[1]),
				'report_summary' => $report_summary,
				'report_summary_total_amount' => $report_summary_total_amount,
				'count_of_tickets' => $count_of_tickets,
			];
		}
		$this->data['services_type_list'] = ServiceType::select('id', 'name')->orderBy('name')->get();
		$this->data['check_new_update'] = 1;

		return response()->json($this->data);
	}

	public function exportProvisionalReport(Request $request) {
		// dd($request->all());
		try {
			ini_set('max_execution_time', 0);
			ini_set('display_errors', 1);
			ini_set("memory_limit", "10000M");

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

			Excel::create('ticket_provisional_report', function ($excel) use ($total_count, $request, $range1, $range2, $activity_infos, $count_splitup, $total_activity_net_amount, $total_activity_tax, $total_activity_invoice_amount) {

				$excel->sheet('Summary', function ($sheet) use ($total_count, $request, $range1, $range2, $count_splitup, $total_activity_net_amount, $total_activity_tax, $total_activity_invoice_amount) {
					$summary = [['Period', date('d/M/Y', strtotime($range1)) . ' to ' . date('d/M/Y', strtotime($range2))], ['Services', 'Count']];
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
					$summary[] = [];
					$summary[] = ['Net Total Before approved of BO', $total_activity_net_amount];
					$summary[] = ['Tax Total Before approved of BO', $total_activity_tax];
					$summary[] = ['Grand Total Before approved of BO', $total_activity_invoice_amount];
					$sheet->fromArray($summary, NULL, 'A1', false, false);

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
					$cell_number = count(json_decode($request->services_type_id)) + 3;
					$sheet->cells('A' . $cell_number . ':B' . $cell_number, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});

					$cell_number1 = count(json_decode($request->services_type_id)) + 5;
					$sheet->cells('A' . $cell_number1 . ':B' . $cell_number1, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});

					$cell_number2 = count(json_decode($request->services_type_id)) + 6;
					$sheet->cells('A' . $cell_number2 . ':B' . $cell_number2, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});

					$cell_number3 = count(json_decode($request->services_type_id)) + 7;
					$sheet->cells('A' . $cell_number3 . ':B' . $cell_number3, function ($cell) {
						$cell->setFont(array(
							'size' => '10',
							'bold' => true,
						))->setBackground('#F3F3F3');
					});
				});
//setAutoHeadingGeneration

				$excel->sheet('Ticket Informations', function ($sheet) use ($request, $range1, $range2, $activity_infos) {

					$tickets = $activity_infos
						->select(
							'asps.asp_code',
							'asps.axpta_code',
							'asps.workshop_name',
							'asps.name',
							'asps.workshop_type',
							'service_types.name as mis_service_type',
							'call_centers.name as call_centers_name',
							'locations.name as location',
							'districts.name as district',
							'states.name as state',
							'clients.name as client_name',
							'service_types.name as asp_service_type',
							'service_types.name as bo_service_type',
							'Invoices.invoice_no as invoice_no',
							DB::raw('DATE_FORMAT(Invoices.created_at,"%d/%m/%Y") as invoice_date'),
							'activity_portal_statuses.name as flow_current_status',
							'cc_km.value as mis_km',
							DB::raw('cc_km_charges.value as mis_km_charge'),
							DB::raw('not_collected.value as mis_not_collect'),
							DB::raw('collected.value as mis_collect'),
							DB::raw('net_amount.value as mis_paid_amount'),
							DB::raw('tax_amount.value as mis_tax'),
							DB::raw('invoice_amount.value as mis_invoice_amount'),
							'activities.*'
							// DB::raw('IF(mis_informations.claim_status=0,"NEW","DEFERED") as claim_status')
						)
						->join('service_types', 'service_types.id', 'activities.service_type_id')
						->join('call_centers', 'call_centers.id', 'cases.call_center_id')

						->join('clients', 'clients.id', 'cases.client_id')
						->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
						->leftJoin('activity_details as cc_km', function ($join) {
							$join->on('cc_km.activity_id', 'activities.id')
								->where('cc_km.key_id', 280); //CC TOTAL KM
						})
						->join('activity_details as cc_km_charges', function ($join) {
							$join->on('cc_km_charges.activity_id', 'activities.id')
								->where('cc_km_charges.key_id', 150); //CC TOTAL KM CHARGES
						})
						->join('activity_details as not_collected', function ($join) {
							$join->on('not_collected.activity_id', 'activities.id')
								->where('not_collected.key_id', 282); //CC NOT COLLECTED AMOUNT
						})
						->join('activity_details as collected', function ($join) {
							$join->on('collected.activity_id', 'activities.id')
								->where('collected.key_id', 281); //CC COLLECTED AMOUNT
						})
						->join('activity_details as net_amount', function ($join) {
							$join->on('net_amount.activity_id', 'activities.id')
								->where('net_amount.key_id', 174); //CC NET AMOUNT
						})
						->join('activity_details as tax_amount', function ($join) {
							$join->on('tax_amount.activity_id', 'activities.id')
								->where('tax_amount.key_id', 174); //CC NET AMOUNT
						})
						->join('activity_details as invoice_amount', function ($join) {
							$join->on('invoice_amount.activity_id', 'activities.id')
								->where('invoice_amount.key_id', 182); //BO AMOUNT
						})
						// ->leftJoin('service_types as asp_services', 'asp_services.id', 'mis_informations.asp_service_type_id')

						// ->leftJoin('service_types as bo_services', 'bo_services.id', 'mis_informations.bo_service_type_id')
						->join('locations', 'asps.location_id', 'locations.id')
						->leftJoin('Invoices', 'Invoices.id', 'activities.invoice_id')
						->join('districts', 'districts.id', 'asps.district_id')
						->join('states', 'states.id', 'asps.state_id')
						->where('activity_portal_statuses.company_id', Auth::user()->company_id)
						->orderBy('asps.asp_code')
						->orderBy('cases.date')
						->get()
						->toArray()
					;

					dd($tickets);

					foreach ($tickets as $ticket) {
						$asp_status = config('rsa.asp_statuses_label')[$ticket['asp_status_id']];
						$location_type = config('constants.asp_filter_types_label')[$ticket['location_type']];
						$workshop_type = config('constants.workshop_types_label')[$ticket['workshop_type']];
						$created_at = $ticket['created_at'];
						$updated_at = $ticket['updated_at'];
						$collection = collect($ticket);
						$collection->forget(['invoice_id', 'asp_id', 'service_type_id', 'asp_service_type_id', 'bo_service_type_id', 'asp_status_id', 'call_center_id', 'client_id', 'updated_by', 'mis_net_amount', 'workshop_type', 'total_km', 'paid_amount_by_customer', 'unpaid_amount', 'created_at', 'updated_at']);

						$mis_km_charge = ($ticket['workshop_type'] == 1) ? 0 : (float) $ticket['mis_km_charge'];
						$mis_not_collect = ($ticket['workshop_type'] == 1) ? 0 : (float) $ticket['mis_not_collect'];
						$mis_collect = ($ticket['workshop_type'] == 1) ? 0 : (float) $ticket['mis_collect'];
						$mis_paid_amount = ($ticket['workshop_type'] == 1) ? 0 : (float) $ticket['mis_paid_amount'];
						$mis_tax = ($ticket['workshop_type'] == 1) ? 0 : (float) $ticket['mis_tax'];
						$mis_invoice_amount = ($ticket['workshop_type'] == 1) ? 0 : (float) $ticket['mis_invoice_amount'];
						$collection->put('mis_km_charge', $mis_km_charge);
						$collection->put('mis_not_collect', $mis_not_collect);
						$collection->put('mis_collect', $mis_collect);
						$collection->put('mis_paid_amount', $mis_paid_amount);
						$collection->put('mis_tax', $mis_tax);
						$collection->put('mis_invoice_amount', $mis_invoice_amount);

						$collection->put('km_during_breakdown', (int) $ticket['km_during_breakdown']);
						$collection->put('excess_km_charges', (float) $ticket['excess_km_charges']);
						$collection->put('non_member_charges', (float) $ticket['non_member_charges']);
						$collection->put('service_charges', (float) $ticket['service_charges']);
						$collection->put('bo_net_amount', (float) $ticket['bo_net_amount']);

						$collection->put('deduction', (float) $ticket['deduction']);
						$collection->put('tax', (float) $ticket['tax']);
						$collection->put('payout_amount', (float) $ticket['payout_amount']);
						$collection->put('bo_invoice_amount', (float) $ticket['bo_invoice_amount']);
						$collection->put('onward_google_km', (float) $ticket['onward_google_km']);
						$collection->put('dealer_google_km', (float) $ticket['dealer_google_km']);

						$collection->put('return_google_km', (float) $ticket['return_google_km']);
						$collection->put('excess_km', (float) $ticket['excess_km']);
						$collection->put('asp_collected_charges', (float) $ticket['asp_collected_charges']);
						$collection->put('bo_collected_charges', (float) $ticket['bo_collected_charges']);
						$collection->put('bo_other_charges', (float) $ticket['bo_other_charges']);
						$collection->put('bo_km', (float) $ticket['bo_km']);
						$collection->put('asp_other_charges', (float) $ticket['asp_other_charges']);
						$collection->put('asp_km', (float) $ticket['asp_km']);

						$collection->put('asp_status', $asp_status);
						$collection->put('location_type', $location_type);
						$collection->put('workshop_type', $workshop_type);
						$collection->put('created_at', $created_at);
						$collection->put('updated_at', $updated_at);
						$result[] = $collection->toArray();
					}

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
		dd('general report');
	}

}
