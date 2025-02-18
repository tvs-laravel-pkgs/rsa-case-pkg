<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexInCaseAndActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('cases', function (Blueprint $table) {
			$table->index('customer_contact_number');
			$table->index('vin_no');
			$table->index('csr');
		});

		Schema::table('activities', function (Blueprint $table) {
			$table->index('crm_activity_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('cases', function (Blueprint $table) {
			$table->dropIndex('cases_customer_contact_number_index');
			$table->dropIndex('cases_vin_no_index');
			$table->dropIndex('cases_csr_index');
		});

		Schema::table('activities', function (Blueprint $table) {
			$table->dropIndex('activities_crm_activity_id_index');
		});
	}
}
