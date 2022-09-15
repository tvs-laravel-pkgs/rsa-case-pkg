<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;

class ActivityWhatsappLog extends Model {
	protected $fillable = [
		'activity_id',
		'type_id',
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

}