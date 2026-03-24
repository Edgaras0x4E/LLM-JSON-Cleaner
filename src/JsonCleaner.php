<?php

namespace Edgaras\LLMJsonCleaner;

class JsonCleaner
{

    public static function extract(string $input, bool $asArray = true): array|string|null
    { 
        $decoded = json_decode($input, $asArray);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }
 
        $sanitized = self::sanitize($input);
        $decoded = json_decode($sanitized, $asArray);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }
 
        if (preg_match('/```json\s*([\s\S]+?)\s*```/', $input, $matches)) {
            $candidate = trim($matches[1]);
            $decoded = self::tryDecode($candidate, $asArray);
            if ($decoded !== null) {
                return $decoded;
            }
        }
 
        if (preg_match('/\/\*([\s\S]+?)\*\//', $input, $matches)) {
            $candidate = trim($matches[1]);
            $decoded = self::tryDecode($candidate, $asArray);
            if ($decoded !== null) {
                return $decoded;
            }
        }
 
        if (preg_match('/(\{[\s\S]+\})/', $input, $matches)) {
            $candidate = trim($matches[1]);
            $decoded = self::tryDecode($candidate, $asArray);
            if ($decoded !== null) {
                return $decoded;
            }
        }
 
        if (preg_match('/(\[[\s\S]+\])/', $input, $matches)) {
            $candidate = trim($matches[1]);
            $decoded = self::tryDecode($candidate, $asArray);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    private static function tryDecode(string $json, bool $asArray): array|string|null
    {
        $decoded = json_decode($json, $asArray);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        $sanitized = self::sanitize($json);
        $decoded = json_decode($sanitized, $asArray);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $asArray ? $decoded : json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    private static function sanitize(string $json): string
    {
        $json = self::fixSingleQuotes($json);
        $json = self::fixUnquotedKeys($json);
        $json = self::removeTrailingCommas($json);
        $json = self::sanitizeMultilineStrings($json);
        return $json;
    }

    private static function fixSingleQuotes(string $json): string
    {
        $length = strlen($json);
        $result = '';
        $i = 0;
        $inDouble = false;
        $inSingle = false;

        while ($i < $length) {
            $char = $json[$i];

            if ($inDouble) {
                if ($char === '\\' && $i + 1 < $length) {
                    $result .= $char . $json[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($char === '"') {
                    $inDouble = false;
                }
                $result .= $char;
                $i++;
            } elseif ($inSingle) {
                if ($char === '\\' && $i + 1 < $length) {
                    $next = $json[$i + 1];
                    if ($next === "'") {
                        $result .= "'";
                        $i += 2;
                    } else {
                        $result .= $char . $next;
                        $i += 2;
                    }
                    continue;
                }
                if ($char === "'") {
                    $inSingle = false;
                    $result .= '"';
                    $i++;
                    continue;
                }
                if ($char === '"') {
                    $result .= '\\"';
                    $i++;
                    continue;
                }
                $result .= $char;
                $i++;
            } else {
                if ($char === '"') {
                    $inDouble = true;
                    $result .= $char;
                } elseif ($char === "'") {
                    $inSingle = true;
                    $result .= '"';
                } else {
                    $result .= $char;
                }
                $i++;
            }
        }

        return $result;
    }

    private static function fixUnquotedKeys(string $json): string
    {
        $length = strlen($json);
        $result = '';
        $inString = false;
        $i = 0;

        while ($i < $length) {
            $char = $json[$i];

            if ($inString) {
                if ($char === '\\' && $i + 1 < $length) {
                    $result .= $char . $json[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }
                $result .= $char;
                $i++;
            } else {
                if ($char === '"') {
                    $inString = true;
                    $result .= $char;
                    $i++;
                } elseif (ctype_alpha($char) || $char === '_') {
                    $word = '';
                    $j = $i;
                    while ($j < $length && (ctype_alnum($json[$j]) || $json[$j] === '_')) {
                        $word .= $json[$j];
                        $j++;
                    }
                    $k = $j;
                    while ($k < $length && ($json[$k] === ' ' || $json[$k] === "\t" || $json[$k] === "\n" || $json[$k] === "\r")) {
                        $k++;
                    }
                    if ($k < $length && $json[$k] === ':') {
                        $result .= '"' . $word . '"';
                    } else {
                        $result .= $word;
                    }
                    $i = $j;
                } else {
                    $result .= $char;
                    $i++;
                }
            }
        }

        return $result;
    }

    private static function removeTrailingCommas(string $json): string
    {
        $length = strlen($json);
        $result = '';
        $inString = false;
        $i = 0;

        while ($i < $length) {
            $char = $json[$i];

            if ($inString) {
                if ($char === '\\' && $i + 1 < $length) {
                    $result .= $char . $json[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }
                $result .= $char;
                $i++;
            } else {
                if ($char === '"') {
                    $inString = true;
                    $result .= $char;
                    $i++;
                } elseif ($char === ',') {
                    $j = $i + 1;
                    while ($j < $length && ($json[$j] === ' ' || $json[$j] === "\t" || $json[$j] === "\n" || $json[$j] === "\r")) {
                        $j++;
                    }
                    if ($j < $length && ($json[$j] === '}' || $json[$j] === ']')) {
                        $i++;
                    } else {
                        $result .= $char;
                        $i++;
                    }
                } else {
                    $result .= $char;
                    $i++;
                }
            }
        }

        return $result;
    }

    private static function sanitizeMultilineStrings(string $json): string
    {
        $length = strlen($json);
        $result = '';
        $inString = false;
        $i = 0;

        while ($i < $length) {
            $char = $json[$i];

            if ($inString) {
                if ($char === '\\' && $i + 1 < $length) {
                    $result .= $char . $json[$i + 1];
                    $i += 2;
                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                    $result .= $char;
                    $i++;
                    continue;
                }

                if ($char === "\r" && $i + 1 < $length && $json[$i + 1] === "\n") {
                    $result .= '\\n';
                    $i += 2;
                    continue;
                }
                if ($char === "\n") {
                    $result .= '\\n';
                    $i++;
                    continue;
                }
                if ($char === "\r") {
                    $result .= '\\n';
                    $i++;
                    continue;
                }
                if ($char === "\t") {
                    $result .= '\\t';
                    $i++;
                    continue;
                }

                $result .= $char;
                $i++;
            } else {
                if ($char === '"') {
                    $inString = true;
                }
                $result .= $char;
                $i++;
            }
        }

        return $result;
    }
}
