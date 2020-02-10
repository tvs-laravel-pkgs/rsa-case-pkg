<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\InvoiceStatus;
use Illuminate\Database\Seeder;

class InvoiceStatusTableSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$invoices_statuses = [
			1 => [
				'name' => 'Payment Pending',
				'company_id' => 1,
			],
			2 => [
				'name' => 'Paid',
				'company_id' => 1,
			],
			3 => [
				'name' => 'Payment Inprogress',
				'company_id' => 1,
			],
		];

		foreach ($invoices_statuses as $key => $invoices_status_val) {
			$invoices_status = InvoiceStatus::firstOrNew([
				'company_id' => $invoices_status_val['company_id'],
				'name' => $invoices_status_val['name'],
			]);
			$invoices_status->fill($invoices_status_val);
			$invoices_status->created_by_id = 72;
			$invoices_status->updated_by_id = 72;
			$invoices_status->save();
		}
	}
}
