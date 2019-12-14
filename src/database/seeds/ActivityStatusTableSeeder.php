<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\ActivityStatus;
use Illuminate\Database\Seeder;

class ActivityStatusTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$activity_statuses = [
			1 => [
				'name' => 'Open',
				'company_id' => 1,
			],
			2 => [
				'name' => 'Assigned',
				'company_id' => 1,
			],
			3 => [
				'name' => 'In Progress',
				'company_id' => 1,
			],
			4 => [
				'name' => 'Cancelled',
				'company_id' => 1,
			],
			5 => [
				'name' => 'Failure',
				'company_id' => 1,
			],
			6 => [
				'name' => 'Re-Assigned',
				'company_id' => 1,
			],
			7 => [
				'name' => 'Successful',
				'company_id' => 1,
			],
		];

		foreach ($activity_statuses as $key => $activity_status_val) {
			$activity_status = ActivityStatus::firstOrNew([
				'company_id' => $activity_status_val['company_id'],
				'name' => $activity_status_val['name'],
			]);
			$activity_status->fill($activity_status_val);
			$activity_status->created_by_id = 72;
			$activity_status->updated_by_id = 72;
			$activity_status->save();
		}
	}
}
