<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Test Case Manager API',
    'docs' => 'https://github.com/qedhourse580/test-case-manager-api#readme',
]));
