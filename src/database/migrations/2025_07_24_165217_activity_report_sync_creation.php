<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityReportSyncCreation extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('activity_report_sync')) {
			Schema::create('activity_report_sync', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('activity_id');
				$table->unsignedTinyInteger('sync_status')->default(0)->comment('0-Not Synced,1-Sync Running');
				$table->text('errors')->nullable();
				$table->timestamps();

				$table->foreign("activity_id")->references("id")->on("activities")->onDelete("cascade")->onUpdate("cascade");
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_report_sync');
	}
}
