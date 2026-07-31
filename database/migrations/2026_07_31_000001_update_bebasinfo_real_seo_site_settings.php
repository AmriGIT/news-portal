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
            'site_tagline' => 'Informasi bebas, untuk semua.',
            'site_description' => 'BebasInfo menyajikan berita terkini seputar nasional, ekonomi, teknologi, olahraga, dan gaya hidup dengan bahasa jelas dan mudah dipahami.',
            'default_seo_title' => 'BebasInfo - Informasi Bebas untuk Semua',
            'default_seo_description' => 'BebasInfo menyajikan berita terbaru, akurat, dan mudah dipahami tentang nasional, ekonomi, teknologi, olahraga, dan gaya hidup.',
            'footer_text' => 'BebasInfo adalah media informasi digital dengan prinsip jelas, bebas, dan mudah diakses semua pembaca.',
            'contact_email' => null,
            'contact_phone' => null,
            'contact_address' => null,
            'facebook_url' => null,
            'instagram_url' => null,
            'youtube_url' => null,
            'x_url' => null,
            'tiktok_url' => null,
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
