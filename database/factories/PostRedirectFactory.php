<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostRedirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostRedirect>
 */
class PostRedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = fake()->unique()->slug();
        $destination = fake()->unique()->slug();

        return [
            'post_id' => Post::factory(),
            'source_path' => '/berita/'.$source,
            'destination_path' => '/berita/'.$destination,
            'status_code' => 301,
            'is_active' => true,
            'hit_count' => 0,
            'last_hit_at' => null,
            'old_slug' => $source,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => 302,
        ]);
    }
}
