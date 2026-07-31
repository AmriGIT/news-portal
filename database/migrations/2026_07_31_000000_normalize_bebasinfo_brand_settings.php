<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $updates = [
            'site_name' => 'BebasInfo',
            'default_seo_title' => 'BebasInfo - Informasi Bebas untuk Semua',
            'site_description' => 'BebasInfo menyajikan berita terkini seputar nasional, ekonomi, teknologi, olahraga, dan gaya hidup dengan bahasa jelas dan mudah dipahami.',
            'site_tagline' => 'Informasi bebas, untuk semua.',
        ];

        foreach ($updates as $key => $value) {
            DB::table('site_settings')
                ->where('key', $key)
                ->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
