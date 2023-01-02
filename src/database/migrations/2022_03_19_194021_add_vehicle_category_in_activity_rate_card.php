<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehicleCategoryInActivityRateCard extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_ratecards', function (Blueprint $table) {
			$table->unsignedInteger('vehicle_category_id')->nullable()->after('activity_id');

			$table->foreign('vehicle_category_id')->references('id')->on('vehicle_categories')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_ratecards', function (Blueprint $table) {
			$table->dropForeign('activity_ratecards_vehicle_category_id_foreign');
			$table->dropColumn('vehicle_category_id');
		});
	}
}
