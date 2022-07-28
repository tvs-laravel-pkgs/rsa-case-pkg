<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddL1L2ChangedServiceTypeIdInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedInteger('l1_changed_service_type_id')->nullable()->after('service_type_changed_on_level');
			$table->unsignedInteger('l2_changed_service_type_id')->nullable()->after('l1_changed_service_type_id');

			$table->foreign('l1_changed_service_type_id')->references('id')->on('service_types')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('l2_changed_service_type_id')->references('id')->on('service_types')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropForeign('activities_l1_changed_service_type_id_foreign');
			$table->dropForeign('activities_l2_changed_service_type_id_foreign');

			$table->dropColumn('l1_changed_service_type_id');
			$table->dropColumn('l2_changed_service_type_id');
		});
	}
}
