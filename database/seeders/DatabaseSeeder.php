<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\ServiceNodeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Room;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use App\Support\ServiceTranslations;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@luma.test'], [
            'name' => 'Alex Morgan',
            'role' => UserRole::SuperAdmin,
            'company_id' => null,
            'password' => 'Admin123!',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $hotels = [
            ['name' => 'Nusa Bay Hotel', 'legal_name' => 'PT Nusa Hospitality', 'email' => 'hello@nusabay.test', 'phone' => '+62 361 555 0142', 'rooms_count' => 48, 'status' => CompanyStatus::Active, 'plan' => 'Pro', 'owner' => ['Dewi Larasati', 'dewi@nusabay.test']],
            ['name' => 'The Sayan House', 'legal_name' => 'PT Sayan Living', 'email' => 'stay@sayanhouse.test', 'phone' => '+62 812 4400 1122', 'rooms_count' => 24, 'status' => CompanyStatus::Trial, 'plan' => 'MVP', 'owner' => ['Made Surya', 'made@sayanhouse.test']],
            ['name' => 'Coral Coast Villas', 'legal_name' => 'Coral Coast Group', 'email' => 'hello@coralcoast.test', 'phone' => '+62 878 2200 9988', 'rooms_count' => 16, 'status' => CompanyStatus::Active, 'plan' => 'Standard', 'owner' => ['Rina Putri', 'rina@coralcoast.test']],
        ];

        foreach ($hotels as $hotel) {
            $owner = $hotel['owner'];
            unset($hotel['owner']);

            $company = Company::updateOrCreate(['slug' => Str::slug($hotel['name'])], [
                ...$hotel,
                'public_id' => (string) Str::uuid(),
                'timezone' => 'Asia/Makassar',
                'currency' => 'IDR',
                'trial_ends_at' => $hotel['status'] === CompanyStatus::Trial ? now()->addDays(14) : null,
            ]);

            User::updateOrCreate(['email' => $owner[1]], [
                'company_id' => $company->id,
                'name' => $owner[0],
                'role' => UserRole::CompanyOwner,
                'password' => 'Demo1234!',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }

        $company = Company::where('slug', 'nusa-bay-hotel')->firstOrFail();
        $owner = $company->owner;
        foreach (['101', '118', '204', '221', '305', '412'] as $roomNumber) {
            Room::updateOrCreate(
                ['company_id' => $company->id, 'number' => $roomNumber],
                ['floor' => mb_substr($roomNumber, 0, 1), 'pin_hash' => Hash::make('1234'), 'is_active' => true]
            );
        }
        $this->call(DemoStaySeeder::class);
        $managers = collect([
            ['name' => 'Agung Pratama', 'email' => 'agung@nusabay.test', 'phone' => '+62 812 1000 2001'],
            ['name' => 'Maya Chen', 'email' => 'maya@nusabay.test', 'phone' => '+62 812 1000 2002'],
        ])->map(fn (array $manager) => User::updateOrCreate(['email' => $manager['email']], [
            ...$manager,
            'company_id' => $company->id,
            'role' => UserRole::Manager,
            'password' => 'Demo1234!',
            'email_verified_at' => now(),
            'is_active' => true,
        ]));

        $food = $this->category($company, 'Еда и напитки', 'bi-cup-hot', 10);
        $breakfast = $this->category($company, 'Завтрак', 'bi-egg-fried', 10, $food);
        $this->service($company, $breakfast, 'Континентальный завтрак', 'Кофе, выпечка, фрукты и свежий сок', 185000, 30, 'bi-egg-fried', 10);
        $this->service($company, $breakfast, 'Завтрак по-балийски', 'Nasi goreng, фрукты и напиток', 210000, 35, 'bi-bowl-hot', 20);
        $this->service($company, $food, 'Room service', 'Заказ из меню ресторана в номер', null, 40, 'bi-bell', 30);

        $housekeeping = $this->category($company, 'Для номера', 'bi-house-heart', 20);
        $towels = $this->service($company, $housekeeping, 'Дополнительные полотенца', 'Комплект банных полотенец', null, 15, 'bi-droplet', 10);
        $cleaning = $this->service($company, $housekeeping, 'Уборка номера', 'Полная уборка в удобное время', null, 45, 'bi-stars', 20);
        $this->service($company, $housekeeping, 'Прачечная', 'Заберём вещи и вернём после стирки', 75000, 240, 'bi-basket', 30);

        $transport = $this->category($company, 'Транспорт', 'bi-car-front', 30);
        $transfer = $this->service($company, $transport, 'Трансфер в аэропорт', 'Частный автомобиль до аэропорта DPS', 350000, 60, 'bi-airplane', 10);

        $this->call(ServiceCatalogSeeder::class);
        $this->call(BackgroundSetSeeder::class);

        $requestData = [
            [$towels, '305', 'Anna Petrova', 'Принести два комплекта полотенец', RequestStatus::New, RequestPriority::High, null, now()->addMinutes(10)],
            [$cleaning, '204', 'Liam Wilson', 'Уборка номера после 14:00', RequestStatus::Accepted, RequestPriority::Normal, $managers[0], now()->addMinutes(80)],
            [$transfer, '118', 'Sofia Garcia', 'Трансфер в аэропорт к 18:30', RequestStatus::InProgress, RequestPriority::High, $managers[1], now()->addHours(3)],
            [null, '412', 'Noah Brown', 'Не работает кондиционер', RequestStatus::WaitingGuest, RequestPriority::Urgent, $managers[0], now()->subMinutes(15)],
            [$towels, '221', 'Emma Lee', 'Детская кроватка в номер', RequestStatus::Completed, RequestPriority::Normal, $managers[1], now()->subHour()],
        ];

        foreach ($requestData as [$service, $room, $guest, $title, $status, $priority, $assignee, $dueAt]) {
            $item = ServiceRequest::create([
                'public_id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'service_node_id' => $service?->id,
                'assigned_to' => $assignee?->id,
                'created_by' => $owner->id,
                'source' => 'guest',
                'room_number' => $room,
                'guest_name' => $guest,
                'title' => $title,
                'description' => 'Заявка из демонстрационного набора Workspace.',
                'status' => $status,
                'priority' => $priority,
                'price_minor' => $service?->price_minor,
                'due_at' => $dueAt,
                'accepted_at' => $status !== RequestStatus::New ? now()->subMinutes(20) : null,
                'completed_at' => $status === RequestStatus::Completed ? now()->subMinutes(5) : null,
                'created_at' => now()->subMinutes(random_int(15, 180)),
                'updated_at' => now(),
            ]);
            $item->history()->create([
                'user_id' => $owner->id,
                'to_status' => $status->value,
                'note' => 'Демонстрационная заявка',
                'created_at' => $item->created_at,
            ]);
        }

        $owner->notify(new WorkspaceNotification([
            'title' => 'Workspace готов к работе',
            'body' => 'Каталог услуг, команда и канбан заявок настроены.',
            'url' => route('workspace.dashboard'),
            'icon' => 'bi-stars',
        ]));
    }

    private function category(Company $company, string $name, string $icon, int $order, ?ServiceNode $parent = null): ServiceNode
    {
        return ServiceNode::create(['company_id' => $company->id, 'parent_id' => $parent?->id, 'type' => ServiceNodeType::Category, 'name' => $name, 'translations' => ServiceTranslations::for($name), 'icon' => $icon, 'sort_order' => $order]);
    }

    private function service(Company $company, ServiceNode $parent, string $name, string $description, ?int $price, int $sla, string $icon, int $order): ServiceNode
    {
        return ServiceNode::create(['company_id' => $company->id, 'parent_id' => $parent->id, 'type' => ServiceNodeType::Service, 'name' => $name, 'description' => $description, 'translations' => ServiceTranslations::for($name), 'background_key' => $this->backgroundFor($name), 'price_minor' => $price, 'sla_minutes' => $sla, 'icon' => $icon, 'sort_order' => $order]);
    }

    private function backgroundFor(string $name): string
    {
        return match ($name) {
            'Континентальный завтрак', 'Завтрак по-балийски', 'Room service' => 'food',
            'Дополнительные полотенца', 'Уборка номера', 'Прачечная' => 'room',
            'Трансфер в аэропорт' => 'transport',
            default => 'wellness',
        };
    }
}
