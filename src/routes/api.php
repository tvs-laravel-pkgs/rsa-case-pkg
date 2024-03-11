<?php
Route::group(['namespace' => 'Abs\RsaCasePkg\Api', 'middleware' => ['api']], function () {
	//ASP AUTO BILLING - WHATSAPP WEBHOOK RESPONSE
	Route::post('whatsapp/webhook/response', 'ActivityController@whatsappWebhookResponse')->name('whatsappWebhookResponse');

	Route::group(['prefix' => 'api/case-pkg'], function () {
		Route::post('case/save', 'CaseController@save');

		Route::post('activity/create', 'ActivityController@createActivity');

		Route::post('get-invoiceable-activities', 'ActivityController@getInvoiceableActivities');

		Route::post('activity/reject-po', 'ActivityController@rejectActivityPo');

		Route::post('activity/encryption', 'ActivityController@activityEncryption');

		Route::get('activity-approved/get-details/{encryption_key}', 'ActivityController@getActivityApprovedDetails')->name('getActivityApprovedDetails');

		Route::post('create-invoice', 'InvoiceController@createInvoice');

		Route::post('get-invoice-list', 'InvoiceController@getList');
		Route::post('get-invoice-details', 'InvoiceController@getDetails');
		Route::get('invoice/view/{id}/{type_id}', 'InvoiceController@viewInvoice')->name('viewInvoice');

		//ASP AUTO BILLING - WHATSAPP
		Route::post('tow-images/upload', 'ActivityController@uploadTowImages');

		Route::post('policy/save', 'PolicyController@save');
		Route::post('policy-entitlement/update', 'PolicyController@updatePolicyEntitlement');

		// Route::group(['middleware' => ['auth:api']], function () {
		// });
	});
});