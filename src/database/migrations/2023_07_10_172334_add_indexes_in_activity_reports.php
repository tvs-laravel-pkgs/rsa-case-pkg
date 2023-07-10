<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesInActivityReports extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_reports', function (Blueprint $table) {
			$table->index('invoice_date');
			$table->index('transaction_date');

			$table->index('imported_date');
			$table->index('asp_data_filled_date');
			$table->index('l1_deffered_date');
			$table->index('l1_approved_date');
			$table->index('l2_deffered_date');
			$table->index('l2_approved_date');
			$table->index('l3_deffered_date');
			$table->index('l3_approved_date');
			$table->index('l4_deffered_date');
			$table->index('l4_approved_date');
			$table->index('invoice_generated_date');
			$table->index('axapta_generated_date');
			$table->index('payment_completed_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_reports', function (Blueprint $table) {
			$table->dropIndex('activity_reports_invoice_date_index');
			$table->dropIndex('activity_reports_transaction_date_index');

			$table->dropIndex('activity_reports_imported_date_index');
			$table->dropIndex('activity_reports_asp_data_filled_date_index');
			$table->dropIndex('activity_reports_l1_deffered_date_index');
			$table->dropIndex('activity_reports_l1_approved_date_index');
			$table->dropIndex('activity_reports_l2_deffered_date_index');
			$table->dropIndex('activity_reports_l2_approved_date_index');
			$table->dropIndex('activity_reports_l3_deffered_date_index');
			$table->dropIndex('activity_reports_l3_approved_date_index');
			$table->dropIndex('activity_reports_l4_deffered_date_index');
			$table->dropIndex('activity_reports_l4_approved_date_index');
			$table->dropIndex('activity_reports_invoice_generated_date_index');
			$table->dropIndex('activity_reports_axapta_generated_date_index');
			$table->dropIndex('activity_reports_payment_completed_date_index');
		});
	}
}
