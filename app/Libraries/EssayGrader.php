<?php

namespace App\Libraries;

class EssayGrader
{
    public $model_config;
    public $essay_prompt;
    public $essay_old;
    public $essay_new;
    public $essay_old_feedback;
    public $trait_instructions;
    public $precheck_model_config;
    public $api_key;
    public $student_grade; // New property for student grade
    public $content_id;
    public $student_id;

    public $log_filename;
    
    public function __construct($model_config, $essay_prompt, $essay_old, $essay_new, $essay_old_feedback, $trait_instructions, $student_grade, $precheck_model_config = null, $requestData)
    {
        $this->model_config = $model_config;
        $this->essay_prompt = $essay_prompt;
        $this->essay_old = '';
        $this->essay_new = $essay_new;
        $this->essay_old_feedback = '';
        $this->trait_instructions = $trait_instructions;
        $this->student_grade = $student_grade; // Assign student grade
        // Use provided precheck model, or default to "gpt4o-mini"
        $this->precheck_model_config = $precheck_model_config ? $precheck_model_config : ($GLOBALS['MODELS']["gpt4o-mini"] ?? null);
        
        // Get API key from environment or config
        $this->api_key = env('openai.apiKey', '');
        if (empty($this->api_key)) {
            // Try to read from properties.ini if it exists
            $propertiesPath = ROOTPATH . '../properties.ini';
            if (file_exists($propertiesPath)) {
                $prop = parse_ini_file($propertiesPath, true, INI_SCANNER_RAW);
                $this->api_key = $prop['api_key'] ?? '';
            }
        }
        
        $this->content_id = $requestData['content_id'] ?? null;
        $this->student_id = $requestData['student_id'] ?? null;
        $this->log_filename = WRITEPATH . 'logs/essay/';
        
        // Ensure log directory exists
        if (!is_dir($this->log_filename)) {
            mkdir($this->log_filename, 0755, true);
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
            "model" => $model,
            "messages" => $messages
        ];
        $jsonData = json_encode($data);
        $tstjsonData = json_encode($data, JSON_PRETTY_PRINT);
        $promptLogFileName = $this->log_filename . $this->student_id . '_' . $this->content_id . '_' . 'promptlog.txt';
        file_put_contents($promptLogFileName, "\n Data: " . $tstjsonData, FILE_APPEND);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->api_key
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "cURL error: " . curl_error($ch) . "\n";
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $promptLogFileName = $this->log_filename . $this->student_id . '_' . $this->content_id . '_' . 'promptlog.txt';
        file_put_contents($promptLogFileName, "\n Model Response: " . json_encode($response, JSON_PRETTY_PRINT), FILE_APPEND);
        
        // Log to Common model if available
        if (class_exists('\App\Models\Common_model')) {
            $common_model = new \App\Models\Common_model();
            if (method_exists($common_model, 'createLog')) {
                $common_model->createLog(
                    $jsonData,
                    'v1/student/getOpenAiFeedback',
                    $response,
                    'OpenAiFeedbackResponse'
                );
            }
        }

        $decoded = json_decode($response, true);
        return $decoded;
    }

    /**
     * Build the prompt for a specific trait.
     *
     * @param string $trait
     * @return string
     */
    public function build_trait_prompt($trait)
    {
        $context_block = "<context>\nYou are an expert essay grading and writing coach agent.\n</context>\n";
        $instruction_block = "<instruction>\n" .
            "Grade the essay on '{$trait}' with the following guidelines, on a scale of 1-5:\n" .
            $this->trait_instructions[$trait] . "\n\n" .
            "Return your output strictly in valid JSON format with the following structure:\n" .
            "{\n" .
            "  \"{$trait}\": {\n" .
            "      \"score\": <number>,\n" .
            "      \"feedback\": [\n" .
            "         {\"snippet\": \"<text snippet>\", \"suggestion\": \"<suggestion>\"}\n" .
            "      ]\n" .
            "  }\n" .
            "}\n" .
            "Do not include any additional text or commentary outside of this JSON structure.\n" .
            "</instruction>\n";
        $essay_block = "Essay Prompt: " . $this->essay_prompt . "\nEssay:\n" . $this->essay_new;
        return $context_block . "\n" . $instruction_block . "\n" . $essay_block;
    }

    /**
     * Build a single prompt to grade all traits combined.
     *
     * @return string
     */
    public function build_combined_trait_prompt()
    {
        // ----- ROLE -----
        $role_block = "<role>
You are an expert Essay Grader & Writing Coach. Your primary goal is to build student confidence. Your feedback should be supportive, specific, and actionable, and your scoring should be
encouraging.
</role>";

        // ----- CONTEXT -----
        $context_block = "<context>
Student grade: {$this->student_grade}
Essay topic/prompt: \"{$this->essay_prompt}\"
Tailor wording and expectations to Grade {$this->student_grade} (simple, clear; avoid jargon).
</context>";

        // ----- TASK -----
        $task = "<task>

Analyze the student essay using the trait guidelines. For each trait:
- Assign a score (1–5).
- Provide a brief rationale. If score < 5, explicitly name what's missing vs. the guideline.
- Provide 2–4 concrete suggestions, each with (a) a short quoted snippet from the essay (≤200 chars), (b) a precise improvement, (c) WHY it helps, and (d) a tiny example rewrite.
 Also provide: overall summary, strengths, top opportunities, and a 2–3 step Next Edit Plan the student can do now.
 </task>";

        $scoring_policy = "<scoring_policy>
 Generosity and Optimism are key:
 - Assume a baseline score of 4 for each trait. Only deduct points if specific guidelines are clearly and repeatedly missed.
 - When evidence is mixed or between two levels, always choose the HIGHER score.
 - Prioritize the student's effort and the core message over minor mechanical errors (especially for Conventions).
 </scoring_policy>";

        $grading_criteria_block = "<grading_criteria>
 Scale per trait:
 1 = Far below expectations
 2 = Below expectations, with significant issues
 3 = Meets expectations
 4 = Above expectations, showing good effort
 5 = Excellent effort and clear understanding shown for this grade
 </grading_criteria>";

        // ----- ESSAY TO GRADE -----
        $essay_to_grade_block = "<essay_to_grade>
{$this->essay_new}
</essay_to_grade>";

        // ----- OUTPUT FORMAT (STRICT JSON) -----
        $traitBlocks = [];

        foreach ($this->trait_instructions as $trait => $desc) {
            $traitBlocks[$trait] = [
                "score" => "<number>",
                "rationale" => "<rationale>",
                "feedback" => [
                    [
                        "snippet" => "<verbatim>",
                        "suggestion" => "<suggestion>",
                        "why" => "<why it helps>",
                        "example_rewrite" => "<example>"
                    ],
                    [
                        "snippet" => "...",
                        "suggestion" => "...",
                        "why" => "...",
                        "example_rewrite" => "..."
                    ]
                ]
            ];
        }

        // Full structure
        $outputArray = [
            "overall" => [
                "total_score" => "<number>",
                "summary" => "<2–3 sentence overview tailored to the student>",
                "strengths" => ["<short strength>", "<short strength>"],
                "top_opportunities" => ["<short opportunity>", "<short opportunity>"]
            ],
            "traits" => $traitBlocks,
            "next_edit_plan" => [
                [
                    "priority" => 1,
                    "action" => "<one high-impact change>",
                    "example_rewrite" => "<one sentence showing how>"
                ]
            ]
        ];

        // Encode it as JSON string and wrap in <output_format> tags
        $output_format_block = "<output_format>\n";
        $output_format_block .= " Return your output strictly as ONE valid JSON object. No extra text, no markdown, no code fences.\n\n";
        $output_format_block .= json_encode($outputArray, JSON_PRETTY_PRINT);
        $output_format_block .= "\n</output_format>";

        $promptLogFileName = $this->log_filename . $this->student_id . '_' . $this->content_id . '_' . 'promptlog.txt';
        // Log (optional)
        file_put_contents(
            $promptLogFileName,
            "\n".$role_block."\n".$context_block."\n".$task."\n".$scoring_policy."\n".$grading_criteria_block."\n".$essay_to_grade_block."\n".$output_format_block,
            FILE_APPEND
        );

        return $role_block . "\n" . $context_block . "\n" . $task . "\n" . $scoring_policy . "\n" . $grading_criteria_block . "\n" . $essay_to_grade_block . "\n" . $output_format_block;
    }

    public function precheck_similarity()
    {
        if (trim($this->essay_old) == "") {
            return true;
        }

        $prompt = "Compare the following two essays for similarity. " .
            "If they are too similar, reply with exactly \"GET OUT OF HERE\". " .
            "If they are sufficiently different, reply with exactly \"KEEP GOING\".\n\n" .
            "Essay Old:\n" . $this->essay_old . "\n\n" .
            "Essay New:\n" . $this->essay_new;
        $messages = [
            ["role" => "user", "content" => $prompt]
        ];
        $response = $this->callOpenAI($this->precheck_model_config->model_name, $messages);
        if (!$response || !isset($response["choices"][0]["message"]["content"])) {
            // echo "Precheck similarity API call failed.\n";
            return false;
        }
        $result_text = trim($response["choices"][0]["message"]["content"]);
        $cleaned_text = strtoupper(trim($this->clean_json_output($result_text)));
        if (strpos($cleaned_text, "GET OUT OF HERE") !== false) {
            return false;
        } elseif (strpos($cleaned_text, "KEEP GOING") !== false) {
            return true;
        } else {
            // echo "Ambiguous precheck response: " . $cleaned_text . "\n";
            return false;
        }
    }

    /**
     * Grade all essay traits in a single API call.
     *
     * @return array An associative array with 'result' and 'usage'.
     */
    public function grade_all_traits_combined()
    {
        $prompt = $this->build_combined_trait_prompt();
        $messages = [
            ["role" => "user", "content" => $prompt]
        ];
        $response = $this->callOpenAI($this->model_config->model_name, $messages);

        if (!$response || !isset($response["choices"][0]["message"]["content"])) {
            //  echo "API request for combined traits failed.\n";
            return ["result" => null, "usage" => []];
        }
        $result_text = $response["choices"][0]["message"]["content"];
        $cleaned_text = $this->clean_json_output($result_text);
        $result_json = json_decode($cleaned_text, true);
        if ($result_json === null) {
            // echo "Failed to parse JSON for combined traits: " . json_last_error_msg() . "\n";
        }
        $usage = isset($response["usage"]) ? $response["usage"] : [];
        return ["result" => $result_json, "usage" => $usage];
    }

    public function run($params)
    {
        // --- Precheck for Essay Similarity ---
        // Commented out as per pre-migration code
        /*
        if (trim($this->essay_old) != "") {
            $proceed = $this->precheck_similarity();
            if (!$proceed) {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "LLM precheck response indicated: GET OUT OF HERE. Exiting grading process.";
                $this->printjson($this->jsonarr);exit;
            } else {
               // echo "LLM precheck response indicated: KEEP GOING. Proceeding with grading.\n";
            }
        } else {
          //  echo "No old essay provided; bypassing precheck.\n";
        }
        */
        
        // --- Grade All Traits (combined API call) ---
        $combined_res = $this->grade_all_traits_combined();
        if (!empty($combined_res['result']) && !empty($combined_res['usage'])) {
            $combined_results = $combined_res["result"];
            $total_usage = $combined_res["usage"];

            // --- Calculate Costs ---
            $total_cost = $this->calculate_cost($total_usage, $this->model_config);

            // --- Output Results ---
            $score_array = [];
            foreach ($this->trait_instructions as $trait => $instruction) {
                $trait_data = isset($combined_results['traits'][$trait]) ? $combined_results['traits'][$trait] : [];
                $score = isset($trait_data["score"]) ? $trait_data["score"] : 0;
                $score_array[] = $score;
            }

            $overall_total = array_sum($score_array);
            $overall_possible = 5 * count($this->trait_instructions);
            $overall_percentage = ($overall_total / $overall_possible) * 100;

            $result['overall_total'] = $overall_total;
            $result['overall_possible'] = $overall_possible;
            $result['combined_results'] = $combined_results;
            $result['prompt_token'] = $total_usage["prompt_tokens"];
            $result['completion_token'] = $total_usage["completion_tokens"];
            $result['total_token'] = $total_usage["total_tokens"];
            $result['total_cost'] = $total_cost;
            $apiCostLogFileName = $this->log_filename . $this->student_id . '_' . $this->content_id . '_' . 'apicost.txt';
            file_put_contents($apiCostLogFileName, "\nUsage Statistics (combined):\n" .
                "  Prompt Tokens: " . $total_usage["prompt_tokens"] . "\n" .
                "  Completion Tokens: " . $total_usage["completion_tokens"] . "\n" .
                "  Total Tokens: " . $total_usage["total_tokens"] . "\n" .
                "  Total cost:" . $total_cost, FILE_APPEND);

            return $result;

        } else {
            return null;
        }
    }
    
    private function clean_json_output($text)
    {
        $lines = preg_split("/\r\n|\r|\n/", trim($text));
        if (count($lines) > 0 && strpos(trim($lines[0]), "```") === 0) {
            // Remove the first and last line (assumed to be code fences)
            array_shift($lines);
            array_pop($lines);
        }
        return implode("\n", $lines);
    }

    private function calculate_cost($usage, $model_config)
    {
        if (!is_array($usage)) {
            $usage = (array) $usage;
        }
        $prompt_tokens = isset($usage["prompt_tokens"]) ? $usage["prompt_tokens"] : 0;
        $completion_tokens = isset($usage["completion_tokens"]) ? $usage["completion_tokens"] : 0;
        $prompt_tokens_details = isset($usage["prompt_tokens_details"]) ? $usage["prompt_tokens_details"] : [];
        $cached_tokens = isset($prompt_tokens_details["cached_tokens"]) ? $prompt_tokens_details["cached_tokens"] : 0;
        $non_cached_tokens = $prompt_tokens - $cached_tokens;

        $input_cost_per_token = $model_config->input_cost / 1000000;
        $cached_cost_per_token = ($model_config->cached_input_cost ? $model_config->cached_input_cost : 0) / 1000000;
        $output_cost_per_token = $model_config->output_cost / 1000000;

        return ($non_cached_tokens * $input_cost_per_token) +
            ($cached_tokens * $cached_cost_per_token) +
            ($completion_tokens * $output_cost_per_token);
    }
}
