<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCcClarifiedInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dateTime('cc_clarified_at')->nullable()->after('bo_deffered_cc_l2_user_escalated_at');
			$table->unsignedInteger('cc_clarified_by_id')->nullable()->after('cc_clarified_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropForeign('activity_logs_cc_clarified_by_id_foreign');

			$table->dropColumn('cc_clarified_at');
			$table->dropColumn('cc_clarified_by_id');
		});
	}
}
