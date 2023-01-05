<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAspDataEntryDoneInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->boolean('is_asp_data_entry_done')->nullable()->after('status_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropColumn('is_asp_data_entry_done');
		});
	}
}
