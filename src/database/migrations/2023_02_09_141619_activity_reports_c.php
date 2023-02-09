<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActivityReportsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('activity_reports')) {
			Schema::create('activity_reports', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger("activity_id")->nullable();
				$table->string('case_number', 32)->nullable();
				$table->dateTime('case_date')->nullable();
				$table->dateTime('case_submission_closing_date')->nullable();
				$table->text('case_submission_closing_date_remarks')->nullable();
				$table->string('crm_activity_id', 191)->nullable();
				$table->string('activity_number', 32)->nullable();
				$table->dateTime('activity_created_date')->nullable();
				$table->string('client', 150)->nullable();
				$table->unsignedInteger("client_user_id")->nullable();
				$table->string('customer_name', 255)->nullable();
				$table->string('customer_contact_number', 15)->nullable();

				$table->string('asp_name', 191)->nullable();
				$table->unsignedInteger("asp_user_id")->nullable();
				$table->string('axapta_code', 191)->nullable();
				$table->string('asp_code', 191)->nullable();
				$table->string('asp_contact_number', 15)->nullable();
				$table->string('asp_email', 191)->nullable();
				$table->string('asp_has_gst', 10)->nullable();
				$table->string('asp_type', 20)->nullable();
				$table->string('auto_invoice', 10)->nullable();
				$table->string('workshop_name', 191)->nullable();
				$table->string('workshop_type', 191)->nullable();
				$table->string('rm_name', 191)->nullable();
				$table->unsignedInteger("rm_user_id")->nullable();
				$table->string('zm_name', 191)->nullable();
				$table->unsignedInteger("zm_user_id")->nullable();
				$table->string('nm_name', 191)->nullable();
				$table->unsignedInteger("nm_user_id")->nullable();

				$table->string('location', 191)->nullable();
				$table->string('district', 191)->nullable();
				$table->string('state', 191)->nullable();
				$table->string('vehicle_registration_number', 24)->nullable();
				$table->string('membership_type', 191)->nullable();
				$table->string('vehicle_model', 255)->nullable();
				$table->string('vehicle_make', 255)->nullable();
				$table->string('case_status', 191)->nullable();
				$table->string('finance_status', 191)->nullable();
				$table->string('final_approved_bo_service_type', 191)->nullable();
				$table->string('asp_activity_rejected_reason', 191)->nullable();
				$table->string('asp_po_accepted', 10)->nullable();
				$table->string('asp_po_rejected_reason', 191)->nullable();
				$table->string('portal_status', 191)->nullable();
				$table->string('activity_status', 191)->nullable();
				$table->text('activity_description')->nullable();
				$table->string('is_towing_attachment_mandatory', 10)->nullable();
				$table->string('towing_attachment_mandatory_by', 191)->nullable();

				$table->text('remarks')->nullable();
				$table->text('manual_uploading_remarks')->nullable();
				$table->text('general_remarks')->nullable();
				$table->text('bo_comments')->nullable();
				$table->text('deduction_reason')->nullable();
				$table->text('defer_reason')->nullable();
				$table->text('asp_resolve_comments')->nullable();
				$table->string('is_exceptional', 10)->nullable();
				$table->text('exceptional_reason')->nullable();

				$table->string('invoice_number', 255)->nullable();
				$table->date('invoice_date')->nullable();
				$table->unsignedDecimal('invoice_amount', 12, 2)->nullable();
				$table->string('invoice_status', 191)->nullable();
				$table->date('transaction_date')->nullable();
				$table->string('voucher', 255)->nullable();
				$table->unsignedDecimal('tds_amount', 12, 2)->nullable();
				$table->unsignedDecimal('paid_amount', 12, 2)->nullable();

				$table->string('bd_lat', 255)->nullable();
				$table->string('bd_long', 255)->nullable();
				$table->text('bd_location')->nullable();
				$table->string('bd_city', 255)->nullable();
				$table->string('bd_state', 255)->nullable();
				$table->string('location_type', 255)->nullable();
				$table->string('location_category', 255)->nullable();

				$table->string('sla_achieved_delayed', 191)->nullable();
				$table->string('cc_waiting_time', 191)->nullable();
				$table->string('cc_total_km', 191)->nullable();
				$table->unsignedDecimal('cc_collected_amount', 12, 2)->nullable();
				$table->unsignedDecimal('cc_not_collected_amount', 12, 2)->nullable();
				$table->dateTime('asp_reached_date')->nullable();
				$table->text('asp_start_location')->nullable();
				$table->text('asp_end_location')->nullable();
				$table->string('onward_google_km', 191)->nullable();
				$table->string('dealer_google_km', 191)->nullable();
				$table->string('return_google_km', 191)->nullable();
				$table->string('onward_km', 191)->nullable();
				$table->string('dealer_km', 191)->nullable();
				$table->string('return_km', 191)->nullable();
				$table->string('drop_location_type', 60)->nullable();
				$table->string('drop_dealer', 255)->nullable();
				$table->text('drop_location')->nullable();
				$table->string('drop_location_lat', 255)->nullable();
				$table->string('drop_location_long', 255)->nullable();
				$table->unsignedDecimal('amount', 12, 2)->nullable();
				$table->string('paid_to', 60)->nullable();
				$table->string('payment_mode', 60)->nullable();
				$table->string('payment_receipt_no', 60)->nullable();

				$table->unsignedDecimal('cc_service_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_membership_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_eatable_items_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_toll_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_green_tax_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_border_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_octroi_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_excess_charges', 12, 2)->nullable();
				$table->unsignedDecimal('cc_fuel_charges', 12, 2)->nullable();

				$table->unsignedDecimal('asp_service_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_membership_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_eatable_items_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_toll_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_green_tax_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_border_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_octroi_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_excess_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_fuel_charges', 12, 2)->nullable();

				$table->unsignedDecimal('bo_service_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_membership_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_eatable_items_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_toll_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_green_tax_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_border_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_octroi_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_excess_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_fuel_charges', 12, 2)->nullable();

				$table->string('asp_waiting_time', 191)->nullable();
				$table->string('bo_waiting_time', 191)->nullable();
				$table->unsignedDecimal('cc_waiting_charges', 12, 2)->nullable();
				$table->unsignedDecimal('asp_waiting_charges', 12, 2)->nullable();
				$table->unsignedDecimal('bo_waiting_charges', 12, 2)->nullable();

				$table->string('cc_service_type', 191)->nullable();
				$table->string('asp_service_type', 191)->nullable();
				$table->string('bo_service_type', 191)->nullable();
				$table->string('bo_km_travelled', 191)->nullable();
				$table->unsignedDecimal('bo_collected', 12, 2)->nullable();
				$table->unsignedDecimal('bo_not_collected', 12, 2)->nullable();
				$table->string('asp_km_travelled', 191)->nullable();
				$table->unsignedDecimal('asp_collected', 12, 2)->nullable();
				$table->unsignedDecimal('asp_not_collected', 12, 2)->nullable();
				$table->unsignedDecimal('cc_po_amount', 12, 2)->nullable();
				$table->unsignedDecimal('cc_net_amount', 12, 2)->nullable();
				$table->unsignedDecimal('cc_amount', 12, 2)->nullable();
				$table->unsignedDecimal('bo_tax_amount', 12, 2)->nullable();
				$table->unsignedDecimal('bo_net_amount', 12, 2)->nullable();
				$table->unsignedDecimal('bo_po_amount', 12, 2)->nullable();
				$table->unsignedDecimal('bo_deduction', 12, 2)->nullable();
				$table->unsignedDecimal('bo_amount', 12, 2)->nullable();
				$table->unsignedDecimal('asp_po_amount', 12, 2)->nullable();
				$table->unsignedDecimal('asp_net_amount', 12, 2)->nullable();
				$table->unsignedDecimal('asp_amount', 12, 2)->nullable();

				$table->dateTime('imported_date')->nullable();
				$table->string('imported_by', 191)->nullable();
				$table->string('duration_between_import_and_asp_data_filled', 20)->nullable();

				$table->dateTime('asp_data_filled_date')->nullable();
				$table->string('asp_data_filled_by', 191)->nullable();
				$table->string('duration_between_asp_data_filled_and_l1_deffered', 20)->nullable();

				$table->dateTime('l1_deffered_date')->nullable();
				$table->string('l1_deffered_by', 191)->nullable();
				$table->string('duration_between_asp_data_filled_and_l1_approved', 20)->nullable();

				$table->dateTime('l1_approved_date')->nullable();
				$table->string('l1_approved_by', 191)->nullable();
				$table->string('duration_between_l1_approved_and_invoice_generated', 20)->nullable();
				$table->string('duration_between_l1_approved_and_l2_deffered', 20)->nullable();

				$table->dateTime('l2_deffered_date')->nullable();
				$table->string('l2_deffered_by', 191)->nullable();
				$table->string('duration_between_l1_approved_and_l2_approved', 20)->nullable();

				$table->dateTime('l2_approved_date')->nullable();
				$table->string('l2_approved_by', 191)->nullable();
				$table->string('duration_between_l2_approved_and_invoice_generated', 20)->nullable();
				$table->string('duration_between_l1_approved_and_l3_deffered', 20)->nullable();
				$table->string('duration_between_l2_approved_and_l3_deffered', 20)->nullable();

				$table->dateTime('l3_deffered_date')->nullable();
				$table->string('l3_deffered_by', 191)->nullable();
				$table->string('duration_between_l2_approved_and_l3_approved', 20)->nullable();

				$table->dateTime('l3_approved_date')->nullable();
				$table->string('l3_approved_by', 191)->nullable();
				$table->string('duration_between_l3_approved_and_invoice_generated', 20)->nullable();
				$table->string('duration_between_l1_approved_and_l4_deffered', 20)->nullable();
				$table->string('duration_between_l2_approved_and_l4_deffered', 20)->nullable();
				$table->string('duration_between_l3_approved_and_l4_deffered', 20)->nullable();

				$table->dateTime('l4_deffered_date')->nullable();
				$table->string('l4_deffered_by', 191)->nullable();
				$table->string('duration_between_l3_approved_and_l4_approved', 20)->nullable();

				$table->dateTime('l4_approved_date')->nullable();
				$table->string('l4_approved_by', 191)->nullable();
				$table->string('duration_between_l4_approved_and_invoice_generated', 20)->nullable();

				$table->dateTime('invoice_generated_date')->nullable();
				$table->string('invoice_generated_by', 191)->nullable();
				$table->string('duration_between_invoice_generated_and_axapta_generated', 20)->nullable();

				$table->dateTime('axapta_generated_date')->nullable();
				$table->string('axapta_generated_by', 191)->nullable();
				$table->string('duration_between_axapta_generated_and_payment_completed', 20)->nullable();

				$table->dateTime('payment_completed_date')->nullable();
				$table->string('total_no_of_days', 20)->nullable();

				$table->string('source', 20)->nullable();
				$table->unsignedDecimal('range_limit', 5, 2)->nullable();
				$table->unsignedDecimal('below_range_price', 12, 2)->nullable();
				$table->unsignedDecimal('above_range_price', 12, 2)->nullable();
				$table->unsignedDecimal('waiting_charge_per_hour', 12, 2)->nullable();
				$table->unsignedDecimal('empty_return_range_price', 12, 2)->nullable();
				$table->string('adjustment_type', 20)->nullable();
				$table->unsignedDecimal('adjustment', 12, 2)->nullable();

				$table->string('created_by', 191)->nullable();
				$table->string('created_year', 10)->nullable();
				$table->string('created_date', 20)->nullable();

				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("activity_id")->references("id")->on("activities")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("client_user_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("asp_user_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("rm_user_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("zm_user_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("nm_user_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique('activity_id');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('activity_reports');
	}
}
