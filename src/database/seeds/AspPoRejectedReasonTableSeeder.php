<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\AspPoRejectedReason;
use Illuminate\Database\Seeder;

class AspPoRejectedReasonTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$asp_po_rejected_reasons = [
			1 => [
				'name' => 'Invalid Payment Amount',
				'company_id' => 1,
			],
			2 => [
				'name' => 'KM is Wrong',
				'company_id' => 1,
			],
		];

		foreach ($asp_po_rejected_reasons as $key => $asp_po_rejected_reason_val) {
			$asp_po_rejected_reason = AspPoRejectedReason::firstOrNew([
				'company_id' => $asp_po_rejected_reason_val['company_id'],
				'name' => $asp_po_rejected_reason_val['name'],
			]);
			$asp_po_rejected_reason->fill($asp_po_rejected_reason_val);
			$asp_po_rejected_reason->created_by_id = 72;
			$asp_po_rejected_reason->updated_by_id = 72;
			$asp_po_rejected_reason->save();
		}
	}
}
