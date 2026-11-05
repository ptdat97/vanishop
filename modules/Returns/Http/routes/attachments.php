<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\ReturnAttachmentController;

// Link có chữ ký, hết hạn 30 phút (ReturnView::$attachments). Không cần phiên.
Route::get('/_vani/doi-tra/anh/{attachment}', ReturnAttachmentController::class)->middleware('signed')->whereNumber('attachment')->name('returns.attachment');
