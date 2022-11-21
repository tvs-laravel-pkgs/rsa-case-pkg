<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotEligibleByColumnsInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->unsignedInteger('not_eligible_moved_by_id')->nullable()->after('towing_attachments_mandatory_by_id');
			$table->dateTime('not_eligible_moved_at')->nullable()->after('not_eligible_moved_by_id');
			$table->text('not_eligible_reason')->nullable()->after('not_eligible_moved_at');

			$table->foreign('not_eligible_moved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropForeign('activities_not_eligible_moved_by_id_foreign');

			$table->dropColumn('not_eligible_moved_by_id');
			$table->dropColumn('not_eligible_moved_at');
			$table->dropColumn('not_eligible_reason');
		});
	}
}
