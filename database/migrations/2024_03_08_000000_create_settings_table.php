<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generic key/value store for school-wide toggles that don't
        // belong to any one row — starting with whether position/ranking
        // is shown on results at all. Deliberately not a dedicated
        // boolean column bolted onto an unrelated table; more settings
        // will likely follow and this avoids a new migration + column
        // for each one.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
