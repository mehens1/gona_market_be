<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('server')->group(function () {
    Route::get('/migrate', function () {
        try {
            Artisan::call('migrate');
            return 'Migration completed successfully!';
        } catch (\Exception $e) {
            return 'Migration failed: ' . $e->getMessage();
        }
    });

    Route::get('/seeder', function () {
        try {
            Artisan::call('db:seed');
            return 'Database seeding completed successfully!';
        } catch (\Exception $e) {
            return 'Seeding failed: ' . $e->getMessage();
        }
    });

    Route::get('/optimization', function () {
        try {
            Artisan::call('optimize');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            return 'Optimization completed successfully!';
        } catch (\Exception $e) {
            return 'Optimization failed: ' . $e->getMessage();
        }
    });

    Route::get('/generate-jwt-secret', function () {
        try {
            Artisan::call('jwt:secret');
            return 'JWT secret generated successfully!';
        } catch (\Exception $e) {
            return 'Failed to generate JWT secret: ' . $e->getMessage();
        }
    });

    Route::get('/vendor-publish', function () {
        try {
            Artisan::call('jwt:secret');
            return 'Configuration published successfully!';
        } catch (\Exception $e) {
            return 'Failed to publish configuration: ' . $e->getMessage();
        }
    });

});
