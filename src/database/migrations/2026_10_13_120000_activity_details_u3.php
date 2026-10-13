<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ActivityDetailsU3 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		//INT UNSIGNED AUTO_INCREMENT EXHAUSTED AT 4294967295, WIDEN PRIMARY KEY TO BIGINT UNSIGNED
		DB::statement('ALTER TABLE `activity_details` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		DB::statement('ALTER TABLE `activity_details` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;');
	}
}
