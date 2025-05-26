<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
// Add this to routes/web.php
Route::get('/preview-pdf/{filename}', function ($filename) {
    // Ensure the file exists in the correct location
    $path = storage_path('app/public/pdfs/' . $filename);
    
    if (!file_exists($path)) {
        abort(404, 'PDF not found');
    }

    // Force inline display with proper headers
    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$filename.'"'
    ]);
})->middleware('web'); // Add web middleware if not already applied