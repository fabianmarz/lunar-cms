<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // The panel's gate only grants handles present in the manifest, which
        // reads spatie permission rows — the row must exist or the handle
        // denies for everyone.
        Permission::firstOrCreate([
            'name' => 'cms:manage-pages',
            'guard_name' => config('lunar.staff.guard', 'staff'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');

        Permission::where('name', 'cms:manage-pages')->delete();
    }
};
