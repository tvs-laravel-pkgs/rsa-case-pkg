<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\ActivityPortalStatus;
use Illuminate\Database\Seeder;

class ActivityPortalStatusTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		// 1,	13|4,	14,
		// 2, 	5,	11|7,	13,	14,
		// 4,	5,	11,	13,	14
		// 5,	7,	5
		// 6,	11|7,
		$activity_portal_statuses = [
			1 => [
				'name' => 'Case Closed - Waiting for ASP to Generate Invoice',
				'company_id' => 1,
			],
			2 => [
				'name' => 'ASP Rejected CC Details - Waiting for ASP Data Entry',
				'company_id' => 1,
			],
			4 => [
				'name' => 'ASP Rejected Invoice Amount - Waiting for ASP Data Entry',
				'company_id' => 1,
			],
			5 => [
				'name' => 'ASP Completed Data Entry - Waiting for BO Bulk Verification',
				'company_id' => 1,
			],
			6 => [
				'name' => 'ASP Completed Data Entry - Waiting for BO Individual Verification',
				'company_id' => 1,
			],
			7 => [
				'name' => 'BO Rejected - Waiting for ASP Data Re-Entry',
				'company_id' => 1,
			],
			8 => [
				'name' => 'ASP Data Re-Entry Completed - Waiting for BO Bulk Verification',
				'company_id' => 1,
			],
			9 => [
				'name' => 'ASP Data Re-Entry Completed - Waiting for BO Individual Verification',
				'company_id' => 1,
			],
			10 => [
				'name' => 'Invoice Amount Calculated - Waiting for Case Closure',
				'company_id' => 1,
			],
			11 => [
				'name' => 'BO Approved - Waiting for Invoice Generation by ASP',
				'company_id' => 1,
			],
			12 => [
				'name' => 'Invoiced - Waiting for Payment',
				'company_id' => 1,
			],
			13 => [
				'name' => 'Payment Inprogress',
				'company_id' => 1,
			],
			14 => [
				'name' => 'Paid',
				'company_id' => 1,
			],
			15 => [
				'name' => 'Not Eligible for Payout',
				'company_id' => 1,
			],
			16 => [
				'name' => 'Own Patrol Activity - Not Eligible for Payout',
				'company_id' => 1,
			],

		];

		foreach ($activity_portal_statuses as $id => $activity_portal_status_val) {
			$activity_portal_status = ActivityPortalStatus::firstOrNew([
				'id' => $id,
			]);
			$activity_portal_status->fill($activity_portal_status_val);
			$activity_portal_status->created_by_id = 72;
			$activity_portal_status->updated_by_id = 72;
			$activity_portal_status->save();
		}
	}
}
