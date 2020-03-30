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

			//ACTIVITY REPORT
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'activity-reports',
				'display_name' => 'Activities Report',
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

		];
		Permission::createFromArrays($permissions);
	}
}