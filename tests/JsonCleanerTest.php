<?php

use PHPUnit\Framework\TestCase;
use Edgaras\LLMJsonCleaner\JsonCleaner;

class JsonCleanerTest extends TestCase
{ 

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
 
    public function testInvalidInputReturnsNull(): void
    {
        $input = "This is not JSON at all.";
        $result = JsonCleaner::extract($input, true);
        $this->assertNull($result);
    }
}
