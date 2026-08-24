<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('code')->unique();

            $table->enum('type', [

                'thermal',
                'label',
                'inkjet',
                'laser'

            ]);

            $table->enum('driver', [

                'network',
                'windows',
                'usb'

            ]);

            $table->string('ip_address')->nullable();

            $table->integer('port')->nullable();

            $table->string('device_name')->nullable();

            $table->string('paper_size')->default('80mm');

            $table->boolean('is_default')->default(false);

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
