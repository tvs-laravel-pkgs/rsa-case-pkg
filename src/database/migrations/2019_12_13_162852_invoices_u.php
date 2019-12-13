<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvoicesU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('Invoices', function (Blueprint $table) {
			$table->unsignedInteger('company_id')->nullable()->default(1)->after('id');
			$table->unsignedInteger('status_id')->nullable()->after('asp_pan_number');

			$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
			$table->foreign('status_id')->references('id')->on('invoice_statuses')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('Invoices', function (Blueprint $table) {
			$table->dropForeign('invoices_company_id_foreign');
			$table->dropForeign('invoices_status_id_foreign');

			$table->dropColumn('company_id');
			$table->dropColumn('status_id');
		});
	}
}
