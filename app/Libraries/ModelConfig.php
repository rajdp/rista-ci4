<?php

namespace App\Libraries;

class ModelConfig
{
    public $alias;
    public $model_name;
    public $input_cost;         // cost per 1M tokens (dollars)
    public $cached_input_cost;  // optional cost per 1M tokens (cached)
    public $output_cost;        // cost per 1M tokens (dollars)

    public function __construct($alias, $model_name, $input_cost, $cached_input_cost, $output_cost)
    {
        $this->alias = $alias;
        $this->model_name = $model_name;
        $this->input_cost = $input_cost;
        $this->cached_input_cost = $cached_input_cost;
        $this->output_cost = $output_cost;
    }
}





