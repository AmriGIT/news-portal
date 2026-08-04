<?php

use App\Enums\NewsImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->json('abilities');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('news_imports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('import_token_id')->nullable()->constrained('import_tokens')->nullOnDelete();
            $table->string('original_filename');
            $table->string('package_hash', 64)->nullable()->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('requested_publish_mode')->default('draft');
            $table->string('status')->default(NewsImportStatus::Uploaded->value)->index();
            $table->string('content_mode')->nullable();
            $table->string('image_mode')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('valid_items')->default(0);
            $table->unsignedInteger('invalid_items')->default(0);
            $table->unsignedInteger('imported_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->json('warnings')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('news_import_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_import_id')->constrained('news_imports')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->index();
            $table->string('requested_status')->nullable();
            $table->string('final_status')->nullable();
            $table->json('source_ids')->nullable();
            $table->string('image_path')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamps();
        });

        Schema::create('news_import_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_import_id')->constrained('news_imports')->cascadeOnDelete();
            $table->string('source_id')->index();
            $table->text('requested_url')->nullable();
            $table->text('final_url')->nullable();
            $table->string('publisher')->nullable();
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->timestamps();

            $table->unique(['news_import_id', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_import_sources');
        Schema::dropIfExists('news_import_items');
        Schema::dropIfExists('news_imports');
        Schema::dropIfExists('import_tokens');
    }
};
