<?php
Route::group(['namespace' => 'Abs\RsaCasePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'case-pkg/api'], function () {
		Route::post('case/save', 'CaseController@saveCase');
		Route::post('activity/save', 'CaseController@saveActivity');
		Route::post('create-invoice', 'InvoiceController@createInvoice');
		Route::post('get-eligible-po-list', 'CaseController@getEligiblePOList');
		Route::post('get-invoice-list', 'InvoiceController@getInvoiceList');
		Route::post('get-invoice-details', 'InvoiceController@getInvoiceDetails');
		Route::group(['middleware' => ['auth:api']], function () {
		});
	});
});