<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\ActivityFinanceStatus;
use Illuminate\Database\Seeder;

class ActivityFinanceStatusTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$statuses = [
			1 => [
				'company_id' => 1,
				'name' => 'Matured',
				'po_eligibility_type_id' => 340, //Normal Payout
			],
			2 => [
				'company_id' => 1,
				'name' => 'Matured - Empty Return',
				'po_eligibility_type_id' => 341, //Empty Return Payout
			],
			3 => [
				'company_id' => 1,
				'name' => 'Not Matured',
				'po_eligibility_type_id' => 342, //No Payout
			],
		];

		foreach ($statuses as $id => $status_val) {
			$asp_status = ActivityFinanceStatus::firstOrNew([
				'id' => $id,
			]);
			$asp_status->fill($status_val);
			$asp_status->save();
		}
	}
}
