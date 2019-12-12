<?php
Route::group(['namespace' => 'Abs\RsaCasePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'case-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});