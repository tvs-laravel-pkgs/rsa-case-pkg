<?php

use Illuminate\Database\Migrations\Migration;

class ChangeTimestampsToDatetimeInActivityReports extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		DB::statement('ALTER TABLE `activity_reports` MODIFY `created_at` DATETIME NULL;');
		DB::statement('ALTER TABLE `activity_reports` MODIFY `updated_at` DATETIME NULL;');
		DB::statement('ALTER TABLE `activity_reports` MODIFY `deleted_at` DATETIME NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		DB::statement('ALTER TABLE `activity_reports` MODIFY `created_at` TIMESTAMP NULL;');
		DB::statement('ALTER TABLE `activity_reports` MODIFY `updated_at` TIMESTAMP NULL;');
		DB::statement('ALTER TABLE `activity_reports` MODIFY `deleted_at` TIMESTAMP NULL;');
	}
}
