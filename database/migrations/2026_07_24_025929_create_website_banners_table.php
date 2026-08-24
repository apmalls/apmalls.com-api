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
        Schema::create('website_banners', function (Blueprint $table) {

            $table->id();

            // Basic Information
            $table->string('title');
            $table->string('sub_title')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Banner Images
            $table->string('desktop_image');
            $table->string('mobile_image')->nullable();

            // Banner Type
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('video_url')->nullable();

            // Banner Position
            $table->enum('position', [
                'home_hero',
                'home_top',
                'home_middle',
                'home_bottom',
                'category',
                'product',
                'offer',
                'popup',
            ])->default('home_hero');

            // Call To Action
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('open_new_tab')->default(false);

            // Display Settings
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);

            // Schedule
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'position', 'sort_order']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_banners');
    }
};
