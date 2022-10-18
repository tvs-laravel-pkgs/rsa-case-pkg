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
				'name' => 'ASP Completed Data Entry - Waiting for L1 Bulk Verification',
				'company_id' => 1,
			],
			6 => [
				'name' => 'ASP Completed Data Entry - Waiting for L1 Individual Verification',
				'company_id' => 1,
			],
			7 => [
				'name' => 'BO Rejected - Waiting for ASP Data Re-Entry',
				'company_id' => 1,
			],
			8 => [
				'name' => 'ASP Data Re-Entry Completed - Waiting for L1 Bulk Verification',
				'company_id' => 1,
			],
			9 => [
				'name' => 'ASP Data Re-Entry Completed - Waiting for L1 Individual Verification',
				'company_id' => 1,
			],
			10 => [
				'name' => 'Invoice Amount Calculated - Waiting for Case Closure',
				'company_id' => 1,
			],
			11 => [
				'name' => 'Waiting for Invoice Generation by ASP',
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
			17 => [
				'name' => 'On Hold',
				'company_id' => 1,
			],
			18 => [
				'name' => 'Waiting for L2 Bulk Verification',
				'company_id' => 1,
			],
			19 => [
				'name' => 'Waiting for L2 Individual Verification',
				'company_id' => 1,
			],
			20 => [
				'name' => 'Waiting for L3 Bulk Verification',
				'company_id' => 1,
			],
			21 => [
				'name' => 'Waiting for L3 Individual Verification',
				'company_id' => 1,
			],
			22 => [
				'name' => 'Rejected - Waiting for L1 Individual Verification',
				'company_id' => 1,
			],
			23 => [
				'name' => 'Waiting for L4 Bulk Verification',
				'company_id' => 1,
			],
			24 => [
				'name' => 'Waiting for L4 Individual Verification',
				'company_id' => 1,
			],
			25 => [
				'name' => 'Waiting for Charges Acceptance by ASP',
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
