<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\CaseCancelledReason;
use Illuminate\Database\Seeder;

class CaseCancelledReasonTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$case_cancelled_reasons = [
			1 => [
				'name' => 'ASP DELAY',
				'company_id' => 1,
			],
			2 => [
				'name' => 'AUTOMATICALLY STARTED',
				'company_id' => 1,
			],
			3 => [
				'name' => 'CHARGES RELATED',
				'company_id' => 1,
			],
			4 => [
				'name' => 'CUSTOMER NOT READY TO WAIT',
				'company_id' => 1,
			],
			5 => [
				'name' => 'CUSTOMER NOT RESPONDING',
				'company_id' => 1,
			],
			6 => [
				'name' => 'DEALER SUPPORT',
				'company_id' => 1,
			],
			7 => [
				'name' => 'DRIVABLE CONDITION',
				'company_id' => 1,
			],
			8 => [
				'name' => 'FIR FORMALITIES PENDING',
				'company_id' => 1,
			],
			9 => [
				'name' => 'REFUSED FOR TOWING',
				'company_id' => 1,
			],
		];

		foreach ($case_cancelled_reasons as $key => $case_cancelled_reason_val) {
			$case_cancelled_reason = CaseCancelledReason::firstOrNew([
				'company_id' => $case_cancelled_reason_val['company_id'],
				'name' => $case_cancelled_reason_val['name'],
			]);
			$case_cancelled_reason->company_id = $case_cancelled_reason_val['company_id'];
			$case_cancelled_reason->name = $case_cancelled_reason_val['name'];
			$case_cancelled_reason->created_by_id = 72;
			$case_cancelled_reason->updated_by_id = 72;
			$case_cancelled_reason->save();
		}
	}
}
