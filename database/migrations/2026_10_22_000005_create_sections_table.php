<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // シート → 大項目 → 中項目 → 小項目 の階層（最大4階層）
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('depth');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'parent_id']);
        });

        Schema::table('test_cases', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('project_id')
                ->constrained('sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });
        Schema::dropIfExists('sections');
    }
};
