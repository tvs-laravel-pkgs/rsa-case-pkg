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
				'name' => 'delete-activities',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 4,
				'parent' => 'activity-status',
				'name' => 'export-activities',
				'display_name' => 'Export',
			],
			[
				'display_order' => 5,
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
				'display_order' => 5,
				'parent' => 'activity-status',
				'name' => 'view-own-activities',
				'display_name' => 'View Only Own',
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

			//ASP INVOICES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'asp-invoices',
				'display_name' => 'ASP Invoices',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-invoices',
				'name' => 'view-all-asp-invoices',
				'display_name' => 'View All',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-invoices',
				'name' => 'view-only-state-asp-invoices',
				'display_name' => 'Only State Mapped',
			],
			[
				'display_order' => 6,
				'parent' => 'asp-invoices',
				'name' => 'view-only-own-asp-invoices',
				'display_name' => 'Only Own',
			],

		];
		Permission::createFromArrays($permissions);
	}
}