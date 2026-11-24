<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sppg_id', 50)->nullable()->unique();
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('village')->nullable();
        });

        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->string('contact_person')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('nib_number', 13)->nullable()->unique();
            $table->string('nib_document_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sppg_id']);
            $table->dropColumn('sppg_id');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('village');
        });

        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->dropUnique(['nib_number']);
            $table->dropColumn([
                'contact_person',
                'district',
                'village',
                'postal_code',
                'nib_number',
                'nib_document_path',
            ]);
        });
    }
};
