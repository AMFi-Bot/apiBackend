<?php

namespace App\Http\Controllers\API\v1\Discord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp;
use Illuminate\Support\Facades\Log;


class DiscordRequestController extends Controller
{
    /**
     *
     * Fetches Discord API requests
     *
     * @param string $method The discord API request method
     * @param string $path The discord API request path. Must starts without slash
     * @param array  $data Request data. Must be JSON
     * @param array  $headers Request headers
     * @param string $content_type The request content type
     *
     * @return array ["response_data" => "Discord API response",
     *                "response_status" => "The status of the response",
     *                "response_headers" => "Headers of the response"
     *               ]
     *
     */
    public static function fetchRequest(
        string $method,
        string $path,
        array $data = null,
        array $headers = [],
        string $content_type = "application/json",
    ) {
        $API_ENDPOINT = "https://discord.com/api/v10";

        $client = new GuzzleHttp\Client(['base_uri' => $API_ENDPOINT]);

        $headers_send = [
            "Content-type" => $content_type,
        ];
        $headers_send = array_merge($headers_send, $headers);

        $options = [
            'headers' => $headers_send,
            'http_errors' => false,
        ];

        if ($content_type == "application/json")
            $options["json"] = $data;
        elseif ($content_type == "application/x-www-form-urlencoded")
            $options["form_params"] = $data;
        elseif ($content_type == "multipart/form-data")
            $options["multipart"] = $data;
        else
            $options["body"] = $data;

        $response = $client->request($method, $path, $options);

        if ($response->getStatusCode() == 429) {
            Log::critical(
                "Discord API Rate Limit exception. path: $path method: $method " .
                    "response headers: " . json_encode(
                        $response->getHeaders()
                    ) .
                    " response body: " . json_encode($response->getBody())
            );

            DiscordRequestController::fetchRequest($method, $path, $data, $headers);
        }

        return [
            "response_data" => json_decode($response->getBody()->__toString(), true),
            "response_status" => $response->getStatusCode(),
            "response_headers" => $response->getHeaders()
        ];
    }

    /**
     * 
     * Fetches request to Discord API with built-in authorization system
     * 
     * @param string $method The discord API request method
     * @param string $path The discord API request path. Must starts without slash
     * @param array $data Request data. Must be JSON
     * @param array $headers Request headers
     * @param string $content_type The request content type
     * @param string $authorization_type The user authorization type. Accept
     * "Bearer" and "Bot".
     * @param string authorization_token The user authorization token
     * @param Request $u_request The user request. Uses to get authorization token, nullable
     * @param bool $fetch_tokens If true when getting 401 error will automatically 
     * refresh tokens
     * 
     * @return array ["response_data" => "Discord API response",
     *                "response_status" => "The status of the response",
     *                "response_headers" => "Headers of the response"
     *               ]
     * 
     */
    public static function smartRequest(
        string $method,
        string $path,
        array $data = null,
        array $headers = [],
        string $content_type = "application/json",
        string $authorization_type = "Bearer",
        string $authorization_token = null,
        Request $u_request = null,
        bool $fetch_tokens = true,
    ) {

        $headers_send = [];

        if ($authorization_type == "Bearer" && $u_request) {
            $headers_send["Authorization"] = "Bearer " .
                $u_request->user()->discord_token;
        } elseif ($authorization_type == "Bot") {
            $headers_send["Authorization"] = "Bot " .
                env("DISCORD_BOT_TOKEN");
        } elseif ($authorization_type && $authorization_token) {
            $headers_send["Authorization"] = $authorization_type . " " .
                $authorization_token;
        } elseif ($authorization_token) {
            $headers_send["Authorization"] = $authorization_token;
        }

        $headers_send = array_merge($headers_send, $headers);

        $response = DiscordRequestController::fetchRequest(
            $method,
            $path,
            $data,
            $headers_send,
            $content_type
        );

        if ($response["response_status"] == 401) {
            if ($authorization_type == "Bearer" && $fetch_tokens && $u_request) {
                // Try get access token by token

                $client_id = env("DISCORD_CLIENT_ID");
                $client_secret = env("DISCORD_CLIENT_SECRET");

                $body = [
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $u_request->user()->discord_refresh_token,
                ];

                $resp = DiscordRequestController::fetchRequest(
                    "POST",
                    "oauth2/token",
                    $body,
                    [],
                    "application/x-www-form-urlencoded",
                );

                if ($resp["response_status"] == 200) {
                    $access_token_response = $resp["response_data"];

                    $u_request->user()->discord_token =
                        $access_token_response["access_token"];

                    $u_request->user()->discord_refresh_token =
                        $access_token_response["refresh_token"];

                    $u_request->user()->save();

                    $resp = DiscordRequestController::smartRequest(
                        $method,
                        $path,
                        $data,
                        $headers,
                        $content_type,
                        $authorization_type,
                        $authorization_token,
                        $u_request,
                        false
                    );

                    return $resp;
                } else {

                    $u_request->user()->discord_id = "";
                    $u_request->user()->discord_token = "";
                    $u_request->user()->discord_refresh_token = "";
                    $u_request->user()->save();

                    return $response;
                }
            } elseif ($authorization_type == "Bot") { // throw Critrical error
                Log::emergency("Bot authorization token is invalid");
                return $response;
            }
        }
        return $response;
    }
}
