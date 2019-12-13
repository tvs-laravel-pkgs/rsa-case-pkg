<?php
Route::group(['namespace' => 'Abs\RsaCasePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'case-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			Route::post('case/save', 'Api\CaseController@saveCase');
			Route::post('activity/save', 'Api\CaseController@saveActivity');
			Route::post('create-invoice', 'Api\InvoiceController@createInvoice');
			Route::post('get-eligible-po-list', 'Api\CaseController@getEligiblePOList');
			Route::post('get-invoice-list', 'Api\InvoiceController@getInvoiceList');
			Route::post('get-invoice-details', 'Api\InvoiceController@getInvoiceDetails');
		});
	});
});