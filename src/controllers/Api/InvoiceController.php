<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use App\Asp;
use App\Http\Controllers\Controller;
use App\Invoices;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use URL;
use Validator;

class InvoiceController extends Controller {
	private $successStatus = 200;

	public function createInvoice(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);

		$errors = [];
		DB::beginTransaction();
		try {

			$validator = Validator::make($request->all(), [
				'activity_id.*' => 'required|numeric|exists:activities,crm_activity_id',
				'asp_code' => 'required|string|exists:asps,asp_code',
				'invoice_number' => 'nullable|string|max:20',
				'invoice_date' => 'nullable|string|date_format:"Y-m-d"',
				'invoice_copy' => 'nullable',
			]);
			if ($validator->fails()) {
				//CREATE INVOICE API LOG
				$errors = $validator->errors()->all();
				saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			if (!isset($request->activity_id)) {
				//CREATE INVOICE API LOG
				$errors[] = 'Activity ID is required';
				saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Activity ID is required',
					],
				], $this->successStatus);
			}

			//GET ASP
			$asp = ASP::where('asp_code', $request->asp_code)->first();

			$activities = Activity::select(
				'invoice_id',
				'crm_activity_id',
				'asp_id',
				'case_id'
			)
				->whereIn('crm_activity_id', $request->activity_id)
				->whereIn('status_id', [11, 1]) //Waiting for Invoice Generation by ASP OR Case Closed - Waiting for ASP to Generate Invoice
				->get();

			//CUSTOM VALIDATION SAID BY BUSINESS TEAM
			$aug21ToNov21caseExist = false;
			$afterDec21caseExist = false;
			if ($activities->isNotEmpty()) {
				foreach ($activities as $key => $activity) {
					//CHECK ASP MATCHES WITH ACTIVITY ASP
					if ($activity->asp_id != $asp->id) {
						//CREATE INVOICE API LOG
						$errors[] = 'ASP not matched for activity ID ' . $activity->crm_activity_id;
						saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
						DB::commit();

						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'ASP not matched for activity ID ' . $activity->crm_activity_id,
							],
						], $this->successStatus);
					}
					//CHECK IF INVOICE ALREADY CREATED FOR ACTIVITY
					if (!empty($activity->invoice_id)) {
						//CREATE INVOICE API LOG
						$errors[] = 'Invoice already created for activity ID ' . $activity->crm_activity_id;
						saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
						DB::commit();

						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'Invoice already created for activity ID ' . $activity->crm_activity_id,
							],
						], $this->successStatus);
					}

					//CUSTOM VALIDATION SAID BY BUSINESS TEAM
					if (!$aug21ToNov21caseExist) {
						if ($activity->case && ((date('Y-m-d', strtotime($activity->case->date)) >= "2021-08-01") && (date('Y-m-d', strtotime($activity->case->date)) <= "2021-11-31"))) {
							$aug21ToNov21caseExist = true;
						}
					}

					if (!$afterDec21caseExist) {
						if ($activity->case && (date('Y-m-d', strtotime($activity->case->date)) >= "2021-12-01")) {
							$afterDec21caseExist = true;
						}
					}
				}
			} else {
				//CREATE INVOICE API LOG
				$errors[] = 'Activity not found';
				saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Activity not found',
					],
				], $this->successStatus);
			}

			if ($aug21ToNov21caseExist && $afterDec21caseExist) {
				//CREATE INVOICE API LOG
				$errors[] = "August'21 to November'21 cases should be separately invoiced. Cases done from 1st December 2021 should be invoiced separately for INP Payment";
				saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						"August'21 to November'21 cases should be separately invoiced. Cases done from 1st December 2021 should be invoiced separately for INP Payment",
					],
				], $this->successStatus);
			}

			//CHECK ACTIVITY IS ACCEPTED OR NOT
			$activities_with_accepted = Activity::select([
				'crm_activity_id',
				'status_id',
			])
				->whereIn('crm_activity_id', $request->activity_id)
				->get();
			if ($activities_with_accepted->isNotEmpty()) {
				foreach ($activities_with_accepted as $key => $activity_accepted) {
					//EXCEPT(Case Closed - Waiting for ASP to Generate Invoice AND Waiting for Invoice Generation by ASP)
					if ($activity_accepted->status_id != 1 && $activity_accepted->status_id != 11) {
						//CREATE INVOICE API LOG
						$errors[] = 'ASP not accepted / case not closed for activity ID ' . $activity_accepted->crm_activity_id;
						saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
						DB::commit();

						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'ASP not accepted / case not closed for activity ID ' . $activity_accepted->crm_activity_id,
							],
						], $this->successStatus);
					}
				}
			}

			//SELF INVOICE
			if ($asp->has_gst && !$asp->is_auto_invoice) {
				if (!$request->invoice_number) {
					//CREATE INVOICE API LOG
					$errors[] = 'Invoice number is required';
					saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Invoice number is required',
						],
					], $this->successStatus);
				}
				if (!$request->invoice_date) {
					//CREATE INVOICE API LOG
					$errors[] = 'Invoice date is required';
					saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Invoice date is required',
						],
					], $this->successStatus);
				}
				// if (!$request->invoice_copy) {
				// 	return response()->json([
				// 		'success' => false,
				// 		'error' => 'Validation Error',
				// 		'errors' => 'Invoice copy is required',
				// 	], $this->successStatus);
				// }

				//CHECK IF ZERO AS FIRST LETTER
				$invoiceNumberfirstLetter = substr(trim($request->invoice_number), 0, 1);
				if (is_numeric($invoiceNumberfirstLetter)) {
					if ($invoiceNumberfirstLetter == 0) {
						//CREATE INVOICE API LOG
						$errors[] = 'Invoice number should not start with zero';
						saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
						DB::commit();
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'Invoice number should not start with zero',
							],
						], $this->successStatus);
					}
				}

				//SPECIAL CHARACTERS NOT ALLOWED AT PREFIX
				if (!preg_match("/^[A-Za-z0-9]{1}/", $request->invoice_number)) {
					//CREATE INVOICE API LOG
					$errors[] = 'Special characters are not allowed at the beginning of the invoice number';
					saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
					DB::commit();
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Special characters are not allowed at the beginning of the invoice number',
						],
					], $this->successStatus);
				}

				//SPECIAL CHARACTERS NOT ALLOWED AT SUFFIX
				if (!preg_match("/[A-Za-z0-9]{1}$/", $request->invoice_number)) {
					//CREATE INVOICE API LOG
					$errors[] = 'Special characters are not allowed at the end of the invoice number';
					saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
					DB::commit();
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Special characters are not allowed at the end of the invoice number',
						],
					], $this->successStatus);
				}

				$invoice_no = $request->invoice_number;
				$invoice_date = date('Y-m-d H:i:s', strtotime($request->invoice_date));
			} else {
				//SYSTEM

				//GENERATE INVOICE NUMBER
				$invoice_no = generateAppInvoiceNumber();
				$invoice_date = new Carbon();
			}

			//STORE ATTACHMENT
			$value = "";
			if (!empty($request->invoice_copy)) {
				$image = $request->invoice_copy; // base64 encoded
				$image = str_replace('data:image/png;base64,', '', $image);
				$image = str_replace(' ', '+', $image);
				$f = finfo_open();
				$mime_type = finfo_buffer($f, base64_decode($image), FILEINFO_MIME_TYPE);
				$extension = '';
				if ($mime_type == "image/jpeg") {
					$extension = 'jpg';
				} elseif ($mime_type == "image/png") {
					$extension = 'png';
				} elseif ($mime_type == "application/pdf") {
					$extension = 'pdf';
				} else {
					//CREATE INVOICE API LOG
					$errors[] = 'Invoice copy must be following file type: jpeg, png or pdf.';
					saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
					DB::commit();

					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Invoice copy must be following file type: jpeg, png or pdf.',
						],
					], $this->successStatus);

				}
				$max_id = Invoices::selectRaw("Max(id) as id")->first();

				if (!empty($max_id)) {
					$ids = $max_id->id + 1;
					$imageName = "Invoice" . $ids . "." . $extension;
				} else {
					$imageName = "Invoice1" . "." . $extension;
				}
				Storage::disk('asp-invoice-attachment-folder')->put($imageName, base64_decode($image));
				$value = $imageName;
			}
			//CREATE INVOICE
			$invoice_c = Invoices::createInvoice($asp, $request->activity_id, $invoice_no, $invoice_date, $value, false);

			if (!$invoice_c['success']) {
				//CREATE INVOICE API LOG
				DB::rollBack();
				$errors[] = $invoice_c['message'];
				saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);
				// DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						$invoice_c['message'],
					],
				], $this->successStatus);
			}

			//CREATE INVOICE API LOG
			saveApiLog(106, NULL, $request->all(), $errors, NULL, 120);

			DB::commit();
			if ($invoice_c['success']) {
				return response()->json([
					'success' => true,
					'message' => 'Invoice created successfully',
					'invoice' => $invoice_c['invoice'],
				], $this->successStatus);
			}

		} catch (\Exception $e) {
			DB::rollBack();
			//CREATE INVOICE API LOG
			$errors[] = $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
			saveApiLog(106, NULL, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function getList(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		$errors = [];
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
				'offset' => 'nullable|numeric',
				'limit' => 'nullable|numeric',
			]);

			if ($validator->fails()) {
				//GET INVOICE LIST API LOG
				$errors = $validator->errors()->all();
				saveApiLog(107, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//GET ASP
			$asp = Asp::where('asp_code', $request->asp_code)->first();

			$invoices = Invoices::
				select(
				'Invoices.id',
				'Invoices.invoice_no',
				// DB::raw("(CASE WHEN (asps.has_gst = 1 && asps.is_auto_invoice = 0) THEN  Invoices.invoice_no ELSE CONCAT(Invoices.invoice_no,'-',Invoices.id) END) as invoice_no"),
				// DB::raw("(CASE WHEN (asps.is_auto_invoice = 1) THEN CONCAT(Invoices.invoice_no,'-',Invoices.id) ELSE Invoices.invoice_no END) as invoice_no"),
				// DB::raw("CONCAT(Invoices.invoice_no,'-',Invoices.id) as invoice_no"),
				DB::raw("date_format(Invoices.created_at,'%d-%m-%Y') as invoice_date"),
				DB::raw("COUNT(activities.id) as no_of_tickets"),
				// DB::raw("ROUND(SUM(activities.bo_invoice_amount),2) as invoice_amount"),
				DB::raw("ROUND(Invoices.invoice_amount,2) as invoice_amount"),
				'invoice_statuses.name as payment_status',
				'asps.workshop_name as workshop_name',
				'asps.asp_code as asp_code'
			)
				->where('activities.asp_id', '=', $asp->id)
			// ->where('Invoices.flow_current_status', 'Waiting for Batch Generation')
				->join('asps', 'Invoices.asp_id', '=', 'asps.id')
				->leftjoin('invoice_statuses', 'invoice_statuses.id', '=', 'Invoices.status_id')
				->join('activities', 'Invoices.id', '=', 'activities.invoice_id');

			if ($request->offset && $request->limit) {
				$invoices->offset($request->offset);
			}
			if ($request->limit) {
				$invoices->limit($request->limit);
			}

			$invoices = $invoices->groupBy('Invoices.id')->orderBy('Invoices.created_at', 'desc')->get();

			if (count($invoices) > 0) {
				foreach ($invoices as $key => $invoice) {
					$invoice->invoice_copy = URL::asset('storage/app/public/invoices/' . $invoice->id . '.pdf');
				}
			}

			//GET INVOICE LIST API LOG
			saveApiLog(107, NULL, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'invoices' => $invoices,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//GET INVOICE LIST API LOG
			$errors[] = $e->getMessage() . ' Line:' . $e->getLine();
			saveApiLog(107, NULL, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . ' Line:' . $e->getLine(),
				],
			], $this->successStatus);
		}
	}

	public function getDetails(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		$errors = [];

		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
				'invoice_no' => 'required|string',
			]);

			if ($validator->fails()) {
				//GET INVOICE DETAIL API LOG
				$errors = $validator->errors()->all();
				saveApiLog(108, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$invoice = Invoices::whereRaw("CONCAT(invoice_no,'-',id) like ?", ["%$request->invoice_no%"])->first();

			if (!$invoice) {
				//GET INVOICE DETAIL API LOG
				$errors[] = 'Selected invoice no is invalid';
				saveApiLog(108, NULL, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Selected invoice no is invalid',
					],
				], $this->successStatus);

			}

			//GET ASP
			$asp = Asp::where('asp_code', $request->asp_code)->first();

			//GET INVOICE DETAIL
			$invoices = Invoices::select(
				'Invoices.*',
				DB::raw("CONCAT(invoice_no,'-',id) as invoice_no")
			)
				->whereRaw("CONCAT(invoice_no,'-',id) like ?", ["%$request->invoice_no%"])
				->where('asp_id', $asp->id)
				->where('flow_current_status', 'Waiting for Batch Generation')
				->first();

			$invoices->asp = $asp;

			//GET INVOICE DETAIL API LOG
			saveApiLog(108, NULL, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'invoices' => $invoices,
			], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			//GET INVOICE DETAIL API LOG
			$errors[] = $e->getMessage() . ' Line:' . $e->getLine();
			saveApiLog(108, NULL, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'errors' => [
					$e->getMessage() . ' Line:' . $e->getLine(),
				],
			], $this->successStatus);
		}
	}
}
