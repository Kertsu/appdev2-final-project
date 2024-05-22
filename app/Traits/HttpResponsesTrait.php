<?php

namespace App\Traits;

trait HttpResponsesTrait
{
    private function success($data, $message = null, $code = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message
        ], $code);
    }

    private function error($data = null, $error, $code)
    {
        return response()->json([
            'data' => $data,
            'error' => $error
        ], $code);
    }
}
