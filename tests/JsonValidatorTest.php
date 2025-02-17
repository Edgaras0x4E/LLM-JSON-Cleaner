<?php

use PHPUnit\Framework\TestCase;
use Edgaras\LLMJsonCleaner\JsonValidator;

class JsonValidatorTest extends TestCase
{
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

    public function testExtraFieldsAreIgnored(): void
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
        $this->assertTrue($result, print_r($result, true));
    }
 
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
    
     
}
