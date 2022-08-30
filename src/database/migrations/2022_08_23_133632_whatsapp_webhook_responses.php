<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WhatsappWebhookResponses extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('whatsapp_webhook_responses')) {
			Schema::create('whatsapp_webhook_responses', function (Blueprint $table) {
				$table->increments('id');
				$table->text('payload')->nullable();
				$table->string('status', 64)->nullable();
				$table->text('errors')->nullable();
				$table->timestamps();
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('whatsapp_webhook_responses');
	}
}
