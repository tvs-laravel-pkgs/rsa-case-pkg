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
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'membership_no' => [
					'required:true',
					Rule::exists('memberships', 'order_number'),
				],
				'expiry_reason' => [
					'required:true',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
			Membership::where('order_number', $request->membership_no)->update(['expiry_reason' => $request->expiry_reason]);
			return response()->json([
				'success' => true,
				'message' => 'Updated successfully',
			], $this->successStatus);
			DB::commit();
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

}
