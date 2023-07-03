<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddElkSyncFlagInActivityReports extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_reports', function (Blueprint $table) {
			$table->unsignedTinyInteger('elk_sync_flag')->nullable()->after('elk_synched_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_reports', function (Blueprint $table) {
			$table->dropColumn('elk_sync_flag');
		});
	}
}
