<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscordGuild extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'channels',
        'roles',
        'module_general',
        'module_moderation',
        'module_automoderation',
        'module_commands',
        'module_features',
    ];
}
