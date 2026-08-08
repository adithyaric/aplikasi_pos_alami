<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'po_template_docx')) {
                $table->string('po_template_docx')->nullable()->after('po_number_padding');
            }

            if (! Schema::hasColumn('suppliers', 'po_template_xlsx')) {
                $table->string('po_template_xlsx')->nullable()->after('po_template_docx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'po_template_xlsx')) {
                $table->dropColumn('po_template_xlsx');
            }

            if (Schema::hasColumn('suppliers', 'po_template_docx')) {
                $table->dropColumn('po_template_docx');
            }
        });
    }
};
