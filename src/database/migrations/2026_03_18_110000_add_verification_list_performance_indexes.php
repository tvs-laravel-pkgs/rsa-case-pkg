<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificationListPerformanceIndexes extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		// Composite index for WHERE status_id IN (...) AND deleted_at IS NULL
		// Used by both getBulkVerificationList and getIndividualVerificationList count + data queries
		Schema::table('activities', function (Blueprint $table) {
			$table->index(
				['status_id', 'deleted_at'],
				'activities_status_id_deleted_at_index'
			);
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropIndex('activities_status_id_deleted_at_index');
		});

	}
}
