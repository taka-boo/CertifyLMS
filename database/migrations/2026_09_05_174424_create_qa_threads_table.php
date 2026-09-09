<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_threads', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // どの資格に関する質問か（certificationsテーブルへの紐付け）
            // ※資格が削除されそうになっても、スレッドが残っていれば削除を拒否する
            $table->foreignUlid('certification_id')
                ->constrained('certifications')
                ->restrictOnDelete();

            // どのユーザーが質問したか（usersテーブルへの紐付け）
            // ※ユーザーが削除されそうになっても、スレッドが残っていれば削除を拒否する
            $table->foreignUlid('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->text('body');

            // スレッドの状態(未解決/解決済)。QaThreadStatus enumに対応
            $table->string('status')->default('open');

            // 解決した日時。一覧・詳細画面での「解決 3時間前」のような相対時刻表示に使う(statusとは別に必要)
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['certification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_threads');
    }
};
