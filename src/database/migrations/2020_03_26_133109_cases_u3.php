<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesU3 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->string('vehicle_registration_number', 24)->nullable()->change();
			$table->text('bd_location')->change();
			$table->string('bd_city', 255)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->string('vehicle_registration_number', 24)->nullable(false)->change();
			$table->string('bd_location', 191)->change();
			$table->string('bd_city', 191)->change();
		});
	}
}
