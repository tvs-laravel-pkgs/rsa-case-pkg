<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PoliciesPoC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::rename('policies', 'policies_sales');

		Schema::create('policies', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id');
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->unsignedInteger('type_id')->nullable();
			$table->foreign('type_id')->references('id')->on('policy_types')->onDelete('SET NULL')->onUpdate('cascade');
			$table->string('number');
			$table->date('start_date');
			$table->date('end_date');
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
		Schema::dropIfExists('policies');
		Schema::rename('policies_sales', 'policies');
	}
}
