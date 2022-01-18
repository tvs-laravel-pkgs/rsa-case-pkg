<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserLogInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->unsignedInteger('imported_by_id')->nullable()->after('imported_at');
			$table->unsignedInteger('asp_data_filled_by_id')->nullable()->after('asp_data_filled_at');
			$table->unsignedInteger('bo_deffered_by_id')->nullable()->after('bo_deffered_at');
			$table->unsignedInteger('bo_approved_by_id')->nullable()->after('bo_approved_at');
			$table->unsignedInteger('invoice_generated_by_id')->nullable()->after('invoice_generated_at');
			$table->unsignedInteger('axapta_generated_by_id')->nullable()->after('axapta_generated_at');

			$table->foreign('imported_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('asp_data_filled_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('bo_deffered_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('bo_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('invoice_generated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('axapta_generated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropForeign('activity_logs_imported_by_id_foreign');
			$table->dropForeign('activity_logs_asp_data_filled_by_id_foreign');
			$table->dropForeign('activity_logs_bo_deffered_by_id_foreign');
			$table->dropForeign('activity_logs_bo_approved_by_id_foreign');
			$table->dropForeign('activity_logs_invoice_generated_by_id_foreign');
			$table->dropForeign('activity_logs_axapta_generated_by_id_foreign');

			$table->dropColumn('imported_by_id');
			$table->dropColumn('asp_data_filled_by_id');
			$table->dropColumn('bo_deffered_by_id');
			$table->dropColumn('bo_approved_by_id');
			$table->dropColumn('invoice_generated_by_id');
			$table->dropColumn('axapta_generated_by_id');
		});
	}
}
