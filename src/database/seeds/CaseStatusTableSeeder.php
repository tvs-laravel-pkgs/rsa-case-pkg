<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\CaseStatus;
use Illuminate\Database\Seeder;

class CaseStatusTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$case_statuses = [
			1 => [
				'name' => 'Open',
				'company_id' => 1,
			],
			2 => [
				'name' => 'In Progress',
				'company_id' => 1,
			],
			3 => [
				'name' => 'Cancelled',
				'company_id' => 1,
			],
			4 => [
				'name' => 'Closed',
				'company_id' => 1,
			],
			5 => [
				'name' => 'Pre-Close',
				'company_id' => 1,
			],
		];

		foreach ($case_statuses as $key => $case_status_val) {
			$case_status = CaseStatus::firstOrNew([
				'company_id' => $case_status_val['company_id'],
				'name' => $case_status_val['name'],
			]);
			$case_status->fill($case_status_val);
			$case_status->created_by_id = 72;
			$case_status->updated_by_id = 72;
			$case_status->save();
		}
	}
}
