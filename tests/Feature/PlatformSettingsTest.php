<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_open_system_and_settings_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);

        $this->actingAs($admin)->get(route('platform.system'))
            ->assertOk()
            ->assertSee('Состояние системы')
            ->assertSee('Проверка подключений');

        $this->actingAs($admin)->get(route('platform.settings.edit'))
            ->assertOk()
            ->assertSee('Настройки платформы')
            ->assertSee('Email-уведомления');
    }

    public function test_company_user_cannot_open_platform_system_pages(): void
    {
        $owner = User::factory()->create(['role' => UserRole::CompanyOwner]);

        $this->actingAs($owner)->get(route('platform.system'))->assertForbidden();
        $this->actingAs($owner)->get(route('platform.settings.edit'))->assertForbidden();
    }

    public function test_platform_admin_can_save_defaults_and_notification_switches(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);

        $this->actingAs($admin)->put(route('platform.settings.update'), [
            'platform_name' => 'Luma Hotels',
            'support_email' => 'support@luma.dev',
            'default_trial_days' => 30,
            'default_timezone' => 'Asia/Makassar',
            'default_currency' => 'IDR',
            'email_notifications_enabled' => '1',
            'push_notifications_enabled' => '0',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Luma Hotels', PlatformSetting::read('platform_name'));
        $this->assertSame('30', PlatformSetting::read('default_trial_days'));
        $this->assertTrue(PlatformSetting::enabled('email_notifications_enabled'));
        $this->assertFalse(PlatformSetting::enabled('push_notifications_enabled'));

        $this->actingAs($admin)->get(route('platform.system'))
            ->assertOk()
            ->assertSee('Luma Hotels')
            ->assertSee('support@luma.dev');
    }
}
