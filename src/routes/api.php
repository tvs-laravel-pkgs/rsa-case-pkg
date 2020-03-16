<?php
Route::group(['namespace' => 'Abs\RsaCasePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'api/case-pkg'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			Route::post('case/save', 'CaseController@save');

			Route::post('activity/create', 'ActivityController@createActivity');

			Route::post('get-invoiceable-activities', 'ActivityController@getInvoiceableActivities');

			Route::post('activity/reject-po', 'ActivityController@rejectActivityPo');

			Route::post('create-invoice', 'InvoiceController@createInvoice');

			Route::post('get-invoice-list', 'InvoiceController@getList');
			Route::post('get-invoice-details', 'InvoiceController@getDetails');
		});
	});
});