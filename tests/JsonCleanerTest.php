<?php

use PHPUnit\Framework\TestCase;
use Edgaras\LLMJsonCleaner\JsonCleaner;

class JsonCleanerTest extends TestCase
{

    // === Direct JSON decoding ===

    public function testDirectJsonDecodingAsArray(): void
    {
        $input = '{"name":"Alice","age":30}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function testDirectJsonDecodingAsString(): void
    {
        $input = '{"name":"Alice","age":30}';
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('Alice', $decoded['name']);
        $this->assertEquals(30, $decoded['age']);
    }

    // === Code block extraction ===

    public function testJsonInsideCodeBlockAsArray(): void
    {
        $input = "```json
        {\"name\":\"Bob\",\"age\":25}
        ```";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Bob', $result['name']);
        $this->assertEquals(25, $result['age']);
    }

    public function testJsonInsideCodeBlockAsString(): void
    {
        $input = "```json
        {\"name\":\"Bob\",\"age\":25}
        ```";
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('Bob', $decoded['name']);
        $this->assertEquals(25, $decoded['age']);
    }

    public function testJsonInsideCodeWithExtraTextBlockAsString(): void
    {
        $input = "
        Extra text 123
        ```json
        {\"name\":\"Bob\",\"age\":25}
        ```";
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('Bob', $decoded['name']);
        $this->assertEquals(25, $decoded['age']);
    }

    // === Text-embedded extraction ===

    public function testJsonExtraTextBlockAsString(): void
    {
        $input = "
        Extra text 123
        {\"name\":\"Bob\",\"age\":25}
        ";
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('Bob', $decoded['name']);
        $this->assertEquals(25, $decoded['age']);
    }

    public function testJsonExtraTextBlockAsStringNoBackslash(): void
    {
        $input = '
        Extra text 123
        {"name":"Bob","age":25}
        ';
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('Bob', $decoded['name']);
        $this->assertEquals(25, $decoded['age']);
    }

    // === Comment extraction ===

    public function testJsonInsideCommentsAsArray(): void
    {
        $input = "/*{\"city\":\"Paris\",\"country\":\"France\"}*/";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Paris', $result['city']);
        $this->assertEquals('France', $result['country']);
    }

    public function testJsonInsideCommentsAsString(): void
    {
        $input = "/*{\"city\":\"Paris\",\"country\":\"France\"}*/";
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals('Paris', $decoded['city']);
        $this->assertEquals('France', $decoded['country']);
    }

    // === Embedded in text ===

    public function testJsonEmbeddedInTextAsArray(): void
    {
        $input = "Some text before {\"x\":1, \"y\":2} some text after";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['x']);
        $this->assertEquals(2, $result['y']);
    }

    public function testJsonEmbeddedInTextAsString(): void
    {
        $input = "Some text before {\"x\":1, \"y\":2} some text after";
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals(1, $decoded['x']);
        $this->assertEquals(2, $decoded['y']);
    }

    // === Invalid input ===

    public function testInvalidInputReturnsNull(): void
    {
        $input = "This is not JSON at all.";
        $result = JsonCleaner::extract($input, true);
        $this->assertNull($result);
    }

    // === Multiline string values ===

    public function testMultilineStringValueAsArray(): void
    {
        $input = '{"summary": "Line one
and line two"}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("Line one\nand line two", $result['summary']);
    }

    public function testMultilineStringValueAsString(): void
    {
        $input = '{"summary": "Line one
and line two"}';
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertEquals("Line one\nand line two", $decoded['summary']);
    }

    public function testMultilineInCodeBlock(): void
    {
        $input = 'Here is the result:
```json
{"description": "First line
second line
third line"}
```';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("First line\nsecond line\nthird line", $result['description']);
    }

    public function testMultilineInEmbeddedText(): void
    {
        $input = 'Some text before {"note": "has
newlines
in it"} some text after';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("has\nnewlines\nin it", $result['note']);
    }

    public function testCarriageReturnNewline(): void
    {
        $input = "{\"text\": \"line one\r\nline two\"}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("line one\nline two", $result['text']);
    }

    public function testLiteralTabInStringValue(): void
    {
        $input = "{\"text\": \"col1\tcol2\"}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("col1\tcol2", $result['text']);
    }

    public function testEscapedNewlineNotDoubleEscaped(): void
    {
        $input = '{"text": "already escaped\\nnewline"}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("already escaped\nnewline", $result['text']);
    }

    public function testMultipleFieldsWithMultilineValues(): void
    {
        $input = '{"title": "Line A
Line B", "body": "Para 1
Para 2
Para 3"}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("Line A\nLine B", $result['title']);
        $this->assertEquals("Para 1\nPara 2\nPara 3", $result['body']);
    }

    public function testMultilineWithNestedObjects(): void
    {
        $input = '{"outer": {"inner": "multi
line"}, "clean": "ok"}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("multi\nline", $result['outer']['inner']);
        $this->assertEquals("ok", $result['clean']);
    }

    // === Top-level array extraction ===

    public function testTopLevelArrayExtraction(): void
    {
        $input = 'Here is the list: [{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}] hope that helps!';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[0]['name']);
        $this->assertEquals('Bob', $result[1]['name']);
    }

    public function testTopLevelArrayExtractionAsString(): void
    {
        $input = 'Result: [{"id":1},{"id":2}]';
        $result = JsonCleaner::extract($input, false);
        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
    }

    public function testTopLevelArrayInCodeBlock(): void
    {
        $input = '```json
[{"item":"apple"},{"item":"banana"}]
```';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('apple', $result[0]['item']);
        $this->assertEquals('banana', $result[1]['item']);
    }

    public function testTopLevelArrayDirect(): void
    {
        $input = '[{"a":1},{"a":2},{"a":3}]';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    // === Trailing comma removal ===

    public function testTrailingCommaInObject(): void
    {
        $input = '{"name": "Alice", "age": 30,}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function testTrailingCommaInArray(): void
    {
        $input = '[1, 2, 3,]';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testTrailingCommaNestedObjects(): void
    {
        $input = '{"items": [{"id": 1,}, {"id": 2,}],}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(1, $result['items'][0]['id']);
        $this->assertEquals(2, $result['items'][1]['id']);
    }

    public function testTrailingCommaInEmbeddedText(): void
    {
        $input = 'Here: {"key": "value",} done.';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }

    public function testTrailingCommaNotRemovedInsideStrings(): void
    {
        $input = '{"text": "a,}"}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('a,}', $result['text']);
    }

    // === Single quotes to double quotes ===

    public function testSingleQuotedJson(): void
    {
        $input = "{'name': 'Alice', 'age': 30}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function testSingleQuotesWithDoubleQuoteInside(): void
    {
        $input = "{'text': 'she said \"hello\"'}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('she said "hello"', $result['text']);
    }

    public function testSingleQuotesWithEscapedSingleQuote(): void
    {
        $input = "{'text': 'it\\'s fine'}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("it's fine", $result['text']);
    }

    public function testSingleQuotesNestedObjects(): void
    {
        $input = "{'person': {'name': 'Bob', 'active': true}}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Bob', $result['person']['name']);
        $this->assertTrue($result['person']['active']);
    }

    public function testSingleQuotesInCodeBlock(): void
    {
        $input = "```json
{'key': 'value'}
```";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }

    // === Unquoted keys ===

    public function testUnquotedKeys(): void
    {
        $input = '{name: "Alice", age: 30}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function testUnquotedKeysNested(): void
    {
        $input = '{person: {name: "Alice", active: true}}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['person']['name']);
        $this->assertTrue($result['person']['active']);
    }

    public function testUnquotedKeysWithArray(): void
    {
        $input = '{items: ["a", "b", "c"]}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals(['a', 'b', 'c'], $result['items']);
    }

    public function testUnquotedKeysPreservesBooleanValues(): void
    {
        $input = '{active: true, deleted: false, name: null}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertTrue($result['active']);
        $this->assertFalse($result['deleted']);
        $this->assertNull($result['name']);
    }

    // === Combined fixes ===

    public function testCombinedSingleQuotesAndTrailingComma(): void
    {
        $input = "{'name': 'Alice', 'age': 30,}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function testCombinedUnquotedKeysAndTrailingComma(): void
    {
        $input = '{name: "Alice", age: 30,}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function testCombinedSingleQuotesUnquotedKeysTrailingComma(): void
    {
        $input = "{name: 'Alice', items: ['a', 'b',],}";
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(['a', 'b'], $result['items']);
    }

    public function testCombinedMultilineAndTrailingComma(): void
    {
        $input = '{"text": "line one
line two", "other": "ok",}';
        $result = JsonCleaner::extract($input, true);
        $this->assertIsArray($result);
        $this->assertEquals("line one\nline two", $result['text']);
        $this->assertEquals('ok', $result['other']);
    }
}
