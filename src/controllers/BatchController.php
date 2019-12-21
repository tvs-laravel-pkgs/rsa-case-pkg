<?php

namespace Abs\RsaCasePkg;
use App\Batch;
use App\Http\Controllers\Controller;
use App\Invoices;
use App\StateUser;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class BatchController extends Controller {

	public function getList(Request $request) {

		$invoices = Invoices::select('Invoices.id', 'Invoices.invoice_no', DB::raw("date_format(Invoices.created_at,'%d-%m-%Y') as invoice_date"), DB::raw("ROUND(SUM(Invoices.invoice_amount),2) as invoice_amount"), 'asps.asp_code as asp_code',
			'asps.workshop_name as workshop_name', DB::raw("COUNT(activities.id) as no_of_tickets"))
			->join('asps', 'Invoices.asp_id', '=', 'asps.id')
			->join('users', 'users.id', 'asps.user_id')
			->join('activities', 'activities.invoice_id', '=', 'Invoices.id')
			->whereNULL('Invoices.batch_id')
			->groupBy('Invoices.id')
		;
		if ($request->get('date')) {
			$invoices->whereRaw('DATE_FORMAT(Invoices.created_at,"%d-%m-%Y") =  "' . $request->get('date') . '"');
		}

		if ($request->get('invoice_number')) {
			$invoices->where('Invoices.invoice_no', 'LIKE', '%' . $request->get('invoice_number') . '%');
		}

		if ($request->get('workshop_name')) {
			$invoices->where('asps.workshop_name', 'LIKE', '%' . $request->get('workshop_name') . '%');
		}

		if ($request->get('asp_code')) {
			$invoices->where('asps.asp_code', 'LIKE', '%' . $request->get('asp_code') . '%');
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
			->setRowAttr([
				'id' => function ($invoices) {
					return route('angular') . '/#!/rsa-case-pkg/invoice/view/' . $invoices->id . '/2';
				},
			])
			->addColumn('action', function ($invoices) {
				return '<input type="checkbox" class="ticket_id no-link child_select_all" name="invoice_ids[]" value="' . $invoices->id . '">';
			})
			->make(true);
	}

	public function generateBatch(Request $request) {
		if ($request->get('invoice_ids')) {
			$invoice_ids = $request->get('invoice_ids');
			$batch_generate = Batch::batchGenerate($invoice_ids);
			if ($batch_generate) {
				return response()->json(['success' => true]);
			} else {
				return response()->json(['success' => false, 'error' => 'Something went wrong!.Please Try Again']);
			}
		} else {
			return response()->json(['success' => false, 'error' => 'No Invoice selected,Select atleast one invoice']);
		}

	}

}
