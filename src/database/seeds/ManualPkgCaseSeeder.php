<?php
namespace Abs\RsaCasePkg\Database\Seeds;

use Abs\RsaCasePkg\CaseStatus;
use Abs\RsaCasePkg\RsaCase;
use App\CallCenter;
use App\Client;
use App\Company;
use App\District;
use App\Entity;
use App\Subject;
use App\VehicleModel;
use DB;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ManualPkgCaseSeeder extends Seeder {
	public function run() {
		$faker = Faker::create();
		DB::beginTransaction();
		global $company_id;

		if (!$company_id) {
			$company_id = $this->command->ask("Enter company id", '1');
			$company = Company::findOrFail($company_id);
		}

		// $admin = $company->admin();

		// $number = $this->command->ask("Enter case number", 'TIK001');

		$eligibility_type_headers = ['ID', 'Eligibility Type'];
		$q1 = Entity::where('entity_type_id', 13);
		$q2 = clone $q1;
		$eligibility_types = $q1->select(['id', 'name'])->get()->toArray();
		$eligibility_type_ids = $q2->pluck('name')->toArray();

		$this->command->table($eligibility_type_headers, $eligibility_types);
		$eligibility_type_id = $this->command->ask("Select Eligibility Type", 1);

		$city = District::inRandomOrder()->first();
		$case = RsaCase::create([
			'company_id' => $company->id,
			'number' => 'tik' . rand(),
			'date' => date('Y-m-d H:i:s'),
			'call_center_id' => CallCenter::inRandomOrder()->first()->id,
			'client_id' => Client::inRandomOrder()->first()->id,
			'customer_name' => $faker->name,
			'customer_contact_number' => $faker->e164PhoneNumber,
			'contact_name' => $faker->name,
			'contact_number' => $faker->e164PhoneNumber,
			'cancel_reason' => null,
			'data_filled_date' => date('Y-m-d H:i:s'),
			'vehicle_registration_number' => 'TN ' . $faker->numberBetween(10, 99) . ' BV ' . $faker->numberBetween(1000, 9999),
			'vin_no' => null,
			'eligibility_type_id' => $eligibility_type_id,
			'vehicle_model_id' => VehicleModel::inRandomOrder()->first()->id,
			'subject_id' => Subject::inRandomOrder()->first()->id,
			'policy_id' => null, //App\Policy::inRandomOrder()->first()->id,
			'status_id' => CaseStatus::inRandomOrder()->first()->id,
			'bd_lat' => null,
			'bd_long' => null,
			'bd_location' => null,
			'bd_city_id' => $city->id,
			'bd_state_id' => $city->state_id,
		]);
		$case->number = 'tik' . $case->id;
		$case->save();
		$this->command->info('Case Number : ' . $case->number);
		DB::commit();
	}
}
