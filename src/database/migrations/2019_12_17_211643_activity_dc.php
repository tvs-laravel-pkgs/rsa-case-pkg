<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityDc extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::dropIfExists('activity_details');
		Schema::dropIfExists('activities');
		if (!Schema::hasTable('activity_finance_statuses')) {
			Schema::create('activity_finance_statuses', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->string('name', 191);
				$table->unsignedInteger('po_eligibility_type_id');
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');

				$table->foreign('po_eligibility_type_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

				$table->unique(["company_id", "name"]);
			});
		}
		Schema::create('activities', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('crm_activity_id');
			$table->string('number', 32)->nullable();
			$table->unsignedInteger('data_src_id')->nullable();
			$table->unsignedInteger('asp_id');
			$table->unsignedInteger('case_id');
			$table->unsignedInteger('service_type_id');
			$table->unsignedInteger('status_id')->nullable();
			$table->boolean('asp_accepted_cc_details');
			$table->string('reason_for_asp_rejected_cc_details', 255)->nullable();
			$table->unsignedInteger('finance_status_id');
			$table->boolean('asp_po_accepted')->nullable();
			$table->string('asp_po_rejected_reason', 255)->nullable();
			$table->unsignedInteger('asp_activity_status_id')->nullable();
			$table->unsignedInteger('asp_activity_rejected_reason_id')->nullable();
			$table->unsignedInteger('invoice_id')->nullable();
			$table->unsignedInteger('activity_status_id')->nullable();
			$table->string('description', 191)->nullable();
			$table->string('remarks', 255)->nullable();
			$table->string('deduction_reason', 191)->nullable();
			$table->string('bo_comments', 191)->nullable();
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('finance_status_id')->references('id')->on('activity_finance_statuses')->onDelete('RESTRICT')->onUpdate('cascade');
			$table->foreign('data_src_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('asp_id')->references('id')->on('asps')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('case_id')->references('id')->on('cases')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('activity_portal_statuses')->onDelete('SET NULL')->onUpdate('cascade');

			$table->foreign('asp_activity_status_id')->references('id')->on('activity_asp_statuses')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('asp_activity_rejected_reason_id')->references('id')->on('asp_activity_rejected_reasons')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('activity_status_id')->references('id')->on('activity_statuses')->onDelete('SET NULL')->onUpdate('cascade');

			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

			$table->unique(["crm_activity_id"]);
			$table->unique(["number"]);
		});

		Schema::create('activity_details', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->unsignedInteger('activity_id');
			$table->unsignedInteger('key_id')->nullable();
			$table->string('value', 191)->nullable();
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('activity_id')->references('id')->on('activities')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('key_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');

			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_details');
		Schema::dropIfExists('activities');
	}
}
