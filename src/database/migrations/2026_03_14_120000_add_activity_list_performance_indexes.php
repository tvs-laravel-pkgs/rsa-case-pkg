<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivityListPerformanceIndexes extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->index('deleted_at', 'activities_deleted_at_index');
			$table->index(['deleted_at', 'case_id'], 'activities_deleted_at_case_id_index');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropIndex('activities_deleted_at_index');
			$table->dropIndex('activities_deleted_at_case_id_index');
		});
	}
}
