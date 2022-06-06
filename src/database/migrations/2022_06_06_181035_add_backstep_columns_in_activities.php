<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackstepColumnsInActivities extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->text('backstep_reason')->nullable()->after('defer_reason');
			$table->unsignedInteger('backstep_by_id')->nullable()->after('backstep_reason');

			$table->foreign('backstep_by_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropForeign('activities_backstep_by_id_foreign');
			$table->dropColumn('backstep_by_id');
			$table->dropColumn('backstep_reason');
		});
	}
}
