<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesU6 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->index('date');
			$table->index('number');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->dropIndex('cases_date_index');
			$table->dropIndex('cases_number_index');
		});
	}
}
