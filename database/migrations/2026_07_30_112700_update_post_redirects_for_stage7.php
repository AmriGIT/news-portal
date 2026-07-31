<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('post_redirects', 'source_path')) {
            return;
        }

        Schema::table('post_redirects', function (Blueprint $table): void {
            $table->string('source_path')->nullable()->after('post_id');
            $table->string('destination_path')->nullable()->after('source_path');
            $table->unsignedSmallInteger('status_code')->default(301)->after('destination_path');
            $table->boolean('is_active')->default(true)->index()->after('status_code');
            $table->unsignedBigInteger('hit_count')->default(0)->after('is_active');
            $table->timestamp('last_hit_at')->nullable()->after('hit_count');
        });

        DB::table('post_redirects')
            ->leftJoin('posts', 'post_redirects.post_id', '=', 'posts.id')
            ->select([
                'post_redirects.id',
                'post_redirects.old_slug',
                'posts.slug as current_slug',
            ])
            ->orderBy('post_redirects.id')
            ->get()
            ->each(function (object $redirect): void {
                $oldSlug = trim((string) $redirect->old_slug, '/');
                $currentSlug = trim((string) ($redirect->current_slug ?: $redirect->old_slug), '/');

                DB::table('post_redirects')
                    ->where('id', $redirect->id)
                    ->update([
                        'source_path' => '/berita/'.$oldSlug,
                        'destination_path' => '/berita/'.$currentSlug,
                        'status_code' => 301,
                        'is_active' => true,
                    ]);
            });

        Schema::table('post_redirects', function (Blueprint $table): void {
            $table->unique('source_path');
            $table->index(['status_code', 'is_active']);
        });

        $this->makePostRelationNullable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('post_redirects', 'source_path')) {
            return;
        }

        Schema::table('post_redirects', function (Blueprint $table): void {
            $table->dropUnique(['source_path']);
            $table->dropIndex(['status_code', 'is_active']);
            $table->dropColumn([
                'source_path',
                'destination_path',
                'status_code',
                'is_active',
                'hit_count',
                'last_hit_at',
            ]);
        });
    }

    private function makePostRelationNullable(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('post_redirects', function (Blueprint $table): void {
            $table->dropForeign(['post_id']);
            $table->foreignId('post_id')->nullable()->change();
            $table->foreign('post_id')->references('id')->on('posts')->nullOnDelete();
        });
    }
};
