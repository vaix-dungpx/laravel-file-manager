<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::controller(\App\Http\Controllers\FileController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/my-files/{folder?}', 'myFiles')->where('folder', '(.*)')->name('myFiles'); // Router Danh sách File

        Route::get('/trash', 'trash')->name('trash'); // Router Thùng rác

        Route::post('/folder/create', 'createFolder')->name('folder.create'); // Router Tạo thư mục

        Route::post('/file', 'store')->name('file.store'); // Router Lưu file

        Route::delete('/file', 'destroy')->name('file.delete'); // Router Xóa file

        Route::post('/file/restore', 'restore')->name('file.restore'); // Router Khôi phục file

        //Preview file
        Route::get('/file/preview', 'preview')->name('file.preview'); // Router Xem trước file

        Route::delete('/file/delete-forever', 'deleteForever')->name('file.deleteForever'); // Router Xóa file vĩnh viễn

        Route::post('/file/add-to-favourites', 'addToFavourites')->name('file.addToFavourites'); // Router Thêm vào danh sách yêu thích

        Route::post('/file/share', 'share')->name('file.share'); // Router Chia sẻ file

        Route::get('/shared-with-me', 'sharedWithMe')->name('file.sharedWithMe'); // Router File đã chia sẻ với tôi

        Route::get('/shared-by-me', 'sharedByMe')->name('file.sharedByMe'); // Router File tôi đã chia sẻ

        Route::get('/file/download', 'download')->name('file.download'); // Router Tải file về

        Route::get('/file/download-shared-with-me', 'downloadSharedWithMe')->name('file.downloadSharedWithMe'); // Router Tải file đã chia sẻ với tôi

        Route::get('/file/download-shared-by-me', 'downloadSharedByMe')->name('file.downloadSharedByMe'); // Router Tải file tôi đã chia sẻ
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //invoices
    Route::get('/invoices', function () {
        return Inertia::render('Invoices');
    })->name('invoices');
});

require __DIR__ . '/auth.php';
