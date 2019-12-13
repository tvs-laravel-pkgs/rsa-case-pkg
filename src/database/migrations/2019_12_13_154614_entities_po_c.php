<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EntitiesPoC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('entities', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('entity_type_id');
			$table->foreign('entity_type_id')->references('id')->on('entity_types')->onDelete('CASCADE')->onUpdate('cascade');
			$table->string('name', 191);
			$table->unique(["entity_type_id", "name"]);
			$table->unsignedInteger('created_by_id')->nullable();
			$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('updated_by_id')->nullable();
			$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->unsignedInteger('deleted_by_id')->nullable();
			$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('entities');
	}
}
