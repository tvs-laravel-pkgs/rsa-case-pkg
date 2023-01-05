<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddL3ChangedServiceTypeIdInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedInteger('l3_changed_service_type_id')->nullable()->after('l2_changed_service_type_id');

			$table->foreign('l3_changed_service_type_id')->references('id')->on('service_types')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropForeign('activities_l3_changed_service_type_id_foreign');

			$table->dropColumn('l3_changed_service_type_id');
		});
	}
}
