<?php

namespace Edgaras\LLMJsonCleaner;

class JsonValidator
{
    public static function validateSchema(array $data, array $schema): true|array
    {
        if (!self::isList($data)) {
            $data = [$data];
        }

        $errors = [];
 
        $allowedTopLevelKeys = [];
        foreach ($schema as $schemaKey => $_) {
            $parts = explode('.', $schemaKey);
            $allowedTopLevelKeys[] = $parts[0];
        }
        $allowedTopLevelKeys = array_unique($allowedTopLevelKeys);
 
        foreach ($data as $index => $item) {
            $itemErrors = [];
 
            foreach ($schema as $key => $rules) {
                self::singlePassCheck($item, $key, $rules, $itemErrors);
            }
 
            foreach ($item as $fieldKey => $_) {
                if (!in_array($fieldKey, $allowedTopLevelKeys, true)) {
                    if (!isset($itemErrors[$fieldKey])) {
                        $itemErrors[$fieldKey] = [];
                    }
                    $itemErrors[$fieldKey][] = "Unexpected field: $fieldKey";
                }
            }

            if (!empty($itemErrors)) {
                $errors[$index] = $itemErrors;
            }
        }

        return empty($errors) ? true : $errors;
    }
 
    private static function singlePassCheck(array $item, string $key, array $rules, array &$itemErrors): void
    { 
        if (str_contains($key, '.*.')) {
            [$baseKey, $rest] = explode('.*.', $key, 2);
 
            if (!array_key_exists($baseKey, $item) || !is_array($item[$baseKey])) {
                self::initErrorArray($itemErrors, $baseKey);
                $itemErrors[$baseKey][] = "Missing or invalid array field: $baseKey";
                return;
            }
 
            foreach ($item[$baseKey] as $i => $subItem) {
                $iKey = (string)$i;
                self::initErrorArray($itemErrors, $baseKey);
 
                if (!isset($itemErrors[$baseKey][$iKey]) || !is_array($itemErrors[$baseKey][$iKey])) {
                    $itemErrors[$baseKey][$iKey] = [];
                }
 
                if (str_contains($rest, '.*.')) {
                    if (!is_array($subItem)) {
                        $itemErrors[$baseKey][$iKey][] =
                            "Expected object/array under $baseKey, got " . gettype($subItem);
                        continue;
                    }
                    self::singlePassCheck($subItem, $rest, $rules, $itemErrors[$baseKey][$iKey]);
                } else {
                    if ($rest === '') {
                        self::validateValue($subItem, $rules, $itemErrors[$baseKey][$iKey]);
                    } else {
                        if (!is_array($subItem)) {
                            $itemErrors[$baseKey][$iKey][] =
                                "Expected object/array under $baseKey, got " . gettype($subItem);
                        } else {
                            self::directKeyCheck($subItem, $rest, $rules, $itemErrors[$baseKey][$iKey]);
                        }
                    }
                }
 
                if (empty($itemErrors[$baseKey][$iKey])) {
                    unset($itemErrors[$baseKey][$iKey]);
                }
            }
 
            if (isset($itemErrors[$baseKey]) && empty($itemErrors[$baseKey])) {
                unset($itemErrors[$baseKey]);
            }
        } else {
            self::directKeyCheck($item, $key, $rules, $itemErrors);
        }
    }
 
    private static function directKeyCheck(array $item, string $key, array $rules, array &$itemErrors): void
    {
        if (str_ends_with($key, '.*')) {
            $actualKey = substr($key, 0, -2);
            self::initErrorArray($itemErrors, $actualKey);
            if (!array_key_exists($actualKey, $item) || !is_array($item[$actualKey])) {
                $itemErrors[$actualKey][] = "Missing or invalid array field: $actualKey";
                return;
            }
            foreach ($item[$actualKey] as $i => $value) {
                $iKey = (string)$i;
                self::initErrorArray($itemErrors[$actualKey], $iKey);
                self::validateValue($value, $rules, $itemErrors[$actualKey][$iKey]);
                if (empty($itemErrors[$actualKey][$iKey])) {
                    unset($itemErrors[$actualKey][$iKey]);
                }
            }
            if (empty($itemErrors[$actualKey])) {
                unset($itemErrors[$actualKey]);
            }
            return;
        }
 
        self::initErrorArray($itemErrors, $key);
 
        if (!array_key_exists($key, $item)) {
            $itemErrors[$key][] = "Missing required field: $key";
        } else {
            $value = $item[$key];
            foreach ($rules as $rule) {
                $result = self::applyRule($value, $rule);
                if ($result !== true) {
                    $itemErrors[$key][] = $result;
                }
            }
        }
 
        if (empty($itemErrors[$key])) {
            unset($itemErrors[$key]);
        }
    }
 
    private static function validateValue(mixed $value, array $rules, array &$errors): void
    {
        foreach ($rules as $rule) {
            $result = self::applyRule($value, $rule);
            if ($result !== true) {
                $errors[] = $result;
            }
        }
    }
 
    private static function applyRule(mixed $value, string $rule): bool|string
    {
        if (str_contains($rule, ':')) {
            [$ruleName, $param] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $param = null;
        }

        return match ($ruleName) {
            'required' => (empty($value) && $value !== 0 && $value !== '0')
                ? "Field is required."
                : true,
            'string' => is_string($value)
                ? true
                : "Must be a string.",
            'integer' => is_int($value)
                ? true
                : 'Must be an integer.',
            'boolean' => is_bool($value)
                ? true
                : "Must be a boolean.",
            'array' => is_array($value)
                ? true
                : "Must be an array.",
            'min' => is_array($value)
                ? (count($value) >= (int)$param ? true : "Must have at least $param items.")
                : (is_numeric($value) && (int)$value >= (int)$param
                    ? true
                    : "Must be at least $param."),
            'max' => is_array($value)
                ? (count($value) <= (int)$param ? true : "Must not have more than $param items.")
                : (is_numeric($value) && (int)$value <= (int)$param
                    ? true
                    : "Must not exceed $param."),
            'in' => in_array($value, explode(',', $param), true)
                ? true
                : "Invalid value. Allowed: $param.",
            default => "Invalid validation rule: $rule",
        };
    }
 
    private static function isList(array $arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }
 
    private static function initErrorArray(array &$arr, string $key): void
    {
        if (!isset($arr[$key]) || !is_array($arr[$key])) {
            $arr[$key] = [];
        }
    }
}
