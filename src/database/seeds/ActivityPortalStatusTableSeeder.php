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
				'name' => 'Waiting for Work Completion',
				'company_id' => 1,
			],
			2 => [
				'name' => 'Invoice Amount Calculated - Waiting for ASP Invoice Amount Confirmation',
				'company_id' => 1,
			],
			3 => [
				'name' => 'ASP Accepted Invoice Amount - Waiting for Invoice Generation by ASP',
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
				'name' => 'Pending Invoice Generation',
				'company_id' => 1,
			],
			11 => [
				'name' => 'BO Approved - Waiting for Invoice Generation by ASP',
				'company_id' => 1,
			],
			12 => [
				'name' => 'Waiting for Invoice Generation by ASP',
				'company_id' => 1,
			],
			13 => [
				'name' => 'Invoiced - Waiting for Payment',
				'company_id' => 1,
			],
			14 => [
				'name' => 'Paid',
				'company_id' => 1,
			],
		];

		foreach ($activity_portal_statuses as $key => $activity_portal_status_val) {
			$activity_portal_status = ActivityPortalStatus::firstOrNew([
				'company_id' => $activity_portal_status_val['company_id'],
				'name' => $activity_portal_status_val['name'],
			]);
			$activity_portal_status->company_id = $activity_portal_status_val['company_id'];
			$activity_portal_status->name = $activity_portal_status_val['name'];
			$activity_portal_status->created_by_id = 72;
			$activity_portal_status->updated_by_id = 72;
			$activity_portal_status->save();
		}
	}
}
