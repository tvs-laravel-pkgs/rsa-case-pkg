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
				'name' => 'mapped-dealer-membership',
				'display_name' => 'Mapped Dealers',
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
				'display_name' => 'Dealer Report',
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
				'display_order' => 4,
				'parent' => 'dealer-commission-invoice',
				'name' => 'all-dealer-commission-invoice',
				'display_name' => 'All',
			],
			[
				'display_order' => 5,
				'parent' => 'dealer-commission-invoice',
				'name' => 'approve-dealer-commission-invoice',
				'display_name' => 'Approve',
			],
			[
				'display_order' => 6,
				'parent' => 'dealer-commission-invoice',
				'name' => 'finance-export-dealer-commission-invoice',
				'display_name' => 'Finance Export',
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
				'display_name' => 'Finance Export',
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
				'name' => 'all-membership-customer-invoice',
				'display_name' => 'All',
			],
		];
		Permission::createFromArrays($permissions);
	}
}