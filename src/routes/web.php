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
	Route::post('activity-status/export', 'ActivityController@exportActivities')->name('exportActivities');

	//ASP NEW ACTIVITY
	Route::get('/new-activity/get-form-data/{id?}', 'ActivityController@activityNewGetFormData')->name('activityNewGetFormData');
	Route::post('asp/activity/verify', 'ActivityController@verifyActivity')->name('verifyActivity');

	//ACTIVITY DEFERRED
	Route::get('/activity-deferred/get-filter-data', 'ActivityController@getFilterData')->name('getActivityDeferredFilterData');
	Route::get('/activity-deferred/get-list', 'ActivityController@getDeferredList')->name('getActivityDeferredList');
	Route::get('/deferred-activity/get-form-data/{id?}', 'ActivityController@activityDeferredGetFormData')->name('activityDeferredGetFormData');
	// Route::post('asp/activity-deferred/save', 'ActivityController@saveActitvity')->name('saveDeferredActitvity');

	Route::post('asp/activity/update', 'ActivityController@updateActivity')->name('updateActivity');

	//ACTIVITY APPROVED
	Route::get('/activity-approved/get-filter-data', 'ActivityController@getFilterData')->name('getActivityApprovedFilterData');
	Route::get('/activity-approved/get-list', 'ActivityController@getApprovedList')->name('getActivityApprovedList');
	Route::post('/activity-approved/get-activiy-encryption-key', 'ActivityController@getActivityEncryptionKey')->name('getActivityEncryptionKey');
	Route::get('/activity-approved/get-details/{encryption_key}', 'ActivityController@getActivityApprovedDetails')->name('getActivityApprovedDetails');
	Route::post('/activity-approved/generate-invoice', 'ActivityController@generateInvoice')->name('generateInvoice');

	//ACTIVITY VERIFICATION
	Route::get('/activity-verification/{view_type_id?}/view/{activity_status_id?}', 'ActivityController@viewActivityStatus')->name('viewActivityStatus');

	Route::get('/activity-verification/bulk/get-list', 'ActivityController@getBulkVerificationList')->name('getBulkActivityVerificationList');
	Route::get('/activity-verification/individual/get-list', 'ActivityController@getIndividualVerificationList')->name('getIndividualActivityVerificationList');
	Route::post('/activity-verification/saveDiffer', 'ActivityController@saveActivityDiffer')->name('saveActivityDiffer');
	Route::post('/activity-verification/approve', 'ActivityController@approveActivity')->name('approveActivity');
	Route::post('/activity-verification/bulk-approve', 'ActivityController@bulkApproveActivity')->name('bulkApproveActivity');

	//INVOICE
	Route::get('/invoice/get-filter-data/{type_id}', 'InvoiceController@getFilterData')->name('getFilterData');
	Route::get('/invoice/get-list', 'InvoiceController@getList')->name('getListData');
	Route::get('/invoice/view/{id}/{type_id}', 'InvoiceController@viewInvoice')->name('viewInvoice');
	Route::get('/invoice/download/{id}', 'InvoiceController@downloadInvoice')->name('downloadInvoice');
	Route::post('/invoice/export', 'InvoiceController@export')->name('exportInvoice');
	Route::get('/invoice/get/payment-info/{id}', 'InvoiceController@getPaymentInfo')->name('getPaymentInfo');

	//BATCH GENERATION
	Route::get('/batch-generation/get-list', 'BatchController@getList')->name('getListData');
	Route::post('/batch-generation/generate-batch', 'BatchController@generateBatch')->name('generateBatch');

	//EXCEPTIONAL REPORT
	Route::get('/exceptional-report/get-filter-data', 'ActivityReportController@getExceptionalReportFilterData')->name('getActivityExceptionalReportFilterData');
	Route::get('/exceptional-report/get-list', 'ActivityReportController@getExceptionalReportList')->name('getActivityExceptionalReportList');

	//RECONCILIATION REPORT
	Route::get('/reconciliation-report/get-graph-data', 'ActivityReportController@getReconciliationReport')->name('getReconciliationReport');

	//PROVISIONAL REPORT
	Route::get('/provisional-report/get-date-filter', 'ActivityReportController@getReportBasedDate')->name('getReportBasedDate');
	Route::get('/provisional-report/get-report', 'ActivityReportController@getProvisionalReport')->name('getProvisionalReport');
	Route::post('/provisional-report/export-report', 'ActivityReportController@exportProvisionalReport')->name('exportProvisionalReport');

	//GENERAL REPORT
	Route::get('/general-report/get-report', 'ActivityReportController@getGeneralReport')->name('getGeneralReport');

});