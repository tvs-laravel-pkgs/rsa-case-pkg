<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddL2L3ApprovalByInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->datetime('l2_approved_at')->nullable()->after('bo_approved_by_id');
			$table->unsignedInteger('l2_approved_by_id')->nullable()->after('l2_approved_at');
			$table->datetime('l3_approved_at')->nullable()->after('l2_approved_by_id');
			$table->unsignedInteger('l3_approved_by_id')->nullable()->after('l3_approved_at');

			$table->foreign('l2_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('l3_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropForeign('activity_logs_l2_approved_by_id_foreign');
			$table->dropForeign('activity_logs_l3_approved_by_id_foreign');

			$table->dropColumn('l2_approved_at');
			$table->dropColumn('l2_approved_by_id');
			$table->dropColumn('l3_approved_at');
			$table->dropColumn('l3_approved_by_id');
		});
	}
}
