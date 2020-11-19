<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesU5 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->datetime('submission_closing_date')->nullable()->after('bd_location_category_id');
			$table->text('submission_closing_date_remarks')->nullable()->after('submission_closing_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->dropColumn('submission_closing_date');
			$table->dropColumn('submission_closing_date_remarks');
		});
	}
}
