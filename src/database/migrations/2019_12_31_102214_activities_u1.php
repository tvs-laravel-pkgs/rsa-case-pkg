<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivitiesU1 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->string('defer_reason', 191)->nullable()->after('bo_comments');
			$table->unsignedTinyInteger('is_exceptional_check')->default(0)->after('defer_reason');
			$table->string('exceptional_reason', 191)->nullable()->after('is_exceptional_check');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->dropColumn('defer_reason');
			$table->dropColumn('is_exceptional_check');
			$table->dropColumn('exceptional_reason');
		});
	}
}
