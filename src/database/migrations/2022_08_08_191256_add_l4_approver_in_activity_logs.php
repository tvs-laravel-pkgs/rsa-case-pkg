<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddL4ApproverInActivityLogs extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->datetime('l4_deffered_at')->nullable()->after('l3_approved_by_id');
			$table->unsignedInteger('l4_deffered_by_id')->nullable()->after('l4_deffered_at');
			$table->datetime('l4_approved_at')->nullable()->after('l4_deffered_by_id');
			$table->unsignedInteger('l4_approved_by_id')->nullable()->after('l4_approved_at');

			$table->foreign('l4_deffered_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->foreign('l4_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_logs', function (Blueprint $table) {
			$table->dropForeign('activity_logs_l4_deffered_by_id_foreign');
			$table->dropForeign('activity_logs_l4_approved_by_id_foreign');

			$table->dropColumn('l4_deffered_at');
			$table->dropColumn('l4_deffered_by_id');
			$table->dropColumn('l4_approved_at');
			$table->dropColumn('l4_approved_by_id');
		});
	}
}
