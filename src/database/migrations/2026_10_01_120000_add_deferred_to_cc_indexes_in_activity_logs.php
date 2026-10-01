<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeferredToCcIndexesInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->index('deferred_to_cc_at', 'activity_logs_deferred_to_cc_at_index');
			$table->index('cc_clarified_at', 'activity_logs_cc_clarified_at_index');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropIndex('activity_logs_deferred_to_cc_at_index');
			$table->dropIndex('activity_logs_cc_clarified_at_index');
		});
	}
}
