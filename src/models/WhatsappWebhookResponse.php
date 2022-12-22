<?php

namespace Abs\RsaCasePkg;

use Illuminate\Database\Eloquent\Model;

class WhatsappWebhookResponse extends Model {
	protected $fillable = [
		'payload',
		'status',
		'errors',
	];
	protected $table = 'whatsapp_webhook_responses';

}