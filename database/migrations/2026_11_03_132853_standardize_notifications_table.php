<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the early prototype notifications table with Laravel's
     * standard DatabaseNotification schema when needed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $columns = collect(Schema::getColumns('notifications'))->keyBy('name');

        $typeName = $columns['notifiable_type']['type_name'] ?? '';
        $idName = $columns['notifiable_id']['type_name'] ?? '';

        $isStandard = in_array($typeName, ['varchar', 'char', 'string'], true)
            && in_array($idName, ['int', 'integer', 'bigint'], true);

        if ($isStandard) {
            return;
        }

        Schema::drop('notifications');

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::drop('notifications');

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->text('notifiable_id');
            $table->unsignedInteger('notifiable_type');
            $table->text('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_id']);
            $table->index(['notifiable_type']);
        });
    }
};
