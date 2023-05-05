<?php

namespace Abs\RsaCasePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\RsaCasePkg\Activity;
use App\Jobs\ElkJobQueue;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityReport extends Model {
	use SoftDeletes;
	use SeederTrait;

	protected $fillable = [
		'activity_id',
	];

	protected $table = 'activity_reports';
	public $timestamps = true;

	// Relationships --------------------------------------------------------------

	public function activity(): BelongsTo {
		return $this->belongsTo(Activity::class, 'activity_id');
	}

	// STATIC FUNCTION ------------------------------------------------------------

	public static function saveReport($activityId = '') {
		if (!empty($activityId)) {
			$activity = Activity::withTrashed()->select([
				'activities.*',
				DB::raw('DATE_FORMAT(cases.date, "%d-%m-%Y %H:%i:%s") as case_date'),
			])
				->join('cases', 'cases.id', 'activities.case_id')
				->find($activityId);
			if ($activity) {
				$activityReport = self::withTrashed()->firstOrNew([
					'activity_id' => $activity->id,
				]);
				//NEW
				if (!$activityReport->exists) {
					$activityReport->created_by = $activity->createdBy ? $activity->createdBy->name : NULL;
					$activityReport->created_by_id = $activity->createdBy ? $activity->createdBy->id : NULL;
					$activityReport->created_at = $activity->created_at;
					$activityReport->updated_at = $activity->updated_at;
				} else {
					$activityReport->created_by = $activity->createdBy ? $activity->createdBy->name : NULL;
					$activityReport->created_by_id = $activity->createdBy ? $activity->createdBy->id : NULL;
					$activityReport->updated_by_id = $activity->updatedBy ? $activity->updatedBy->id : NULL;
					$activityReport->updated_at = $activity->updated_at;
				}
				$activityReport->activity_id = $activity->id;

				//CASE
				if ($activity->case) {
					$activityReport->case_number = $activity->case->number;
					$activityReport->case_date = checkDateTimeAndReturnValidDateTimeVal($activity->case_date);

					if (!is_null($activity->case->submission_closing_date)) {
						$activityReport->case_submission_closing_date = checkDateTimeAndReturnValidDateTimeVal($activity->case->submission_closing_date);
					} else {
						$activityReport->case_submission_closing_date = date('Y-m-d H:i:s', strtotime("+3 months", strtotime($activity->case->created_at)));
					}
					$activityReport->case_submission_closing_date_remarks = $activity->case->submission_closing_date_remarks;

					//CLIENT
					if ($activity->case->client) {
						$activityReport->client = $activity->case->client->name;
						$activityReport->client_user_id = $activity->case->client->user ? $activity->case->client->user->id : NULL;
					}

					$activityReport->customer_name = $activity->case->customer_name;
					$activityReport->customer_contact_number = $activity->case->customer_contact_number;
					$activityReport->vehicle_registration_number = !empty($activity->case->vehicle_registration_number) ? $activity->case->vehicle_registration_number : NULL;
					$activityReport->membership_type = !empty($activity->case->membership_type) ? $activity->case->membership_type : NULL;

					//VEHICLE MODEL
					if ($activity->case->vehicleModel) {
						$activityReport->vehicle_model = $activity->case->vehicleModel->name;
						$activityReport->vehicle_make = $activity->case->vehicleModel->vehiclemake ? $activity->case->vehicleModel->vehiclemake->name : NULL;
					}

					$activityReport->case_status = $activity->case->status ? $activity->case->status->name : NULL;
					$activityReport->bd_lat = $activity->case->bd_lat;
					$activityReport->bd_long = $activity->case->bd_long;
					$activityReport->bd_location = $activity->case->bd_location;
					$activityReport->bd_city = $activity->case->bd_city;
					$activityReport->bd_state = $activity->case->bd_state;
					$activityReport->location_type = $activity->case->bdLocationType ? $activity->case->bdLocationType->name : NULL;
					$activityReport->location_category = $activity->case->bdLocationCategory ? $activity->case->bdLocationCategory->name : NULL;
					$activityReport->csr = !empty($activity->case->csr) ? $activity->case->csr : NULL;
				}

				//ASP
				if ($activity->asp) {
					$activityReport->asp_name = $activity->asp->name;
					$activityReport->asp_user_id = $activity->asp->user ? $activity->asp->user->id : NULL;
					$activityReport->axapta_code = $activity->asp->axpta_code;
					$activityReport->asp_code = $activity->asp->asp_code;
					$activityReport->asp_contact_number = $activity->asp->contact_number1;
					$activityReport->asp_email = $activity->asp->email;
					$activityReport->asp_has_gst = $activity->asp->has_gst == 1 ? "Yes" : "No";
					$activityReport->asp_type = $activity->asp->is_self == 1 ? "Self" : "Non Self";
					$activityReport->auto_invoice = $activity->asp->is_auto_invoice == 1 ? "Yes" : "No";
					$activityReport->workshop_name = $activity->asp->workshop_name;
					$activityReport->workshop_type = !empty($activity->asp->workshop_type) ? array_flip(config('constants')['workshop_types'])[$activity->asp->workshop_type] : NULL;

					//RM
					if ($activity->asp->rm) {
						$activityReport->rm_name = $activity->asp->rm->name;
						$activityReport->rm_user_id = $activity->asp->rm->id;

						//ZM
						if ($activity->asp->rm->serviceRmReportingTo) {
							$activityReport->zm_name = $activity->asp->rm->serviceRmReportingTo->name;
							$activityReport->zm_user_id = $activity->asp->rm->serviceRmReportingTo->id;

							//NM
							if ($activity->asp->rm->serviceRmReportingTo->serviceZmReportingTo) {
								$activityReport->nm_name = $activity->asp->rm->serviceRmReportingTo->serviceZmReportingTo->name;
								$activityReport->nm_user_id = $activity->asp->rm->serviceRmReportingTo->serviceZmReportingTo->id;
							}
						}
					}

					$activityReport->location = $activity->asp->location ? $activity->asp->location->name : NULL;
					$activityReport->district = $activity->asp->district ? $activity->asp->district->name : NULL;
					$activityReport->state = $activity->asp->state ? $activity->asp->state->name : NULL;
				}

				//ACTIVITY
				$activityReport->crm_activity_id = $activity->crm_activity_id;
				$activityReport->activity_number = $activity->number;
				$activityReport->activity_created_date = !empty($activity->created_at) ? $activity->created_at : NULL;
				$activityReport->finance_status = $activity->financeStatus ? $activity->financeStatus->name : NULL;
				$activityReport->final_approved_bo_service_type = $activity->serviceType ? $activity->serviceType->name : NULL;
				$activityReport->asp_activity_rejected_reason = $activity->aspActivityRejectedReason ? $activity->aspActivityRejectedReason->name : NULL;
				$activityReport->asp_po_accepted = !is_null($activity->asp_po_accepted) ? ($activity->asp_po_accepted == 1 ? 'Yes' : 'No') : NULL;
				$activityReport->asp_po_rejected_reason = !empty($activity->asp_po_rejected_reason) ? $activity->asp_po_rejected_reason : NULL;
				$activityReport->portal_status = $activity->status ? $activity->status->name : NULL;
				$activityReport->activity_status = $activity->activityStatus ? $activity->activityStatus->name : NULL;
				$activityReport->activity_description = $activity->description;
				$activityReport->is_towing_attachment_mandatory = $activity->is_towing_attachments_mandatory == 1 ? "Yes" : "No";
				$activityReport->towing_attachment_mandatory_by = $activity->towingAttachmentMandatoryBy ? $activity->towingAttachmentMandatoryBy->name : NULL;

				$activityReport->remarks = !empty($activity->remarks) ? $activity->remarks : NULL;
				$activityReport->manual_uploading_remarks = !empty($activity->manual_uploading_remarks) ? $activity->manual_uploading_remarks : NULL;
				$activityReport->general_remarks = !empty($activity->general_remarks) ? $activity->general_remarks : NULL;
				$activityReport->bo_comments = !empty($activity->bo_comments) ? $activity->bo_comments : NULL;
				$activityReport->deduction_reason = !empty($activity->deduction_reason) ? $activity->deduction_reason : NULL;
				$activityReport->defer_reason = !empty($activity->defer_reason) ? $activity->defer_reason : NULL;
				$activityReport->asp_resolve_comments = !empty($activity->asp_resolve_comments) ? $activity->asp_resolve_comments : NULL;
				$activityReport->is_exceptional = $activity->is_exceptional_check == 1 ? "Yes" : "No";
				$activityReport->exceptional_reason = !empty($activity->exceptional_reason) ? $activity->exceptional_reason : NULL;

				//INVOICE

				$activityReport->invoice_number = NULL;
				$activityReport->invoice_date = NULL;
				$activityReport->invoice_amount = NULL;
				$activityReport->invoice_status = NULL;
				$activityReport->transaction_date = NULL;
				$activityReport->voucher = NULL;
				$activityReport->tds_amount = NULL;
				$activityReport->paid_amount = NULL;

				if ($activity->invoice) {
					$activityReport->invoice_number = $activity->invoice->invoice_no;
					$activityReport->invoice_date = !is_null($activity->invoice->created_at) ? checkDateAndReturnValidDateVal(Carbon::parse(str_replace('/', '-', $activity->invoice->created_at))->format('Y-m-d')) : NULL;
					$activityReport->invoice_amount = $activity->invoice->invoice_amount;
					$activityReport->invoice_status = $activity->invoice->invoiceStatus ? $activity->invoice->invoiceStatus->name : NULL;

					//INVOICE VOUCHER
					if ($activity->invoice->invoiceVouchers->isNotEmpty()) {
						$invoiceVoucher = $activity->invoice->invoiceVouchers()->orderBy('id', 'desc')->first();
						$activityReport->transaction_date = checkDateAndReturnValidDateVal($invoiceVoucher->date);
						$activityReport->voucher = $invoiceVoucher->number;
						$activityReport->tds_amount = $invoiceVoucher->tds;
						$activityReport->paid_amount = $invoiceVoucher->paid_amount;
					}
				}

				//ACTIVITY DETAILS
				$activityReport->sla_achieved_delayed = $activity->detail(278) ? checkValueHasValid($activity->detail(278)->value) : NULL;
				$activityReport->cc_waiting_time = $activity->detail(279) ? checkValueHasValid($activity->detail(279)->value) : 0;
				$activityReport->cc_total_km = $activity->detail(280) ? checkValueHasValid($activity->detail(280)->value) : 0;
				$activityReport->cc_collected_amount = $activity->detail(281) ? checkValueHasValid($activity->detail(281)->value) : 0;
				$activityReport->cc_not_collected_amount = $activity->detail(282) ? checkValueHasValid($activity->detail(282)->value) : 0;
				$activityReport->asp_reached_date = $activity->detail(283) ? checkDateTimeAndReturnValidDateTimeVal($activity->detail(283)->value) : NULL;
				$activityReport->asp_start_location = $activity->detail(284) ? checkValueHasValid($activity->detail(284)->value) : NULL;
				$activityReport->asp_end_location = $activity->detail(285) ? checkValueHasValid($activity->detail(285)->value) : NULL;
				$activityReport->onward_google_km = $activity->detail(286) ? checkValueHasValid($activity->detail(286)->value) : NULL;
				$activityReport->dealer_google_km = $activity->detail(287) ? checkValueHasValid($activity->detail(287)->value) : NULL;
				$activityReport->return_google_km = $activity->detail(288) ? checkValueHasValid($activity->detail(288)->value) : NULL;
				$activityReport->onward_km = $activity->detail(289) ? checkValueHasValid($activity->detail(289)->value) : NULL;
				$activityReport->dealer_km = $activity->detail(290) ? checkValueHasValid($activity->detail(290)->value) : NULL;
				$activityReport->return_km = $activity->detail(291) ? checkValueHasValid($activity->detail(291)->value) : NULL;
				$activityReport->drop_location_type = $activity->detail(293) ? checkValueHasValid($activity->detail(293)->value) : NULL;
				$activityReport->drop_dealer = $activity->detail(294) ? checkValueHasValid($activity->detail(294)->value) : NULL;
				$activityReport->drop_location = $activity->detail(295) ? checkValueHasValid($activity->detail(295)->value) : NULL;
				$activityReport->drop_location_lat = $activity->detail(296) ? checkValueHasValid($activity->detail(296)->value) : NULL;
				$activityReport->drop_location_long = $activity->detail(297) ? checkValueHasValid($activity->detail(297)->value) : NULL;
				$activityReport->amount = $activity->detail(298) ? checkValueHasValid($activity->detail(298)->value) : 0;
				$activityReport->paid_to = $activity->detail(299) ? checkValueHasValid($activity->detail(299)->value) : NULL;
				$activityReport->payment_mode = $activity->detail(300) ? checkValueHasValid($activity->detail(300)->value) : NULL;
				$activityReport->payment_receipt_no = $activity->detail(301) ? checkValueHasValid($activity->detail(301)->value) : NULL;

				$activityReport->cc_service_charges = $activity->detail(302) ? checkValueHasValid($activity->detail(302)->value) : 0;
				$activityReport->cc_membership_charges = $activity->detail(303) ? checkValueHasValid($activity->detail(303)->value) : 0;
				$activityReport->cc_eatable_items_charges = $activity->detail(304) ? checkValueHasValid($activity->detail(304)->value) : 0;
				$activityReport->cc_toll_charges = $activity->detail(305) ? checkValueHasValid($activity->detail(305)->value) : 0;
				$activityReport->cc_green_tax_charges = $activity->detail(306) ? checkValueHasValid($activity->detail(306)->value) : 0;
				$activityReport->cc_border_charges = $activity->detail(307) ? checkValueHasValid($activity->detail(307)->value) : 0;
				$activityReport->cc_octroi_charges = $activity->detail(308) ? checkValueHasValid($activity->detail(308)->value) : 0;
				$activityReport->cc_excess_charges = $activity->detail(309) ? checkValueHasValid($activity->detail(309)->value) : 0;
				$activityReport->cc_fuel_charges = $activity->detail(310) ? checkValueHasValid($activity->detail(310)->value) : 0;

				$activityReport->asp_service_charges = $activity->detail(311) ? checkValueHasValid($activity->detail(311)->value) : 0;
				$activityReport->asp_membership_charges = $activity->detail(312) ? checkValueHasValid($activity->detail(312)->value) : 0;
				$activityReport->asp_eatable_items_charges = $activity->detail(313) ? checkValueHasValid($activity->detail(313)->value) : 0;
				$activityReport->asp_toll_charges = $activity->detail(314) ? checkValueHasValid($activity->detail(314)->value) : 0;
				$activityReport->asp_green_tax_charges = $activity->detail(315) ? checkValueHasValid($activity->detail(315)->value) : 0;
				$activityReport->asp_border_charges = $activity->detail(316) ? checkValueHasValid($activity->detail(316)->value) : 0;
				$activityReport->asp_octroi_charges = $activity->detail(317) ? checkValueHasValid($activity->detail(317)->value) : 0;
				$activityReport->asp_excess_charges = $activity->detail(318) ? checkValueHasValid($activity->detail(318)->value) : 0;
				$activityReport->asp_fuel_charges = $activity->detail(319) ? checkValueHasValid($activity->detail(319)->value) : 0;

				$activityReport->bo_service_charges = $activity->detail(320) ? checkValueHasValid($activity->detail(320)->value) : 0;
				$activityReport->bo_membership_charges = $activity->detail(321) ? checkValueHasValid($activity->detail(321)->value) : 0;
				$activityReport->bo_eatable_items_charges = $activity->detail(322) ? checkValueHasValid($activity->detail(322)->value) : 0;
				$activityReport->bo_toll_charges = $activity->detail(323) ? checkValueHasValid($activity->detail(323)->value) : 0;
				$activityReport->bo_green_tax_charges = $activity->detail(324) ? checkValueHasValid($activity->detail(324)->value) : 0;
				$activityReport->bo_border_charges = $activity->detail(325) ? checkValueHasValid($activity->detail(325)->value) : 0;
				$activityReport->bo_octroi_charges = $activity->detail(326) ? checkValueHasValid($activity->detail(326)->value) : 0;
				$activityReport->bo_excess_charges = $activity->detail(327) ? checkValueHasValid($activity->detail(327)->value) : 0;
				$activityReport->bo_fuel_charges = $activity->detail(328) ? checkValueHasValid($activity->detail(328)->value) : 0;

				$activityReport->asp_waiting_time = $activity->detail(329) ? checkValueHasValid($activity->detail(329)->value) : 0;
				$activityReport->bo_waiting_time = $activity->detail(330) ? checkValueHasValid($activity->detail(330)->value) : 0;
				$activityReport->cc_waiting_charges = $activity->detail(331) ? checkValueHasValid($activity->detail(331)->value) : 0;
				$activityReport->asp_waiting_charges = $activity->detail(332) ? checkValueHasValid($activity->detail(332)->value) : 0;
				$activityReport->bo_waiting_charges = $activity->detail(333) ? checkValueHasValid($activity->detail(333)->value) : 0;

				$activityReport->cc_service_type = $activity->detail(153) ? checkValueHasValid($activity->detail(153)->value) : NULL;
				$activityReport->asp_service_type = $activity->detail(157) ? checkValueHasValid($activity->detail(157)->value) : NULL;
				$activityReport->bo_service_type = $activity->detail(161) ? checkValueHasValid($activity->detail(161)->value) : NULL;

				$activityReport->bo_km_travelled = $activity->detail(158) ? checkValueHasValid($activity->detail(158)->value) : 0;
				$activityReport->bo_collected = $activity->detail(159) ? checkValueHasValid($activity->detail(159)->value) : 0;
				$activityReport->bo_not_collected = $activity->detail(160) ? checkValueHasValid($activity->detail(160)->value) : 0;

				$activityReport->asp_km_travelled = $activity->detail(154) ? checkValueHasValid($activity->detail(154)->value) : 0;
				$activityReport->asp_collected = $activity->detail(155) ? checkValueHasValid($activity->detail(155)->value) : 0;
				$activityReport->asp_not_collected = $activity->detail(156) ? checkValueHasValid($activity->detail(156)->value) : 0;

				$activityReport->cc_po_amount = $activity->detail(170) ? checkValueHasValid($activity->detail(170)->value) : 0;
				$activityReport->cc_net_amount = $activity->detail(174) ? checkValueHasValid($activity->detail(174)->value) : 0;
				$activityReport->cc_amount = $activity->detail(180) ? checkValueHasValid($activity->detail(180)->value) : 0;

				$activityReport->bo_tax_amount = $activity->detail(179) ? checkValueHasValid($activity->detail(179)->value) : 0;
				$activityReport->bo_net_amount = $activity->detail(176) ? checkValueHasValid($activity->detail(176)->value) : 0;
				$activityReport->bo_po_amount = $activity->detail(172) ? checkValueHasValid($activity->detail(172)->value) : 0;
				$activityReport->bo_deduction = $activity->detail(173) ? checkValueHasValid($activity->detail(173)->value) : 0;
				$activityReport->bo_amount = $activity->detail(182) ? checkValueHasValid($activity->detail(182)->value) : 0;

				$activityReport->asp_po_amount = $activity->detail(171) ? checkValueHasValid($activity->detail(171)->value) : 0;
				$activityReport->asp_net_amount = $activity->detail(175) ? checkValueHasValid($activity->detail(175)->value) : 0;
				$activityReport->asp_amount = $activity->detail(181) ? checkValueHasValid($activity->detail(181)->value) : 0;

				//ACTIVITY LOG
				if ($activity->log) {
					$totalDays = 0;

					//IMPORTED AT & BY
					$activityReport->imported_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->imported_at);
					$activityReport->imported_by = $activity->log->importedBy ? $activity->log->importedBy->username : NULL;

					// DURATION BETWEEN IMPORT AND ASP DATA FILLED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->imported_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->asp_data_filled_at))) {
						$daysBtwImportAndDataEntry = daysBetweenTwoDates($activity->log->imported_at, $activity->log->asp_data_filled_at);
						$totalDays += $daysBtwImportAndDataEntry;
						$activityReport->duration_between_import_and_asp_data_filled = ($daysBtwImportAndDataEntry > 1) ? ($daysBtwImportAndDataEntry . ' Days') : ($daysBtwImportAndDataEntry . ' Day');
					}

					//ASP DATA FILLED AT & BY
					$activityReport->asp_data_filled_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->asp_data_filled_at);
					$activityReport->asp_data_filled_by = $activity->log->aspDataFilledBy ? $activity->log->aspDataFilledBy->username : NULL;

					// DURATION BETWEEN ASP DATA FILLED AND L1 DEFFERED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->asp_data_filled_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_deffered_at))) {
						$daysBtwDataEntryAndL1Deffered = daysBetweenTwoDates($activity->log->asp_data_filled_at, $activity->log->bo_deffered_at);
						$totalDays += $daysBtwDataEntryAndL1Deffered;
						$activityReport->duration_between_asp_data_filled_and_l1_deffered = ($daysBtwDataEntryAndL1Deffered > 1) ? ($daysBtwDataEntryAndL1Deffered . ' Days') : ($daysBtwDataEntryAndL1Deffered . ' Day');
					}

					//L1 DEFFERED AT & BY
					$activityReport->l1_deffered_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_deffered_at);
					$activityReport->l1_deffered_by = $activity->log->boDefferedBy ? $activity->log->boDefferedBy->username : NULL;

					// DURATION BETWEEN ASP DATA FILLED AND L1 APPROVED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->asp_data_filled_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at))) {
						$daysBtwDataEntryAndL1Approved = daysBetweenTwoDates($activity->log->asp_data_filled_at, $activity->log->bo_approved_at);
						$totalDays += $daysBtwDataEntryAndL1Approved;
						$activityReport->duration_between_asp_data_filled_and_l1_approved = ($daysBtwDataEntryAndL1Approved > 1) ? ($daysBtwDataEntryAndL1Approved . ' Days') : ($daysBtwDataEntryAndL1Approved . ' Day');
					}

					//L1 APPROVED AT & BY
					$activityReport->l1_approved_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at);
					$activityReport->l1_approved_by = $activity->log->boApprovedBy ? $activity->log->boApprovedBy->username : NULL;

					// DURATION BETWEEN L1 APPROVED AND INVOICE GENERATED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->invoice_generated_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at))) {
						$daysBtwL1ApprovedAndInvGenerated = daysBetweenTwoDates($activity->log->invoice_generated_at, $activity->log->bo_approved_at);
						$totalDays += $daysBtwL1ApprovedAndInvGenerated;
						$activityReport->duration_between_l1_approved_and_invoice_generated = ($daysBtwL1ApprovedAndInvGenerated > 1) ? ($daysBtwL1ApprovedAndInvGenerated . ' Days') : ($daysBtwL1ApprovedAndInvGenerated . ' Day');
					}

					// DURATION BETWEEN L1 APPROVED AND L2 DEFFERED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_deffered_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at))) {
						$daysBtwL1ApprovedAndL2Deffered = daysBetweenTwoDates($activity->log->l2_deffered_at, $activity->log->bo_approved_at);
						$totalDays += $daysBtwL1ApprovedAndL2Deffered;
						$activityReport->duration_between_l1_approved_and_l2_deffered = ($daysBtwL1ApprovedAndL2Deffered > 1) ? ($daysBtwL1ApprovedAndL2Deffered . ' Days') : ($daysBtwL1ApprovedAndL2Deffered . ' Day');
					}

					//L2 DEFFERED AT & BY
					$activityReport->l2_deffered_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_deffered_at);
					$activityReport->l2_deffered_by = $activity->log->l2DefferedBy ? $activity->log->l2DefferedBy->username : NULL;

					// DURATION BETWEEN L1 APPROVED AND L2 APPROVED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_approved_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at))) {
						$daysBtwL1ApprovedAndL2Approved = daysBetweenTwoDates($activity->log->l2_approved_at, $activity->log->bo_approved_at);
						$totalDays += $daysBtwL1ApprovedAndL2Approved;
						$activityReport->duration_between_l1_approved_and_l2_approved = ($daysBtwL1ApprovedAndL2Approved > 1) ? ($daysBtwL1ApprovedAndL2Approved . ' Days') : ($daysBtwL1ApprovedAndL2Approved . ' Day');
					}

					//L2 APPROVED AT & BY
					$activityReport->l2_approved_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_approved_at);
					$activityReport->l2_approved_by = $activity->log->l2ApprovedBy ? $activity->log->l2ApprovedBy->username : NULL;

					// DURATION BETWEEN L2 APPROVED AND INVOICE GENERATED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->invoice_generated_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_approved_at))) {
						$daysBtwL2ApprovedAndInvGenerated = daysBetweenTwoDates($activity->log->invoice_generated_at, $activity->log->l2_approved_at);
						$totalDays += $daysBtwL2ApprovedAndInvGenerated;
						$activityReport->duration_between_l2_approved_and_invoice_generated = ($daysBtwL2ApprovedAndInvGenerated > 1) ? ($daysBtwL2ApprovedAndInvGenerated . ' Days') : ($daysBtwL2ApprovedAndInvGenerated . ' Day');
					}

					// DURATION BETWEEN L1 APPROVED AND L3 DEFERRED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_deffered_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at))) {
						$daysBtwL1ApprovedAndL3Deffered = daysBetweenTwoDates($activity->log->l3_deffered_at, $activity->log->bo_approved_at);
						$totalDays += $daysBtwL1ApprovedAndL3Deffered;
						$activityReport->duration_between_l1_approved_and_l3_deffered = ($daysBtwL1ApprovedAndL3Deffered > 1) ? ($daysBtwL1ApprovedAndL3Deffered . ' Days') : ($daysBtwL1ApprovedAndL3Deffered . ' Day');
					}

					// DURATION BETWEEN L2 APPROVED AND L3 DEFERRED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_deffered_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_approved_at))) {
						$daysBtwL2ApprovedAndL3Deferred = daysBetweenTwoDates($activity->log->l3_deffered_at, $activity->log->l2_approved_at);
						$totalDays += $daysBtwL2ApprovedAndL3Deferred;
						$activityReport->duration_between_l2_approved_and_l3_deffered = ($daysBtwL2ApprovedAndL3Deferred > 1) ? ($daysBtwL2ApprovedAndL3Deferred . ' Days') : ($daysBtwL2ApprovedAndL3Deferred . ' Day');
					}

					//L3 DEFERRED AT & BY
					$activityReport->l3_deffered_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_deffered_at);
					$activityReport->l3_deffered_by = $activity->log->l3DefferedBy ? $activity->log->l3DefferedBy->username : NULL;

					// DURATION BETWEEN L2 APPROVED AND L3 APPROVED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_approved_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_approved_at))) {
						$daysBtwL2ApprovedAndL3Approved = daysBetweenTwoDates($activity->log->l3_approved_at, $activity->log->l2_approved_at);
						$totalDays += $daysBtwL2ApprovedAndL3Approved;
						$activityReport->duration_between_l2_approved_and_l3_approved = ($daysBtwL2ApprovedAndL3Approved > 1) ? ($daysBtwL2ApprovedAndL3Approved . ' Days') : ($daysBtwL2ApprovedAndL3Approved . ' Day');
					}

					//L3 APPROVED AT & BY
					$activityReport->l3_approved_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_approved_at);
					$activityReport->l3_approved_by = $activity->log->l3ApprovedBy ? $activity->log->l3ApprovedBy->username : NULL;

					// DURATION BETWEEN L3 APPROVED AND INVOICE GENERATED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->invoice_generated_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_approved_at))) {
						$daysBtwL3ApprovedAndInvGenerated = daysBetweenTwoDates($activity->log->invoice_generated_at, $activity->log->l3_approved_at);
						$totalDays += $daysBtwL3ApprovedAndInvGenerated;
						$activityReport->duration_between_l3_approved_and_invoice_generated = ($daysBtwL3ApprovedAndInvGenerated > 1) ? ($daysBtwL3ApprovedAndInvGenerated . ' Days') : ($daysBtwL3ApprovedAndInvGenerated . ' Day');
					}

					// DURATION BETWEEN L1 APPROVED AND L4 DEFERRED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_deffered_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->bo_approved_at))) {
						$daysBtwL1ApprovedAndL4Deferred = daysBetweenTwoDates($activity->log->l4_deffered_at, $activity->log->bo_approved_at);
						$totalDays += $daysBtwL1ApprovedAndL4Deferred;
						$activityReport->duration_between_l1_approved_and_l4_deffered = ($daysBtwL1ApprovedAndL4Deferred > 1) ? ($daysBtwL1ApprovedAndL4Deferred . ' Days') : ($daysBtwL1ApprovedAndL4Deferred . ' Day');
					}

					// DURATION BETWEEN L2 APPROVED AND L4 DEFERRED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_deffered_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l2_approved_at))) {
						$daysBtwL2ApprovedAndL4Deferred = daysBetweenTwoDates($activity->log->l4_deffered_at, $activity->log->l2_approved_at);
						$totalDays += $daysBtwL2ApprovedAndL4Deferred;
						$activityReport->duration_between_l2_approved_and_l4_deffered = ($daysBtwL2ApprovedAndL4Deferred > 1) ? ($daysBtwL2ApprovedAndL4Deferred . ' Days') : ($daysBtwL2ApprovedAndL4Deferred . ' Day');
					}

					// DURATION BETWEEN L3 APPROVED AND L4 DEFERRED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_deffered_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_approved_at))) {
						$daysBtwL3ApprovedAndL4Deferred = daysBetweenTwoDates($activity->log->l4_deffered_at, $activity->log->l3_approved_at);
						$totalDays += $daysBtwL3ApprovedAndL4Deferred;
						$activityReport->duration_between_l3_approved_and_l4_deffered = ($daysBtwL3ApprovedAndL4Deferred > 1) ? ($daysBtwL3ApprovedAndL4Deferred . ' Days') : ($daysBtwL3ApprovedAndL4Deferred . ' Day');
					}

					//L4 DEFERRED AT & BY
					$activityReport->l4_deffered_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_deffered_at);
					$activityReport->l4_deffered_by = $activity->log->l4DefferedBy ? $activity->log->l4DefferedBy->username : NULL;

					// DURATION BETWEEN L3 APPROVED AND L4 APPROVED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_approved_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l3_approved_at))) {
						$daysBtwL3ApprovedAndL4Approved = daysBetweenTwoDates($activity->log->l4_approved_at, $activity->log->l3_approved_at);
						$totalDays += $daysBtwL3ApprovedAndL4Approved;
						$activityReport->duration_between_l3_approved_and_l4_approved = ($daysBtwL3ApprovedAndL4Approved > 1) ? ($daysBtwL3ApprovedAndL4Approved . ' Days') : ($daysBtwL3ApprovedAndL4Approved . ' Day');
					}

					//L4 APPROVED AT & BY
					$activityReport->l4_approved_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_approved_at);
					$activityReport->l4_approved_by = $activity->log->l4ApprovedBy ? $activity->log->l4ApprovedBy->username : NULL;

					// DURATION BETWEEN L4 APPROVED AND INVOICE GENERATED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->invoice_generated_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->l4_approved_at))) {
						$daysBtwL4ApprovedAndInvGenerated = daysBetweenTwoDates($activity->log->invoice_generated_at, $activity->log->l4_approved_at);
						$totalDays += $daysBtwL4ApprovedAndInvGenerated;
						$activityReport->duration_between_l4_approved_and_invoice_generated = ($daysBtwL4ApprovedAndInvGenerated > 1) ? ($daysBtwL4ApprovedAndInvGenerated . ' Days') : ($daysBtwL4ApprovedAndInvGenerated . ' Day');
					}

					//INVOICE GENERATED AT & BY
					$activityReport->invoice_generated_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->invoice_generated_at);
					$activityReport->invoice_generated_by = $activity->log->invoiceGeneratedBy ? $activity->log->invoiceGeneratedBy->username : NULL;

					// DURATION BETWEEN INVOICE GENERATED AND AXAPTA GENERATED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->invoice_generated_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->axapta_generated_at))) {
						$daysBtwInvGeneratedAndAxaptaGenerated = daysBetweenTwoDates($activity->log->invoice_generated_at, $activity->log->axapta_generated_at);
						$totalDays += $daysBtwInvGeneratedAndAxaptaGenerated;
						$activityReport->duration_between_invoice_generated_and_axapta_generated = ($daysBtwInvGeneratedAndAxaptaGenerated > 1) ? ($daysBtwInvGeneratedAndAxaptaGenerated . ' Days') : ($daysBtwInvGeneratedAndAxaptaGenerated . ' Day');
					}

					//AXAPTA GENERATED AT & BY
					$activityReport->axapta_generated_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->axapta_generated_at);
					$activityReport->axapta_generated_by = $activity->log->axaptaGeneratedBy ? $activity->log->axaptaGeneratedBy->username : NULL;

					// DURATION BETWEEN AXAPTA GENERATED AND PAYMENT COMPLETED
					if (!empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->axapta_generated_at)) && !empty(checkDateTimeAndReturnValidDateTimeVal($activity->log->payment_completed_at))) {
						$daysBtwAxaptaGeneratedAndPayCompleted = daysBetweenTwoDates($activity->log->axapta_generated_at, $activity->log->payment_completed_at);
						$totalDays += $daysBtwAxaptaGeneratedAndPayCompleted;
						$activityReport->duration_between_axapta_generated_and_payment_completed = ($daysBtwAxaptaGeneratedAndPayCompleted > 1) ? ($daysBtwAxaptaGeneratedAndPayCompleted . ' Days') : ($daysBtwAxaptaGeneratedAndPayCompleted . ' Day');
					}

					//PAYMENT COMPLETED AT
					$activityReport->payment_completed_date = checkDateTimeAndReturnValidDateTimeVal($activity->log->payment_completed_at);
					$activityReport->total_no_of_days = ($totalDays > 1) ? ($totalDays . ' Days') : ($totalDays . ' Day');
				}

				$activityReport->source = $activity->dataSource ? $activity->dataSource->name : NULL;

				//RATE CARD
				if ($activity->rateCard) {
					$activityReport->range_limit = $activity->rateCard->range_limit;
					$activityReport->below_range_price = $activity->rateCard->below_range_price;
					$activityReport->above_range_price = $activity->rateCard->above_range_price;
					$activityReport->waiting_charge_per_hour = $activity->rateCard->waiting_charge_per_hour;
					$activityReport->empty_return_range_price = $activity->rateCard->empty_return_range_price;
					$activityReport->adjustment_type = $activity->rateCard->adjustment_type == 1 ? "Percentage" : "Amount";
					$activityReport->adjustment = $activity->rateCard->adjustment;
				}

				$activityReport->created_year = date('Y', strtotime($activity->created_at));
				$activityReport->created_date = date('Y-m-d', strtotime($activity->created_at));
				$activityReport->deleted_by_id = $activity->deleted_by_id;
				$activityReport->deleted_at = $activity->deleted_at;
				$activityReport->save();

				//UPDATE DATA TO ELK
				$activityReport->elkPush();
			}
		}
	}

	public function elkPush() {
		if (!empty($this->toArray())) {
			$updateFields = [];
			$updateFields = $this->toArray();
			$updateFields['created_at'] = !empty($this->created_at) ? Carbon::parse($this->created_at)->timestamp * 1000 : NULL;
			$updateFields['updated_at'] = !empty($this->updated_at) ? Carbon::parse($this->updated_at)->timestamp * 1000 : NULL;
			$updateFields['case_date'] = !empty($this->case_date) ? Carbon::parse($this->case_date)->timestamp * 1000 : NULL;
			$updateFields['case_submission_closing_date'] = !empty($this->case_submission_closing_date) ? Carbon::parse($this->case_submission_closing_date)->timestamp * 1000 : NULL;
			$updateFields['activity_created_date'] = !empty($this->activity_created_date) ? Carbon::parse($this->activity_created_date)->timestamp * 1000 : NULL;
			$updateFields['asp_reached_date'] = !empty($this->asp_reached_date) ? Carbon::parse($this->asp_reached_date)->timestamp * 1000 : NULL;
			$updateFields['imported_date'] = !empty($this->imported_date) ? Carbon::parse($this->imported_date)->timestamp * 1000 : NULL;
			$updateFields['asp_data_filled_date'] = !empty($this->asp_data_filled_date) ? Carbon::parse($this->asp_data_filled_date)->timestamp * 1000 : NULL;
			$updateFields['l1_deffered_date'] = !empty($this->l1_deffered_date) ? Carbon::parse($this->l1_deffered_date)->timestamp * 1000 : NULL;
			$updateFields['l1_approved_date'] = !empty($this->l1_approved_date) ? Carbon::parse($this->l1_approved_date)->timestamp * 1000 : NULL;
			$updateFields['l2_deffered_date'] = !empty($this->l2_deffered_date) ? Carbon::parse($this->l2_deffered_date)->timestamp * 1000 : NULL;
			$updateFields['l2_approved_date'] = !empty($this->l2_approved_date) ? Carbon::parse($this->l2_approved_date)->timestamp * 1000 : NULL;
			$updateFields['l3_deffered_date'] = !empty($this->l3_deffered_date) ? Carbon::parse($this->l3_deffered_date)->timestamp * 1000 : NULL;
			$updateFields['l3_approved_date'] = !empty($this->l3_approved_date) ? Carbon::parse($this->l3_approved_date)->timestamp * 1000 : NULL;
			$updateFields['l4_deffered_date'] = !empty($this->l4_deffered_date) ? Carbon::parse($this->l4_deffered_date)->timestamp * 1000 : NULL;
			$updateFields['l4_approved_date'] = !empty($this->l4_approved_date) ? Carbon::parse($this->l4_approved_date)->timestamp * 1000 : NULL;
			$updateFields['invoice_generated_date'] = !empty($this->invoice_generated_date) ? Carbon::parse($this->invoice_generated_date)->timestamp * 1000 : NULL;
			$updateFields['axapta_generated_date'] = !empty($this->axapta_generated_date) ? Carbon::parse($this->axapta_generated_date)->timestamp * 1000 : NULL;
			$updateFields['payment_completed_date'] = !empty($this->payment_completed_date) ? Carbon::parse($this->payment_completed_date)->timestamp * 1000 : NULL;
			$updateFields['deleted_at'] = !empty($this->deleted_at) ? Carbon::parse($this->deleted_at)->timestamp * 1000 : NULL;

			$elkParams = [];
			$elkParams['id'] = $this->id;
			$elkParams['index'] = 'activity_reports';
			$elkParams['updatedDoc'] = $updateFields;
			ElkJobQueue::dispatch($elkParams);
		}
	}

}
