<?php

namespace Abs\RsaCasePkg;
use App\Batch;
use App\Http\Controllers\Controller;
use App\Invoices;
use App\StateUser;
use Auth;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class BatchController extends Controller {

	public function getList(Request $request) {

		$invoices = Invoices::select('Invoices.id', DB::raw("CONCAT(Invoices.invoice_no,'-',Invoices.id) as invoice_no"), DB::raw("date_format(Invoices.created_at,'%d-%m-%Y') as invoice_date"), DB::raw("ROUND(SUM(Invoices.invoice_amount),2) as invoice_amount"), 'asps.asp_code as asp_code', 'Invoices.start_date as start_date',
			'Invoices.end_date as end_date',
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
			->addColumn('period', function ($invoices) {
				return $invoices->start_date . " - " . $invoices->end_date;
			})
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
			return response()->json(['success' => false, 'error' => 'No Invoice selected, select atleast one invoice']);
		}

	}

	public function getPaidBatchList(Request $request) {
		$batches = Batch::where('status', '=', "Payment Confirmed")
			->join('Invoices', 'Invoices.batch_id', '=', 'batches.id')
			->join('activities', 'activities.invoice_id', '=', 'Invoices.id')
			->join('asps', 'asps.id', '=', 'batches.asp_id')
			->selectRaw("
                    batches.id as batchid,
                    batches.batch_number,
                    batches.created_at as created_at,
                    batches.tds as tds,
                    FORMAT(batches.paid_amount,2) as paid_amount,
                    asps.asp_code as asp_code,
                    asps.name as asp_name,
                    asps.workshop_name as workshop_name,
                    IF(asps.is_self = 1,'SELF','NON-SELF') as asp_type,
                    CONCAT(DATE_FORMAT(min(Invoices.start_date), '%d/%m/%Y'),' - ',DATE_FORMAT(max(Invoices.end_date), '%d/%m/%Y')) as date_period,
                    COUNT(activities.id) as tickets_count")
			->groupBy('batches.id')
		// ->get()
		;

		// dd($batches);
		if (Auth::user()->role_id == 6) {
			$batches->whereIn('asps.state_id', $statesid);
		}

		if ($request->get('date')) {
			$batches->whereRaw('DATE_FORMAT(batches.created_at,"%d-%m-%Y") =  "' . $request->get('date') . '"');
		}

		if ($request->get('batch_number')) {
			$batches->where('batches.batch_number', 'LIKE', '%' . $request->get('batch_number') . '%');
		}

		if ($request->get('workshop_name')) {
			$batches->where('asps.workshop_name', 'LIKE', '%' . $request->get('workshop_name') . '%');
		}

		if ($request->get('asp_code')) {
			$batches->where('asps.asp_code', 'LIKE', '%' . $request->get('asp_code') . '%');
		}

		return Datatables::of($batches)
			->setRowAttr([
				'id' => function ($batches) {
					return route('angular') . '/#!/rsa-case-pkg/batch-view/' . $batches->batchid . '/11';
				},
			])
			->make(true);
	}

	public function exportUnpaidbatches(Request $request) {
		try
		{
			ini_set('max_execution_time', 0);
			ini_set('memory_limit', '5000M');

			if (empty($request->batch_ids)) {
				return redirect()->back()->with('error', "Please Select Batch");
			}

			dd($request->all());
			// Start retriving information
			$ticketList = MisInformation::
				select(
				'mis_informations.*',
				'mis_informations.id as id',
				'b.batch_number as batch_number',
				'a.axpta_code as axpta_code',
				'a.workshop_name as workshop_name',
				// 'i.invoice_no as invoice_no',
				DB::raw('CONCAT(i.invoice_no, "-", i.id) AS invoice_no'),
				'i.created_at as created_at',
				'a.asp_code as asp_code',
				'l.name as location_name',
				's.name as state_name',
				'a.gst_registration_number as gst_registration_number',
				'a.tax_calculation_method as tax_calculation_method',
				'a.bank_name as bank_name',
				'a.bank_account_number as bank_account_number',
				'a.bank_ifsc_code as bank_ifsc_code',
				'a.pan_number as pan_number',
				'a.check_in_favour as check_in_favour'
			)
				->join('asps AS a', 'a.id', '=', 'mis_informations.asp_id')
				->join('locations AS l', 'l.id', '=', 'a.location_id')
				->join('states AS s', 's.id', '=', 'a.state_id')
				->join('Invoices AS i', 'i.id', '=', 'mis_informations.invoice_id')
				->join('batches AS b', 'b.id', '=', 'i.batch_id')
				->whereIn('i.batch_id', $batch_ids)
				->with('client')
				->orderBy('mis_informations.asp_id')
				->get()
			;

			$invoice_ids = Batch::join('Invoices AS i', 'i.batch_id', '=', 'batches.id')
				->whereIn('batches.id', $batch_ids)
				->pluck('i.id')
				->toArray()
			;

			$exportInfo = $this->getaxapta->startExport($batch_ids, $invoice_ids, $ticketList);
			$exportSheet2Info = $this->getaxapta->startSheet2Export($batch_ids, $invoice_ids, $ticketList);
			//dd($exportSheet2Info, $exportInfo);
			if (!$exportInfo) {
				return back()->withErrors(['exportError', $exportInfo]);
			}
			if (!$exportSheet2Info) {
				return back()->withErrors(['exportError', $exportSheet2Info]);
			}
			Excel::create('Axapta_export_' . date('Y-m-d H:i:s'), function ($axaptaInfo) use ($exportInfo, $exportSheet2Info) {
				$axaptaInfo->sheet('Summary', function ($sheet) use ($exportInfo) {
					$sheet->cell(1, function ($row) {
						$row->setBackground('#CCCCCC');
					});
					$sheet->fromArray($exportInfo);
				});
				$axaptaInfo->sheet('Details', function ($sheet) use ($exportSheet2Info) {
					$sheet->cell(1, function ($row) {
						$row->setBackground('#CCCCCC');
					});
					$sheet->fromArray($exportSheet2Info);
				});

			})->export('xls');
		} catch (\Exception $e) {
			dd($e);
			$message = ['error' => $e->getMessage()];
			return redirect()->back()->with($message)->withInput();
		}
	}
	public function getUnpaidBatchList(Request $request) {
		$batches = Batch::join('Invoices', 'Invoices.batch_id', '=', 'batches.id')
			->join('activities', 'activities.invoice_id', '=', 'Invoices.id')
			->join('asps', 'asps.id', '=', 'batches.asp_id')
			->selectRaw("
                    batches.id as batchid,
                    batches.batch_number,
                    batches.created_at as created_at,
                    batches.tds as tds,
                    asps.asp_code as asp_code,
                    asps.name as asp_name, batches.status,
                    asps.workshop_name as workshop_name,
                    IF(asps.is_self = 1,'SELF','NON-SELF') as asp_type,
                    CONCAT(DATE_FORMAT(min(Invoices.start_date), '%d/%m/%Y'),' - ',DATE_FORMAT(max(Invoices.end_date), '%d/%m/%Y')) as date_period,
                    COUNT(activities.id) as tickets_count,COUNT(DISTINCT(Invoices.id)) as invoices_count")
			->groupBy('batches.id')
		// ->get()
		;

		if (!Entrust::can('view-all-asp-unpaid-batches')) {
			if (Entrust::can('view-only-state-mapped-asp-unpaid-batches')) {
				$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
				$batches->whereIn('asps.state_id', $states);
			}
			if (Entrust::can('view-own-asp-unpaid-batches')) {
				$batches->where('asps.user_id', Auth::id());
			}
		}

		if ($request->get('date')) {
			$batches->whereRaw('DATE_FORMAT(batches.created_at,"%d-%m-%Y") =  "' . $request->get('date') . '"');
		}

		if ($request->get('batch_number')) {
			$batches->where('batches.batch_number', 'LIKE', '%' . $request->get('batch_number') . '%');
		}

		if ($request->get('workshop_name')) {
			$batches->where('asps.workshop_name', 'LIKE', '%' . $request->get('workshop_name') . '%');
		}

		if ($request->get('asp_code')) {
			$batches->where('asps.asp_code', 'LIKE', '%' . $request->get('asp_code') . '%');
		}

		if ($request->get('status') && $request->get('status') != '-1') {
			$batches->where('batches.status', $request->get('status'));
		} else {
			$batches->whereIn('batches.status', ['Waiting for Payment', 'Payment Inprogress']);
		}

		return Datatables::of($batches)
			->setRowAttr([
				'id' => function ($batches) {
					return route('angular') . '/#!/rsa-case-pkg/batch-view/' . $batches->batchid . '/12';
				},
			])

			->addColumn('paid_amount', function ($batches) {
				$paid_amount = Invoices::where('batch_id', $batches->batchid)->sum('invoice_amount');
				return $paid_amount;
			})

			->addColumn('action', function ($batches) {
				return '<input type="checkbox" class="ticket_id no-link child_select_all ibtnDel" name="batch_ids[]" value="' . $batches->batchid . '">';
			})
			->make(true);
	}

	public function batchView(Batch $batch) {
		$this->data['batch'] = $batch;
		$this->data['asp'] = $batch->asp;
		$this->data['tickets'] = $batch->tickets;

		if ($batch->has_gst AND $batch->invoices) {
			$batch_invoices = $batch->invoices;
			foreach ($batch_invoices as $key => $value) {
				$batch->invoices->asp = $value->asp;
				$batch->invoices->activities = $value->activities;
			}
			$this->data['invoice_details'] = $batch_invoices;
			$this->data['invoice_editable'] = ['disabled' => 'disabled'];
		}
		$this->data['payment_editable'] = ['disabled' => 'disabled'];
		// $this->data['attachments'] = Attachment::where('entity_id', $batch->id)
		// 	->where('entity_type', config('constants.entity_types.BATCH_ATTACHMENT'))
		// 	->get();
		$this->data['period'] = $batch->period;

		return response()->json($this->data);
	}

}
