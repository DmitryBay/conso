<?php

use App\Support\ServiceTranslations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_nodes', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('description');
            $table->string('background_key', 30)->nullable()->after('icon');
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->string('locale', 10)->default('ru')->after('guest_name');
        });

        DB::table('service_nodes')->select(['id', 'name'])->orderBy('id')->each(function ($node) {
            $translations = ServiceTranslations::for($node->name);
            if ($translations !== []) {
                DB::table('service_nodes')->where('id', $node->id)->update(['translations' => json_encode($translations, JSON_UNESCAPED_UNICODE)]);
            }
        });

        DB::table('service_nodes')->whereIn('name', ['Континентальный завтрак', 'Завтрак по-балийски', 'Room service'])->update(['background_key' => 'food']);
        DB::table('service_nodes')->whereIn('name', ['Дополнительные полотенца', 'Уборка номера', 'Прачечная'])->update(['background_key' => 'room']);
        DB::table('service_nodes')->where('name', 'Трансфер в аэропорт')->update(['background_key' => 'transport']);
    }

    public function down(): void
    {
        Schema::table('guest_sessions', fn (Blueprint $table) => $table->dropColumn('locale'));
        Schema::table('service_nodes', fn (Blueprint $table) => $table->dropColumn(['translations', 'background_key']));
    }
};
