<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivitiesPoC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('activities', function (Blueprint $table) {
			$table->increments('id');
			$table->string('number', 32);
			$table->unique(["number"]);
			$table->unsignedInteger('asp_id');
			$table->foreign('asp_id')->references('id')->on('asps')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('case_id');
			$table->foreign('case_id')->references('id')->on('cases')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('service_type_id');
			$table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('asp_status_id')->nullable();
			$table->foreign('asp_status_id')->references('id')->on('activity_asp_statuses')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('asp_activity_rejected_reason_id')->nullable();
			$table->foreign('asp_activity_rejected_reason_id')->references('id')->on('asp_activity_rejected_reasons')->onDelete('SET NULL')->onUpdate('cascade');
			$table->boolean('asp_po_accepted')->nullable();
			$table->unsignedInteger('asp_po_rejected_reason_id')->nullable();
			$table->foreign('asp_po_rejected_reason_id')->references('id')->on('asp_po_rejected_reasons')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('invoice_id')->nullable();
			$table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('status_id')->nullable();
			$table->foreign('status_id')->references('id')->on('activity_portal_statuses')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('activity_status_id')->nullable();
			$table->foreign('activity_status_id')->references('id')->on('activity_statuses')->onDelete('SET NULL')->onUpdate('cascade');
			$table->string('service_description', 191)->nullable();
			$table->unsignedDecimal('amount', 11, 2)->nullable();
			$table->string('remarks', 255)->nullable();
			$table->unsignedInteger('drop_location_type_id')->nullable();
			$table->foreign('drop_location_type_id')->references('id')->on('entities')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('drop_dealer_id')->nullable();
			$table->foreign('drop_dealer_id')->references('id')->on('dealers')->onDelete('SET NULL')->onUpdate('cascade');
			$table->string('drop_location', 512)->nullable();
			$table->unsignedDecimal('drop_location_lat', 11, 2)->nullable();
			$table->unsignedDecimal('drop_location_long', 11, 2)->nullable();
			$table->unsignedDecimal('excess_km', 11, 2)->nullable();
			$table->unsignedInteger('crm_activity_id');
			$table->unique(["crm_activity_id"]);
			$table->datetime('asp_reached_date')->nullable();
			$table->string('asp_start_location', 255)->nullable();
			$table->string('asp_end_location', 255)->nullable();
			$table->unsignedDecimal('asp_bd_google_km', 11, 2)->nullable();
			$table->unsignedDecimal('bd_dealer_google_km', 11, 2)->nullable();
			$table->unsignedDecimal('return_google_km', 11, 2)->nullable();
			$table->unsignedDecimal('asp_bd_return_empty_km', 11, 2)->nullable();
			$table->unsignedDecimal('bd_dealer_km', 11, 2)->nullable();
			$table->unsignedDecimal('return_km', 11, 2)->nullable();
			$table->unsignedDecimal('total_travel_google_km', 11, 2)->nullable();
			$table->unsignedInteger('paid_to_id')->nullable();
			$table->foreign('paid_to_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('payment_mode_id')->nullable();
			$table->foreign('payment_mode_id')->references('id')->on('entities')->onDelete('SET NULL')->onUpdate('cascade');
			$table->string('payment_receipt_no', 50)->nullable();
			$table->string('deduction_reason', 191)->nullable();
			$table->string('bo_comments', 191)->nullable();
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
		Schema::dropIfExists('activities');
	}
}
