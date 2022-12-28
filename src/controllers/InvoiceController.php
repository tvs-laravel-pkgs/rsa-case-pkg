<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\Activity;
use App\Asp;
use App\Attachment;
use App\Http\Controllers\Admin\AxaptaExportController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SoapController;
use App\Invoices;
use App\InvoiceVoucher;
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
		$this->data['invoice'] = $invoice = Invoices::where('Invoices.id', $invoice_id)->
			select(
			'Invoices.*',
			'asps.gst_registration_number',
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

		if (!$invoice) {
			return response()->json([
				'success' => false,
				'errors' => ['Invoice not found'],
			]);
		}

		if ($type_id == 1) {
			$title = 'Waiting for Finance Process';
		} elseif ($type_id == 2) {
			$title = 'Payment Inprogress';
		} elseif ($type_id == 3) {
			$title = 'Paid Invoice';
		}
		$this->data['title'] = $title;

		$activities = Activity::join('cases', 'cases.id', 'activities.case_id')
			->join('call_centers', 'call_centers.id', 'cases.call_center_id')
			->join('service_types', 'service_types.id', 'activities.service_type_id')
			->join('activity_portal_statuses', 'activity_portal_statuses.id', 'activities.status_id')
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
			->leftJoin('activity_details as waiting_charges', function ($join) {
				$join->on('waiting_charges.activity_id', 'activities.id')
					->where('waiting_charges.key_id', 326); //BO waiting charges
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
					->where('total_amount.key_id', 182); //BO TOTAL AMOUNT
			})
			->leftjoin('configs as data_sources', 'data_sources.id', 'activities.data_src_id')
			->select([
				'activities.number as activityNumber',
				'activities.id',
				'activities.asp_id as asp_id',
				'cases.number',
				DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y")as date'),
				'activity_portal_statuses.name as status',
				'call_centers.name as callcenter',
				'cases.vehicle_registration_number',
				'service_types.name as service_type',
				'km_charge.value as km_charge_value',
				'km_travelled.value as km_value',
				'not_collected_amount.value as not_collect_value',
				'waiting_charges.value as waiting_charges',
				'net_amount.value as net_value',
				'collect_amount.value as collect_value',
				'total_amount.value as total_value',
				'total_tax_perc.value as total_tax_perc_value',
				'total_tax_amount.value as total_tax_amount_value',
				'data_sources.name as data_source',
			])
			->where('invoice_id', $invoice_id)
			->groupBy('activities.id')
			->get();

		if (count($activities) > 0) {
			foreach ($activities as $key => $activity) {
				$taxes = DB::table('activity_tax')->leftjoin('taxes', 'activity_tax.tax_id', '=', 'taxes.id')->where('activity_id', $activity->id)->select('taxes.tax_name', 'taxes.tax_rate', 'activity_tax.*')->get();
				$activity->taxes = $taxes;
			}
		}
		$this->data['activities'] = $activities;
		$this->data['invoice_amount'] = number_format($invoice->amount, 2);
		$this->data['invoice_amount_in_word'] = getIndianCurrency($invoice->amount);
		$this->data['mis_infos'] = $invoice->tickets;
		$this->data['mis_info'] = $invoice->tickets;
		$asp = $invoice->asp;
		$asp->rm = $invoice->asp->rm;
		$this->data['period'] = $invoice->startdate . ' to ' . $invoice->enddate;
		// if ($asp->has_gst && !$asp->is_auto_invoice) {
		// 	$this->data['inv_no'] = $invoice->invoice_no;
		// } else {
		// 	$this->data['inv_no'] = $invoice->invoice_no . '-' . $invoice->id;
		// }
		$this->data['inv_no'] = $invoice->invoice_no;
		$this->data['irn'] = $invoice->irn;
		$this->data['inv_date'] = $invoice->created_at;
		$this->data['batch'] = "";
		$this->data['asp'] = $asp;
		$this->data['rsa_address'] = config('rsa.INVOICE_ADDRESS');

		//CHECK NEW/OLD COMPANY ADDRESS BY INVOICE CREATION DATE
		$inv_created = date('Y-m-d', strtotime(str_replace('/', '-', $invoice->created_at)));
		$automobile_company_effect_date = config('rsa.AUTOMOBILE_COMPANY_EFFECT_DATE');
		$ki_company_effect_date = config('rsa.KI_COMPANY_EFFECT_DATE');

		$this->data['auto_assist_company_address'] = false;
		$this->data['automobile_company_address'] = false;
		$this->data['ki_company_address'] = false;

		if ($inv_created < $automobile_company_effect_date) {
			$this->data['auto_assist_company_address'] = true;
		} elseif ($inv_created >= $automobile_company_effect_date && $inv_created < $ki_company_effect_date) {
			$this->data['automobile_company_address'] = true;
		} else {
			$this->data['ki_company_address'] = true;
		}

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

		//CALL SOAP API TO GET INVOCIE VOUCHER DETAILS
		// if (Entrust::can('view-invoice-payment-info')) {
		// 	$invoice_no = $invoice->invoice_no;
		// 	$this->getSoap->GetPaymentInfoByInvoice($invoice->id, $invoice_no);
		// }

		$this->data['invoice_vouchers_amount'] = InvoiceVoucher::select(
			DB::raw("SUM(invoice_amount) as total_amount")
		)->where('invoice_id', $invoice_id)
			->groupBy('invoice_id')
			->first();

		$this->data['invoice_vouchers'] = InvoiceVoucher::with('invoice')->where('invoice_id', $invoice_id)->get();
		$this->data['success'] = true;

		return response()->json($this->data);
	}

	public function export(Request $request) {
		try {
			ini_set('max_execution_time', 0);
			ini_set('display_errors', 1);
			ini_set('memory_limit', '5000M');

			if (empty($request->invoice_ids)) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/1')->with('error', 'Please select atleast one invoice');
			}
			$invoice_ids = $request->invoice_ids;

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
				->whereIn('Invoices.id', $invoice_ids)
				->orderBy('activities.asp_id')
				->groupBy('activities.id')
				->get();

			$exportInfo = $this->getaxapta->startExportInvoice($invoice_ids, $activities);
			$exportSheet2Info = $this->getaxapta->startSheet2ExportInvoice($invoice_ids, $activities);

			if (!$exportInfo) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/1')->with('error', 'Invoice not found');
			}
			if (!$exportSheet2Info) {
				return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/1')->with('error', 'Invoice not found');
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
			return Redirect::to(route('angular') . '/#!/rsa-case-pkg/invoice/list/1')->with('error', $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile());
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
			Activity::whereIn('invoice_id', $request->invoiceIds)->update(['invoice_id' => NULL, 'status_id' => 6]);
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

}
