<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // テスト結果を記録するたびに1行追加する（上書きしない）
        Schema::create('test_result_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_result_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['test_result_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_result_histories');
    }
};
