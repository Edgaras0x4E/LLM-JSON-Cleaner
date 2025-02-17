<?php

namespace Edgaras\LLMJsonCleaner;

class JsonCleaner
{ 
    
    public static function extract(string $input, bool $asArray = true): array|string|null
    {
        // Try direct JSON decoding first (best case scenario)
        $decoded = json_decode($input, $asArray);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        // Match JSON inside ```json ``` blocks
        if (preg_match('/```json\s*([\s\S]+?)\s*```/', $input, $matches)) {
            $decoded = json_decode(trim($matches[1]), $asArray);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }

        // Match JSON inside /* */ comments
        if (preg_match('/\/\*([\s\S]+?)\*\//', $input, $matches)) {
            $decoded = json_decode(trim($matches[1]), $asArray);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }

        // Match JSON inside { ... } but ignore anything before or after
        if (preg_match('/(\{[\s\S]+\})/', $input, $matches)) {
            $decoded = json_decode(trim($matches[1]), $asArray);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }

        return null;
    }
}
