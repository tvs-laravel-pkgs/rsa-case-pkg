<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\PolicyType;
use Illuminate\Database\Seeder;

class PolicyTypeTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$policy_types = [
			1 => [
				'name' => 'Extended Warranty',
				'company_id' => 1,
			],
			2 => [
				'name' => 'RSA Policy',
				'company_id' => 1,
			],
			3 => [
				'name' => 'Warranty',
				'company_id' => 1,
			],
		];

		foreach ($policy_types as $key => $policy_type_val) {
			$policy_type = PolicyType::firstOrNew([
				'company_id' => $policy_type_val['company_id'],
				'name' => $policy_type_val['name'],
			]);
			$policy_type->company_id = $policy_type_val['company_id'];
			$policy_type->name = $policy_type_val['name'];
			$policy_type->created_by_id = 72;
			$policy_type->updated_by_id = 72;
			$policy_type->save();
		}
	}
}
