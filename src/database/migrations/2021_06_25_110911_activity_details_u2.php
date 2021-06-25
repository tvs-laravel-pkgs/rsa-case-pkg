<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityDetailsU2 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_details', function (Blueprint $table) {
			$table->index(['activity_id', 'key_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_details', function (Blueprint $table) {
			$table->dropIndex('activity_details_activity_id_key_id_index');
		});
	}
}
