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
	Route::get('/activity-status/{view_type_id?}/view/{activity_status_id?}', 'ActivityController@viewActivityStatus')->name('viewActivityStatus');

	//ASP NEW ACTIVITY
	Route::get('/new-activity/get-form-data/{id?}', 'ActivityController@activityNewGetFormData')->name('activityNewGetFormData');
	Route::post('asp/activity/verify', 'ActivityController@verifyActivity')->name('verifyActivity');

	//ACTIVITY DEFERRED
	Route::get('/activity-deferred/get-filter-data', 'ActivityController@getFilterData')->name('getActivityDeferredFilterData');
	Route::get('/activity-deferred/get-list', 'ActivityController@getDeferredList')->name('getActivityDeferredList');
	Route::get('/deferred-activity/get-form-data/{id?}', 'ActivityController@activityDeferredGetFormData')->name('activityDeferredGetFormData');
	// Route::post('asp/activity-deferred/save', 'ActivityController@saveActitvity')->name('saveDeferredActitvity');

	Route::post('asp/activity/update', 'ActivityController@updateActitvity')->name('updateActitvity');

	//ACTIVITY APPROVED
	Route::get('/activity-approved/get-filter-data', 'ActivityController@getFilterData')->name('getActivityApprovedFilterData');
	Route::get('/activity-approved/get-list', 'ActivityController@getApprovedList')->name('getActivityApprovedList');
	Route::post('/activity-approved/get-activiy-encryption-key', 'ActivityController@getActivityEncryptionKey')->name('getActivityEncryptionKey');
	Route::get('/activity-approved/get-details/{encryption_key}', 'ActivityController@getActivityApprovedDetails')->name('getActivityApprovedDetails');
	Route::post('/activity-approved/generate-invoice', 'ActivityController@generateInvoice')->name('generateInvoice');

	//ACTIVITY VERIFICATION
	Route::get('/activity-verification/{view_type_id?}/view/{activity_status_id?}', 'ActivityController@viewActivityStatus')->name('viewActivityStatus');

	Route::get('/activity-verification/get-list', 'ActivityController@getVerificationList')->name('getActivityVerificationList');
	Route::post('/activity-verification/saveDiffer', 'ActivityController@saveActivityDiffer')->name('saveActivityDiffer');
	Route::post('/activity-verification/approve', 'ActivityController@approveActivity')->name('approveActivity');

	//INVOICE
	Route::get('/invoice/get-filter-data', 'InvoiceController@getFilterData')->name('getFilterData');
	Route::get('/invoice/get-list', 'InvoiceController@getList')->name('getListData');
	Route::get('/invoice/view/{id}', 'InvoiceController@viewInvoice')->name('viewInvoice');
	Route::get('/invoice/download/{id}', 'InvoiceController@downloadInvoice')->name('downloadInvoice');

	//BATCH GENERATION
	Route::get('/batch-generation/get-list', 'BatchController@getList')->name('getListData');
	Route::post('/batch-generation/generate-batch', 'BatchController@generateBatch')->name('generateBatch');
});