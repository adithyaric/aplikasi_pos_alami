<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'po_template')) {
                $table->string('po_template')->nullable()->after('po_number_padding');
            }
        });

        if (Schema::hasColumn('suppliers', 'po_template_docx')
            && Schema::hasColumn('suppliers', 'po_template_xlsx')) {
            DB::statement(
                "UPDATE suppliers SET po_template = COALESCE(po_template_xlsx, po_template_docx) WHERE po_template IS NULL"
            );
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'po_template_xlsx')) {
                $table->dropColumn('po_template_xlsx');
            }

            if (Schema::hasColumn('suppliers', 'po_template_docx')) {
                $table->dropColumn('po_template_docx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'po_template_docx')) {
                $table->string('po_template_docx')->nullable()->after('po_number_padding');
            }

            if (! Schema::hasColumn('suppliers', 'po_template_xlsx')) {
                $table->string('po_template_xlsx')->nullable()->after('po_template_docx');
            }
        });

        if (Schema::hasColumn('suppliers', 'po_template')) {
            DB::statement(
                "UPDATE suppliers SET po_template_docx = CASE WHEN LOWER(po_template) LIKE '%.docx' THEN po_template END, po_template_xlsx = CASE WHEN LOWER(po_template) LIKE '%.xlsx' THEN po_template END"
            );
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'po_template')) {
                $table->dropColumn('po_template');
            }
        });
    }
};
