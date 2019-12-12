<?php

Route::group(['namespace' => 'Abs\RsaCasePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'case-pkg'], function () {
	Route::get('/cases/get-list', 'CaseController@getCaseList')->name('getCaseList');
	Route::get('/case/get-form-data/{id?}', 'CaseController@getCaseFormData')->name('getCaseFormData');
	Route::post('/case/save', 'CaseController@saveCase')->name('saveCase');
	Route::get('/case/delete/{id}', 'CaseController@deleteCase')->name('deleteCase');

});