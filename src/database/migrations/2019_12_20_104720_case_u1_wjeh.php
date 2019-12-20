<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CaseU1Wjeh extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->dropForeign('cases_membership_type_id_foreign');
			$table->dropForeign('cases_bd_city_id_foreign');
		});
		Schema::table('cases', function (Blueprint $table) {
			$table->renameColumn('membership_type_id', 'membership_type');
			$table->renameColumn('bd_city_id', 'bd_city');
		});
		Schema::table('cases', function (Blueprint $table) {
			$table->string('membership_type')->change();
			$table->string('bd_city')->nullable()->change();
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->renameColumn('membership_type', 'membership_type_id');
			$table->renameColumn('bd_city', 'bd_city_id');

		});
	}
}
