<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesU4 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->string('bd_state', 255)->nullable()->after('bd_city');
			$table->unsignedInteger('bd_location_type_id')->nullable()->after('bd_state');
			$table->unsignedInteger('bd_location_category_id')->nullable()->after('bd_location_type_id');

			$table->foreign('bd_location_type_id')->references('id')->on('configs')->onDelete('set null')->onUpdate('cascade');
			$table->foreign('bd_location_category_id')->references('id')->on('configs')->onDelete('set null')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->dropColumn('bd_state');
			$table->dropForeign('cases_bd_location_type_id_foreign');
			$table->dropForeign('cases_bd_location_category_id_foreign');
			$table->dropColumn('bd_location_type_id');
			$table->dropColumn('bd_location_category_id');
		});
	}
}
