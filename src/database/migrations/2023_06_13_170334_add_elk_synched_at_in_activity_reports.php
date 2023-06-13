<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddElkSynchedAtInActivityReports extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_reports', function (Blueprint $table) {
			$table->dateTime('elk_synched_at')->nullable()->after('adjustment');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_reports', function (Blueprint $table) {
			$table->dropColumn('elk_synched_at');
		});
	}
}
