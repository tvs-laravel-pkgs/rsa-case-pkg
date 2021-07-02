<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesU7 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->index('vehicle_registration_number');
			$table->index('deleted_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->dropIndex('cases_vehicle_registration_number_index');
			$table->dropIndex('cases_deleted_at_index');
		});
	}
}
