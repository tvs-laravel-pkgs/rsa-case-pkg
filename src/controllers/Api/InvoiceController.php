<?php

namespace Abs\RsaCasePkg\Api;
use Abs\RsaCasePkg\Activity;
use App\Asp;
use App\Http\Controllers\Controller;
use App\Invoices;
use DB;
use Illuminate\Http\Request;
use Validator;

class InvoiceController extends Controller {
	private $successStatus = 200;

	public function createInvoice(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'activity_id.*' => 'required|numeric|exists:activities,crm_activity_id',
				'asp_code' => 'required|string|exists:asps,asp_code',
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//CHECK IF INVOICE ALREADY CREATED FOR ACTIVITY
			$activities = Activity::select('invoice_id', 'crm_activity_id')->whereIn('crm_activity_id', $request->activity_id)->get();
			if (!empty($activities)) {
				foreach ($activities as $key => $activity) {
					if (!empty($activity->invoice_id)) {
						return response()->json([
							'success' => false,
							'message' => 'Validation Error',
							'errors' => 'Invoice already created for activity ID ' . $activity->crm_activity_id,
						], $this->successStatus);
					}
				}
			}

			//CHECK ACTIVITY IS ACCEPTED OR NOT
			$activities_with_accepted = Activity::select('crm_activity_id', 'status_id')->whereIn('crm_activity_id', $request->activity_id)->get();
			if (!empty($activities_with_accepted)) {
				foreach ($activities_with_accepted as $key => $activity_accepted) {
					if ($activity_accepted->status_id != 1) {
						return response()->json([
							'success' => false,
							'message' => 'Validation Error',
							'errors' => 'ASP not accepted for activity ID ' . $activity_accepted->crm_activity_id,
						], $this->successStatus);
					}
				}
			}

			//GET ASP
			$asp = ASP::where('asp_code', $request->asp_code)->first();

			$invoice_c = Invoices::createInvoice($asp, $request->activity_id);

			DB::commit();
			if ($invoice_c) {
				return response()->json([
					'success' => true,
					'message' => 'Invoice created successfully',
				], $this->successStatus);
			} else {
				return response()->json([
					'success' => false,
					'message' => 'Invoice not created',
				], $this->successStatus);

			}

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getList(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->all()], $this->successStatus);
			}

			//GET ASP
			$asp = Asp::where('asp_code', $request->asp_code)->first();

			$invoices = Invoices::
				select(
				'invoices.id as id',
				DB::raw("CONCAT(invoices.invoice_no,'-',invoices.id) as invoice_no"),
				DB::raw("date_format(invoices.created_at,'%d-%m-%Y') as invoice_date"),
				DB::raw("COUNT(activities.id) as no_of_tickets"),
				// DB::raw("ROUND(SUM(activities.bo_invoice_amount),2) as invoice_amount"),
				DB::raw("ROUND(invoices.invoice_amount,2) as invoice_amount"),
				'asps.workshop_name as workshop_name',
				'asps.asp_code as asp_code'
			)
				->where('activities.asp_id', '=', $asp->id)
				->where('invoices.flow_current_status', 'Waiting for Batch Generation')
				->join('asps', 'invoices.asp_id', '=', 'asps.id')
				->join('activities', 'invoices.id', '=', 'activities.invoice_id')
				->groupBy('invoices.id')
				->get();

			DB::commit();
			return response()->json(['success' => true, 'invoices' => $invoices], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}

	public function getDetails(Request $request) {
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'asp_code' => 'required|string|exists:asps,asp_code',
				'invoice_no' => 'required|string|exists:Invoices,invoice_no',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->all()], $this->successStatus);
			}

			//GET ASP
			$asp = Asp::where('asp_code', $request->asp_code)->first();

			//GET INVOICE DETAIL
			$invoices = Invoices::with('asp')
				->where('invoice_no', $request->invoice_no)
				->where('asp_id', $asp->id)
				->where('invoices.flow_current_status', 'Waiting for Batch Generation')
				->first();

			DB::commit();
			return response()->json(['success' => true, 'invoices' => $invoices], $this->successStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage() . ' Line:' . $e->getLine()]], $this->successStatus);
		}
	}
}
