<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\AspStatus;
use Illuminate\Database\Seeder;

class AspStatusTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$asp_statuses = [
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
				'name' => 'Successful',
				'company_id' => 1,
			],
		];

		foreach ($asp_statuses as $key => $asp_status_val) {
			$asp_status = AspStatus::firstOrNew([
				'company_id' => $asp_status_val['company_id'],
				'name' => $asp_status_val['name'],
			]);
			$asp_status->company_id = $asp_status_val['company_id'];
			$asp_status->name = $asp_status_val['name'];
			$asp_status->created_by_id = 72;
			$asp_status->updated_by_id = 72;
			$asp_status->save();
		}
	}
}
