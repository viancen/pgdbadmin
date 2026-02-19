<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/docs/connecting', [DocsController::class, 'connecting'])->name('docs.connecting');

Route::middleware('pg.auth')->group(function () {
    Route::post('/switch-db', [DashboardController::class, 'switchDb'])->name('switch-db');
    Route::get('/table/{schema}/{table}', [TableController::class, 'browse'])->name('table.browse');
    Route::get('/table/{schema}/{table}/structure', [TableController::class, 'structure'])->name('table.structure');
    Route::post('/table/{schema}/{table}/column', [TableController::class, 'columnDdl'])->name('table.column.ddl');
    Route::post('/table/{schema}/{table}/row', [TableController::class, 'updateRow'])->name('table.row.update');
    Route::post('/table/{schema}/{table}/row/delete', [TableController::class, 'deleteRow'])->name('table.row.delete');
    Route::get('/query', [QueryController::class, 'index'])->name('query.index');
    Route::post('/query', [QueryController::class, 'execute'])->name('query.execute');
    Route::post('/query/create-view', [QueryController::class, 'createView'])->name('query.create-view');
});
