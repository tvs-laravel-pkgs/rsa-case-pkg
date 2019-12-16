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

			//CHECK ACTIVITY IS ACCEPTED OR NOT
			$activities_with_accepted = Activity::select('crm_activity_id', 'asp_po_accepted')->whereIn('crm_activity_id', $request->activity_id)->get();

			if (!empty($activities_with_accepted)) {
				foreach ($activities_with_accepted as $key => $activity_accepted) {
					if ($activity_accepted->asp_po_accepted != 1) {
						return response()->json([
							'success' => false,
							'message' => 'Validation Error',
							'errors' => 'ASP not accepted for activity ID ' . $activity_accepted->crm_activity_id,
						], $this->successStatus);
					}
				}
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
}