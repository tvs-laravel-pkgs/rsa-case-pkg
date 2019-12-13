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
				'name' => 'Accepted',
				'company_id' => 1,
			],
			2 => [
				'name' => 'Started To BD',
				'company_id' => 1,
			],
			3 => [
				'name' => 'Reached BD',
				'company_id' => 1,
			],
			4 => [
				'name' => 'Reached Garage',
				'company_id' => 1,
			],
		];

		foreach ($activity_statuses as $key => $activity_status_val) {
			$activity_status = ActivityStatus::firstOrNew([
				'company_id' => $activity_status_val['company_id'],
				'name' => $activity_status_val['name'],
			]);
			$activity_status->company_id = $activity_status_val['company_id'];
			$activity_status->name = $activity_status_val['name'];
			$activity_status->created_by_id = 72;
			$activity_status->updated_by_id = 72;
			$activity_status->save();
		}
	}
}
