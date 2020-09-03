<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivitiesU4 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activities', function (Blueprint $table) {
			$table->text('description')->default(null)->change();
			$table->text('remarks')->default(null)->change();
			$table->text('asp_resolve_comments')->default(null)->change();
			$table->text('deduction_reason')->default(null)->change();
			$table->text('bo_comments')->default(null)->change();
			$table->text('defer_reason')->default(null)->change();
			$table->text('exceptional_reason')->default(null)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->string('description', 191)->default(null)->change();
			$table->string('remarks', 255)->default(null)->change();
			$table->string('asp_resolve_comments', 255)->default(null)->change();
			$table->string('deduction_reason', 191)->default(null)->change();
			$table->string('bo_comments', 191)->default(null)->change();
			$table->string('defer_reason', 191)->default(null)->change();
			$table->string('exceptional_reason', 191)->default(null)->change();
		});
	}
}
