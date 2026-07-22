<?php

$providers = [
    'llama' => [
        'label' => 'Meta Llama',
        'models' => [
            'llama-8b' => [
                'label' => 'Llama 3.1 8B (Fast)',
                'description' => 'Cepat & responsif, cocok untuk tugas harian.',
                'model' => 'meta/llama-3.1-8b-instruct',
                'provider' => 'nvidia',
            ],
            'llama-70b' => [
                'label' => 'Llama 3.1 70B (Smart)',
                'description' => 'Lebih cerdas & akurat untuk penalaran kompleks.',
                'model' => 'meta/llama-3.1-70b-instruct',
                'provider' => 'nvidia',
            ],
        ],
    ],
    'openai' => [
        'label' => 'OpenAI GPT',
        'models' => [
            'gpt-oss-120b' => [
                'label' => 'GPT-OSS 120B',
                'description' => 'Model open-source terkalibrasi tinggi.',
                'model' => 'openai/gpt-oss-120b',
                'provider' => 'openai',
            ],
        ],
    ],
    'mistral' => [
        'label' => 'Mistral AI',
        'models' => [
            'mistral-small-4' => [
                'label' => 'Mistral Small 4 119B',
                'description' => 'Cepat & efisien dengan nalar Mistral.',
                'model' => 'mistralai/mistral-small-4-119b-2603',
                'provider' => 'mistral',
            ],
        ],
    ],
    'qwen' => [
        'label' => 'Qwen AI',
        'models' => [
            'qwen-122b' => [
                'label' => 'Qwen 3.5 122B',
                'description' => 'Kombinasi performa & kecepatan yang optimal.',
                'model' => 'qwen/qwen3.5-122b-a10b',
                'provider' => 'nvidia',
            ],
        ],
    ],
    'nvidia-nemotron' => [
        'label' => 'NVIDIA Nemotron',
        'models' => [
            'nemotron-ultra' => [
                'label' => 'Nemotron 3 Ultra 550B',
                'description' => 'Model raksasa dari NVIDIA dengan fitur reasoning budget khusus.',
                'model' => 'nvidia/nemotron-3-ultra-550b-a55b',
                'provider' => 'nvidia',
                'extra_args' => [
                    'max_tokens' => 16384,
                    'chat_template_kwargs' => [
                        'enable_thinking' => true,
                    ],
                    'reasoning_budget' => 16384,
                ]
            ],
        ],
    ],
];

// Flat list of models for validation and quick lookups
$models = [];
foreach ($providers as $provKey => $provInfo) {
    foreach ($provInfo['models'] as $modelKey => $modelMeta) {
        $models[$modelKey] = $modelMeta;
    }
}

return [
    'default' => 'llama-8b',
    'providers' => $providers,
    'models' => $models,
];
