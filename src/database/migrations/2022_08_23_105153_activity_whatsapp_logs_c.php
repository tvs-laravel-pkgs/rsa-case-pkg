<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityWhatsappLogsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('activity_whatsapp_logs')) {
			Schema::create('activity_whatsapp_logs', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('activity_id');
				$table->unsignedInteger('type_id')->nullable();
				$table->text('request')->nullable();
				$table->text('response')->nullable();
				$table->string('remarks', 191)->nullable();
				$table->timestamps();

				$table->foreign("activity_id")->references("id")->on("activities")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("type_id")->references("id")->on("configs")->onDelete("SET NULL")->onUpdate("CASCADE");
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_whatsapp_logs');
	}
}
