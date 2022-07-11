<?php

use Illuminate\Support\Facades\Route;

// API versioning

Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// Latest api version

Route::prefix("")->group(base_path('routes/api/v1.php'));
