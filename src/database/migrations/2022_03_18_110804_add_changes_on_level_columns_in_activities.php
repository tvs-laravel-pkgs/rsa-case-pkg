<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChangesOnLevelColumnsInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedTinyInteger('service_type_changed_on_level')->nullable()->after('reason_for_asp_rejected_cc_details');
			$table->unsignedTinyInteger('km_changed_on_level')->nullable()->after('service_type_changed_on_level');
			$table->unsignedTinyInteger('not_collected_amount_changed_on_level')->nullable()->after('km_changed_on_level');
			$table->unsignedTinyInteger('collected_amount_changed_on_level')->nullable()->after('not_collected_amount_changed_on_level');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropColumn('service_type_changed_on_level');
			$table->dropColumn('km_changed_on_level');
			$table->dropColumn('not_collected_amount_changed_on_level');
			$table->dropColumn('collected_amount_changed_on_level');
		});
	}
}
