<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentAssetController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentPageController;
use App\Http\Controllers\EditorSaveController;
use App\Http\Controllers\PdfImportController;
use App\Http\Controllers\PdfUtilitiesController;
use App\Http\Controllers\SavedColorController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout Route (Auth)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Workspace Routes (User-Based & Strictly Isolated)
Route::middleware('auth')->group(function () {
    // Dashboard & Home
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Document Management
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::get('/{id}', [DocumentController::class, 'show'])->name('show');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
        Route::post('/{id}/duplicate', [DocumentController::class, 'duplicate'])->name('duplicate');
        Route::post('/{id}/star', [DocumentController::class, 'toggleStar'])->name('star');
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [DocumentController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force', [DocumentController::class, 'forceDelete'])->name('forceDelete');
        Route::post('/bulk-action', [DocumentController::class, 'bulkAction'])->name('bulkAction');
        Route::post('/empty-trash', [DocumentController::class, 'emptyTrash'])->name('emptyTrash');

        // Page Management
        Route::post('/{id}/pages', [DocumentPageController::class, 'store'])->name('pages.store');
        Route::put('/{id}/pages/{pageId}', [DocumentPageController::class, 'update'])->name('pages.update');
        Route::post('/{id}/pages/{pageId}/duplicate', [DocumentPageController::class, 'duplicate'])->name('pages.duplicate');
        Route::post('/{id}/pages/reorder', [DocumentPageController::class, 'reorder'])->name('pages.reorder');
        Route::delete('/{id}/pages/{pageId}', [DocumentPageController::class, 'destroy'])->name('pages.destroy');

        // Editor Save & Versions
        Route::post('/{id}/save', [EditorSaveController::class, 'save'])->name('save');
        Route::get('/{id}/versions', [EditorSaveController::class, 'versions'])->name('versions');
        Route::post('/{id}/versions/{versionId}/restore', [EditorSaveController::class, 'restoreVersion'])->name('versions.restore');
    });

    // Templates System
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('index');
        Route::post('/', [TemplateController::class, 'store'])->name('store');
        Route::post('/{id}/use', [TemplateController::class, 'useTemplate'])->name('use');
        Route::delete('/{id}', [TemplateController::class, 'destroy'])->name('destroy');
    });

    // PDF Import
    Route::post('/pdf/import', [PdfImportController::class, 'import'])->name('pdf.import');

    // PDF Utilities Hub
    Route::prefix('tools')->name('tools.')->group(function () {
        Route::get('/', [PdfUtilitiesController::class, 'index'])->name('index');
        Route::post('/merge', [PdfUtilitiesController::class, 'merge'])->name('merge');
        Route::post('/split', [PdfUtilitiesController::class, 'split'])->name('split');
        Route::post('/rotate', [PdfUtilitiesController::class, 'rotate'])->name('rotate');
        Route::post('/watermark', [PdfUtilitiesController::class, 'watermark'])->name('watermark');
        Route::post('/page-numbers', [PdfUtilitiesController::class, 'pageNumbers'])->name('pageNumbers');
        Route::post('/images-to-pdf', [PdfUtilitiesController::class, 'imagesToPdf'])->name('imagesToPdf');
    });

    // Asset & Font Uploads
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::post('/upload-image', [DocumentAssetController::class, 'uploadImage'])->name('uploadImage');
        Route::post('/upload-font', [DocumentAssetController::class, 'uploadFont'])->name('uploadFont');
        Route::delete('/{id}', [DocumentAssetController::class, 'destroy'])->name('destroy');
    });

    // Signatures
    Route::prefix('signatures')->name('signatures.')->group(function () {
        Route::get('/', [SignatureController::class, 'index'])->name('index');
        Route::post('/', [SignatureController::class, 'store'])->name('store');
        Route::delete('/{id}', [SignatureController::class, 'destroy'])->name('destroy');
    });

    // Saved Colors
    Route::prefix('saved-colors')->name('saved-colors.')->group(function () {
        Route::get('/', [SavedColorController::class, 'index'])->name('index');
        Route::post('/', [SavedColorController::class, 'store'])->name('store');
        Route::delete('/{id}', [SavedColorController::class, 'destroy'])->name('destroy');
    });
});
