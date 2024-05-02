<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\QueryController;

Route::get('/n-plus-query', [QueryController::class, 'nPlusQuery']);
Route::get('/not-n-plus-query', [QueryController::class, 'notNPlusQuery']);
Route::get('/n-plus-query-from-builder', [QueryController::class, 'nPlusQueryFromBuilder']);
Route::get('/deteck-all-n-plus-query', [QueryController::class, 'detectAllNPlusQuery']);
Route::get('/deteck-n-plus-query-on-morph-relation', [QueryController::class, 'detectNPlusQueryOnMorphRelation']);
Route::get('/deteck-n-plus-query-on-morph-relation-with-builder', [QueryController::class, 'detectNPlusQueryOnMorphRelationWithBuilder']);
Route::get('/n-plus-query-ignores-redirects', [QueryController::class, 'nPlusQueryIgnoresRedirects']);
Route::get('/fire-an-event-if-detect-n-query', [QueryController::class, 'fireAnEventIfDetectNQuery']);
Route::get('/use-trace-line-to-detect-query', [QueryController::class, 'useTraceLineToDetectQuery']);
