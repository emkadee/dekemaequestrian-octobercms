<?php

Route::get('/reservations', function () {
    return 'Hello World';
});
Route::group(['prefix' => 'adminde/mk3d/booking'], function() {
    Route::get('reservations/getReservations', 'Mk3d\Booking\Controllers\Reservations@getReservations');
});

?>