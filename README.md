# LLM-JSON-Cleaner

PHP library for sanitizing JSON responses from LLM APIs and validating them against a specified JSON schema.  

## Features

- **JSON Response Cleaning:** Remove unwanted artifacts.
- **Schema Validation:** Validate and enforce JSON schema constraints.

## Installation

Install the package via Composer:

```bash
composer require edgaras/llm-json-cleaner
```

## Usage

### Extracting JSON from LLM Responses

```php
require_once 'vendor/autoload.php';

use Edgaras\LLMJsonCleaner\JsonCleaner;

$llmResponse = "Hi there! Please find the details below:\n\n{
    \"task\": \"generate_report\",
    \"parameters\": {
        \"date\": \"2025-02-17\",
        \"format\": \"pdf\"
    }
}\n\nLet me know if you need further assistance.";

// Return JSON only
$extractJson = JsonCleaner::extract($llmResponse, false);
echo $extractJson;
// {"task":"generate_report","parameters":{"date":"2025-02-17","format":"pdf"}}

// Return JSON only as an array
$extractJsonAsArray = JsonCleaner::extract($llmResponse, true);
print_r($extractJsonAsArray);
// (
//  [task] => generate_report
//  [parameters] => Array
//      (
//          [date] => 2025-02-17
//          [format] => pdf
//      )
// )
```

### Cleaning Malformed JSON

`JsonCleaner::extract()` automatically fixes common LLM output issues:

```php
// Trailing commas
$json = JsonCleaner::extract('{"name": "Alice", "age": 30,}', true);
// ['name' => 'Alice', 'age' => 30]

// Single-quoted JSON
$json = JsonCleaner::extract("{'name': 'Alice'}", true);
// ['name' => 'Alice']

// Unquoted keys
$json = JsonCleaner::extract('{name: "Alice", active: true}', true);
// ['name' => 'Alice', 'active' => true]

// Multiline string values (literal newlines inside strings)
$json = JsonCleaner::extract('{"text": "line one
line two"}', true);
// ['text' => "line one\nline two"]

// Top-level arrays
$json = JsonCleaner::extract('Here: [{"id":1},{"id":2}] done.', true);
// [['id' => 1], ['id' => 2]]
```

### Validating JSON Against a Schema

```php
require_once 'vendor/autoload.php';

use Edgaras\LLMJsonCleaner\JsonValidator;


$json = '{
  "order_id": 401,
  "customer": "Alice",
  "payment_methods": [
    {
      "method_id": "p1",
      "type": "Credit Card"
    },
    {
      "method_id": "p2",
      "type": "PayPal"
    }
  ]
}';

$schema = [
  'order_id' => ['required', 'integer', 'min:1'],
  'customer' => ['required', 'string'],
  'payment_methods' => ['required', 'array', 'min:1'],
  'payment_methods.*.method_id' => ['required', 'string'],
  'payment_methods.*.type' => ['required', 'string'],
];

$validator = JsonValidator::validateSchema(json_decode($json, 1), $schema);
var_dump($validator);
// bool(true)


$schemaNotFull = [
  'order_id' => ['required', 'integer', 'min:1'],
  'customer' => ['required', 'string'], 
];

$validator2 = JsonValidator::validateSchema(json_decode($json, 1), $schemaNotFull);
print_r($validator2);
// Array
// (
//    [0] => Array
//        (
//            [payment_methods] => Array
//                (
//                    [0] => Unexpected field: payment_methods
//                )
//        )
// )
```

### Validation Rules

| Rule | Description |
|---|---|
| `required` | Field must be present and non-empty |
| `nullable` | Field may be `null` (skips other rules when null) |
| `string` | Must be a string |
| `integer` | Must be an integer |
| `float` | Must be a float or integer |
| `numeric` | Must be numeric (int, float, or numeric string) |
| `boolean` | Must be a boolean |
| `array` | Must be an array |
| `email` | Must be a valid email address |
| `url` | Must be a valid URL |
| `date` | Must be a valid date string |
| `min:N` | Minimum value (numbers) or minimum item count (arrays) |
| `max:N` | Maximum value (numbers) or maximum item count (arrays) |
| `in:a,b,c` | Value must be one of the listed options |

Fields without `required` are optional - missing fields won't trigger errors, but present fields are still validated.

Nested array items are validated using wildcard dot notation: `items.*.field_name`

Unexpected fields (not defined in schema) are detected at all nesting levels.

## Changelog

### v1.1.0

**JsonCleaner**
- Added automatic trailing comma removal (`{"a": 1,}`)
- Added single-quote to double-quote conversion (`{'key': 'value'}`)
- Added unquoted key detection and quoting (`{key: "value"}`)
- Added multiline string value sanitization (literal newlines inside JSON strings)
- Added top-level JSON array extraction (`[...]` embedded in text)
- All sanitization is string-aware - content inside quoted values is never corrupted

**JsonValidator**
- Added `nullable` rule - allows `null` values and skips further validation
- Added `float` rule - accepts floats and integers
- Added `numeric` rule - accepts any numeric value including numeric strings
- Added `email`, `url`, `date` validation rules
- Added optional field support - fields without `required` don't error when missing
- Added unexpected nested field detection inside wildcard arrays (not just top-level)
- Added sequential array check for wildcard (`.*`) paths - associative arrays are rejected
- Fixed `required` rule to allow `false` boolean values

**General**
- Tested and compatible with PHP 8.4 and PHP 8.5