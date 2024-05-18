<?php

namespace app\cnsts;

class ELASTIC_SEARCH
{
    public const INNER_FIELDS = '_INNER_FIELDS_';
    public const EXIST_FIELD = '_EXIST_FIELD_';

    public const AGGREGATE_AVG = 'avg';
    public const AGGREGATE_MAX = 'max';
    public const AGGREGATE_MIN = 'min';
    public const AGGREGATE_SUM = 'sum';
    public const AGGREGATE_COUNT = 'value_count';
    public const AGGREGATE_TERMS = 'terms';

    public const TYPE_LONG = 'long';
    public const TYPE_INT = 'integer';
    public const TYPE_SHORT = 'short';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_FLOAT = 'float';
    public const TYPE_SCALED_FLOAT = 'scaled_float';
    public const TYPE_KEYWORD = 'keyword';
    public const TYPE_TEXT = 'text';
    public const TYPE_DATE = 'date';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_OBJECT = 'object';
    public const TYPE_NESTED = 'nested';
    public const TYPE_GEO_POINT = 'geo_point';

    public const DATE_NULL_VALUE = 0;
    public const DATE_YM_NULL_VALUE = 0;
    public const STRING_NULL_VALUE = '_NULL_VALUE_';
    public const NUMBER_NULL_FIELD = 0;

    public const FORMAT_DATE = 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||yyyy.MM.dd||epoch_millis';
    public const FORMAT_DATE_YMD = 'yyyy-MM-dd||yyyy.MM.dd||epoch_millis';
    public const FORMAT_DATE_YM = 'yyyy-MM||yyyy.MM||epoch_millis';


    public const ANALYZER_MATCH = 'ik_max_word';
    public const ANALYZER_MP = 'match&pinyin';
    public const ANALYZER_NONE = 'not_analyzed';
    public const ANALYZER_STD = 'standard';

    public const ANALYZER_MAP = [
        self::ANALYZER_MP => [
            'type' => self::TYPE_TEXT,
            'analyzer' => self::ANALYZER_MP,
            'search_analyzer' => 'ik_smart',
        ],
        self::ANALYZER_MATCH => [
            'type' => self::TYPE_TEXT,
            'analyzer' => self::ANALYZER_MATCH,
            'search_analyzer' => 'ik_smart',
        ],
        self::ANALYZER_STD => [
            'type' => self::TYPE_TEXT,
            'analyzer' => self::ANALYZER_STD,
            'search_analyzer' => 'standard',
        ],
        self::ANALYZER_NONE => [],
    ];


    public const EXACT_FIND_SUFFIX = '_exact_';

    public const INDEX_SETTINGS = [
        'mapping.total_fields.limit' => 5000,
        'number_of_shards' => 0,
        'number_of_replicas' => 0,
        'max_result_window' => 50000,
        'index' => [
            'refresh_interval' => '1s',
            'max_ngram_diff' => 31,
            'search' => [
                'slowlog' => [
                    'threshold' => [
                        'fetch' => [
                            'warn' => '7s',
                            'debug' => '1s',
                            'info' => '4s'
                        ],
                        'query' => [
                            'warn' => '7s',
                            'debug' => '1s',
                            'info' => '4s'
                        ]
                    ]
                ]
            ],
            'indexing' => [
                'slowlog' => [
                    'threshold' => [
                        'index' => [
                            'warn' => '7s',
                            'debug' => '1s',
                            'info' => '4s'
                        ]
                    ]
                ]
            ],
            'analysis' => [
                'tokenizer' => [],
                'filter' => [],
                'analyzer' => [],
            ],
        ],
    ];


    public const TABLE_MAP = [
        'user' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
            'properties' => [
                "id" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE
                ],
                "uuid" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE
                ],
                "name" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE,
                    "fields" => [
                        "like" => self::ANALYZER_MAP[self::ANALYZER_STD]
                    ]
                ],
                "telephone" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE,
                    "fields" => [
                        "like" => self::ANALYZER_MAP[self::ANALYZER_STD]
                    ]
                ],
                "sex" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE
                ],
                "password" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE
                ],
                "status" => [
                    "type" => self::TYPE_KEYWORD,
                    "null_value" => self::STRING_NULL_VALUE
                ],
                "amount" => [
                    "type" => self::TYPE_SCALED_FLOAT,
                    "null_value" => self::NUMBER_NULL_FIELD,
                    'scaling_factor' => '100',
                ],
                "ext" => [
                    "type" => self::TYPE_NESTED,
                    "properties" => [
                        "pc_name" => [
                            "type" => self::TYPE_KEYWORD,
                            "null_value" => self::STRING_NULL_VALUE,
                            "fields" => [
                                "like" => self::ANALYZER_MAP[self::ANALYZER_STD]
                            ]
                        ],
                        "env_name" => [
                            "type" => self::TYPE_KEYWORD,
                            "null_value" => self::STRING_NULL_VALUE,
                        ],
                    ],
                ],
                "superior" => [
                    "properties" => [
                        "com_id" => [
                            "type" => self::TYPE_KEYWORD,
                            "null_value" => self::STRING_NULL_VALUE,
                        ],
                    ],
                ],
                "create_time" => [
                    "type" => self::TYPE_DATE,
                    "format" => self::FORMAT_DATE,
                    "null_value" => self::DATE_NULL_VALUE,
                ],
                "update_time" => [
                    "type" => self::TYPE_DATE,
                    "format" => self::FORMAT_DATE,
                    "null_value" => self::DATE_NULL_VALUE,
                ],
            ],
        ],
    ];

    public static function getMapping($index_name): array
    {
        if (empty(self::TABLE_MAP[$index_name])) {
            return [ERRNO::INDEX_NO_EXIST, ERRNO::e(ERRNO::INDEX_NO_EXIST)];
        }
        return [
            "index" => $index_name,
            "body" => [
                "settings" => array_merge(self::INDEX_SETTINGS, self::TABLE_MAP[$index_name]['settings']),
                "mappings" => [
                    "dynamic" => false,
                    "include_in_all" => false,
                    "properties" => self::TABLE_MAP[$index_name]['properties'],
                ],
            ],
            "include_type_name" => true,
        ];
    }
}