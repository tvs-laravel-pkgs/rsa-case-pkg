<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationBetweenCcClarifiedAndL1DeferredInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->string("duration_between_cc_clarified_and_l1_deffered", 20)->nullable()->after('cc_clarified_by_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropColumn('duration_between_cc_clarified_and_l1_deffered');
		});
	}
}
