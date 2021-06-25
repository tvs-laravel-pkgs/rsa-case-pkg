<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityLogsU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->index('imported_at');
			$table->index('asp_data_filled_at');
			$table->index('bo_deffered_at');
			$table->index('bo_approved_at');
			$table->index('invoice_generated_at');
			$table->index('axapta_generated_at');
			$table->index('payment_completed_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropIndex('activity_logs_imported_at_index');
			$table->dropIndex('activity_logs_asp_data_filled_at_index');
			$table->dropIndex('activity_logs_bo_deffered_at_index');
			$table->dropIndex('activity_logs_bo_approved_at_index');
			$table->dropIndex('activity_logs_invoice_generated_at_index');
			$table->dropIndex('activity_logs_axapta_generated_at_index');
			$table->dropIndex('activity_logs_payment_completed_at_index');
		});
	}
}
