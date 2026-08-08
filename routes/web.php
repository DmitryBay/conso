<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificationController as PlatformNotificationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController as PlatformUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ImpersonationController;
use App\Http\Controllers\Guest\AccessController as GuestAccessController;
use App\Http\Controllers\Guest\CatalogController as GuestCatalogController;
use App\Http\Controllers\Guest\NotificationPreferenceController as GuestNotificationPreferenceController;
use App\Http\Controllers\Guest\OrderController as GuestOrderController;
use App\Http\Controllers\Workspace\BackgroundLibraryController;
use App\Http\Controllers\Workspace\DashboardController as WorkspaceDashboardController;
use App\Http\Controllers\Workspace\GuestStayController;
use App\Http\Controllers\Workspace\NotificationController;
use App\Http\Controllers\Workspace\PushSubscriptionController;
use App\Http\Controllers\Workspace\ServiceRequestController;
use App\Http\Controllers\Workspace\ServiceTreeController;
use App\Http\Controllers\Workspace\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    return $user && ! $user->isSuperAdmin()
        ? redirect()->route('workspace.dashboard')
        : redirect()->route('platform.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/demo/platform-login', [AuthenticatedSessionController::class, 'demoPlatformLogin'])->name('demo.platform-login');
Route::get('/auto-login/platform', [AuthenticatedSessionController::class, 'signedPlatformLogin'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('auto-login.platform');
Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])->middleware('auth')->name('impersonation.stop');

Route::prefix('guest/{company:slug}')->name('guest.')->middleware('guest.locale')->group(function () {
    Route::get('manifest.webmanifest', [GuestAccessController::class, 'manifest'])->name('manifest');
    Route::get('/', [GuestAccessController::class, 'show'])->name('access');
    Route::post('access', [GuestAccessController::class, 'store'])->name('access.store');

    Route::middleware('guest.hotel')->group(function () {
        Route::get('session/status', [GuestAccessController::class, 'status'])->name('session.status');
        Route::get('catalog', GuestCatalogController::class)->name('catalog');
        Route::post('logout', [GuestAccessController::class, 'destroy'])->name('logout');
        Route::get('services/{serviceNode}/order', [GuestOrderController::class, 'create'])->name('orders.create');
        Route::post('services/{serviceNode}/order', [GuestOrderController::class, 'store'])->name('orders.store');
        Route::get('orders', [GuestOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{serviceRequest}', [GuestOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{serviceRequest}/confirm', [GuestOrderController::class, 'confirm'])->name('orders.confirm');
        Route::get('bill', [GuestOrderController::class, 'bill'])->name('bill');
        Route::post('push-subscriptions', [GuestNotificationPreferenceController::class, 'storePush'])->name('push-subscriptions.store');
        Route::delete('push-subscriptions', [GuestNotificationPreferenceController::class, 'destroyPush'])->name('push-subscriptions.destroy');
        Route::patch('notifications/email', [GuestNotificationPreferenceController::class, 'updateEmail'])->name('notifications.email');
    });
});

Route::prefix('platform')->name('platform.')->middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('companies', CompanyController::class)->except('destroy');
    Route::post('users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');
    Route::resource('users', PlatformUserController::class)->only(['index', 'edit', 'update']);
    Route::get('notifications', [PlatformNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [PlatformNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{notification}', [PlatformNotificationController::class, 'read'])->name('notifications.read');
    Route::get('system', SystemController::class)->name('system');
    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::prefix('workspace')->name('workspace.')->middleware(['auth', 'role:company_owner,manager', 'company', 'workspace.locale'])->group(function () {
    Route::get('/', WorkspaceDashboardController::class)->name('dashboard');

    Route::get('requests', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::post('requests', [ServiceRequestController::class, 'store'])->name('requests.store');
    Route::get('requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::patch('requests/{serviceRequest}/take', [ServiceRequestController::class, 'take'])->name('requests.take');
    Route::patch('requests/{serviceRequest}/status', [ServiceRequestController::class, 'status'])->name('requests.status');

    Route::get('stays', [GuestStayController::class, 'index'])->name('stays.index');
    Route::post('stays', [GuestStayController::class, 'store'])->name('stays.store');
    Route::patch('stays/{guestStay}/extend', [GuestStayController::class, 'extend'])->name('stays.extend');
    Route::patch('stays/{guestStay}/pin', [GuestStayController::class, 'updatePin'])->name('stays.pin');
    Route::patch('stays/{guestStay}/checkout', [GuestStayController::class, 'checkout'])->name('stays.checkout');

    Route::get('services', [ServiceTreeController::class, 'index'])->name('services.index');
    Route::post('services/guides/bali', [ServiceTreeController::class, 'installBaliGuides'])->name('services.guides.bali');
    Route::post('services', [ServiceTreeController::class, 'store'])->name('services.store');
    Route::put('services/{serviceNode}', [ServiceTreeController::class, 'update'])->name('services.update');
    Route::delete('services/{serviceNode}', [ServiceTreeController::class, 'destroy'])->name('services.destroy');

    Route::get('backgrounds', [BackgroundLibraryController::class, 'index'])->name('backgrounds.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::post('push-subscriptions/test', [PushSubscriptionController::class, 'test'])->name('push-subscriptions.test');

    Route::middleware('role:company_owner')->group(function () {
        Route::post('backgrounds', [BackgroundLibraryController::class, 'store'])->name('backgrounds.store');
        Route::patch('backgrounds/{backgroundSet}/activate', [BackgroundLibraryController::class, 'activate'])->name('backgrounds.activate');
        Route::delete('backgrounds/images/{backgroundImage}', [BackgroundLibraryController::class, 'destroy'])->name('backgrounds.destroy');
        Route::get('team', [TeamMemberController::class, 'index'])->name('team.index');
        Route::post('team', [TeamMemberController::class, 'store'])->name('team.store');
        Route::put('team/{member}', [TeamMemberController::class, 'update'])->name('team.update');
        Route::patch('team/{member}/toggle', [TeamMemberController::class, 'toggle'])->name('team.toggle');
    });
});
