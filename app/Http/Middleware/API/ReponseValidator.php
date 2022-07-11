<?php

namespace App\Http\Middleware\API;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\Response as SR;

class ReponseValidator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->expectsJson();

        $response = $next($request);

        // $resp_data = json_decode($response->getContent());
        // if (!$resp_data) $resp_data = $response->getContent();

        $resp = [];

        if (gettype($response) == 'array') {
            $resp = $response;
        } elseif (gettype($response) == 'object') {
            $resp =
                json_decode($response->getContent(), true);

            if (!$resp) {
                $resp = $response->getContent();
            }

            if (gettype($resp) == 'array') {
                $resp['status'] =
                    array_key_exists('status', $resp) ? $resp['status'] : $response->getStatusCode();
            } else {
                $status = $response->getStatusCode();

                if ($resp)
                    $resp = ["message" => $resp, "status" => $status];
                else $resp = ["status" => $status];
            }
        }

        $status = $resp['status'];

        unset($resp['status']);


        $headers = [];

        if (gettype($response) == 'object') {
            $headers = $response->headers->all();
        } else if (array_key_exists("headers", $resp) && gettype($resp["headers"]) == 'array')
            $headers = $resp["headers"];

        $headers["content-type"] = "application/json";

        $response = [];

        $var = substr($status, 0, 1);

        if ($var == 2) {
            $response = [
                "success" => true,
                "status" => $status,
                "status_description" => SR::$statusTexts[$status],
            ];

            if ($resp) $response["data"]  = $resp;
        } elseif ($var == 3) {
            $response = [
                "status" => $status,
                "status_description" => SR::$statusTexts[$status],
                "response_type" => "Redirect quest",
            ];

            if ($resp) $response["data"]  = $resp;
        } elseif ($var == 4) {
            $response = [
                "success" => false,
                "status" => $status,
                "status_description" => SR::$statusTexts[$status],
                "response_type" => "Client error.",
            ];

            if ($resp) $response["error"]  = $resp;
        } elseif ($var == 5) {
            $response = [
                "success" => false,
                "status" => $status,
                "status_description" => SR::$statusTexts[$status],
                "response_type" => "Server error"
            ];
            Log::alert([
                "error_type" => "An error from user request",
                "request_path" => $request->url(),
            ]);
            Log::error($resp);
        }


        return response()->json($response, $response["status"], $headers);
    }
}
