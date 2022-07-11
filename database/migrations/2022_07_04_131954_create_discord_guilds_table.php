<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discord_guilds', function (Blueprint $table) {
            $table->string('id')->unique;
            $table->string('name')->default("");
            $table->string('icon')->nullable();
            $table->json('module_general')->default(json_encode([]));
            $table->json('module_moderation')->default(json_encode([]));
            $table->json('module_automoderation')->default(json_encode([]));
            $table->json('module_commands')->default(json_encode([]));
            $table->json('module_features')->default(json_encode([]));
            $table->json('channels')->default(json_encode([]));
            $table->json('roles')->default(json_encode([]));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_guilds');
    }
};
