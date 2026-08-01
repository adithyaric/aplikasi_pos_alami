<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refunds')) {
            return;
        }

        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])
                    ->default('approved')
                    ->after('notes');
            }

            if (! Schema::hasColumn('refunds', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('refunds', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('refunds', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('refunds')) {
            return;
        }

        Schema::table('refunds', function (Blueprint $table) {
            if (Schema::hasColumn('refunds', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            $dropColumns = [];

            foreach (['approval_note', 'approved_at', 'status'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
