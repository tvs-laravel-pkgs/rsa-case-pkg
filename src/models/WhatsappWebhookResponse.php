<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;

class WhatsappWebhookResponse extends Model {
	protected $fillable = [
		'activity_id',
		'type_id',
		'request',
		'response',
		'remarks',
	];
	protected $table = 'whatsapp_webhook_responses';

	// Relationships --------------------------------------------------------------

	public function activity() {
		return $this->belongsTo('Abs\RsaCasePkg\Activity', 'activity_id');
	}

	public function type() {
		return $this->belongsTo('App\Config', 'type_id');
	}

}