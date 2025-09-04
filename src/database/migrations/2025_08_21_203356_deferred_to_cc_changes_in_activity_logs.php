<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeferredToCcChangesInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		DB::statement('ALTER TABLE `activity_logs` CHANGE `bo_deffered_cc_l2_user_escalated_at` `deferred_to_cc_l2_user_escalated_at` DATETIME NULL DEFAULT NULL;');
		DB::statement('ALTER TABLE `activity_logs` CHANGE `duration_between_cc_clarified_and_l1_deffered` `duration_between_cc_clarified_and_deferred_to_cc` VARCHAR(20) NULL DEFAULT NULL;');

		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dateTime('deferred_to_cc_at')->nullable()->after('bo_deffered_by_id');
			$table->unsignedInteger('deferred_to_cc_by_id')->nullable()->after('deferred_to_cc_at');

			$table->foreign("deferred_to_cc_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		DB::statement('ALTER TABLE `activity_logs` CHANGE `deferred_to_cc_l2_user_escalated_at` `bo_deffered_cc_l2_user_escalated_at` DATETIME NULL DEFAULT NULL;');
		DB::statement('ALTER TABLE `activity_logs` CHANGE `duration_between_cc_clarified_and_deferred_to_cc` `duration_between_cc_clarified_and_l1_deffered` VARCHAR(20) NULL DEFAULT NULL;');

		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropForeign('activity_logs_deferred_to_cc_by_id_foreign');

			$table->dropColumn('deferred_to_cc_at');
			$table->dropColumn('deferred_to_cc_by_id');
		});
	}
}
