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
				'name' => 'ASP cancelled his assignment',
				'po_eligibility_type_id' => 342, //No Payout
			],
			2 => [
				'company_id' => 1,
				'name' => 'ASP cancelled while on garage as ticket cancelled',
				'po_eligibility_type_id' => 342, //No Payout
			],
			3 => [
				'company_id' => 1,
				'name' => 'Empty return as Ticket cancelled',
				'po_eligibility_type_id' => 341, //Empty Return Payout
			],
			4 => [
				'company_id' => 1,
				'name' => 'Work Completed',
				'po_eligibility_type_id' => 340, //Normal Payout
			],
			5 => [
				'company_id' => 1,
				'name' => 'Work not completed, vehicle need to be towed',
				'po_eligibility_type_id' => 341, //Empty Return Payout
			],
		];

		foreach ($statuses as $key => $status_val) {
			$asp_status = ActivityFinanceStatus::firstOrNew([
				'company_id' => $status_val['company_id'],
				'name' => $status_val['name'],
			]);
			$asp_status->fill($status_val);
			$asp_status->save();
		}
	}
}
