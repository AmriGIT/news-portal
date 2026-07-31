<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed development data.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = config('admin.development_user');

        $adminUser = User::query()->updateOrCreate([
            'email' => $admin['email'],
        ], [
            'name' => $admin['name'],
            'password' => Hash::make($admin['password']),
            'email_verified_at' => now(),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $editors = User::factory()
            ->editor()
            ->count(3)
            ->sequence(
                ['name' => 'Editor Satu', 'email' => 'editor1@example.test'],
                ['name' => 'Editor Dua', 'email' => 'editor2@example.test'],
                ['name' => 'Editor Tiga', 'email' => 'editor3@example.test'],
            )
            ->create();

        $categories = Category::factory()
            ->count(5)
            ->sequence(
                ['name' => 'Nasional', 'slug' => 'nasional', 'sort_order' => 10],
                ['name' => 'Teknologi', 'slug' => 'teknologi', 'sort_order' => 20],
                ['name' => 'Bisnis', 'slug' => 'bisnis', 'sort_order' => 30],
                ['name' => 'Olahraga', 'slug' => 'olahraga', 'sort_order' => 40],
                ['name' => 'Hiburan', 'slug' => 'hiburan', 'sort_order' => 50],
            )
            ->create();

        $tags = Tag::factory()->count(15)->create();

        $postIndex = 0;
        $state = function () use ($adminUser, $categories, $editors, &$postIndex): array {
            $index = $postIndex++;

            return [
                'author_id' => $editors->random()->id,
                'editor_id' => $index % 3 === 0 ? $adminUser->id : $editors->random()->id,
                'category_id' => $categories->random()->id,
                'is_featured' => $index < 5,
            ];
        };

        $posts = collect()
            ->merge(Post::factory()->draft()->count(6)->state($state)->create())
            ->merge(Post::factory()->review()->count(6)->state($state)->create())
            ->merge(Post::factory()->scheduled()->count(6)->state($state)->create())
            ->merge(Post::factory()->published()->count(8)->state($state)->create())
            ->merge(Post::factory()->archived()->count(4)->state($state)->create());

        $posts->each(function (Post $post) use ($tags): void {
            $post->tags()->sync($tags->random(fake()->numberBetween(1, 4))->pluck('id')->all());
        });

        $posts->take(5)->each(function (Post $post): void {
            $oldSlug = 'old-'.$post->slug;

            PostRedirect::query()->firstOrCreate([
                'source_path' => '/berita/'.$oldSlug,
            ], [
                'post_id' => $post->id,
                'destination_path' => '/berita/'.$post->slug,
                'status_code' => 301,
                'is_active' => true,
                'old_slug' => $oldSlug,
            ]);
        });

        $this->seedSiteSettings();
    }

    private function seedSiteSettings(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'BebasInfo',
                'group' => 'general',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => 'BebasInfo menyajikan berita terkini seputar nasional, ekonomi, teknologi, olahraga, dan gaya hidup dengan bahasa jelas dan mudah dipahami.',
                'group' => 'general',
                'type' => 'text',
                'is_public' => true,
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Informasi bebas, untuk semua.',
                'group' => 'general',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'contact_email',
                'value' => null,
                'group' => 'contact',
                'type' => 'email',
                'is_public' => true,
            ],
            [
                'key' => 'default_seo_title',
                'value' => 'BebasInfo - Informasi Bebas untuk Semua',
                'group' => 'seo',
                'type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'default_seo_description',
                'value' => 'BebasInfo menyajikan berita terbaru, akurat, dan mudah dipahami tentang nasional, ekonomi, teknologi, olahraga, dan gaya hidup.',
                'group' => 'seo',
                'type' => 'text',
                'is_public' => true,
            ],
            [
                'key' => 'default_robots_index',
                'value' => '1',
                'group' => 'seo',
                'type' => 'boolean',
                'is_public' => true,
            ],
            [
                'key' => 'default_robots_follow',
                'value' => '1',
                'group' => 'seo',
                'type' => 'boolean',
                'is_public' => true,
            ],
            [
                'key' => 'posts_per_page',
                'value' => '10',
                'group' => 'content',
                'type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'footer_text',
                'value' => 'BebasInfo adalah media informasi digital dengan prinsip jelas, bebas, dan mudah diakses semua pembaca.',
                'group' => 'footer',
                'type' => 'text',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::query()->updateOrCreate([
                'key' => Str::slug($setting['key'], '_'),
            ], $setting);
        }
    }
}
