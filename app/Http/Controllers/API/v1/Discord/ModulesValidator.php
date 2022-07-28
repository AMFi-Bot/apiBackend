<?php

namespace App\Http\Controllers\API\v1\Discord;

use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Symfony\Component\Translation\MessageSelector;
use Illuminate\Support\Facades\Validator as FacadeValidator;
use Illuminate\Validation\ClosureValidationRule;
use phpDocumentor\Reflection\PseudoTypes\Numeric_;

use App\Models\DiscordGuild;
use Closure;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function PHPSTORM_META\type;

class ModulesValidator
{

    /**
     * 
     * Validates given data as module object
     * 
     * @param string|array $data Module data in json type
     * @param Numeric_ $guild_id Id of guild
     * @param "general" $moduleType Type of module
     * 
     * @return array Validated data
     * 
     */
    public static function validate(string|array $data, $guild_id, $moduleType)
    {
        if (gettype($data) == "string") {
            $data = json_decode($data, true);
        }

        if ($moduleType === "general")
            $rules = ModulesValidator::getGeneralModuleRules($guild_id);
        else {
            throw abort(400, "Invalid module name " . $moduleType);
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                "success" => false,
                "errors" => $validator->errors(),
            ];
        } else {
            return ["success" => true];
        }
    }

    /**
     * 
     * @param Numeric_ $guild_id Id of guild
     * 
     * @return array Rules array
     * 
     */
    public static function getGeneralModuleRules($guild_id): array
    {
        return [
            "log_channel" => [
                "numeric",
                ModulesValidator::getDiscordChannelValidator($guild_id)
            ],
        ];
    }

    /**
     * 
     * @param Numeric_ $guild_id Id of guild
     * 
     * @return Closure Validator rule closure
     * 
     */
    public static function getDiscordChannelValidator($guild_id): Closure
    {
        return function ($attribute, $value, $fail) use ($guild_id) {
            $guild = DiscordGuild::where(["id" => $guild_id])->first();

            if (!isset($guild)) return $fail('validation.discord.guild.not_exist');

            $guild_channels = json_decode($guild->channels, true);

            foreach ($guild_channels as $channel) {
                if ($channel == $value) return;
            }

            $fail('validation.discord.guild.channel.not_exist');
        };
    }
}
