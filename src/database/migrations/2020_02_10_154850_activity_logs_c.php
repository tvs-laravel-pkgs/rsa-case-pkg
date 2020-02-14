<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityLogsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('activity_logs', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('activity_id');
			$table->datetime('imported_at')->nullable();
			$table->datetime('asp_data_filled_at')->nullable();
			$table->datetime('bo_deffered_at')->nullable();
			$table->datetime('bo_approved_at')->nullable();
			$table->datetime('invoice_generated_at')->nullable();
			$table->datetime('axapta_generated_at')->nullable();
			$table->datetime('payment_completed_at')->nullable();
			$table->unsignedInteger('created_by_id')->nullable();
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('activity_id')->references('id')->on('activities')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');

			$table->unique(["activity_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_logs');
	}
}
