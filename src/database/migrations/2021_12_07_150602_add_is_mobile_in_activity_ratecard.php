<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsMobileInActivityRatecard extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('activity_ratecards', function (Blueprint $table) {
			$table->unsignedDecimal('below_range_price_margin', 5, 2)->nullable()->after('adjustment');
			$table->unsignedDecimal('above_range_price_margin', 5, 2)->nullable()->after('below_range_price_margin');
			$table->unsignedInteger('fleet_count')->nullable()->after('above_range_price_margin');
			$table->boolean('is_mobile')->default(0)->after('fleet_count');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('activity_ratecards', function (Blueprint $table) {
			$table->dropColumn('below_range_price_margin');
			$table->dropColumn('above_range_price_margin');
			$table->dropColumn('fleet_count');
			$table->dropColumn('is_mobile');
		});
	}
}
