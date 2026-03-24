<?php

use PHPUnit\Framework\TestCase;
use Edgaras\LLMJsonCleaner\JsonValidator;

class JsonValidatorTest extends TestCase
{
    // === Valid data ===

    public function testValidData(): void
    {
        $data = [
            [
                'id' => 1,
                'question' => 'Sample question',
                'answers' => [
                    [
                        'id' => 'a',
                        'answer_option' => 'Option A'
                    ],
                    [
                        'id' => 'b',
                        'answer_option' => 'Option B'
                    ]
                ],
                'correct_answer_id' => 'a',
                'answer_explanation' => 'Explanation text'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers' => ['required','array','min:1'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    // === Missing fields ===

    public function testMissingTopLevelField(): void
    {
        $data = [
            [
                'question' => 'Sample question',
                'answers' => [
                    [
                        'id' => 'a',
                        'answer_option' => 'Option A'
                    ]
                ],
                'correct_answer_id' => 'a',
                'answer_explanation' => 'Explanation text'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers' => ['required','array','min:1'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('id', $result[0]);
    }

    public function testEmptyAnswersArray(): void
    {
        $data = [
            [
                'id' => 2,
                'question' => 'Another question',
                'answers' => [],
                'correct_answer_id' => 'b',
                'answer_explanation' => 'Explanation text'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers' => ['required','array','min:1'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('answers', $result[0]);
    }

    // === Nested wildcards ===

    public function testNestedWildcardCorrect(): void
    {
        $data = [
            [
                'id' => 3,
                'question' => 'Nested wildcard question',
                'answers' => [
                    [
                        'id' => 'x',
                        'answer_option' => 'Nested Option X'
                    ],
                    [
                        'id' => 'y',
                        'answer_option' => 'Nested Option Y'
                    ]
                ],
                'correct_answer_id' => 'x',
                'answer_explanation' => 'Nested explanation'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testNestedWildcardMissingSubKey(): void
    {
        $data = [
            [
                'id' => 4,
                'question' => 'Wildcard missing subkey',
                'answers' => [
                    [
                        'id' => 'a'
                    ],
                    [
                        'id' => 'b'
                    ]
                ],
                'correct_answer_id' => 'a',
                'answer_explanation' => 'Some explanation'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('answers', $result[0]);
    }

    // === Multiple items ===

    public function testMultipleItemsOneInvalid(): void
    {
        $data = [
            [
                'id' => 10,
                'question' => 'Valid item question',
                'answers' => [
                    ['id' => 'c', 'answer_option' => 'Option C'],
                    ['id' => 'd', 'answer_option' => 'Option D']
                ],
                'correct_answer_id' => 'c',
                'answer_explanation' => 'Some explanation'
            ],
            [
                'id' => 11,
                'answers' => [
                    ['id' => 'z', 'answer_option' => 'Option Z']
                ],
                'correct_answer_id' => 'z',
                'answer_explanation' => 'Some explanation'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers' => ['required','array','min:1'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey('question', $result[1]);
    }

    // === Non-array top level ===

    public function testNonArrayTopLevelObject(): void
    {
        $data = [
            'id' => 99,
            'question' => 'Just a single object, no top-level array',
            'answers' => [
                [
                    'id' => 'm',
                    'answer_option' => 'Option M'
                ]
            ],
            'correct_answer_id' => 'm',
            'answer_explanation' => 'Explanation text'
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertTrue($result);
    }

    // === Associative array detection ===

    public function testAnswersAsObject(): void
    {
        $data = [
            [
                'id' => 5,
                'question' => 'Answers as object',
                'answers' => [
                    'key1' => ['id' => 'foo', 'answer_option' => 'Foo option'],
                    'key2' => ['id' => 'bar', 'answer_option' => 'Bar option'],
                ],
                'correct_answer_id' => 'foo',
                'answer_explanation' => 'Oops object for answers'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers' => ['required','array','min:1'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('answers', $result[0]);
    }

    // === Type checks ===

    public function testIntegerButStringValue(): void
    {
        $data = [
            [
                'id' => '30',
                'question' => 'Should fail integer rule',
                'answers' => [
                    ['id' => 'x', 'answer_option' => 'Option X']
                ],
                'correct_answer_id' => 'x',
                'answer_explanation' => 'Explanation text'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('id', $result[0]);
    }

    // === Unexpected fields ===

    public function testExtraFieldsAreDetected(): void
    {
        $data = [
            [
                'id' => 50,
                'question' => 'Extra fields present',
                'answers' => [
                    ['id' => 'a1', 'answer_option' => 'Option 1'],
                    ['id' => 'a2', 'answer_option' => 'Option 2']
                ],
                'correct_answer_id' => 'a1',
                'answer_explanation' => 'Extra fields test',
                'extra_field' => 'Ignore me'
            ]
        ];

        $schema = [
            'id' => ['required','integer','min:1'],
            'question' => ['required','string'],
            'answers.*.id' => ['required','string'],
            'answers.*.answer_option' => ['required','string'],
            'correct_answer_id' => ['required','string'],
            'answer_explanation' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('extra_field', $result[0]);
    }

    // === Deeply nested wildcards ===

    public function testDeeplyNestedMultipleWildcards(): void
    {
        $data = [
            [
                'projects' => [
                    [
                        'title' => 'Project A',
                        'phases' => [
                            [
                                'phase_name' => 'Planning',
                                'tasks' => [
                                    ['task_name' => 'Gather requirements'],
                                    ['task_name' => 'Define scope'],
                                ]
                            ],
                            [
                                'phase_name' => 'Execution',
                                'tasks' => [
                                    ['task_name' => 'Develop features']
                                ]
                            ]
                        ]
                    ],
                    [
                        'title' => 'Project B',
                        'phases' => []
                    ]
                ]
            ]
        ];

        $schema = [
            'projects'                 => ['required','array','min:1'],
            'projects.*.title'        => ['required','string'],
            'projects.*.phases'       => ['required','array','min:1'],
            'projects.*.phases.*.phase_name' => ['required','string'],
            'projects.*.phases.*.tasks'      => ['required','array','min:1'],
            'projects.*.phases.*.tasks.*.task_name' => ['required','string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('projects', $result[0]);
    }

    // === Boolean ===

    public function testBooleanFieldRequired(): void
    {
        $data = [
            [
                'name' => 'Article 1',
                'content' => 'Some content',
                'published' => true
            ],
            [
                'name' => 'Article 2',
                'content' => 'Second content'
            ]
        ];

        $schema = [
            'name'      => ['required','string'],
            'content'   => ['required','string'],
            'published' => ['required','boolean']
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertIsArray($result, 'Missing published should fail');
        $this->assertArrayHasKey(1, $result, 'Second item should have errors');
        $this->assertArrayHasKey('published', $result[1], 'published is missing');
    }

    // === Min/Max rules ===

    public function testIntegerMinRule(): void
    {
        $data = [
            [
                'player_name' => 'PlayerOne',
                'score' => 3,
            ],
            [
                'player_name' => 'PlayerTwo',
                'score' => 10,
            ]
        ];

        $schema = [
            'player_name' => ['required','string'],
            'score'       => ['required','integer','min:5']
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertIsArray($result, 'First item must fail min:5');
        $this->assertArrayHasKey(0, $result, 'Index 0 should have errors');
        $this->assertArrayHasKey('score', $result[0], 'score fails min:5');
    }

    public function testIntegerMaxRule(): void
    {
        $data = [
            [
                'username' => 'ElderPerson',
                'age' => 120
            ]
        ];

        $schema = [
            'username' => ['required','string'],
            'age'      => ['required','integer','max:100']
        ];

        $result = JsonValidator::validateSchema($data, $schema);

        $this->assertIsArray($result, 'Age > 100 must fail');
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('age', $result[0]);
    }

    // === Nullable rule ===

    public function testNullableFieldWithNull(): void
    {
        $data = [
            'name' => 'Alice',
            'bio' => null,
        ];

        $schema = [
            'name' => ['required', 'string'],
            'bio'  => ['nullable', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testNullableFieldWithValue(): void
    {
        $data = [
            'name' => 'Alice',
            'bio' => 'Hello world',
        ];

        $schema = [
            'name' => ['required', 'string'],
            'bio'  => ['nullable', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testNullableFieldWithWrongType(): void
    {
        $data = [
            'name' => 'Alice',
            'bio' => 123,
        ];

        $schema = [
            'name' => ['required', 'string'],
            'bio'  => ['nullable', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('bio', $result[0]);
    }

    public function testNullableRequiredFieldMissing(): void
    {
        $data = [
            'name' => 'Alice',
        ];

        $schema = [
            'name' => ['required', 'string'],
            'bio'  => ['required', 'nullable', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('bio', $result[0]);
    }

    public function testNullableInWildcardArray(): void
    {
        $data = [
            'items' => [
                ['name' => 'A', 'description' => null],
                ['name' => 'B', 'description' => 'Some text'],
            ],
        ];

        $schema = [
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'string'],
            'items.*.description' => ['nullable', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    // === Float rule ===

    public function testFloatRuleWithFloat(): void
    {
        $data = [['price' => 19.99]];
        $schema = ['price' => ['required', 'float']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testFloatRuleWithInteger(): void
    {
        $data = [['price' => 20]];
        $schema = ['price' => ['required', 'float']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testFloatRuleWithString(): void
    {
        $data = [['price' => '19.99']];
        $schema = ['price' => ['required', 'float']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('price', $result[0]);
    }

    // === Numeric rule ===

    public function testNumericRuleWithInt(): void
    {
        $data = [['value' => 42]];
        $schema = ['value' => ['required', 'numeric']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testNumericRuleWithFloatString(): void
    {
        $data = [['value' => '3.14']];
        $schema = ['value' => ['required', 'numeric']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testNumericRuleWithNonNumericString(): void
    {
        $data = [['value' => 'abc']];
        $schema = ['value' => ['required', 'numeric']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('value', $result[0]);
    }

    // === Email rule ===

    public function testEmailRuleValid(): void
    {
        $data = [['email' => 'user@example.com']];
        $schema = ['email' => ['required', 'email']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testEmailRuleInvalid(): void
    {
        $data = [['email' => 'not-an-email']];
        $schema = ['email' => ['required', 'email']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('email', $result[0]);
    }

    // === URL rule ===

    public function testUrlRuleValid(): void
    {
        $data = [['website' => 'https://example.com']];
        $schema = ['website' => ['required', 'url']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testUrlRuleInvalid(): void
    {
        $data = [['website' => 'not a url']];
        $schema = ['website' => ['required', 'url']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('website', $result[0]);
    }

    // === Date rule ===

    public function testDateRuleValid(): void
    {
        $data = [['date' => '2025-02-17']];
        $schema = ['date' => ['required', 'date']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testDateRuleValidNatural(): void
    {
        $data = [['date' => 'January 1, 2025']];
        $schema = ['date' => ['required', 'date']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testDateRuleInvalid(): void
    {
        $data = [['date' => 'not-a-date-at-all']];
        $schema = ['date' => ['required', 'date']];
        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('date', $result[0]);
    }

    // === Optional fields ===

    public function testOptionalFieldMissingPasses(): void
    {
        $data = [
            'name' => 'Alice',
        ];

        $schema = [
            'name' => ['required', 'string'],
            'nickname' => ['string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testOptionalFieldPresentValid(): void
    {
        $data = [
            'name' => 'Alice',
            'nickname' => 'Ali',
        ];

        $schema = [
            'name' => ['required', 'string'],
            'nickname' => ['string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testOptionalFieldPresentInvalid(): void
    {
        $data = [
            'name' => 'Alice',
            'nickname' => 123,
        ];

        $schema = [
            'name' => ['required', 'string'],
            'nickname' => ['string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('nickname', $result[0]);
    }

    public function testOptionalFieldMissingDoesNotTriggerUnexpected(): void
    {
        $data = [
            'name' => 'Alice',
        ];

        $schema = [
            'name' => ['required', 'string'],
            'bio' => ['string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    // === Unexpected nested fields ===

    public function testUnexpectedNestedFieldInWildcard(): void
    {
        $data = [
            [
                'id' => 1,
                'items' => [
                    ['name' => 'A', 'extra' => 'bad'],
                    ['name' => 'B'],
                ],
            ]
        ];

        $schema = [
            'id' => ['required', 'integer'],
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('items', $result[0]);
    }

    public function testNoUnexpectedNestedFieldWhenClean(): void
    {
        $data = [
            [
                'id' => 1,
                'items' => [
                    ['name' => 'A'],
                    ['name' => 'B'],
                ],
            ]
        ];

        $schema = [
            'id' => ['required', 'integer'],
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }

    public function testUnexpectedDeeplyNestedField(): void
    {
        $data = [
            [
                'groups' => [
                    [
                        'name' => 'G1',
                        'members' => [
                            ['name' => 'Alice', 'secret' => 'oops'],
                        ],
                    ],
                ],
            ]
        ];

        $schema = [
            'groups' => ['required', 'array'],
            'groups.*.name' => ['required', 'string'],
            'groups.*.members' => ['required', 'array'],
            'groups.*.members.*.name' => ['required', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('groups', $result[0]);
    }

    // === Required false with boolean value ===

    public function testRequiredRuleAllowsFalseBoolean(): void
    {
        $data = [
            ['active' => false, 'name' => 'Test'],
        ];

        $schema = [
            'active' => ['required', 'boolean'],
            'name' => ['required', 'string'],
        ];

        $result = JsonValidator::validateSchema($data, $schema);
        $this->assertTrue($result);
    }
}
