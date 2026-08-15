<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $demoNodes = DB::table('service_nodes')
            ->where('smart_home_enabled', true)
            ->get(['id', 'parent_id']);

        DB::table('service_nodes')->whereIn('id', $demoNodes->pluck('id'))->delete();

        foreach ($demoNodes->pluck('parent_id')->filter()->unique() as $categoryId) {
            $category = DB::table('service_nodes')->where('id', $categoryId)->first(['id', 'name']);

            if ($category
                && in_array($category->name, ['Умный дом', 'Smart home'], true)
                && ! DB::table('service_nodes')->where('parent_id', $category->id)->exists()) {
                DB::table('service_nodes')->where('id', $category->id)->delete();
            }
        }

        Schema::table('service_nodes', function (Blueprint $table): void {
            $table->dropColumn('smart_home_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('service_nodes', function (Blueprint $table): void {
            $table->boolean('smart_home_enabled')->default(false)->after('option_keys');
        });
    }
};
