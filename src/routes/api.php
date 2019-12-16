<?php
Route::group(['namespace' => 'Abs\RsaCasePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'api/case-pkg'], function () {
		Route::post('case/save', 'CaseController@save');
		Route::post('activity/save', 'ActivityController@save');
		Route::post('get-eligible-po-list', 'ActivityController@getEligiblePOList');
		Route::post('create-invoice', 'InvoiceController@createInvoice');
		Route::post('get-invoice-list', 'InvoiceController@getInvoiceList');
		Route::post('get-invoice-details', 'InvoiceController@getInvoiceDetails');
		Route::group(['middleware' => ['auth:api']], function () {
		});
	});
});