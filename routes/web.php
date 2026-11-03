<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;

// Open the student list when visiting the homepage.
Route::redirect('/', '/students');
Route::resource('students', StudentController::class);