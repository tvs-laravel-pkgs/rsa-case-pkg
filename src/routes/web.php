<?php
Route::group(['namespace' => 'Abs\RsaCasePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'rsa-case-pkg'], function () {
	//CASE
	Route::get('/cases/get-list', 'CaseController@getCaseList')->name('getCaseList');
	Route::get('/case/get-form-data/{id?}', 'CaseController@getCaseFormData')->name('getCaseFormData');
	Route::post('/case/save', 'CaseController@saveCase')->name('saveCase');
	Route::get('/case/delete/{id}', 'CaseController@deleteCase')->name('deleteCase');

	//ACTIVITY STATUS
	Route::get('/activity-status/get-filter-data', 'ActivityController@getFilterData')->name('getActivityStatusFilterData');

	Route::get('/activity-status/get-list', 'ActivityController@getList')->name('getActivityStatusList');
	Route::get('/activity-status/delete/{id}', 'ActivityController@delete')->name('deleteActivity');
	Route::get('/activity-status/view/{activity_status_id?}', 'ActivityController@viewActivityStatus')->name('viewActivityStatus');

	//ACTIVITY DEFERRED
	Route::get('/activity-deferred/get-filter-data', 'ActivityController@getFilterData')->name('getActivityDeferredFilterData');
	Route::get('/activity-deferred/get-list', 'ActivityController@getDeferredList')->name('getActivityDeferredList');

	//ACTIVITY APPROVED
	Route::get('/activity-approved/get-filter-data', 'ActivityController@getFilterData')->name('getActivityApprovedFilterData');
	Route::get('/activity-approved/get-list', 'ActivityController@getApprovedList')->name('getActivityApprovedList');

	//ACTIVITY VERIFICATION
	Route::get('/activity-verification/get-list', 'ActivityController@getVerificationList')->name('getActivityVerificationList');

	//INVOICE
	Route::get('/invoice/get-filter-data', 'InvoiceController@getFilterData')->name('getFilterData');
	Route::get('/invoice/get-list', 'InvoiceController@getList')->name('getListData');
	Route::get('/invoice/view/{id}', 'InvoiceController@viewInvoice')->name('viewInvoice');
	Route::get('/invoice/download/{id}', 'InvoiceController@downloadInvoice')->name('downloadInvoice');

	//BATCH GENERATION
	Route::get('/batch-generation/get-list', 'BatchController@getList')->name('getListData');
	Route::post('/batch-generation/generate-batch', 'BatchController@generateBatch')->name('generateBatch');

});