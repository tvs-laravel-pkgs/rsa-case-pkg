<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCcL2UserEscalatedAtInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dateTime('bo_deffered_cc_l2_user_escalated_at')->nullable()->after('bo_deffered_by_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropColumn('bo_deffered_cc_l2_user_escalated_at');
		});
	}
}
