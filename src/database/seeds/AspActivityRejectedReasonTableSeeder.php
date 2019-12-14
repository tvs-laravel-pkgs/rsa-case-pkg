<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\AspActivityRejectedReason;
use Illuminate\Database\Seeder;

class AspActivityRejectedReasonTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$asp_activity_rejected_reasons = [
			1 => [
				'name' => 'Busy in another breakdown',
				'company_id' => 1,
			],
			2 => [
				'name' => "Can't attend in night/only after 9 am",
				'company_id' => 1,
			],
			3 => [
				'name' => 'Festival/Function',
				'company_id' => 1,
			],
			4 => [
				'name' => 'Heavy rain',
				'company_id' => 1,
			],
			5 => [
				'name' => 'Naxalite area',
				'company_id' => 1,
			],
			6 => [
				'name' => 'No Technician/No Driver',
				'company_id' => 1,
			],
			7 => [
				'name' => 'No tie-up with TVS AA',
				'company_id' => 1,
			],
			8 => [
				'name' => 'Payment issue',
				'company_id' => 1,
			],
			9 => [
				'name' => 'Road block',
				'company_id' => 1,
			],
			10 => [
				'name' => 'ROS Denied',
				'company_id' => 1,
			],
			11 => [
				'name' => 'Switch Off/R N R/ Not reachable',
				'company_id' => 1,
			],
			12 => [
				'name' => 'Too long Distance',
				'company_id' => 1,
			],
			13 => [
				'name' => 'Vehicle got breakdown',
				'company_id' => 1,
			],
		];

		foreach ($asp_activity_rejected_reasons as $key => $asp_activity_rejected_reason_val) {
			$asp_activity_rejected_reason = AspActivityRejectedReason::firstOrNew([
				'company_id' => $asp_activity_rejected_reason_val['company_id'],
				'name' => $asp_activity_rejected_reason_val['name'],
			]);
			$asp_activity_rejected_reason->fill($asp_activity_rejected_reason_val);
			$asp_activity_rejected_reason->created_by_id = 72;
			$asp_activity_rejected_reason->updated_by_id = 72;
			$asp_activity_rejected_reason->save();
		}
	}
}
