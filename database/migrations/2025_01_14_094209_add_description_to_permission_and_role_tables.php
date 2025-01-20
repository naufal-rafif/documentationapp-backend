<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onUpdate('cascade')->onDelete('set null');
            $table->string('description')->nullable();
            $table->integer('level')->nullable();
        });
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
            $table->unsignedBigInteger('group_id')->nullable()->default(null);
            $table->foreign('group_id')->references('id')->on('permission_groups')->onUpdate('cascade')->onDelete('set null');
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->integer('level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('uuid');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->dropColumn('description');
            $table->dropColumn('level');
        });
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('uuid');
            $table->dropColumn('label');
            $table->dropColumn('description');
            $table->dropColumn('group_id');
            $table->dropColumn('level');
        });
    }
};
