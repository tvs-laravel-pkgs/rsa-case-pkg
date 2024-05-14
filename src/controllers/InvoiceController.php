<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use Abs\RsaCasePkg\ActivityReport;
use App\Asp;
use App\Http\Controllers\Admin\AxaptaExportController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SoapController;
use App\Invoices;
use App\InvoiceVoucher;
use App\Oracle\ApInvoiceExport;
use App\StateUser;
use Auth;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Redirect;
use Yajra\Datatables\Datatables;

class InvoiceController extends Controller {
	protected $getaxapta, $getSoap;
	public function __construct(AxaptaExportController $getaxapta, SoapController $getSoap) {
		$this->getSoap = $getSoap;
		$this->getaxapta = $getaxapta;
	}

	public function getFilterData($type_id) {
		if ($type_id == 1) {
			$title = 'Waiting for Finance Process';
		} elseif ($type_id == 2) {
			$title = 'Payment Inprogress';
		} elseif ($type_id == 3) {
			$title = 'Paid Invoices';
		}

		$this->data['extras'] = [
			'asp_list' => collect(Asp::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select ASP']),
			'title' => $title,
		];
		return response()->json($this->data);
	}

	public function getList(Request $request) {
		$invoices = Invoices::select(
			'Invoices.id',
			'Invoices.invoice_no',
			// DB::raw("(CASE WHEN (asps.has_gst = 1 && asps.is_auto_invoice = 0) THEN  Invoices.invoice_no ELSE CONCAT(Invoices.invoice_no,'-',Invoices.id) END) as invoice_no"),
			// DB::raw("(CASE WHEN (asps.is_auto_invoice = 1) THEN CONCAT(Invoices.invoice_no,'-',Invoices.id) ELSE Invoices.invoice_no END) as invoice_no"),
			// DB::raw("CONCAT(Invoices.invoice_no,'-',Invoices.id) as invoice_no"),
			DB::raw("date_format(Invoices.created_at,'%d-%m-%Y') as invoice_date"),
			DB::raw("FORMAT(Invoices.invoice_amount,2) as invoice_amount"),
			'asps.asp_code as asp_code',
			'asps.workshop_name as workshop_name',
			'invoice_statuses.name as payment_status',
			DB::raw("COUNT(activities.id) as no_of_activities")
		)
			->join('asps', 'Invoices.asp_id', '=', 'asps.id')
			->join('users', 'users.id', 'asps.user_id')
			->join('activities', 'activities.invoice_id', '=', 'Invoices.id')
			->join('invoice_statuses', 'invoice_statuses.id', '=', 'Invoices.status_id')
			->orderBy('Invoices.created_at', 'desc')
			->groupBy('Invoices.id')
		;

		if ($request->get('date')) {
			$invoices->whereRaw('DATE_FORMAT(Invoices.created_at,"%d-%m-%Y") =  "' . $request->get('date') . '"');
		}

		//UNPAID
		if ($request->type_id == 1) {
			$invoices->where('Invoices.status_id', 1); //PAYMENT PENDING
			if (!Entrust::can('view-all-asp-unpaid-invoices')) {
				if (Entrust::can('view-only-state-asp-unpaid-invoices')) {
					$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
					$invoices->whereIn('asps.state_id', $states);
				}
				if (Entrust::can('view-only-own-asp-unpaid-invoices')) {
					if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
						$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
						$aspIds[] = Auth::user()->asp->id;
						$invoices->whereIn('asps.id', $aspIds);
					} else {
						$invoices->where('users.id', Auth::id());
					}
				}
			}
		} elseif ($request->type_id == 2) {
			//PAYMENT INPROGRESS
			$invoices->where('Invoices.status_id', 3); //PAYMENT INPROGRESS
			if (!Entrust::can('view-all-asp-payment-inprogress-invoices')) {
				if (Entrust::can('view-only-state-asp-payment-inprogress-invoices')) {
					$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
					$invoices->whereIn('asps.state_id', $states);
				}
				if (Entrust::can('view-only-own-asp-payment-inprogress-invoices')) {
					if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
						$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
						$aspIds[] = Auth::user()->asp->id;
						$invoices->whereIn('asps.id', $aspIds);
					} else {
						$invoices->where('users.id', Auth::id());
					}
				}
			}
		} elseif ($request->type_id == 3) {
			//PAID
			$invoices->where('Invoices.status_id', 2); //PAYMENT PAID
			if (!Entrust::can('view-all-asp-paid-invoices')) {
				if (Entrust::can('view-only-state-asp-paid-invoices')) {
					$states = StateUser::where('user_id', '=', Auth::id())->pluck('state_id')->toArray();
					$invoices->whereIn('asps.state_id', $states);
				}
				if (Entrust::can('view-only-own-asp-paid-invoices')) {
					if (Auth::user()->asp && Auth::user()->asp->is_finance_admin == 1) {
						$aspIds = Asp::where('finance_admin_id', Auth::user()->asp->id)->pluck('id')->toArray();
						$aspIds[] = Auth::user()->asp->id;
						$invoices->whereIn('asps.id', $aspIds);
					} else {
						$invoices->where('users.id', Auth::id());
					}
				}
			}
		}

		return Datatables::of($invoices)
			->setRowAttr([
				'id' => function ($invoices) use ($request) {
					return route('angular') . '/#!/rsa-case-pkg/invoice/view/' . $invoices->id . '/' . $request->type_id;
				},
			])
		// ->filterColumn('invoice_no', function ($query, $keyword) {
		// 	$query->whereRaw("CONCAT(Invoices.invoice_no,'-',Invoices.id) like ?", ["%{$keyword}%"]);
		// })
			->addColumn('action', function ($invoices) {
				return '<input type="checkbox" class="ticket_id no-link child_select_all" name="invoice_ids[]" value="' . $invoices->id . '">';
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

	public function viewInvoice($invoice_id, $type_id) {
		return Invoices::viewData($invoice_id, $type_id);
	}

	public function export(Request $request) {
		// dd($request->all());
		try {
			ini_set('max_execution_time', 0);
			ini_set('display_errors', 1);
			ini_set('memory_limit', '-1');
			ob_end_clean();
			ob_start();

			if (!isset($request->typeId) || (isset($request->typeId) && empty($request->typeId))) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/1')->with('error', 'Type not found');
			}

			$invoiceStatusId = NULL;
			//PAYMENT PENDING
			if ($request->typeId == '1') {
				$invoiceStatusId = 1;
			} elseif ($request->typeId == '2') {
				// PAYMENT INPROGRESS
				$invoiceStatusId = 3;
			}

			$periods = getStartDateAndEndDate($request->exportPeriod);
			$startDate = $periods['start_date'];
			$endDate = $periods['end_date'];

			$activities = Activity::select(
				'activities.*',
				'cases.number as ticket_number',
				'cases.vehicle_registration_number',
				'vehicle_models.name as vehicle_model',
				'vehicle_makes.name as vehicle_make',
				'cases.membership_type as type_of_eligibility',
				'service_types.name as service_type',
				DB::raw('DATE_FORMAT(cases.date,"%d-%m-%Y %H:%i:%s") as ticket_date_time'),
				DB::raw('DATE_FORMAT(cases.created_at,"%d-%m-%Y %H:%i:%s") as created_at'),
				'asps.axpta_code as axpta_code',
				'asps.workshop_name as workshop_name',
				'Invoices.invoice_no',
				// DB::raw("(CASE WHEN (asps.has_gst = 1 && asps.is_auto_invoice = 0) THEN  Invoices.invoice_no ELSE CONCAT(Invoices.invoice_no,'-',Invoices.id) END) as invoice_no"),
				// DB::raw("(CASE WHEN (asps.is_auto_invoice = 1) THEN CONCAT(Invoices.invoice_no,'-',Invoices.id) ELSE Invoices.invoice_no END) as invoice_no"),
				'Invoices.created_at as created_at',
				'asps.asp_code as asp_code',
				'locations.name as location_name',
				'states.name as state_name',
				'asps.gst_registration_number as gst_registration_number',
				'asps.tax_calculation_method as tax_calculation_method',
				'asps.bank_name as bank_name',
				'asps.has_gst',
				'asps.tax_calculation_method',
				'asps.bank_account_number as bank_account_number',
				'asps.bank_ifsc_code as bank_ifsc_code',
				'asps.pan_number as pan_number',
				'asps.check_in_favour as check_in_favour',
				'clients.financial_dimension',
				'bo_invoice_amount.value as invoice_amount',
				'bo_net_amount.value as net_amount',
				'bo_tax_total.value as tax',
				'bo_km_travelled.value as bo_km',
				'bo_payout_amount.value as payout_amount',
				'bo_collected.value as bo_collected_charges',
				'bo_not_collected.value as bo_other_charges',
				'activities.bo_comments as comments'
			)
				->join('cases', 'cases.id', '=', 'activities.case_id')
				->join('clients', 'clients.id', '=', 'cases.client_id')
				->join('asps', 'asps.id', '=', 'activities.asp_id')
				->join('locations', 'locations.id', '=', 'asps.location_id')
				->join('states', 'states.id', '=', 'asps.state_id')
				->join('Invoices', 'Invoices.id', '=', 'activities.invoice_id')
				->join('vehicle_models', 'vehicle_models.id', '=', 'cases.vehicle_model_id')
				->join('vehicle_makes', 'vehicle_makes.id', '=', 'vehicle_models.vehicle_make_id')
				->join('service_types', 'service_types.id', '=', 'activities.service_type_id')
				->leftJoin('activity_details as bo_km_travelled', function ($join) {
					$join->on('bo_km_travelled.activity_id', 'activities.id')
						->where('bo_km_travelled.key_id', 158); //BO KM TRAVELLED
				})
				->leftJoin('activity_details as bo_payout_amount', function ($join) {
					$join->on('bo_payout_amount.activity_id', 'activities.id')
						->where('bo_payout_amount.key_id', 172); //BO PAYOUT AMOUNT
				})
				->leftJoin('activity_details as bo_collected', function ($join) {
					$join->on('bo_collected.activity_id', 'activities.id')
						->where('bo_collected.key_id', 159); //BO COLLECTED
				})
				->leftJoin('activity_details as bo_not_collected', function ($join) {
					$join->on('bo_not_collected.activity_id', 'activities.id')
						->where('bo_not_collected.key_id', 160); //BO NOT COLLECTED
				})
				->leftJoin('activity_details as bo_invoice_amount', function ($join) {
					$join->on('bo_invoice_amount.activity_id', 'activities.id')
						->where('bo_invoice_amount.key_id', 182); //BO INVOICE AMOUNT
				})
				->leftJoin('activity_details as bo_net_amount', function ($join) {
					$join->on('bo_net_amount.activity_id', 'activities.id')
						->where('bo_net_amount.key_id', 176); //BO NET AMOUNT
				})
				->leftJoin('activity_details as bo_tax_total', function ($join) {
					$join->on('bo_tax_total.activity_id', 'activities.id')
						->where('bo_tax_total.key_id', 179); //BO TAX AMOUNT
				})
				->where(function ($query) use ($startDate, $endDate) {
					if (!empty($startDate) && !empty($endDate)) {
						$query->whereRaw('DATE(Invoices.created_at) between "' . $startDate . '" and "' . $endDate . '"');
					}
				})
				->where(function ($query) use ($invoiceStatusId) {
					if (!empty($invoiceStatusId)) {
						$query->where('Invoices.status_id', $invoiceStatusId);
					}
				})
				->orderBy('activities.asp_id')
				->groupBy('activities.id')
				->get();

			if ($activities->isEmpty()) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/' . $request->typeId)->with('error', 'No data not found');
			}

			$invoice_ids = $activities->pluck('invoice_id')->toArray();
			$exportInfo = $this->getaxapta->startExportInvoice($invoice_ids, $activities);
			$exportSheet2Info = $this->getaxapta->startSheet2ExportInvoice($invoice_ids, $activities);

			if (!$exportInfo) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/' . $request->typeId)->with('error', 'Invoice not found');
			}
			if (!$exportSheet2Info) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/' . $request->typeId)->with('error', 'Invoice not found');
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
			})->export('xlsx');
		} catch (\Exception $e) {
			return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/' . $request->typeId)->with('error', $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile());
		}
	}

	public function getPaymentInfo($invoice_id) {
		$invoice = Invoices::find($invoice_id);
		if (!$invoice) {
			return response()->json(['success' => false, 'error' => 'Invoice not found']);
		}

		$asp = Asp::where('id', $invoice->asp_id)->first();
		if (!$asp) {
			return response()->json(['success' => false, 'error' => 'ASP not found']);
		}

		// if ($asp->has_gst && !$asp->is_auto_invoice) {
		// 	$invoice_no = $invoice->invoice_no;
		// } else {
		// 	$invoice_no = $invoice->invoice_no . '-' . $invoice->id;
		// }
		$invoice_no = $invoice->invoice_no;

		$storeInvoicePaymentInfo = $this->getSoap->GetPaymentInfoByInvoice($invoice->id, $invoice_no);
		if (!$storeInvoicePaymentInfo['success']) {
			return response()->json([
				'success' => false,
				'error' => $storeInvoicePaymentInfo['error'],
			]);
		}

		return InvoiceVoucher::getASPInvoicePaymentViewInfo($invoice_id);
	}

	public function getVoucherDetails() {
		try
		{
			$invoices = Invoices::where('flow_current_status', 'Payment Inprogress')->limit(100)->get();
			if ($invoices->isNotEmpty()) {
				foreach ($invoices as $key => $invoice) {
					$invoice_no = $invoice->invoice_no;
					dump(' == before === ' . $invoice_no);
					$soapResponse = $this->getSoap->GetPaymentInfoByInvoice($invoice->id, $invoice_no);
					if (!$soapResponse['success']) {
						dump(' invoice no' . $invoice_no . ' === failure ==');
					} else {
						dump(' invoice no' . $invoice_no . ' === success ==');
					}
				}
			}
		} catch (\Exception $e) {
			dd($e);
		}
	}

	public function cancel(Request $request) {
		// dd($request->all());
		try {
			if (empty($request->invoiceIds)) {
				return response()->json([
					'success' => false,
					'errors' => [
						'Please select atleast one invoice',
					],
				]);
			}

			$invoiceActivities = Activity::select([
				'id',
				'status_id',
				'invoice_id',
			])
				->whereIn('invoice_id', $request->invoiceIds)
				->get();
			if ($invoiceActivities->isNotEmpty()) {
				foreach ($invoiceActivities as $activityKey => $activityVal) {

					$activityVal->invoice_id = NULL;
					$activityVal->status_id = 6; //ASP Completed Data Entry - Waiting for L1 Individual Verification
					$activityVal->save();

					//SAVE ACTIVITY REPORT FOR DASHBOARD
					ActivityReport::saveReport($activityVal->id);
				}
			}

			Invoices::whereIn('id', $request->invoiceIds)->delete();
			return response()->json([
				'success' => true,
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

	public function oracleExport(Request $request) {
		// dd($request->all());
		try {
			ini_set('max_execution_time', 0);
			ini_set('display_errors', 1);
			ini_set('memory_limit', '-1');
			ob_end_clean();
			ob_start();

			if (!isset($request->oraclePageTypeId) || (isset($request->oraclePageTypeId) && empty($request->oraclePageTypeId))) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/1')->with('error', 'Type not found');
			}

			$invoiceStatusId = NULL;
			//PAYMENT PENDING
			if ($request->oraclePageTypeId == '1') {
				$invoiceStatusId = 1;
			} elseif ($request->oraclePageTypeId == '2') {
				// PAYMENT INPROGRESS
				$invoiceStatusId = 3;
			} elseif ($request->oraclePageTypeId == '3') {
				// PAID
				$invoiceStatusId = 2;
			}

			$periods = getStartDateAndEndDate($request->oracle_invoice_period);
			$startDate = $periods['start_date'];
			$endDate = $periods['end_date'];

			$oracleExports = ApInvoiceExport::select(["oracle_ap_invoice_exports.*"])
				->join('Invoices', 'Invoices.id', "oracle_ap_invoice_exports.entity_id")
				->where('oracle_ap_invoice_exports.entity_type_id', 1321) //ASP INVOICE
				->where(function ($query) use ($invoiceStatusId) {
					if (!empty($invoiceStatusId)) {
						$query->where('Invoices.status_id', $invoiceStatusId);
					}
				})
				->where(function ($query) use ($startDate, $endDate) {
					if (!empty($startDate) && !empty($endDate)) {
						$query->whereRaw('DATE(oracle_ap_invoice_exports.invoice_date) between "' . $startDate . '" and "' . $endDate . '"');
					}
				})
				->get();
			if ($oracleExports->isNotEmpty()) {
				foreach ($oracleExports as $oracle_export) {
					$oracleExportsDetails[] = [
						'BusinessUnit' => $oracle_export->business_unit,
						'InvoiceSource' => $oracle_export->invoice_source,
						'SupplierInvoiceNumber' => $oracle_export->supplier_invoice_number,
						'InvoiceAmount' => $oracle_export->invoice_amount,
						'InvoiceDate' => $oracle_export->invoice_date,
						'SupplierNumber' => $oracle_export->supplier_number,
						'SupplierSite' => $oracle_export->supplier_site,
						'InvoiceType' => $oracle_export->invoice_type,
						'AccountingDate' => $oracle_export->accounting_date,
						'Description' => $oracle_export->description,
						'RemitToSupplier' => $oracle_export->remit_to_supplier,
						'AddressName' => $oracle_export->address_name,
						'PaymentMethod' => $oracle_export->payment_method,
						'BankAccount' => $oracle_export->bank_account,
						'DMSGRNNo' => $oracle_export->dms_grn_no,
						'Outlet' => $oracle_export->outlet,
						'ChassisNumber' => $oracle_export->chassis_number,
						'EngineNumber' => $oracle_export->engine_number,
						'Model' => $oracle_export->model,
						'ModelCode' => $oracle_export->model_code,
						'DocumentType' => $oracle_export->document_type,
						'PONumber' => $oracle_export->po_number,
						'PODate' => $oracle_export->po_date,
						'LineType' => $oracle_export->line_type,
						'Amount' => $oracle_export->amount,
						'LineDescription' => $oracle_export->line_description,
						'TaxClassification' => $oracle_export->tax_classification,
						'CGST' => $oracle_export->cgst,
						'SGST' => $oracle_export->sgst,
						'IGST' => $oracle_export->igst,
						'TCS' => $oracle_export->tcs,
						'CESS' => $oracle_export->cess,
						'UGST' => $oracle_export->ugst,
						'HSNCode' => $oracle_export->hsn_code,
						'TaxAmount' => $oracle_export->tax_amount,
						'ProductGroup' => $oracle_export->product_group,
						'AccountingClass' => $oracle_export->accounting_class,
						'Company' => $oracle_export->company,
						'LOB' => $oracle_export->lob,
						'Location' => $oracle_export->location,
						'Department' => $oracle_export->department,
						'NaturalAccount' => $oracle_export->natural_account,
						'ProductSegment' => $oracle_export->product_segment,
						'CustomerSegment' => $oracle_export->customer_segment,
						'Intercompany' => $oracle_export->intercompany,
						'Future1' => $oracle_export->future_1,
						'Future2' => $oracle_export->future_2,
					];
				}
				$timeStamp = date('Ymdhis');
				Excel::create('ASP_AP_INV_' . $timeStamp, function ($excel) use ($oracleExportsDetails) {
					$excel->sheet('Sheet1', function ($sheet) use ($oracleExportsDetails) {
						$sheet->fromArray($oracleExportsDetails, NULL, 'A1');
						$sheet->row(1, function ($row) {
							$row->setBackground('#bbc0c9');
							$row->setAlignment('center');
							$row->setFontSize(10);
							$row->setFontFamily('Work Sans');
							$row->setFontWeight('bold');
						});
						$sheet->setAutoSize(true);
					});
					$excel->setActiveSheetIndex(0);
				})->export('csv');
			} else {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/' . $request->oraclePageTypeId)->with('error', 'No data found!');
			}
		} catch (\Exception $e) {
			return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/' . $request->oraclePageTypeId)->with('error', $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile());
		}
	}
}
