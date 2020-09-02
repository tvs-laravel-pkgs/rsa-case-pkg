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
			$table->text('description')->change();
			$table->text('remarks')->change();
			$table->text('asp_resolve_comments')->change();
			$table->text('deduction_reason')->change();
			$table->text('bo_comments')->change();
			$table->text('defer_reason')->change();
			$table->text('exceptional_reason')->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activities', function (Blueprint $table) {
			$table->string('description', 191)->change();
			$table->string('remarks', 255)->change();
			$table->string('asp_resolve_comments', 255)->change();
			$table->string('deduction_reason', 191)->change();
			$table->string('bo_comments', 191)->change();
			$table->string('defer_reason', 191)->change();
			$table->string('exceptional_reason', 191)->change();
		});
	}
}
