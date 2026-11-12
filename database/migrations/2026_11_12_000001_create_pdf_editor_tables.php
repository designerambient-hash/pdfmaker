<?php

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
        // 1. Documents table
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('title')->default('Untitled Document');
            $table->text('description')->nullable();
            $table->string('page_size', 50)->default('A4');
            $table->string('unit', 10)->default('px');
            $table->float('width')->default(794);
            $table->float('height')->default(1123);
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->boolean('is_starred')->default(false)->index();
            $table->enum('status', ['active', 'trash'])->default('active')->index();
            $table->longText('thumbnail')->nullable();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Document Pages table
        Schema::create('document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('page_number')->default(1)->index();
            $table->float('width')->default(794);
            $table->float('height')->default(1123);
            $table->string('background_type', 30)->default('color');
            $table->string('background_color', 50)->default('#ffffff');
            $table->json('background_gradient')->nullable();
            $table->string('background_image')->nullable();
            $table->string('background_pattern')->nullable();
            $table->float('background_opacity')->default(1.0);
            $table->string('border_color', 50)->nullable();
            $table->float('border_width')->default(0);
            $table->string('border_style', 20)->default('solid');
            $table->json('margins')->nullable();
            $table->longText('canvas_data')->nullable();
            $table->longText('thumbnail')->nullable();
            $table->timestamps();
        });

        // 3. Document Assets table
        Schema::create('document_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained('documents')->cascadeOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type', 50)->default('image');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 4. Templates table
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category', 100)->index();
            $table->text('description')->nullable();
            $table->string('page_size', 50)->default('A4');
            $table->float('width')->default(794);
            $table->float('height')->default(1123);
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->longText('thumbnail')->nullable();
            $table->longText('document_data');
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();
        });

        // 5. Document Versions table
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('name')->nullable();
            $table->longText('document_snapshot');
            $table->longText('thumbnail')->nullable();
            $table->timestamps();
        });

        // 6. Saved Colors table
        Schema::create('saved_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('color_code', 50);
            $table->string('color_type', 30)->default('hex');
            $table->string('name', 100)->nullable();
            $table->timestamps();
        });

        // 7. Custom Fonts table
        Schema::create('custom_fonts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('font_name');
            $table->string('font_family');
            $table->string('font_weight', 30)->default('normal');
            $table->string('font_style', 30)->default('normal');
            $table->string('font_file_path');
            $table->string('font_format', 20)->default('woff2');
            $table->timestamps();
        });

        // 8. Signatures table
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('name')->default('My Signature');
            $table->longText('image_data');
            $table->timestamps();
        });

        // 9. User Settings table
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->json('settings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('custom_fonts');
        Schema::dropIfExists('saved_colors');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('document_assets');
        Schema::dropIfExists('document_pages');
        Schema::dropIfExists('documents');
    }
};

