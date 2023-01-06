<?php

use Illuminate\Database\Migrations\Migration;

class CasesU8 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		DB::statement('ALTER TABLE `cases` MODIFY `contact_name` VARCHAR(255) NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		DB::statement('ALTER TABLE `cases` MODIFY `contact_name` VARCHAR(50) NULL;');
	}
}
