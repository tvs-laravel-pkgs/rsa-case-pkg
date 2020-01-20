<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesU2 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->string('bd_lat', 255)->nullable()->change();
			$table->string('bd_long', 255)->nullable()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			// $table->unsignedDecimal('bd_lat', 11, 2)->change();
			// $table->unsignedDecimal('bd_long', 11, 2)->change();
		});
	}
}
