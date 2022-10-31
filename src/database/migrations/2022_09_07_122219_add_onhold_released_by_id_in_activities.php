<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnholdReleasedByIdInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedInteger('onhold_released_by_id')->nullable()->after('towing_attachments_mandatory_by_id');
			$table->dateTime('onhold_released_at')->nullable()->after('onhold_released_by_id');

			$table->foreign('onhold_released_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropForeign('activities_onhold_released_by_id_foreign');

			$table->dropColumn('onhold_released_by_id');
			$table->dropColumn('onhold_released_at');
		});
	}
}
