<?php

namespace Abs\RsaCasePkg\Api;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;

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

}
