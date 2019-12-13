<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policy extends Model {
	use SoftDeletes;
	protected $table = 'policies';

	//    protected $fillable = [
	//     'policy_name',
	//     'policy_code',
	//     'policy_type',
	//     'policy_start',
	//     'policy_end',
	//     'policy_tenure',
	// ];
	protected $fillable = [
		'company_id',
		'type_id',
		'number',
		'start_date',
		'policy_end',
		'end_date',
		'created_by_id',
		'updated_by_id',
		'deleted_by_id',
	];

	public function getPolicyStartattribute($value) {
		return empty($value) ? "" : date("d-m-Y", strtotime($value));
	}

	public function getPolicyEndattribute($value) {
		return empty($value) ? "" : date("d-m-Y", strtotime($value));
	}

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}

	public function policyType() {
		return $this->belongsTo('App\PolicyType', 'type_id');
	}

}
