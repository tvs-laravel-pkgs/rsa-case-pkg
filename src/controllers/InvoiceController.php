<?php

namespace Abs\RsaCasePkg;
use App\Asp;
use App\Http\Controllers\Controller;
use App\Invoices;
use App\StateUser;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
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

	public function viewInvoice($invoice_id) {
		$this->data['invoice'] = $invoice = Invoices::where('Invoices.id', $invoice_id)->
			select(
			'Invoices.*', 'asps.gst_registration_number',
			'asps.pan_number',
			'asps.account_holder_name',
			'asps.bank_account_number',
			'asps.bank_name', 'asps.bank_branch_name',
			'asps.bank_ifsc_code',
			DB::raw("COUNT(mis_informations.id) as no_of_tickets"),
			DB::raw("SUM(mis_informations.bo_invoice_amount) as amount"),
			DB::raw("DATE_FORMAT(MIN(mis_informations.ticket_date_time),'%d-%m-%Y') as startdate,DATE_FORMAT(MAX(mis_informations.ticket_date_time),'%d-%m-%Y') as enddate")
		)
			->join('mis_informations', 'mis_informations.invoice_id', '=', 'Invoices.id')
			->join('asps', 'asps.id', '=', 'Invoices.asp_id')
			->groupBy('Invoices.id')
			->first();

		$this->data['tickets'] = $invoice->tickets;
		$this->data['sum_invoice_amount'] = $invoice->amount;
		$this->data['mis_infos'] = $invoice->tickets;
		$this->data['mis_info'] = $invoice->tickets;
		$this->data['asp'] = $invoice->asp;
		$this->data['period'] = $invoice->startdate . ' to ' . $invoice->enddate;
		$this->data['inv_no'] = $invoice->invoice_no . '-' . $invoice->id;
		$this->data['inv_date'] = $invoice->created_at;
		$this->data['batch'] = "";
		$this->data['signature_attachment'] = Attachment::where('entity_id', Auth::user()->asp->id)->where('entity_type', config('constants.entity_types.asp_attachments.digital_signature'))->first();

		if ($invoice->asp_gst_registration_number != NULL) {
			$this->data['gst'] = $invoice->asp_gst_registration_number;
		} else {
			$this->data['gst'] = $invoice->gst_registration_number;
		}
		if ($invoice->asp_pan_number != NULL) {
			$this->data['pan'] = $invoice->asp_pan_number;
		} else {
			$this->data['pan'] = $invoice->pan_number;
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

		//dd($this->data['period']->startdate);
		//return view('admin.invoices.view')->with($this->data);
		return view('asp.Invoiced_asp_view')->with($this->data);
	}

}
