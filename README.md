# LLM-JSON-Cleaner

PHP library for sanitizing JSON responses from LLM APIs and validating them against a specified JSON schema.  

## Features

- **JSON Response Cleaning:** Remove unwanted artifacts.
- **Schema Validation:** Validate and enforce JSON schema constraints.

## Installation

Install the package via Composer:

```bash
composer edgaras/llm-json-cleaner
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