<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApiLogsU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('api_logs', function (Blueprint $table) {
			$table->string('entity_number', 255)->nullable()->after('type_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('api_logs', function (Blueprint $table) {
			$table->dropColumn('entity_number');
		});
	}
}
