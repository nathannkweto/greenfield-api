<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConvertSnakeToCamelCase
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Convert regular inputs (Form data / JSON body)
        if (!empty($request->all())) {
            $request->replace($this->convertKeysToCamel($request->all()));
        }

        // 2. Convert uploaded files in Symfony FileBag
        if ($request->files->count() > 0) {
            $request->files->replace($this->convertKeysToCamel($request->allFiles()));
        }

        return $next($request);
    }

    /**
     * Recursively convert array keys to camelCase.
     */
    private function convertKeysToCamel(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $camelKey = Str::camel((string) $key);
            $result[$camelKey] = is_array($value) ? $this->convertKeysToCamel($value) : $value;
        }
        return $result;
    }
}
