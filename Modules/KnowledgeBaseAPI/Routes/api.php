<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Knowledge Base
|--------------------------------------------------------------------------
|
| Here are the API routes for the Knowledge Base module.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/

Route::group(['middleware' => ['auth:api']], function () {
    // Knowledge Base Categories/Folders
    Route::get('/kb/categories', 'KnowledgeBaseAPIController@listCategories');
    Route::get('/kb/categories/{id}', 'KnowledgeBaseAPIController@getCategory');
    
    // Knowledge Base Articles
    Route::get('/kb/articles', 'KnowledgeBaseAPIController@listArticles');
    Route::get('/kb/articles/{id}', 'KnowledgeBaseAPIController@getArticle');
    
    // Search
    Route::get('/kb/search', 'KnowledgeBaseAPIController@search');
    
    // Public endpoint for health check (no auth required)
    Route::get('/kb/health', 'KnowledgeBaseAPIController@health');
});

// Public routes (if public knowledge base is enabled)
Route::group(['prefix' => 'kb/public'], function () {
    Route::get('/categories', 'KnowledgeBaseAPIController@listPublicCategories');
    Route::get('/categories/{id}', 'KnowledgeBaseAPIController@getPublicCategory');
    Route::get('/articles', 'KnowledgeBaseAPIController@listPublicArticles');
    Route::get('/articles/{id}', 'KnowledgeBaseAPIController@getPublicArticle');
    Route::get('/search', 'KnowledgeBaseAPIController@publicSearch');
});
