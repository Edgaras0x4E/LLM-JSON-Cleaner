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
        $allowedTree = self::buildAllowedKeysTree($schema);

        foreach ($data as $index => $item) {
            $itemErrors = [];

            foreach ($schema as $key => $rules) {
                self::singlePassCheck($item, $key, $rules, $itemErrors);
            }

            self::checkUnexpectedFields($item, $allowedTree, $itemErrors);

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
                if (in_array('required', $rules, true) || array_key_exists($baseKey, $item)) {
                    self::initErrorArray($itemErrors, $baseKey);
                    $itemErrors[$baseKey][] = "Missing or invalid array field: $baseKey";
                }
                return;
            }

            if (!self::isList($item[$baseKey]) && !empty($item[$baseKey])) {
                self::initErrorArray($itemErrors, $baseKey);
                $itemErrors[$baseKey][] = "Expected a sequential array for: $baseKey";
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
                if (in_array('required', $rules, true) || array_key_exists($actualKey, $item)) {
                    $itemErrors[$actualKey][] = "Missing or invalid array field: $actualKey";
                }
                if (empty($itemErrors[$actualKey])) {
                    unset($itemErrors[$actualKey]);
                }
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
            if (in_array('required', $rules, true)) {
                $itemErrors[$key][] = "Missing required field: $key";
            }
        } else {
            $value = $item[$key];
            if ($value === null && in_array('nullable', $rules, true)) {
               
            } else {
                foreach ($rules as $rule) {
                    $result = self::applyRule($value, $rule);
                    if ($result !== true) {
                        $itemErrors[$key][] = $result;
                    }
                }
            }
        }

        if (empty($itemErrors[$key])) {
            unset($itemErrors[$key]);
        }
    }

    private static function validateValue(mixed $value, array $rules, array &$errors): void
    {
        if ($value === null && in_array('nullable', $rules, true)) {
            return;
        }
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
            'required' => (empty($value) && $value !== 0 && $value !== '0' && $value !== false)
                ? "Field is required."
                : true,
            'nullable' => true,
            'string' => is_string($value)
                ? true
                : "Must be a string.",
            'integer' => is_int($value)
                ? true
                : 'Must be an integer.',
            'float' => (is_float($value) || is_int($value))
                ? true
                : 'Must be a float.',
            'numeric' => is_numeric($value)
                ? true
                : 'Must be numeric.',
            'boolean' => is_bool($value)
                ? true
                : "Must be a boolean.",
            'array' => is_array($value)
                ? true
                : "Must be an array.",
            'email' => (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false)
                ? true
                : "Must be a valid email address.",
            'url' => (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false)
                ? true
                : "Must be a valid URL.",
            'date' => (is_string($value) && strtotime($value) !== false)
                ? true
                : "Must be a valid date.",
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

    private static function buildAllowedKeysTree(array $schema): array
    {
        $tree = [];
        foreach ($schema as $key => $_) {
            $parts = explode('.', $key);
            $current = &$tree;
            foreach ($parts as $part) {
                if ($part === '*') {
                    if (!isset($current['*'])) {
                        $current['*'] = [];
                    }
                    $current = &$current['*'];
                } else {
                    if (!isset($current[$part])) {
                        $current[$part] = [];
                    }
                    $current = &$current[$part];
                }
            }
            unset($current);
        }
        return $tree;
    }

    private static function checkUnexpectedFields(array $data, array $allowedTree, array &$errors): void
    {
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $allowedTree)) {
                self::initErrorArray($errors, $key);
                $errors[$key][] = "Unexpected field: $key";
                continue;
            }

            if (is_array($value) && isset($allowedTree[$key]['*'])) {
                foreach ($value as $i => $subItem) {
                    if (is_array($subItem)) {
                        $iKey = (string)$i;
                        self::initErrorArray($errors, $key);
                        if (!isset($errors[$key][$iKey]) || !is_array($errors[$key][$iKey])) {
                            $errors[$key][$iKey] = [];
                        }
                        self::checkUnexpectedFields($subItem, $allowedTree[$key]['*'], $errors[$key][$iKey]);
                        if (empty($errors[$key][$iKey])) {
                            unset($errors[$key][$iKey]);
                        }
                    }
                }
                if (isset($errors[$key]) && empty($errors[$key])) {
                    unset($errors[$key]);
                }
            }
        }
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
