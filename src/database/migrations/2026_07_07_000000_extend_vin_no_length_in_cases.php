<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendVinNoLengthInCases extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->string('vin_no', 25)->nullable()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->string('vin_no', 24)->nullable()->change();
		});
	}
}
