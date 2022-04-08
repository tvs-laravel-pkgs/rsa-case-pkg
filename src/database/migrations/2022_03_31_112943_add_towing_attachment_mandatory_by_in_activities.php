<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTowingAttachmentMandatoryByInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedInteger('towing_attachments_mandatory_by_id')->nullable()->after('is_towing_attachments_mandatory');

			$table->foreign('towing_attachments_mandatory_by_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropForeign('activities_towing_attachments_mandatory_by_id_foreign');
			$table->dropColumn('towing_attachments_mandatory_by_id');
		});
	}
}
