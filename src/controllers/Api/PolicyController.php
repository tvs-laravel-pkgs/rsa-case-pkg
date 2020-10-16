<?php

namespace Abs\RsaCasePkg\Api;
use App\Http\Controllers\Controller;
use App\Membership;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class PolicyController extends Controller {
	private $successStatus = 200;

	public function save(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		dd($request->all());
		DB::beginTransaction();
		try {

		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . ' Line:' . $e->getLine(),
				],
			], $this->successStatus);
		}
	}

	public function updatePolicyEntitlement(Request $request) {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		$errors = [];
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'membership_number' => [
					'required:true',
					Rule::exists('memberships', 'order_number'),
				],
				'expiry_reason' => [
					'required:true',
				],
			]);
			if ($validator->fails()) {
				//SAVE CASE API LOG
				$errors = $validator->errors()->all();
				saveApiLog(109, $request->membership_number, $request->all(), $errors, NULL, 121);
				DB::commit();

				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			Membership::where('order_number', $request->membership_number)->update([
				'expiry_reason' => $request->expiry_reason,
				'expiry_date' => date('Y-m-d'),
			]);

			//SAVE CASE API LOG
			saveApiLog(109, $request->membership_number, $request->all(), $errors, NULL, 120);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Policy entitlement updated successfully',
			], $this->successStatus);

		} catch (\Exception $e) {
			DB::rollBack();
			//SAVE CASE API LOG
			$errors[] = $e->getMessage() . ' Line:' . $e->getLine();
			saveApiLog(109, $request->membership_number, $request->all(), $errors, NULL, 121);

			return response()->json([
				'success' => false,
				'error' => 'Exception Error',
				'errors' => [
					$e->getMessage() . ' Line:' . $e->getLine(),
				],
			], $this->successStatus);
		}
	}

}
