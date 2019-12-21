<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use App\Asp;
use App\Attachment;
use App\Http\Controllers\Controller;
use App\Invoices;
use App\StateUser;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Yajra\Datatables\Datatables;

class InvoiceController extends Controller {

	public function getFilterData() {
		$this->data['extras'] = [
			'asp_list' => collect(Asp::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select ASP']),
		];
		return response()->json($this->data);
	}

	public function getList(Request $request) {

		$invoices = Invoices::select('Invoices.id', 'Invoices.invoice_no', DB::raw("date_format(Invoices.created_at,'%d-%m-%Y') as invoice_date"), DB::raw("ROUND(SUM(Invoices.invoice_amount),2) as invoice_amount"), 'asps.asp_code as asp_code',
			'asps.workshop_name as workshop_name', DB::raw("COUNT(activities.id) as no_of_tickets"))
			->join('asps', 'Invoices.asp_id', '=', 'asps.id')
			->join('users', 'users.id', 'asps.user_id')
			->join('activities', 'activities.invoice_id', '=', 'Invoices.id')
			->groupBy('Invoices.id')
		;
		if ($request->get('date')) {
			$invoices->whereRaw('DATE_FORMAT(Invoices.created_at,"%d-%m-%Y") =  "' . $request->get('date') . '"');
		}
		if (!Entrust::can('view-all-activities')) {
			if (Entrust::can('view-mapped-state-activities')) {
				$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
				$invoices->whereIn('asps.state_id', $states);
			}
			if (Entrust::can('view-own-activities')) {
				$invoices->where('users.id', Auth::id());
			}
		}

		return Datatables::of($invoices)
			->addColumn('action', function ($invoices) {
				$action = '<div class="dataTable-actions">
				<a href="#!/rsa-case-pkg/invoice/view/' . $invoices->id . '">
					                <i class="fa fa-eye dataTable-icon--view" aria-hidden="true"></i>
					            </a>';
				$action .= '</div>';
				return $action;
			})
			->make(true);
	}

	public function downloadInvoice($invoice_id) {

		$pdf = Invoices::generatePDF($invoice_id);
		$filepath = 'storage/app/public/invoices/' . $invoice_id . '.pdf';
		$response = Response::download($filepath);
		ob_end_clean();
		return $response;
	}
	public function viewInvoice($invoice_id) {

		$this->data['invoice'] = $invoice = Invoices::where('Invoices.id', $invoice_id)->
			select(
			'Invoices.*', 'asps.gst_registration_number',
			'asps.pan_number',
			'asps.account_holder_name',
			'asps.bank_account_number',
			'asps.bank_name', 'asps.bank_branch_name',
			'asps.bank_ifsc_code',
			'Invoices.invoice_amount as amount',
			DB::raw("COUNT(activities.id) as no_of_tickets"),
			DB::raw("DATE_FORMAT(MIN(Invoices.start_date),'%d-%m-%Y') as startdate,DATE_FORMAT(MAX(Invoices.end_date),'%d-%m-%Y') as enddate")
		)
			->join('activities', 'activities.invoice_id', '=', 'Invoices.id')
			->join('asps', 'asps.id', '=', 'Invoices.asp_id')
			->groupBy('Invoices.id')
			->first();

		$activities = Activity::join('cases', 'cases.id', 'activities.case_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
			->leftJoin('activity_details as km_charge', function ($join) {
				$join->on('km_charge.activity_id', 'activities.id')
					->where('km_charge.key_id', 158); //BO KM TRAVELLED
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

			->leftJoin('activity_details as total_amount', function ($join) {
				$join->on('total_amount.activity_id', 'activities.id')
					->where('total_amount.key_id', 182); //BO TOTAL AMOUNT
			})
			->select('activities.number', DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y")as date'), 'activity_portal_statuses.name as status', 'call_centers.name as callcenter', 'cases.vehicle_registration_number', 'service_types.name as service_type', 'km_charge.value as km_value', 'not_collected_amount.value as not_collect_value', 'net_amount.value as net_value', 'collect_amount.value as collect_value', 'total_amount.value as total_value')
			->where('invoice_id', $invoice_id)
			->groupBy('activities.id')
			->get();

		$this->data['activities'] = $activities;
		$this->data['invoice_amount'] = number_format($invoice->amount, 2);
		$this->data['mis_infos'] = $invoice->tickets;
		$this->data['mis_info'] = $invoice->tickets;
		$asp = $invoice->asp;
		$asp->rm = $invoice->asp->rm;
		$this->data['period'] = $invoice->startdate . ' to ' . $invoice->enddate;
		$this->data['inv_no'] = $invoice->invoice_no . '-' . $invoice->id;
		$this->data['inv_date'] = $invoice->created_at;
		$this->data['batch'] = "";
		$this->data['asp'] = $asp;
		$this->data['rsa_address'] = config('rsa.INVOICE_ADDRESS');

		$this->data['signature_attachment'] = Attachment::where('entity_id', $asp->id)->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))->first();

		$this->data['signature_attachment_path'] = url('storage/' . config('rsa.asp_attachment_path_view'));

		$directoryPath = storage_path('app/public/invoices/' . $invoice_id . '.pdf');
		if (file_exists($directoryPath)) {
			$this->data['invoice_availability'] = $invoice_availability = "yes";
			$this->data['invoice_attachment_file'] = url('storage/app/public/invoices/' . $invoice_id . '.pdf');
		} else {
			$this->data['invoice_availability'] = $invoice_availability = "no";
			$this->data['invoice_attachment_file'] = "";
		}
		if ($invoice->asp_bank_account_number != NULL) {
			$this->data['bank_account_number'] = $invoice->asp_bank_account_number;
		} else {
			$this->data['bank_account_number'] = $invoice->bank_account_number;
		}
		if ($invoice->asp_bank_name != NULL) {
			$this->data['bank_name'] = $invoice->asp_bank_name;
		} else {
			$this->data['bank_name'] = $invoice->bank_name;
		}
		if ($invoice->asp_bank_branch_name != NULL) {
			$this->data['bank_branch_name'] = $invoice->asp_bank_branch_name;
		} else {
			$this->data['bank_branch_name'] = $invoice->bank_branch_name;
		}
		if ($invoice->asp_bank_ifsc_code != NULL) {
			$this->data['bank_ifsc_code'] = $invoice->asp_bank_ifsc_code;
		} else {
			$this->data['bank_ifsc_code'] = $invoice->bank_ifsc_code;
		}

		return response()->json($this->data);
	}

}
