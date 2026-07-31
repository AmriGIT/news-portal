<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'author_id' => User::factory()->editor(),
            'editor_id' => null,
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
            'excerpt' => fake()->optional()->paragraph(),
            'content' => fake()->paragraphs(8, true),
            'featured_image' => fake()->optional()->imageUrl(1200, 675, 'news'),
            'featured_image_alt' => fake()->optional()->sentence(5),
            'featured_image_caption' => fake()->optional()->sentence(8),
            'featured_image_credit' => fake()->optional()->name(),
            'status' => PostStatus::Draft,
            'is_featured' => false,
            'published_at' => null,
            'seo_title' => fake()->optional()->sentence(7),
            'seo_description' => fake()->optional()->sentence(14),
            'canonical_url' => null,
            'og_image' => null,
            'robots_index' => true,
            'robots_follow' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Review,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Scheduled,
            'published_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Archived,
            'published_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
