<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_open_category_list(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($admin)->get(CategoryResource::getUrl('index'))->assertOk();
        $this->actingAs($editor)->get(CategoryResource::getUrl('index'))->assertOk();
    }

    public function test_admin_can_create_category_and_slug_is_generated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Berita Nasional',
                'description' => 'Kategori berita nasional.',
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas(Category::class, [
            'name' => 'Berita Nasional',
            'slug' => 'berita-nasional',
        ]);
    }

    public function test_editor_cannot_create_or_edit_category(): void
    {
        $editor = User::factory()->editor()->create();
        $category = Category::factory()->create();

        $this->actingAs($editor);

        $this->get(CategoryResource::getUrl('create'))->assertForbidden();
        $this->get(CategoryResource::getUrl('edit', ['record' => $category]))->assertForbidden();
    }

    public function test_category_slug_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['slug' => 'nasional']);

        $this->actingAs($admin);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Nasional Lain',
                'slug' => 'nasional',
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_admin_can_edit_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm([
                'name' => 'Kategori Update',
                'slug' => 'kategori-update',
                'description' => 'Deskripsi baru',
                'seo_title' => 'SEO Baru',
                'seo_description' => 'Deskripsi SEO baru',
                'is_active' => false,
                'sort_order' => 20,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas(Category::class, [
            'id' => $category->id,
            'name' => 'Kategori Update',
            'slug' => 'kategori-update',
            'is_active' => false,
            'sort_order' => 20,
        ]);
    }

    public function test_admin_can_delete_unused_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing(Category::class, [
            'id' => $category->id,
        ]);
    }

    public function test_category_used_by_posts_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Post::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->callAction(DeleteAction::class)
            ->assertActionHalted();

        $this->assertDatabaseHas(Category::class, [
            'id' => $category->id,
        ]);
    }

    public function test_category_posts_count_filter_and_search_work(): void
    {
        $admin = User::factory()->admin()->create();
        $active = Category::factory()->create(['name' => 'Politik Nasional', 'slug' => 'politik-nasional', 'is_active' => true]);
        $inactive = Category::factory()->inactive()->create(['name' => 'Arsip Lama', 'slug' => 'arsip-lama']);
        Post::factory()->count(2)->create(['category_id' => $active->id]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertTableColumnStateSet('posts_count', 2, $active)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords(collect([$active]))
            ->assertCanNotSeeTableRecords(collect([$inactive]))
            ->resetTableFilters()
            ->searchTable('Politik')
            ->assertCanSeeTableRecords(collect([$active]))
            ->assertCanNotSeeTableRecords(collect([$inactive]));
    }
}
