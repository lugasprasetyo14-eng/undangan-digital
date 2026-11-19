<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvitationEventController;
use App\Http\Controllers\PublicInvitationController;
use App\Http\Controllers\InvitationGuestController;
use App\Http\Controllers\GuestRsvpController;
use App\Http\Controllers\InvitationContentController;
use App\Http\Controllers\PublicContentController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/billing', [App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::post('/dashboard/invitations/{invitation}/checkout', [App\Http\Controllers\BillingController::class, 'checkout'])->middleware('throttle:6,1')->name('billing.checkout');
    Route::get('/dashboard/billing/{invoice}', [App\Http\Controllers\BillingController::class, 'show'])->name('billing.show');
    Route::post('/dashboard/billing/{invoice}/check', [App\Http\Controllers\BillingController::class, 'check'])->middleware('throttle:3,1')->name('billing.check');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('dashboard/invitations')->name('dashboard.invitations.')->group(function () {
        Route::get('/', [InvitationController::class, 'index'])->name('index');
        Route::get('/create', [InvitationController::class, 'create'])->name('create');
        Route::post('/', [InvitationController::class, 'store'])->name('store');
        Route::get('/{invitation}/edit', [InvitationController::class, 'edit'])->name('edit');
        Route::put('/{invitation}', [InvitationController::class, 'update'])->name('update');
        Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
        Route::get('/{invitation}/preview', [InvitationController::class, 'preview'])->name('preview');
        Route::post('/{invitation}/publish', [InvitationController::class, 'publish'])->name('publish');
        Route::post('/{invitation}/unpublish', [InvitationController::class, 'unpublish'])->name('unpublish');
        Route::post('/{invitation}/events', [InvitationEventController::class, 'store'])->name('events.store');
        Route::put('/{invitation}/events/{event}', [InvitationEventController::class, 'update'])->name('events.update');
        Route::delete('/{invitation}/events/{event}', [InvitationEventController::class, 'destroy'])->name('events.destroy');
        Route::get('/{invitation}/guests', [InvitationGuestController::class, 'index'])->name('guests.index');
        Route::get('/{invitation}/guests/create', [InvitationGuestController::class, 'create'])->name('guests.create');
        Route::post('/{invitation}/guests', [InvitationGuestController::class, 'store'])->name('guests.store');
        Route::get('/{invitation}/guests/{guest}/edit', [InvitationGuestController::class, 'edit'])->name('guests.edit');
        Route::put('/{invitation}/guests/{guest}', [InvitationGuestController::class, 'update'])->name('guests.update');
        Route::delete('/{invitation}/guests/{guest}', [InvitationGuestController::class, 'destroy'])->name('guests.destroy');
        Route::get('/{invitation}/guests-export', [InvitationGuestController::class, 'export'])->name('guests.export');
        Route::get('/{invitation}/guests/{guest}/qr', [InvitationGuestController::class, 'qr'])->name('guests.qr');
        Route::get('/{invitation}/check-in/{token}', [InvitationGuestController::class, 'verify'])->name('guests.verify');
        Route::post('/{invitation}/check-in/{token}', [InvitationGuestController::class, 'checkIn'])->name('guests.check-in');
        Route::get('/{invitation}/content', [InvitationContentController::class, 'index'])->name('content.index');
        Route::post('/{invitation}/content/messages/{message}/{action}', [InvitationContentController::class, 'messageAction'])->name('content.messages.action');
        Route::delete('/{invitation}/content/messages/{message}', [InvitationContentController::class, 'deleteMessage'])->name('content.messages.delete');
        Route::post('/{invitation}/content/media', [InvitationContentController::class, 'upload'])->name('content.media.store');
        Route::post('/{invitation}/content/media/sort', [InvitationContentController::class, 'sortMedia'])->name('content.media.sort');
        Route::delete('/{invitation}/content/media/{media}', [InvitationContentController::class, 'deleteMedia'])->name('content.media.delete');
        Route::post('/{invitation}/content/gifts', [InvitationContentController::class, 'gift'])->name('content.gifts.store');
        Route::delete('/{invitation}/content/gifts/{gift}', [InvitationContentController::class, 'deleteGift'])->name('content.gifts.delete');
        Route::post('/{invitation}/content/wishlist', [InvitationContentController::class, 'wishlist'])->name('content.wishlist.store');
        Route::delete('/{invitation}/content/wishlist/{wishlist}', [InvitationContentController::class, 'deleteWishlist'])->name('content.wishlist.delete');
        Route::post('/{invitation}/content/wishlist/{wishlist}/reservations/{reservation}/cancel', [InvitationContentController::class, 'cancelReservation'])->name('content.wishlist.cancel');
        Route::get('/{invitation}/content/media/{media}/{variant?}', [InvitationContentController::class, 'file'])->name('content.media.file');
        Route::get('/{invitation}/preview/media/{media}/{variant?}', [InvitationContentController::class, 'previewFile'])->name('preview.media.file');
        Route::get('/{invitation}/preview/gift/{gift}', [InvitationContentController::class, 'previewGiftFile'])->name('preview.gift.file');
    });
});

Route::get('/w/{slug}', [PublicInvitationController::class, 'show'])->name('invitations.public');
Route::post('/billing/tokopay/callback', App\Http\Controllers\TokopayCallbackController::class)->name('billing.callback');
Route::post('/w/{slug}/rsvp', [GuestRsvpController::class, 'store'])->middleware('throttle:10,1')->name('invitations.rsvp');
Route::post('/w/{slug}/messages', [PublicContentController::class, 'message'])->middleware('throttle:10,1')->name('invitations.messages.store');
Route::post('/w/{slug}/wishlist/{wishlist}/reserve', [PublicContentController::class, 'reserve'])->middleware('throttle:10,1')->name('invitations.wishlist.reserve');
Route::get('/w/{slug}/media/{media}/{variant?}', [InvitationContentController::class, 'publicFile'])->name('invitations.media.file');
Route::get('/w/{slug}/gift/{gift}', [InvitationContentController::class, 'giftFile'])->name('invitations.gift.file');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin');
    Route::get('/admin/users', [App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/{user}', [App\Http\Controllers\AdminController::class, 'user'])->name('admin.users.show');
    Route::get('/admin/invitations', [App\Http\Controllers\AdminController::class, 'invitations'])->name('admin.invitations');
    Route::get('/admin/invitations/{invitation}', [App\Http\Controllers\AdminController::class, 'invitation'])->name('admin.invitations.show');
    Route::patch('/admin/invitations/{invitation}/unpublish', [App\Http\Controllers\AdminController::class, 'unpublish'])->name('admin.invitations.unpublish');
    Route::get('/admin/themes', [App\Http\Controllers\AdminController::class, 'themes'])->name('admin.themes');
    Route::patch('/admin/themes/{theme}', [App\Http\Controllers\AdminController::class, 'updateTheme'])->name('admin.themes.update');
    Route::get('/admin/packages', [App\Http\Controllers\AdminController::class, 'packages'])->name('admin.packages');
    Route::patch('/admin/packages/{package}', [App\Http\Controllers\AdminController::class, 'updatePackage'])->name('admin.packages.update');
    Route::get('/admin/invoices', [App\Http\Controllers\AdminController::class, 'invoices'])->name('admin.invoices');
    Route::get('/admin/invoices/{invoice}', [App\Http\Controllers\AdminController::class, 'invoice'])->name('admin.invoices.show');
    Route::get('/admin/audit-logs', [App\Http\Controllers\AdminController::class, 'auditLogs'])->name('admin.audit');
});
