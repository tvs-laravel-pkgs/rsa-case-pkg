<?php

namespace Abs\RsaCasePkg;
use Abs\RsaCasePkg\RsaCase;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CaseController extends Controller {

	public function __construct() {
	}

	public function getRsaCaseList(Request $request) {
		$case_list = RsaCase::withTrashed()
			->select(
				'cases.id',
				'cases.code',
				'cases.name',
				DB::raw('IF(cases.mobile_no IS NULL,"--",cases.mobile_no) as mobile_no'),
				DB::raw('IF(cases.email IS NULL,"--",cases.email) as email'),
				DB::raw('IF(cases.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('cases.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->case_code)) {
					$query->where('cases.code', 'LIKE', '%' . $request->case_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->case_name)) {
					$query->where('cases.name', 'LIKE', '%' . $request->case_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('cases.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('cases.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('cases.id', 'desc');

		return Datatables::of($case_list)
			->addColumn('code', function ($case_list) {
				$status = $case_list->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $case_list->code;
			})
			->addColumn('action', function ($case_list) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/case-pkg/case/edit/' . $case_list->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_case"
					onclick="angular.element(this).scope().deleteRsaCase(' . $case_list->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getRsaCaseFormData($id = NULL) {
		if (!$id) {
			$case = new RsaCase;
			$address = new Address;
			$action = 'Add';
		} else {
			$case = RsaCase::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['case'] = $case;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveRsaCase(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'RsaCase Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'name.required' => 'RsaCase Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				'pincode.required' => 'Pincode is Required',
				'pincode.max' => 'Maximum 6 Characters',
				'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required|max:255|min:3',
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$case = new RsaCase;
				$case->created_by_id = Auth::user()->id;
				$case->created_at = Carbon::now();
				$case->updated_at = NULL;
				$address = new Address;
			} else {
				$case = RsaCase::withTrashed()->find($request->id);
				$case->updated_by_id = Auth::user()->id;
				$case->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$case->fill($request->all());
			$case->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$case->deleted_at = Carbon::now();
				$case->deleted_by_id = Auth::user()->id;
			} else {
				$case->deleted_by_id = NULL;
				$case->deleted_at = NULL;
			}
			$case->gst_number = $request->gst_number;
			$case->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $case->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['RsaCase Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['RsaCase Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteRsaCase($id) {
		$delete_status = RsaCase::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
