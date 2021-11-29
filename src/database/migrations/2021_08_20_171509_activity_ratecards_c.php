<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityRatecardsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('activity_ratecards')) {
			Schema::create('activity_ratecards', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('activity_id');
				$table->unsignedDecimal('range_limit', 5, 2)->nullable();
				$table->unsignedDecimal('below_range_price', 12, 2)->nullable();
				$table->unsignedDecimal('above_range_price', 12, 2)->nullable();
				$table->unsignedDecimal('waiting_charge_per_hour', 12, 2)->nullable();
				$table->unsignedDecimal('empty_return_range_price', 12, 2)->nullable();
				$table->unsignedTinyInteger('adjustment_type')->nullable();
				$table->unsignedDecimal('adjustment', 12, 2)->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("activity_id")->references("id")->on("activities")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique('activity_id');

				$table->index('activity_id');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_ratecards');
	}
}
