<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivitiesU3 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->string('crm_activity_id', 255)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedInteger('crm_activity_id')->change();
		});
	}
}
