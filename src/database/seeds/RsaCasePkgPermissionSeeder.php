<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class RsaCasePkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//ACTIVITIES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'activities',
				'display_name' => 'Activities Menu',
			],

			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'activity-status',
				'display_name' => 'Activities Status',
			],
			[
				'display_order' => 1,
				'parent' => 'activity-status',
				'name' => 'edit-activities',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 2,
				'parent' => 'activity-status',
				'name' => 'delete-activities',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 3,
				'parent' => 'activity-status',
				'name' => 'export-activities',
				'display_name' => 'Export',
			],
			[
				'display_order' => 4,
				'parent' => 'activity-status',
				'name' => 'view-all-activities',
				'display_name' => 'View All',
			],
			[
				'display_order' => 5,
				'parent' => 'activity-status',
				'name' => 'view-mapped-state-activities',
				'display_name' => 'Only Mapped States',
			],
			[
				'display_order' => 6,
				'parent' => 'activity-status',
				'name' => 'view-own-activities',
				'display_name' => 'View Only Own',
			],
			[
				'display_order' => 7,
				'parent' => 'activity-status',
				'name' => 'view-cc-details',
				'display_name' => 'View CC Details',
			],
			[
				'display_order' => 8,
				'parent' => 'activity-status',
				'name' => 'backstep-activity',
				'display_name' => 'Backstep Activity',
			],
			[
				'display_order' => 9,
				'parent' => 'activity-status',
				'name' => 'select-case-submission-closing-date-activity',
				'display_name' => 'Select Case Submission Closing Date',
			],
			[
				'display_order' => 10,
				'parent' => 'activity-status',
				'name' => 'export-own-activities',
				'display_name' => 'Export Own',
			],
			[
				'display_order' => 11,
				'parent' => 'activity-status',
				'name' => 'towing-images-required-for-activities',
				'display_name' => 'Towing Images Required Option',
			],
			[
				'display_order' => 12,
				'parent' => 'activity-status',
				'name' => 'display-asp-number-in-activities',
				'display_name' => 'Display ASP Number',
			],
			[
				'display_order' => 13,
				'parent' => 'activity-status',
				'name' => 'own-rm-asp-activities',
				'display_name' => 'Own RM ASPs',
			],
			[
				'display_order' => 14,
				'parent' => 'activity-status',
				'name' => 'export-own-rm-asp-activities',
				'display_name' => 'Export Own RM ASPs',
			],
			[
				'display_order' => 15,
				'parent' => 'activity-status',
				'name' => 'own-zm-asp-activities',
				'display_name' => 'Own ZM ASPs',
			],
			[
				'display_order' => 16,
				'parent' => 'activity-status',
				'name' => 'export-own-zm-asp-activities',
				'display_name' => 'Export Own ZM ASPs',
			],
			[
				'display_order' => 17,
				'parent' => 'activity-status',
				'name' => 'own-nm-asp-activities',
				'display_name' => 'Own NM ASPs',
			],
			[
				'display_order' => 18,
				'parent' => 'activity-status',
				'name' => 'export-own-nm-asp-activities',
				'display_name' => 'Export Own NM ASPs',
			],
			[
				'display_order' => 19,
				'parent' => 'activity-status',
				'name' => 'move-activity-to-not-eligible-payout',
				'display_name' => 'Move to Not Eligible For Payout',
			],

			//ACTIVITIES VERIFICATION
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'activity-verification',
				'display_name' => 'Activity Verification',
			],
			[
				'display_order' => 6,
				'parent' => 'activity-verification',
				'name' => 'verify-all-activities',
				'display_name' => 'Verify All',
			],
			[
				'display_order' => 6,
				'parent' => 'activity-verification',
				'name' => 'verify-mapped-activities',
				'display_name' => 'Only Mapped',
			],

			// //BATCH GENERATION
			// [
			// 	'display_order' => 99,
			// 	'parent' => null,
			// 	'name' => 'invoices-batch-generation',
			// 	'display_name' => 'Batch Generation',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'invoices-batch-generation',
			// 	'name' => 'view-all-invoices-batch-generation',
			// 	'display_name' => 'View All',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'invoices-batch-generation',
			// 	'name' => 'view-only-state-mapped-invoices-batch-generation',
			// 	'display_name' => 'Only State Mapped',
			// ],

			// //UNPAID BATCHES
			// [
			// 	'display_order' => 99,
			// 	'parent' => null,
			// 	'name' => 'asp-unpaid-batches',
			// 	'display_name' => 'ASP Unpaid Batches',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-unpaid-batches',
			// 	'name' => 'view-all-asp-unpaid-batches',
			// 	'display_name' => 'View All',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-unpaid-batches',
			// 	'name' => 'view-only-state-mapped-asp-unpaid-batches',
			// 	'display_name' => 'Only State Mapped',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-unpaid-batches',
			// 	'name' => 'view-own-asp-unpaid-batches',
			// 	'display_name' => 'View Own',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-unpaid-batches',
			// 	'name' => 'export-asp-unpaid-batches',
			// 	'display_name' => 'Export',
			// ],

			// //PAYMENT INPROGRESS BATCHES
			// [
			// 	'display_order' => 99,
			// 	'parent' => null,
			// 	'name' => 'asp-payment-inprogress-batches',
			// 	'display_name' => 'ASP Payment Inprogress Batches',
			// ],

			// //PAID BATCHES
			// [
			// 	'display_order' => 99,
			// 	'parent' => null,
			// 	'name' => 'asp-paid-batches',
			// 	'display_name' => 'ASP Paid Batches',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-paid-batches',
			// 	'name' => 'view-all-asp-paid-batches',
			// 	'display_name' => 'View All',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-paid-batches',
			// 	'name' => 'view-only-state-mapped-asp-paid-batches',
			// 	'display_name' => 'Only State Mapped',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-paid-batches',
			// 	'name' => 'view-own-asp-paid-batches',
			// 	'display_name' => 'View Own',
			// ],
			// [
			// 	'display_order' => 6,
			// 	'parent' => 'asp-paid-batches',
			// 	'name' => 'view-paid-batches-payment-info',
			// 	'display_name' => 'View Payment Info',
			// ],

			//ASP NEW ACTIVITIES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-new-activities',
				'display_name' => 'ASP New Activities',
			],

			//ASP ACTIVITY SEARCH
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-activity-search',
				'display_name' => 'ASP Activity Search',
			],
			[
				'display_order' => 1,
				'parent' => 'asp-activity-search',
				'name' => 'all-asp-activity-search',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'asp-activity-search',
				'name' => 'mapped-state-asp-activity-search',
				'display_name' => 'Only Mapped States',
			],
			[
				'display_order' => 3,
				'parent' => 'asp-activity-search',
				'name' => 'own-asp-activity-search',
				'display_name' => 'View Only Own',
			],
			[
				'display_order' => 4,
				'parent' => 'asp-activity-search',
				'name' => 'own-rm-asp-activity-search',
				'display_name' => 'Own RM ASPs',
			],
			[
				'display_order' => 5,
				'parent' => 'asp-activity-search',
				'name' => 'own-zm-asp-activity-search',
				'display_name' => 'Own ZM ASPs',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-activity-search',
				'name' => 'own-nm-asp-activity-search',
				'display_name' => 'Own NM ASPs',
			],

			//ASP DEFERRED ACTIVITIES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-deferred-activities',
				'display_name' => 'ASP Deferred Activities',
			],

			//ASP APPROVED ACTIVITIES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-approved-activities',
				'display_name' => 'ASP Approved Activities',
			],

			//ASP INVOICES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-invoices',
				'display_name' => 'ASP Invoices Menu',
			],
			//UNPAID
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-unpaid-invoices',
				'display_name' => 'ASP Unpaid Invoices',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-unpaid-invoices',
				'name' => 'view-all-asp-unpaid-invoices',
				'display_name' => 'View All',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-unpaid-invoices',
				'name' => 'view-only-state-asp-unpaid-invoices',
				'display_name' => 'Only State Mapped',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-unpaid-invoices',
				'name' => 'view-only-own-asp-unpaid-invoices',
				'display_name' => 'Only Own',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-unpaid-invoices',
				'name' => 'export-asp-unpaid-invoices',
				'display_name' => 'Export',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-unpaid-invoices',
				'name' => 'cancel-asp-unpaid-invoices',
				'display_name' => 'Cancel',
			],

			//PAYMENT INPROGRESS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-payment-inprogress-invoices',
				'display_name' => 'ASP Payment Inprogress Invoices',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-payment-inprogress-invoices',
				'name' => 'view-all-asp-payment-inprogress-invoices',
				'display_name' => 'View All',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-payment-inprogress-invoices',
				'name' => 'view-only-state-asp-payment-inprogress-invoices',
				'display_name' => 'Only State Mapped',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-payment-inprogress-invoices',
				'name' => 'view-only-own-asp-payment-inprogress-invoices',
				'display_name' => 'Only Own',
			],

			//PAID
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-paid-invoices',
				'display_name' => 'ASP Paid Invoices',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-paid-invoices',
				'name' => 'view-all-asp-paid-invoices',
				'display_name' => 'View All',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-paid-invoices',
				'name' => 'view-only-state-asp-paid-invoices',
				'display_name' => 'Only State Mapped',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-paid-invoices',
				'name' => 'view-only-own-asp-paid-invoices',
				'display_name' => 'Only Own',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-paid-invoices',
				'name' => 'view-invoice-payment-info',
				'display_name' => 'View Payment Info',
			],

			//ASP INVOICE ORACLE EXPORT
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-invoice-oracle-export',
				'display_name' => 'ASP Invoices Oracle Export',
			],

			//ADMIN DASHBOARD
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'admin-dashboard',
				'display_name' => 'Admin Dashboard',
			],

			//BO DASHBOARD
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'bo-dashboard',
				'display_name' => 'BO Dashboard',
			],

			//FINANCE DASHBOARD
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'finance-dashboard',
				'display_name' => 'Finance Dashboard',
			],

			//RM DASHBOARD
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'rm-dashboard',
				'display_name' => 'RM Dashboard',
			],

			//PROVISION APPROVAL
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'provision-approval',
				'display_name' => 'Provision Approval',
			],
			[
				'display_order' => 1,
				'parent' => 'provision-approval',
				'name' => 'edit-provision-approval',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 2,
				'parent' => 'provision-approval',
				'name' => 'export-provision-approval',
				'display_name' => 'Export',
			],
			[
				'display_order' => 3,
				'parent' => 'provision-approval',
				'name' => 'view-all-provision-approval',
				'display_name' => 'View All',
			],
			[
				'display_order' => 4,
				'parent' => 'provision-approval',
				'name' => 'view-mapped-state-provision-approval',
				'display_name' => 'Only Mapped States',
			],

			//DEALER WALLET
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'dealer-wallet',
				'display_name' => 'Dealer Wallet',
			],
			[
				'display_order' => 1,
				'parent' => 'dealer-wallet',
				'name' => 'view-dealer-wallet',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'dealer-wallet',
				'name' => 'self-topup-dealer-wallet',
				'display_name' => 'Topup',
			],
			[
				'display_order' => 3,
				'parent' => 'dealer-wallet',
				'name' => 'own-dealer-wallet',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 4,
				'parent' => 'dealer-wallet',
				'name' => 'all-dealer-wallet',
				'display_name' => 'All',
			],
			[
				'display_order' => 5,
				'parent' => 'dealer-wallet',
				'name' => 'export-own-dealer-wallet',
				'display_name' => 'Export Own',
			],
			[
				'display_order' => 6,
				'parent' => 'dealer-wallet',
				'name' => 'export-all-dealer-wallet',
				'display_name' => 'Export All',
			],
			[
				'display_order' => 7,
				'parent' => 'dealer-wallet',
				'name' => 'own-dealers-dealer-wallet',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 8,
				'parent' => 'dealer-wallet',
				'name' => 'export-own-dealers-dealer-wallet',
				'display_name' => 'Export Own Dealers',
			],
			[
				'display_order' => 9,
				'parent' => 'dealer-wallet',
				'name' => 'check-staus-self-topup-dealer-wallet',
				'display_name' => 'Topup check status',
			],
			[
				'display_order' => 10,
				'parent' => 'dealer-wallet',
				'name' => 'own-dealers-topup-dealer-wallet',
				'display_name' => 'Own Dealers Topup',
			],

			// //TOPUP DEALER WALLET
			// [
			// 	'display_order' => 99,
			// 	'parent' => null,
			// 	'name' => 'topup-dealer-wallet',
			// 	'display_name' => 'Topup Dealer Wallet',
			// ],

			//MEMBERSHIPS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'membership',
				'display_name' => 'Memberships',
			],
			[
				'display_order' => 1,
				'parent' => 'membership',
				'name' => 'add-membership',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'membership',
				'name' => 'edit-membership',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'membership',
				'name' => 'delete-membership',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 4,
				'parent' => 'membership',
				'name' => 'view-membership',
				'display_name' => 'View',
			],
			[
				'display_order' => 5,
				'parent' => 'membership',
				'name' => 'own-membership',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 6,
				'parent' => 'membership',
				'name' => 'own-dealer-membership',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 7,
				'parent' => 'membership',
				'name' => 'own-client-dealers-membership',
				'display_name' => 'Own Client Dealers',
			],
			[
				'display_order' => 8,
				'parent' => 'membership',
				'name' => 'all-membership',
				'display_name' => 'All',
			],
			[
				'display_order' => 9,
				'parent' => 'membership',
				'name' => 'dealer-report-membership',
				'display_name' => 'Own Dealer Report',
			],
			[
				'display_order' => 10,
				'parent' => 'membership',
				'name' => 'admin-report-membership',
				'display_name' => 'Admin Report',
			],
			[
				'display_order' => 11,
				'parent' => 'membership',
				'name' => 'general-report-membership',
				'display_name' => 'General Report - All',
			],
			[
				'display_order' => 12,
				'parent' => 'membership',
				'name' => 'own-general-report-membership',
				'display_name' => 'General Report - Own Only',
			],
			[
				'display_order' => 13,
				'parent' => 'membership',
				'name' => 'create-on-behalf-of-dealer-employee',
				'display_name' => 'Create on behalf of a Dealer Employee',
			],
			[
				'display_order' => 14,
				'parent' => 'membership',
				'name' => 'import-membership',
				'display_name' => 'Import',
			],
			[
				'display_order' => 15,
				'parent' => 'membership',
				'name' => 'initiate-cancel-membership',
				'display_name' => 'Initiate Cancellation',
			],
			[
				'display_order' => 16,
				'parent' => 'membership',
				'name' => 'own-client-dealers-report-membership',
				'display_name' => 'Own Client Dealers Report',
			],
			[
				'display_order' => 17,
				'parent' => 'membership',
				'name' => 'own-dealers-membership',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 18,
				'parent' => 'membership',
				'name' => 'dealers-report-membership',
				'display_name' => 'Own Dealers Report',
			],
			[
				'display_order' => 19,
				'parent' => 'membership',
				'name' => 'own-client-admin-report-membership',
				'display_name' => 'Own Client Admin Report',
			],
			[
				'display_order' => 20,
				'parent' => 'membership',
				'name' => 'own-rm-dealers-membership',
				'display_name' => 'Own RM Dealers',
			],
			[
				'display_order' => 21,
				'parent' => 'membership',
				'name' => 'rm-dealers-report-membership',
				'display_name' => 'Own RM Dealers Report',
			],
			[
				'display_order' => 22,
				'parent' => 'membership',
				'name' => 'import-post-paid-membership',
				'display_name' => 'Import Post Paid Membership',
			],
			[
				'display_order' => 23,
				'parent' => 'membership',
				'name' => 'own-zm-dealers-membership',
				'display_name' => 'Own ZM Dealers',
			],
			[
				'display_order' => 24,
				'parent' => 'membership',
				'name' => 'zm-dealers-report-membership',
				'display_name' => 'Own ZM Dealers Report',
			],
			[
				'display_order' => 25,
				'parent' => 'membership',
				'name' => 'own-nm-dealers-membership',
				'display_name' => 'Own NM Dealers',
			],
			[
				'display_order' => 26,
				'parent' => 'membership',
				'name' => 'nm-dealers-report-membership',
				'display_name' => 'Own NM Dealers Report',
			],
			[
				'display_order' => 27,
				'parent' => 'membership',
				'name' => 'update-membership',
				'display_name' => 'Update',
			],
			[
				'display_order' => 28,
				'parent' => 'membership',
				'name' => 'import-dealer-membership',
				'display_name' => 'Dealer Import',
			],

			//Dealer Invoice Menu
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'dealer-invoice-menu',
				'display_name' => 'Dealer Invoice Menu',
			],
			//DEALER COMMISSION INVOICE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'dealer-commission-invoice',
				'display_name' => 'Dealer Commission Invoices',
			],
			[
				'display_order' => 1,
				'parent' => 'dealer-commission-invoice',
				'name' => 'create-dealer-commission-invoice',
				'display_name' => 'Create',
			],
			[
				'display_order' => 2,
				'parent' => 'dealer-commission-invoice',
				'name' => 'view-dealer-commission-invoice',
				'display_name' => 'View',
			],
			[
				'display_order' => 3,
				'parent' => 'dealer-commission-invoice',
				'name' => 'own-dealer-commission-invoice',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 4,
				'parent' => 'dealer-commission-invoice',
				'name' => 'own-only-dealer-commission-invoice',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 5,
				'parent' => 'dealer-commission-invoice',
				'name' => 'all-dealer-commission-invoice',
				'display_name' => 'All',
			],
			[
				'display_order' => 6,
				'parent' => 'dealer-commission-invoice',
				'name' => 'approve-dealer-commission-invoice',
				'display_name' => 'Approve',
			],
			[
				'display_order' => 7,
				'parent' => 'dealer-commission-invoice',
				'name' => 'finance-export-dealer-commission-invoice',
				'display_name' => 'Finance Export All',
			],
			[
				'display_order' => 8,
				'parent' => 'dealer-commission-invoice',
				'name' => 'finance-export-own-only-dealer-commission-invoice',
				'display_name' => 'Finance Export Own Only',
			],
			[
				'display_order' => 9,
				'parent' => 'dealer-commission-invoice',
				'name' => 'finance-export-own-dealer-commission-invoice',
				'display_name' => 'Finance Export Own Dealer',
			],
			[
				'display_order' => 10,
				'parent' => 'dealer-commission-invoice',
				'name' => 'own-dealers-commission-invoice',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 11,
				'parent' => 'dealer-commission-invoice',
				'name' => 'finance-export-own-dealers-commission-invoice',
				'display_name' => 'Finance Export Own Dealers',
			],
			[
				'display_order' => 12,
				'parent' => 'dealer-commission-invoice',
				'name' => 'unbilled-dealer-commission-invoice',
				'display_name' => 'Unbilled Dealer Commission Invoice',
			],

			//DEALER COMMISSION CREDIT NOTE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'dealer-commission-credit-note',
				'display_name' => 'Dealer Commission Credit Note',
			],
			[
				'display_order' => 1,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'view-dealer-commission-credit-note',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'own-only-dealer-commission-credit-note',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 3,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'own-dealer-commission-credit-note',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 4,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'own-dealers-commission-credit-note',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 5,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'all-dealer-commission-credit-note',
				'display_name' => 'All',
			],
			[
				'display_order' => 6,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'finance-export-dealer-commission-credit-note',
				'display_name' => 'Finance Export All',
			],
			[
				'display_order' => 7,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'finance-export-own-only-dealer-commission-credit-note',
				'display_name' => 'Finance Export Own Only',
			],
			[
				'display_order' => 8,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'finance-export-own-dealer-commission-credit-note',
				'display_name' => 'Finance Export Own Dealer',
			],
			[
				'display_order' => 9,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'finance-export-own-dealers-commission-credit-note',
				'display_name' => 'Finance Export Own Dealers',
			],
			[
				'display_order' => 10,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'unbilled-dealer-commission-credit-note',
				'display_name' => 'Unbilled Dealer Commission Credit Note',
			],
			[
				'display_order' => 11,
				'parent' => 'dealer-commission-credit-note',
				'name' => 'create-dealer-commission-credit-note',
				'display_name' => 'Create Dealer Commission Credit Note',
			],

			//TAX CODES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'tax-code',
				'display_name' => 'Tax Codes',
			],
			[
				'display_order' => 1,
				'parent' => 'tax-code',
				'name' => 'add-tax-code',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'tax-code',
				'name' => 'edit-tax-code',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'tax-code',
				'name' => 'delete-tax-code',
				'display_name' => 'Delete',
			],

			//API LOGS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'api-log',
				'display_name' => 'API Logs',
			],
			[
				'display_order' => 1,
				'parent' => 'api-log',
				'name' => 'view-api-log',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'api-log',
				'name' => 'export-api-log',
				'display_name' => 'Export',
			],

			//DEALER MEMBERSHIP INVOICE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'dealer-membership-invoice',
				'display_name' => 'Dealer Membership Invoices',
			],
			[
				'display_order' => 1,
				'parent' => 'dealer-membership-invoice',
				'name' => 'view-dealer-membership-invoice',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'dealer-membership-invoice',
				'name' => 'own-dealer-membership-invoice',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 3,
				'parent' => 'dealer-membership-invoice',
				'name' => 'all-dealer-membership-invoice',
				'display_name' => 'All',
			],
			[
				'display_order' => 4,
				'parent' => 'dealer-membership-invoice',
				'name' => 'finance-export-dealer-membership-invoice',
				'display_name' => 'Finance Export All',
			],
			[
				'display_order' => 5,
				'parent' => 'dealer-membership-invoice',
				'name' => 'download-dealer-membership-einvoice',
				'display_name' => 'Download E-Invoice',
			],
			[
				'display_order' => 6,
				'parent' => 'dealer-membership-invoice',
				'name' => 'cancel-dealer-membership-einvoice',
				'display_name' => 'Cancel E-Invoice',
			],
			[
				'display_order' => 7,
				'parent' => 'dealer-membership-invoice',
				'name' => 'own-dealers-membership-invoice',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 8,
				'parent' => 'dealer-membership-invoice',
				'name' => 'finance-export-own-only-dealer-membership-invoice',
				'display_name' => 'Finance Export Own',
			],
			[
				'display_order' => 9,
				'parent' => 'dealer-membership-invoice',
				'name' => 'finance-export-own-dealers-membership-invoice',
				'display_name' => 'Finance Export Own Dealers',
			],

			//UNBILLED MEMBERSHIP INVOICE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'unbilled-invoice',
				'display_name' => 'Unbilled Invoices',
			],
			[
				'display_order' => 1,
				'parent' => 'unbilled-invoice',
				'name' => 'create-billed-invoice',
				'display_name' => 'Create Invoice',
			],
			[
				'display_order' => 2,
				'parent' => 'unbilled-invoice',
				'name' => 'preview-unbilled-invoice',
				'display_name' => 'Preview',
			],

			//MEMBERSHIP EXPIRY
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'renewal-membership',
				'display_name' => 'Renewal Memberships',
			],
			[
				'display_order' => 1,
				'parent' => 'renewal-membership',
				'name' => 'own-renewal-membership',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 2,
				'parent' => 'renewal-membership',
				'name' => 'own-dealer-renewal-membership',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 3,
				'parent' => 'renewal-membership',
				'name' => 'all-renewal-membership',
				'display_name' => 'All',
			],

			//VEHICLE SEARCH
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'vehicle-search',
				'display_name' => 'Vehicle Search',
			],
			[
				'display_order' => 1,
				'parent' => 'vehicle-search',
				'name' => 'own-vehicle-search',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 2,
				'parent' => 'vehicle-search',
				'name' => 'own-dealer-vehicle-search',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 3,
				'parent' => 'vehicle-search',
				'name' => 'all-vehicle-search',
				'display_name' => 'All',
			],
			[
				'display_order' => 4,
				'parent' => 'vehicle-search',
				'name' => 'own-client-vehicle-search',
				'display_name' => 'Own Client',
			],
			[
				'display_order' => 5,
				'parent' => 'vehicle-search',
				'name' => 'own-client-dealers-vehicle-search',
				'display_name' => 'Own Client Dealers',
			],

			//ASP INVOICES - OLD PROCESS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp_invoices',
				'display_name' => 'ASP Invoices',
			],
			[
				'display_order' => 1,
				'parent' => 'asp_invoices',
				'name' => 'all_asp_invoices',
				'display_name' => 'All',
			],
			[
				'display_order' => 2,
				'parent' => 'asp_invoices',
				'name' => 'state_mapped_asp_invoices',
				'display_name' => 'Only State Mapped',
			],
			[
				'display_order' => 3,
				'parent' => 'asp_invoices',
				'name' => 'own_asp_invoices',
				'display_name' => 'Only Own',
			],

			//MEMBERSHIP CANCELLATION
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'membership-cancellation',
				'display_name' => 'Membership Cancellation',
			],
			[
				'display_order' => 1,
				'parent' => 'membership-cancellation',
				'name' => 'view-membership-cancellation',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'membership-cancellation',
				'name' => 'approve-membership-cancellation',
				'display_name' => 'Approve',
			],
			[
				'display_order' => 3,
				'parent' => 'membership-cancellation',
				'name' => 'reject-membership-cancellation',
				'display_name' => 'Reject',
			],

			//MEMBERSHIP CANCELLATION INVOICE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'membership-cancellation-invoice',
				'display_name' => 'Membership Cancellation Invoice',
			],
			[
				'display_order' => 1,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'view-membership-cancellation-invoice',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'own-membership-cancellation-invoice',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 3,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'all-membership-cancellation-invoice',
				'display_name' => 'All',
			],
			[
				'display_order' => 4,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'own-dealers-membership-cancellation-invoice',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 5,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'finance-export-membership-cancellation-invoice',
				'display_name' => 'Finance Export All',
			],
			[
				'display_order' => 6,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'finance-export-own-only-membership-cancellation-invoice',
				'display_name' => 'Finance Export Own Only',
			],
			[
				'display_order' => 6,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'finance-export-own-dealers-membership-cancellation-invoice',
				'display_name' => 'Finance Export Own Dealers',
			],
			[
				'display_order' => 7,
				'parent' => 'membership-cancellation-invoice',
				'name' => 'download-cancellation-einvoice',
				'display_name' => 'Download E-Invoice',
			],

			//MEMBERSHIP MENU
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'membership-menu',
				'display_name' => 'Membership Menu',
			],

			//MEMBERSHIP CUSTOMER INVOICE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'membership-customer-invoice',
				'display_name' => 'Membership Customer Invoice',
			],
			[
				'display_order' => 1,
				'parent' => 'membership-customer-invoice',
				'name' => 'view-membership-customer-invoice',
				'display_name' => 'View',
			],
			[
				'display_order' => 2,
				'parent' => 'membership-customer-invoice',
				'name' => 'own-membership-customer-invoice',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 3,
				'parent' => 'membership-customer-invoice',
				'name' => 'own-dealer-membership-customer-invoice',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 4,
				'parent' => 'membership-customer-invoice',
				'name' => 'all-membership-customer-invoice',
				'display_name' => 'All',
			],
			[
				'display_order' => 5,
				'parent' => 'membership-customer-invoice',
				'name' => 'finance-export-membership-customer-invoice',
				'display_name' => 'Finance Export All',
			],
			[
				'display_order' => 6,
				'parent' => 'membership-customer-invoice',
				'name' => 'own-dealers-membership-customer-invoice',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 7,
				'parent' => 'membership-customer-invoice',
				'name' => 'finance-export-own-only-membership-customer-invoice',
				'display_name' => 'Finance Export Own Only',
			],
			[
				'display_order' => 8,
				'parent' => 'membership-customer-invoice',
				'name' => 'finance-export-own-dealer-membership-customer-invoice',
				'display_name' => 'Finance Export Own Dealer',
			],
			[
				'display_order' => 9,
				'parent' => 'membership-customer-invoice',
				'name' => 'finance-export-own-dealers-membership-customer-invoice',
				'display_name' => 'Finance Export Own Dealers',
			],
			[
				'display_order' => 10,
				'parent' => 'membership-customer-invoice',
				'name' => 'download-customer-einvoice',
				'display_name' => 'Download E-Invoice',
			],

			//CREDIT DEBIT NOTE
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'credit-debit-note',
				'display_name' => 'Credit Debit Note',
			],
			[
				'display_order' => 1,
				'parent' => 'credit-debit-note',
				'name' => 'add-credit-debit-note',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'credit-debit-note',
				'name' => 'download-credit-debit-note-einvoice',
				'display_name' => 'Download E-Invoice',
			],
			[
				'display_order' => 3,
				'parent' => 'credit-debit-note',
				'name' => 'download-credit-debit-note-invoice',
				'display_name' => 'Download Normal Invoice',
			],
			[
				'display_order' => 4,
				'parent' => 'credit-debit-note',
				'name' => 'cancel-credit-debit-note-einvoice',
				'display_name' => 'Cancel E-Invoice',
			],

			//RELEASE ONHOLD CASES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'release-onhold-case',
				'display_name' => 'Release OnHold Case',
			],

			//STATEMENT OF ACCOUNT EXPORT
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'statement-of-account',
				'display_name' => 'Statement of Account',
			],

			//CASH SALE
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'cash-sale',
				'display_name' => 'Cash Sale',
			],
			[
				'display_order' => 1,
				'parent' => 'cash-sale',
				'name' => 'download-cash-sale-einvoice',
				'display_name' => 'Download E-Invoice',
			],
			[
				'display_order' => 2,
				'parent' => 'cash-sale',
				'name' => 'download-cash-sale-invoice',
				'display_name' => 'Download Normal Invoice',
			],
			[
				'display_order' => 3,
				'parent' => 'cash-sale',
				'name' => 'cancel-cash-sale-einvoice',
				'display_name' => 'Cancel E-Invoice',
			],
			[
				'display_order' => 4,
				'parent' => 'cash-sale',
				'name' => 'create-cash-sale-credit-note',
				'display_name' => 'Create Credit Note',
			],

			//DIRECT SALE FTP
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'sales_data_upload',
				'display_name' => 'Sales Data Upload',
			],
			[
				'display_order' => 1,
				'parent' => 'sales_data_upload',
				'name' => 'direct_sale_ftp_file_log',
				'display_name' => 'Direct sale FTP log',
			],
			[
				'display_order' => 2,
				'parent' => 'sales_data_upload',
				'name' => 'direct_sale_ftp_file_transaction',
				'display_name' => 'Direct sale FTP file transaction',
			],
			[
				'display_order' => 3,
				'parent' => 'sales_data_upload',
				'name' => 'direct_sale_ftp_folders',
				'display_name' => 'Direct sale FTP folders',
			],
			[
				'display_order' => 4,
				'parent' => 'sales_data_upload',
				'name' => 'failed-direct-sale-transactions-crm-repush',
				'display_name' => 'Failed Direct sale transactions CRM Re-push',
			],
			[
				'display_order' => 5,
				'parent' => 'sales_data_upload',
				'name' => 'edit-failed-direct-sale-transaction',
				'display_name' => 'Edit failed direct sale transaction',
			],

			//QUERY LOG
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'query-log',
				'display_name' => 'Query Log',
			],
			[
				'display_order' => 1,
				'parent' => 'query-log',
				'name' => 'query-log-export',
				'display_name' => 'Export',
			],

			//NON MEMBERSHIP MENU
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'non-membership-menu',
				'display_name' => 'Non Membership Menu',
			],

			//NON MEMBERSHIPS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'non-membership',
				'display_name' => 'Non Memberships',
			],
			[
				'display_order' => 1,
				'parent' => 'non-membership',
				'name' => 'add-non-membership',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'non-membership',
				'name' => 'edit-non-membership',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 4,
				'parent' => 'non-membership',
				'name' => 'view-non-membership',
				'display_name' => 'View',
			],
			[
				'display_order' => 5,
				'parent' => 'non-membership',
				'name' => 'own-non-membership',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 8,
				'parent' => 'non-membership',
				'name' => 'all-non-membership',
				'display_name' => 'All',
			],
			[
				'display_order' => 10,
				'parent' => 'non-membership',
				'name' => 'admin-report-non-membership',
				'display_name' => 'Admin Report',
			],
			[
				'display_order' => 11,
				'parent' => 'non-membership',
				'name' => 'general-report-non-membership',
				'display_name' => 'General Report - All',
			],
			[
				'display_order' => 12,
				'parent' => 'non-membership',
				'name' => 'own-general-report-non-membership',
				'display_name' => 'General Report - Own Only',
			],
			[
				'display_order' => 15,
				'parent' => 'non-membership',
				'name' => 'initiate-cancel-non-membership',
				'display_name' => 'Initiate Cancellation',
			],
			[
				'display_order' => 15,
				'parent' => 'non-membership',
				'name' => 'send-customer-invoice-to-customer-for-non-membership',
				'display_name' => 'Send Customer Invoice To Customer',
			],

			//LOCATOR
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'locator',
				'display_name' => 'Locator',
			],
			[
				'display_order' => 1,
				'parent' => 'locator',
				'name' => 'own-client-locator',
				'display_name' => 'Own Client',
			],
			[
				'display_order' => 1,
				'parent' => 'locator',
				'name' => 'all-locator',
				'display_name' => 'All',
			],

			//DISPLAY LATEST UPDATES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'display-latest-updates',
				'display_name' => 'Display Latest Updates',
			],

			//LOG REPORT MENU
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'log-menu',
				'display_name' => 'Log Menu',
			],

			//USER LOG
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'user-log',
				'display_name' => 'User Log',
			],
			[
				'display_order' => 1,
				'parent' => 'user-log',
				'name' => 'user-log-export',
				'display_name' => 'Export',
			],

			//LAST LOGIN LOG
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'last-login-log',
				'display_name' => 'Last Login Log',
			],
			[
				'display_order' => 1,
				'parent' => 'last-login-log',
				'name' => 'last-login-log-export',
				'display_name' => 'Export',
			],

			//DASHBOARD MAIL LOG
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'elk-mail-log',
				'display_name' => 'Dashboard Mail Log',
			],

			//UNBILLED AUTO INVOICE CRON LOG
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'unbilled-auto-invoice-cron-log',
				'display_name' => 'Unbilled Auto Invoice Cron Log',
			],

			//DISCOUNT REASONS
			[
				'display_order' => 999,
				'parent' => null,
				'name' => 'discount-reason',
				'display_name' => 'Discount Reason',
			],
			[
				'display_order' => 1,
				'parent' => 'discount-reason',
				'name' => 'add-discount-reason',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'discount-reason',
				'name' => 'edit-discount-reason',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'discount-reason',
				'name' => 'delete-discount-reason',
				'display_name' => 'Delete',
			],

			//TDS LOGS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'tds-log',
				'display_name' => 'TDS',
			],
			[
				'display_order' => 1,
				'parent' => 'tds-log',
				'name' => 'add-tds-log',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'tds-log',
				'name' => 'edit-tds-log',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'tds-log',
				'name' => 'download-tds-log-attachment',
				'display_name' => 'Download Attachment',
			],
			[
				'display_order' => 4,
				'parent' => 'tds-log',
				'name' => 'approve-tds-log',
				'display_name' => 'Approve',
			],
			[
				'display_order' => 5,
				'parent' => 'tds-log',
				'name' => 'reject-tds-log',
				'display_name' => 'Reject',
			],
			[
				'display_order' => 6,
				'parent' => 'tds-log',
				'name' => 'own-tds-log',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 7,
				'parent' => 'tds-log',
				'name' => 'own-dealers-tds-log',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 8,
				'parent' => 'tds-log',
				'name' => 'all-finance-tds-log',
				'display_name' => 'Finance All',
			],
			[
				'display_order' => 9,
				'parent' => 'tds-log',
				'name' => 'all-admin-tds-log',
				'display_name' => 'Admin All',
			],
			[
				'display_order' => 10,
				'parent' => 'tds-log',
				'name' => 'view-tds-log',
				'display_name' => 'View',
			],
			[
				'display_order' => 11,
				'parent' => 'tds-log',
				'name' => 'export-own-tds-log',
				'display_name' => 'Finance Export Own',
			],
			[
				'display_order' => 12,
				'parent' => 'tds-log',
				'name' => 'export-own-dealers-tds-log',
				'display_name' => 'Finance Export Own Dealers',
			],
			[
				'display_order' => 13,
				'parent' => 'tds-log',
				'name' => 'export-all-finance-tds-log',
				'display_name' => 'Finance Export All',
			],
			[
				'display_order' => 14,
				'parent' => 'tds-log',
				'name' => 'export-all-admin-tds-log',
				'display_name' => 'Admin Export All',
			],
			[
				'display_order' => 15,
				'parent' => 'tds-log',
				'name' => 'delete-tds-log',
				'display_name' => 'Delete',
			],

			//ACTIVITY WHATSAPP LOGS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'activity-whatsapp-log',
				'display_name' => 'Activity WhatsApp Log',
			],
			[
				'display_order' => 1,
				'parent' => 'activity-whatsapp-log',
				'name' => 'export-activity-whatsapp-log',
				'display_name' => 'Export',
			],

			//QMS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'qms',
				'display_name' => 'QMS',
			],
			[
				'display_order' => 1,
				'parent' => 'qms',
				'name' => 'add-qms',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'qms',
				'name' => 'edit-qms',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'qms',
				'name' => 'accept-qms',
				'display_name' => 'Accept',
			],
			[
				'display_order' => 4,
				'parent' => 'qms',
				'name' => 'delete-qms',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 5,
				'parent' => 'qms',
				'name' => 'own-only-qms',
				'display_name' => 'Own Only',
			],
			[
				'display_order' => 6,
				'parent' => 'qms',
				'name' => 'own-rm-asps-qms',
				'display_name' => 'Own RM ASPs',
			],
			[
				'display_order' => 7,
				'parent' => 'qms',
				'name' => 'own-zm-asps-qms',
				'display_name' => 'Own ZM ASPs',
			],
			[
				'display_order' => 8,
				'parent' => 'qms',
				'name' => 'own-nm-asps-qms',
				'display_name' => 'Own NM ASPs',
			],
			[
				'display_order' => 9,
				'parent' => 'qms',
				'name' => 'own-rm-dealers-qms',
				'display_name' => 'Own RM Dealers',
			],
			[
				'display_order' => 10,
				'parent' => 'qms',
				'name' => 'own-zm-dealers-qms',
				'display_name' => 'Own ZM Dealers',
			],
			[
				'display_order' => 11,
				'parent' => 'qms',
				'name' => 'own-nm-dealers-qms',
				'display_name' => 'Own NM Dealers',
			],
			[
				'display_order' => 12,
				'parent' => 'qms',
				'name' => 'own-dealer-qms',
				'display_name' => 'Own Dealer',
			],
			[
				'display_order' => 13,
				'parent' => 'qms',
				'name' => 'own-dealers-qms',
				'display_name' => 'Own Dealers',
			],
			[
				'display_order' => 14,
				'parent' => 'qms',
				'name' => 'all-qms',
				'display_name' => 'All',
			],
			[
				'display_order' => 15,
				'parent' => 'qms',
				'name' => 'resolver-qms',
				'display_name' => 'Resolver',
			],
			[
				'display_order' => 16,
				'parent' => 'qms',
				'name' => 'clarifier-qms',
				'display_name' => 'Clarifier',
			],
			[
				'display_order' => 17,
				'parent' => 'qms',
				'name' => 'view-qms',
				'display_name' => 'View',
			],
			[
				'display_order' => 18,
				'parent' => 'qms',
				'name' => 'export-qms',
				'display_name' => 'Export',
			],
		];
		Permission::createFromArrays($permissions);
	}
}