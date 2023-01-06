<?php

namespace Abs\RsaCasePkg;

use DB;
use Illuminate\Database\Eloquent\Model;

class ActivityWhatsappLog extends Model {
	protected $fillable = [
		'activity_id',
		'type_id',
		'is_new',
		'request',
		'response',
		'remarks',
	];
	protected $table = 'activity_whatsapp_logs';

	// Relationships --------------------------------------------------------------

	public function activity() {
		return $this->belongsTo('Abs\RsaCasePkg\Activity', 'activity_id');
	}

	public function type() {
		return $this->belongsTo('App\Config', 'type_id');
	}

	public static function getListBaseQuery() {
		$baseQuery = self::select([
			'asps.asp_code as aspCode',
			'cases.number as caseNumber',
			'activities.crm_activity_id as crmActivityId',
			'configs.name as type',
			DB::raw('DATE_FORMAT(activity_whatsapp_logs.created_at,"%d-%m-%Y %h:%i %p") as createdAt'),
		])
			->join('activities', 'activities.id', 'activity_whatsapp_logs.activity_id')
			->join('cases', 'cases.id', 'activities.case_id')
			->join('configs', 'configs.id', 'activity_whatsapp_logs.type_id')
			->join('asps', 'asps.id', 'activities.asp_id')
			->where('activity_whatsapp_logs.is_new', 1)
		;
		return $baseQuery;
	}
}