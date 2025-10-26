<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;

class EssayGrader
{
    protected $modelConfig;
    protected $essayPrompt;
    protected $essayOld;
    protected $essayNew;
    protected $essayOldFeedback;
    protected $traitInstructions;
    protected $precheckModelConfig;
    protected $apiKey;
    protected $studentGrade;
    protected $contentId;
    protected $studentId;
    protected $logFilename;

    public function __construct($modelConfig, $essayPrompt, $essayOld, $essayNew, $essayOldFeedback, $traitInstructions, $studentGrade, $precheckModelConfig = null, $requestData = [])
    {
        $this->modelConfig = $modelConfig;
        $this->essayPrompt = $essayPrompt;
        $this->essayOld = $essayOld;
        $this->essayNew = $essayNew;
        $this->essayOldFeedback = $essayOldFeedback;
        $this->traitInstructions = $traitInstructions;
        $this->studentGrade = $studentGrade;
        $this->precheckModelConfig = $precheckModelConfig ?: $GLOBALS['MODELS']["gpt4o-mini"] ?? null;
        $this->apiKey = env('openai.apiKey', '');
        $this->contentId = $requestData['content_id'] ?? null;
        $this->studentId = $requestData['student_id'] ?? null;
        $this->logFilename = WRITEPATH . 'logs/essay/';
        
        // Ensure log directory exists
        if (!is_dir($this->logFilename)) {
            mkdir($this->logFilename, 0755, true);
        }
    }

    /**
     * Helper method to call the OpenAI Chat Completions API.
     *
     * @param string $model The model name.
     * @param array $messages An array of messages for the chat.
     * @return array|false The decoded JSON response or false on error.
     */
    private function callOpenAI($model, $messages)
    {
        $url = "https://api.openai.com/v1/chat/completions";
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $data,
                'timeout' => 60
            ]);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            } else {
                $this->logError('OpenAI API Error: ' . $response->getStatusCode() . ' - ' . $response->getBody());
                return false;
            }
        } catch (\Exception $e) {
            $this->logError('OpenAI API Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Grade the essay using AI
     *
     * @return array
     */
    public function gradeEssay()
    {
        try {
            // Pre-check the essay for basic requirements
            $precheckResult = $this->precheckEssay();
            if (!$precheckResult['passed']) {
                return [
                    'success' => false,
                    'error' => $precheckResult['message'],
                    'suggestions' => $precheckResult['suggestions'] ?? []
                ];
            }

            // Prepare the grading prompt
            $gradingPrompt = $this->buildGradingPrompt();
            
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an expert essay grader. Provide detailed feedback on student essays focusing on content, structure, grammar, and style.'
                ],
                [
                    'role' => 'user',
                    'content' => $gradingPrompt
                ]
            ];

            $response = $this->callOpenAI($this->modelConfig['model'], $messages);
            
            if (!$response) {
                return [
                    'success' => false,
                    'error' => 'Failed to get AI response'
                ];
            }

            $aiResponse = $response['choices'][0]['message']['content'] ?? '';
            
            // Parse the AI response
            $gradingResult = $this->parseGradingResponse($aiResponse);
            
            // Log the grading result
            $this->logGradingResult($gradingResult);
            
            return [
                'success' => true,
                'grade' => $gradingResult['grade'] ?? 'N/A',
                'feedback' => $gradingResult['feedback'] ?? '',
                'suggestions' => $gradingResult['suggestions'] ?? [],
                'traits' => $gradingResult['traits'] ?? [],
                'overall_score' => $gradingResult['overall_score'] ?? 0
            ];

        } catch (\Exception $e) {
            $this->logError('Essay grading error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while grading the essay'
            ];
        }
    }

    /**
     * Pre-check the essay for basic requirements
     *
     * @return array
     */
    private function precheckEssay()
    {
        if (empty($this->essayNew)) {
            return [
                'passed' => false,
                'message' => 'Essay content is empty',
                'suggestions' => ['Please write your essay before submitting']
            ];
        }

        // Check minimum word count
        $wordCount = str_word_count($this->essayNew);
        if ($wordCount < 50) {
            return [
                'passed' => false,
                'message' => 'Essay is too short',
                'suggestions' => ['Please write at least 50 words']
            ];
        }

        // Check maximum word count
        if ($wordCount > 5000) {
            return [
                'passed' => false,
                'message' => 'Essay is too long',
                'suggestions' => ['Please keep your essay under 5000 words']
            ];
        }

        return ['passed' => true];
    }

    /**
     * Build the grading prompt
     *
     * @return string
     */
    private function buildGradingPrompt()
    {
        $prompt = "Please grade the following essay:\n\n";
        $prompt .= "Essay Prompt: " . $this->essayPrompt . "\n\n";
        $prompt .= "Student Grade Level: " . $this->studentGrade . "\n\n";
        $prompt .= "Essay Content:\n" . $this->essayNew . "\n\n";
        
        if (!empty($this->traitInstructions)) {
            $prompt .= "Grading Instructions:\n" . $this->traitInstructions . "\n\n";
        }
        
        $prompt .= "Please provide:\n";
        $prompt .= "1. Overall grade (A, B, C, D, F)\n";
        $prompt .= "2. Detailed feedback on content, structure, grammar, and style\n";
        $prompt .= "3. Specific suggestions for improvement\n";
        $prompt .= "4. Score out of 100\n";
        $prompt .= "5. Analysis of key traits (creativity, clarity, organization, etc.)\n";
        
        return $prompt;
    }

    /**
     * Parse the AI grading response
     *
     * @param string $response
     * @return array
     */
    private function parseGradingResponse($response)
    {
        $result = [
            'grade' => 'N/A',
            'feedback' => $response,
            'suggestions' => [],
            'traits' => [],
            'overall_score' => 0
        ];

        // Extract grade
        if (preg_match('/grade[:\s]+([A-F])/i', $response, $matches)) {
            $result['grade'] = strtoupper($matches[1]);
        }

        // Extract score
        if (preg_match('/(\d+)\s*\/\s*100|score[:\s]+(\d+)/i', $response, $matches)) {
            $result['overall_score'] = (int)($matches[1] ?? $matches[2]);
        }

        // Extract suggestions
        if (preg_match('/suggestions?[:\s]+(.*?)(?=\n\n|\n[A-Z]|$)/is', $response, $matches)) {
            $suggestions = explode("\n", trim($matches[1]));
            $result['suggestions'] = array_filter(array_map('trim', $suggestions));
        }

        return $result;
    }

    /**
     * Log grading result
     *
     * @param array $result
     */
    private function logGradingResult($result)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'content_id' => $this->contentId,
            'student_id' => $this->studentId,
            'grade' => $result['grade'],
            'score' => $result['overall_score'],
            'model_used' => $this->modelConfig['model'] ?? 'unknown'
        ];

        $logFile = $this->logFilename . 'grading_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Log error
     *
     * @param string $message
     */
    private function logError($message)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'content_id' => $this->contentId,
            'student_id' => $this->studentId,
            'error' => $message
        ];

        $logFile = $this->logFilename . 'errors_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Get available models
     *
     * @return array
     */
    public static function getAvailableModels()
    {
        return [
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini'
        ];
    }

    /**
     * Validate model configuration
     *
     * @param array $config
     * @return bool
     */
    public static function validateModelConfig($config)
    {
        return isset($config['model']) && !empty($config['model']);
    }
}
