<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CasesPoC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('cases', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->string('number', 32);
			$table->unique(["company_id", "number"]);
			$table->datetime('date');
			$table->datetime('data_filled_date');
			$table->string('description', 255)->nullable();
			$table->unsignedInteger('status_id')->nullable();
			$table->foreign('status_id')->references('id')->on('case_statuses')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('cancel_reason_id')->nullable();
			$table->foreign('cancel_reason_id')->references('id')->on('case_cancelled_reasons')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('call_center_id');
			$table->foreign('call_center_id')->references('id')->on('call_centers')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('client_id');
			$table->foreign('client_id')->references('id')->on('clients')->onDelete('CASCADE')->onUpdate('cascade');
			$table->string('customer_name', 255);
			$table->string('customer_contact_number', 15);
			$table->string('contact_name', 50)->nullable();
			$table->string('contact_number', 15)->nullable();
			$table->unsignedInteger('vehicle_model_id')->nullable();
			$table->foreign('vehicle_model_id')->references('id')->on('vehicle_models')->onDelete('SET NULL')->onUpdate('cascade');
			$table->string('vehicle_registration_number', 24);
			$table->string('vin_no', 24)->nullable();
			$table->unsignedInteger('membership_type_id')->nullable();
			$table->foreign('membership_type_id')->references('id')->on('membership_types')->onDelete('SET NULL')->onUpdate('cascade');

			$table->unsignedInteger('subject_id')->nullable();
			$table->foreign('subject_id')->references('id')->on('subjects')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedDecimal('km_during_breakdown', 11, 2)->nullable();
			$table->unsignedDecimal('bd_lat', 11, 2)->nullable();
			$table->unsignedDecimal('bd_long', 11, 2)->nullable();
			$table->string('bd_location', 191)->nullable();
			$table->unsignedInteger('bd_city_id')->nullable();
			$table->foreign('bd_city_id')->references('id')->on('districts')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('created_by_id')->nullable();
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('cases');
	}
}
