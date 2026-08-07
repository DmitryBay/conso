<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use NotificationChannels\WebPush\PushSubscription;

class SystemController extends Controller
{
    public function __invoke(): View
    {
        $databaseOk = true;
        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $databaseOk = false;
        }

        $checks = [
            ['База данных', $databaseOk, $databaseOk ? 'Соединение работает' : 'Нет соединения'],
            ['Email', filled(config('mail.mailers.smtp.username')), filled(config('mail.mailers.smtp.username')) ? config('mail.from.address') : 'SMTP не настроен'],
            ['Push', filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')), filled(config('webpush.vapid.public_key')) ? 'VAPID настроен' : 'Ключи отсутствуют'],
            ['Realtime', filled(config('broadcasting.connections.pusher.key')), filled(config('broadcasting.connections.pusher.key')) ? 'Pusher подключён' : 'Pusher не настроен'],
            ['Хранилище', is_writable(storage_path()), is_writable(storage_path()) ? 'Доступно для записи' : 'Нет доступа на запись'],
        ];

        $stats = [
            'jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'push_subscriptions' => Schema::hasTable('push_subscriptions') ? PushSubscription::count() : 0,
        ];

        $supportEmail = PlatformSetting::read('support_email', config('mail.from.address'));

        return view('admin.system', compact('checks', 'stats', 'supportEmail'));
    }
}
