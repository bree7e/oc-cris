<?php

Route::group(['prefix' => 'api/v1'], function () {
    Route::resource('publications', 'Bree7e\Cris\Api\Publications');
});
