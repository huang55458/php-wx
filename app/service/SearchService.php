<?php

namespace app\service;

use app\cnsts\ELASTIC_SEARCH;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use JsonException;
use think\facade\Log;

class SearchService
{
    private int $es_version = 8;
    private string $index = '';
    private string $split_field = '';
    private array $split_segments = [];
    private bool $trackScores = false;
    private bool $verbose = false;
    private int $pageSize = 20;
    private int $pageNum = 0;
    private int $queryTimeout = 2000;
    private int $aggregateSize = 0;
    private array $rawQuery = [];
    private array $rawFilter = [];
    private array $rawAggs = [];
    private array $rawDistinct = [];
    private array $rawSort = [];
    private array $fields = [];
    private array $convertFields = [];
    private array $split_index = [];
    private array $clientCfg = [
        'timeout' => 8,
        'connect_timeout' => 8,
    ];

    /**
     * @return string
     */
    public function getEsVersion(): string
    {
        return $this->es_version;
    }

    /**
     * @param string $es_version
     */
    public function setEsVersion(string $es_version): void
    {
        $this->es_version = $es_version;
    }

    /**
     * @return bool
     */
    public function isTrackScores(): bool
    {
        return $this->trackScores;
    }

    /**
     * @param bool $trackScores
     */
    public function setTrackScores(bool $trackScores): void
    {
        $this->trackScores = $trackScores;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     */
    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @return int
     */
    public function getPageNum(): int
    {
        return $this->pageNum;
    }

    /**
     * @param int $pageNum
     */
    public function setPageNum(int $pageNum): void
    {
        $this->pageNum = $pageNum;
    }

    /**
     * @return int
     */
    public function getQueryTimeout(): int
    {
        return $this->queryTimeout;
    }

    /**
     * @param int $queryTimeout
     */
    public function setQueryTimeout(int $queryTimeout): void
    {
        $this->queryTimeout = $queryTimeout;
    }

    /**
     * @return array
     */
    public function getClientCfg(): array
    {
        return $this->clientCfg;
    }

    /**
     * @param array $clientCfg
     */
    public function setClientCfg(array $clientCfg): void
    {
        $this->clientCfg = $clientCfg;
    }

    public function getClient(): ?Client
    {
        try {
            return ClientBuilder::create()
                ->setHosts(explode(',', env('ELASTIC_SEARCH_HOST')))
                ->setBasicAuthentication(env('ELASTIC_SEARCH_USER'), env('ELASTIC_SEARCH_PASS'))
                ->setRetries(1)
                ->build();
        } catch (AuthenticationException $e) {
            Log::write($e->getMessage());
        }
        return null;
    }

    public function parseRequest($request): void
    {
        if (isset($request['index'])) {
            $this->index = $request['index'];
        }
        if (isset($request['page_size'])) {
            $this->pageSize = $request['page_size'];
        }
        if (isset($request['page_num'])) {
            $this->pageNum = $request['page_num'];
        }
        if (isset($request['query'])) {
            $this->rawQuery = $request['query'];
        }
        if (isset($request['filter'])) {
            $this->rawFilter = $request['filter'];
        }
        if (isset($request['aggregates'])) {
            foreach ($request['aggregates'] as $key => $value) {
                $this->rawAggs[$key] = [
                    0 => $value[0],
                    1 => $value[1],
                    2 => $value[2] ?? [],
                    3 => $value[3] ?? [],
                ];
            }
        }
        if (isset($request['aggregate_size'])) {
            $this->aggregateSize = $request['aggregate_size'];
        }
        if (isset($request['distinct'])) {
            $this->rawDistinct = $request['distinct'];
        }
        if (isset($request['fields'])) {
            $this->fields = $request['fields'];
        }
        if (isset($request['verbose'])) {
            $this->verbose = $request['verbose'];
        }
        if (isset($request['sort'])) {
            $this->rawSort = $request['sort'];
        }
    }

    protected function parseAggregate($aggregates, $aggregate_size): array
    {
        $group = [];
        $result = [];
        foreach ($aggregates as $name => [$function, $fields, $filter, $ext_config]) {
            $filter = empty($filter) ? [] : $filter;
            if (is_array($fields)) {
                if (count($fields) < 1) {
                    return $result;
                }
                if (count($fields) === 1) {
                    $fields = $fields[0];
                } else {
                    // 根据分组字段进行分组
                    $key = array_reverse($fields);
                    array_pop($key);
                    $key = array_merge($key, $filter);
                    $field = array_pop($fields);
                    try {
                        $key_md5 = '#GROUPBY#' . md5(json_encode($key, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                    } catch (JsonException $e) {
                        Log::write($e->getMessage());
                    }
                    if (!isset($group[$key_md5])) {
                        $group[$key_md5] = [
                            'filter' => $filter[$field] ?? [],
                            'field' => $field,
                            'aggs' => [],
                        ];
                    }
                    $group[$key_md5]['aggs'][$name] = [$function, $fields, $filter, $ext_config];
                    continue;
                }
            }
            $result[$name] = [
                $function => ['field' => $fields],
            ];
            if (!empty($filter) && isset($filter[$fields])) {
                $result[$name] = array_merge($filter[$fields], ['aggs' => [$name => $result[$name]]]);
            }
            if ($function === ELASTIC_SEARCH::AGGREGATE_TERMS) {
                $result[$name][$function]['size'] = $aggregate_size;
                if ($ext_config) {
                    foreach ($ext_config as $k => $v) {
                        $result[$name][$function][$k] = $v;
                    }
                }
            }
        }

        if (!empty($group)) {
            foreach ($group as $key => $item) {
                $field = $item['field'];
                $filter = $item['filter'];
                if (!empty($filter)) {
                    $result[$key] = [
                        'terms' => [
                            'field' => $field,
                            'size' => $aggregate_size,
                            'collect_mode' => 'breadth_first',
                            'execution_hint' => 'map',
                        ],
                        'aggs' => [
                            $key => array_merge(
                                $filter,
                                [
                                    'aggs' => $this->parseAggregate($item['aggs'], $aggregate_size),
                                ]
                            ),
                        ],
                    ];
                } else {
                    $result[$key] = [
                        'terms' => [
                            'field' => $field,
                            'size' => $aggregate_size,
                            'collect_mode' => 'breadth_first',
                            'execution_hint' => 'map',
                        ],
                        'aggs' => $this->parseAggregate($item['aggs'], $aggregate_size),
                    ];
                }
                // 按时间聚合
                if (!empty($ext_config['date_histogram'][$field])) {
                    $result[$key]['date_histogram'] = [
                        'field' => $field,
//                        'time_zone' => '+08:00',
                    ];
                    $result[$key]['date_histogram'] = array_merge($ext_config['date_histogram'][$field], $result[$key]['date_histogram']);
                    unset($result[$key]['terms']);
                }
            }
        }
        return $result;
    }

    protected function parseDistinct($distinctions): array
    {
        $result = [];
        foreach ($distinctions as $field => $size) {
            $aggs = [];
            $sort = $this->parseSort($this->rawSort);
            foreach ($this->rawSort as $k => $v) {
                if ($k === '_score') {
                    $aggs[$k] = [
                        'max' => [
                            'script' => [
                                'lang' => 'painless',
                                'inline' => '_score',
                            ],
                        ],
                    ];
                } else {
                    $aggs[$k] = [
                        'max' => [
                            'field' => $k,
                        ],
                    ];
                }
            }
            $result['distinct'] = [
                'terms' => [
                    'field' => $field,
                    'size' => $size,
                ],
            ];
            if ($sort) {
                $result['distinct']['terms']['order'] = $sort;
            }
            if ($aggs) {
                $result['distinct']['aggs'] = $aggs;
            }
        }
        return $result;
    }

    protected function parseSort($sort): array
    {
        $result = [];
        foreach ($sort as $key => $value) {
            if (isset($value['poi'])) {
                $value[$key] = $value['poi'];
                unset($value['poi']);
                $result['_geo_distance'] = $value;
                continue;
            }
            $result[] = [$key => ELASTIC_SEARCH::SORT_OPERATOR_MAP[$value]];
        }
        return $result;
    }

    private function extractField($field, $value): array
    {
        $type = null;
        $relationship = null;
        $join_size = 0;
        $exact_match = false;
        $prefix = null;
        $nest_prefix = null;
        if (!is_array($value)) {
            $value = [$value];
        }
        if (str_contains($field, '|')) {
            [, $field] = explode('|', $field);
        }
        if (isset($this->convertFields[$field])) {
            $field = $this->convertFields[$field];
        }
        if (str_contains($field, '.')) {
            $explode = explode('.', $field);
            $suffix = array_pop($explode);
            $prefix = $explode[0];
            $field = implode('.', $explode);
            if ($suffix === ELASTIC_SEARCH::EXACT_FIND_SUFFIX) {
                $exact_match = true;
            } else {
                $field .= '.' . $suffix;
            }
        }
        $default_header = ELASTIC_SEARCH::TABLE_MAP[$this->index]['properties'] ?? [];
        $analyzer = $default_header[$field]['analyzer']['fields']['like']['analyzer'] ?? ELASTIC_SEARCH::ANALYZER_NONE;
        $field_type = $default_header[$field]['type'] ?? ELASTIC_SEARCH::TYPE_KEYWORD;
        if ($analyzer !== ELASTIC_SEARCH::ANALYZER_NONE && $exact_match === false && !in_array('', $value, true) && mb_strlen($value[0]) <= 32) {
            $field .= '.' . $analyzer;
        } else {
            $exact_match = true;
        }
        if (isset($default_header[$prefix]) && $default_header[$prefix]['type'] === ELASTIC_SEARCH::TYPE_NESTED) {
            $nest_prefix = $prefix;
        }

        return [$field, $field_type, $type, $analyzer, $exact_match, $relationship, $nest_prefix, $join_size];
    }

    private function parseSplitIndex($filter): void
    {
        $min_date = '';
        $max_date = '';
        if (is_array($filter) && $filter) {
            foreach ($filter as $key => $value) {
                if (is_numeric($key)) {
                    if ('>=' === $value[0] && (empty($min_date) || $min_date > $value[1])) {
                        $min_date = $value[1];
                    }
                    if ('<=' === $value[0] && $max_date < $value[1]) {
                        $max_date = $value[1];
                    }
                }
            }
        }

        if (!empty($min_date) || !empty($max_date)) {
            $min_date = (empty($min_date) || empty(strtotime($min_date))) ? '2017-01-01' : date('Y-m-d', strtotime($min_date));
            $max_date = (empty($max_date) || empty(strtotime($max_date))) ? '3100-01-01' : date('Y-m-d', strtotime($max_date));

            foreach ($this->split_segments as $key => $segment) {
                $range = $segment['range'];
                if ($range[0] > $max_date || $range[1] < $min_date) {
                    continue;
                }
                $this->split_index[] = $this->index . '-' . $key;
            }
        }
    }

    private function combineJoinQuery(array $join_query, $sole_query_type, $logic, $sub, &$result): void
    {
        if ($logic === 'NOT') {
            $logic = 'AND';
        }
        // single join flag
        if ($sole_query_type === 'join' && $sub && count($join_query) === 1) {
            $only_single_join = true;
        } else {
            $only_single_join = false;
        }
        foreach ($join_query as $type => $sub_group_query) {
            $sub_join_query = [];
            $only_single_join_nest = false;
            // nest query in join query
            if (isset($sub_group_query['nest']) && $sub_group_query['nest']) {
                // single join nest flag
                if ($only_single_join && count($sub_join_query) === 1 && count($sub_group_query['nest']) === 1) {
                    $only_single_join_nest = true;
                }
                foreach ($sub_group_query['nest'] as $nest_prefix => $queries) {
                    $sub_join_nest_query = [ELASTIC_SEARCH::LOGIC_MAP[$logic] => $queries];
                    if ($logic === 'OR') {
                        $sub_join_nest_query['minimum_should_match'] = 1;
                    }
                    if ($only_single_join_nest) {
                        $sub_join_query = [
                            'bool' => $sub_join_nest_query,
                            '_meta_' => [
                                'relationship' => $sub_group_query['_param_']['relationship'],
                                'nest_prefix' => $nest_prefix,
                            ],
                        ];
                    } else {
                        $sub_join_nest_query = [
                            'nested' => [
                                'path' => $nest_prefix,
                                'query' => [
                                    'bool' => $sub_join_nest_query,
                                ],
                            ],
                        ];
                        $sub_join_query[] = $sub_join_nest_query;
                    }
                }
            }
            // join query without nest
            if ($sub_group_query['normal']) {
                $sub_join_query = array_merge($sub_join_query, $sub_group_query['normal']);
            }
            // get single join query
            $sub_join_query = [ELASTIC_SEARCH::LOGIC_MAP[$logic] => $sub_join_query];
            if ($logic === 'OR') {
                $sub_join_query['minimum_should_match'] = 1;
            }
            if ($only_single_join) {
                if ($only_single_join_nest) {
                    $result = $sub_join_query[ELASTIC_SEARCH::LOGIC_MAP[$logic]];
                    $result['_meta_']['join_size'] = $sub_group_query['_param_']['join_size'];
                    $result['_meta_']['type'] = $type;
                } else {
                    $result = [
                        'bool' => $sub_join_query,
                        '_meta_' => [
                            'relationship' => $sub_group_query['_param_']['relationship'],
                            'join_size' => $sub_group_query['_param_']['join_size'],
                            'type' => $type,
                        ],
                    ];
                }
            } else {
                $data = [
                    $sub_group_query['_param_']['relationship'] . '_type' => $type,
                    'query' => [
                        'bool' => $sub_join_query,
                    ],
                ];
                if ($sub_group_query['_param_']['join_size'] > 0) {
                    $data['inner_hits'] = [
                        'name' => $sub_group_query['_param_']['name'],
                        'size' => $sub_group_query['_param_']['join_size'],
                    ];
                    if (isset($this->joinFields[$type]) && $this->joinFields[$type]) {
                        $data['inner_hits']['_source']['includes'] = $this->joinFields[$type];
                    }
                }
                $sub_join_query = [
                    'has_' . $sub_group_query['_param_']['relationship'] => $data,
                ];
                $result[] = $sub_join_query;
            }
        }
    }

    private function combineNestQuery(array $nest_query, $sole_query_type, $logic, $sub, &$result): void
    {
        if ($logic === 'NOT') {
            $logic = 'AND';
        }
        if ($sole_query_type === 'nest' && $sub && count($nest_query) === 1) {
            $only_single_nest = true;
        } else {
            $only_single_nest = false;
        }
        foreach ($nest_query as $nest_prefix => $queries) {
            $sub_nest_query = [ELASTIC_SEARCH::LOGIC_MAP[$logic] => $queries];
            if ($logic === 'OR') {
                $sub_nest_query['minimum_should_match'] = 1;
            }
            if ($only_single_nest) {
                $result = [
                    'bool' => $sub_nest_query,
                    '_meta_' => [
                        'nest_prefix' => $nest_prefix,
                    ],
                ];
            } else {
                $sub_nest_query = [
                    'nested' => [
                        'path' => $nest_prefix,
                        'query' => [
                            'bool' => $sub_nest_query,
                        ],
                    ],
                ];
                $result[] = $sub_nest_query;
            }
        }
    }

    private function combineNormalQuery(array $normal_query, &$result): void
    {
        $result = array_merge($result, $normal_query);
    }

    protected function parseClause($query = [], $sub = false, $is_query = false): array
    {
        static $join_name = 0;
        $result = [];
        $logic = 'AND';
        if (is_array($query) && $query) {
            if (array_key_exists('_logic', $query)) {
                $logic = strtoupper(trim($query['_logic']));
                unset($query['_logic']);
            }
            $query_group = [
                'nest' => [],
                'join' => [],
                'normal' => [],
            ];
            $sole_query_type = null;
            foreach ($query as $key => $value) {
                $type = null;
                $relationship = null;
                $nest_prefix = null;
                $join_size = 0;
                $sub_query = [];
                if (is_numeric($key)) {
                    if (is_array($value)) {
                        $sub_query = $this->parseClause($value, true);
                        self::reduceClauseDepth($sub_query);
                        if (isset($sub_query['_meta_'])) {
                            extract($sub_query['_meta_'], EXTR_OVERWRITE);
                            unset($sub_query['_meta_']);
                        }
                    }
                } else {
                    [
                        $key,
                        $field_type,
                        $type,
                        $analyzer,
                        $exact_match,
                        $relationship,
                        $nest_prefix,
                        $join_size
                    ] = $this->extractField($key, $value);
                    // 拆分索引
                    if ($this->split_field === $key && is_array($value)) {
                        $this->parseSplitIndex($value);
                    }
                    if (is_array($value)) {
                        if ($value) {
                            // range query
                            if (isset($value[0]) && is_array($value[0])) {
                                foreach ($value as [$operator, $range_value]) {
                                    $sub_query[ELASTIC_SEARCH::RANGE_OPERATOR_MAP[$operator]] = $range_value;
                                }
                                $sub_query = self::translateFieldName($key, $field_type, $sub_query, 'range', $analyzer, false, $this->es_version);
                            } elseif (isset($value['geo_distance'])) {
                                $sub_query = self::translateFieldName($key, $field_type, $value, 'geo_distance', $analyzer, false, $this->es_version);
                            } else {
                                if (array_key_exists('_logic', $value)) {
                                    unset($value['_logic']);
                                }
                                $value = array_values(array_diff($value, [ELASTIC_SEARCH::ENUM_ALL]));
                                if ($exact_match) {
                                    // terms query
                                    if ($value) {
                                        $sub_query = self::translateFieldName($key, $field_type, $value, 'terms', $analyzer, $exact_match, $this->es_version);
                                    }
                                } else {
                                    // match query
                                    $in_list_query = [];
                                    foreach ($value as $sub_value) {
                                        $in_list_query[] = self::translateFieldName(
                                            $key,
                                            $field_type,
                                            $sub_value,
                                            'match',
                                            $analyzer,
                                            false,
                                            $this->es_version
                                        );
                                    }
                                    $sub_query['bool'][ELASTIC_SEARCH::LOGIC_MAP['OR']] = $in_list_query;
                                }
                            }
                        }
                    } elseif ($value !== ELASTIC_SEARCH::ENUM_ALL) {
                        if ($analyzer === ELASTIC_SEARCH::ANALYZER_NONE || $exact_match) {
                            $operator = 'term';
                        } else {
                            $operator = 'match';
                        }
                        $sub_query = self::translateFieldName($key, $field_type, $value, $operator, $analyzer, false, $this->es_version);
                    }
                }
                if ($sub_query) {
                    // group query for join\nest\normal
                    if ($relationship && isset($type)) {
                        if (!isset($query_group['join'][$type])) {
                            $query_group['join'][$type]['_param_'] = [
                                'relationship' => $relationship,
                                'name' => (string)$join_name++,
                                'join_size' => $join_size,
                            ];
                        }
                        if ($nest_prefix) {
                            $query_group['join'][$type]['nest'][$nest_prefix][] = $sub_query;
                        } else {
                            $query_group['join'][$type]['normal'][] = $sub_query;
                        }
                        $query_type = 'join';
                    } elseif ($nest_prefix) {
                        $query_group['nest'][$nest_prefix][] = $sub_query;
                        $query_type = 'nest';
                    } else {
                        $query_group['normal'][] = $sub_query;
                        $query_type = 'normal';
                    }
                    if ($sole_query_type === null) {
                        $sole_query_type = $query_type;
                    } elseif ($sole_query_type !== $query_type) {
                        $sole_query_type = false;
                    }
                }
            }
            // join query
            if ($query_group['join']) {
                $this->combineJoinQuery($query_group['join'], $sole_query_type, $logic, $sub, $result);
            }
            // nest query
            if ($query_group['nest']) {
                $this->combineNestQuery($query_group['nest'], $sole_query_type, $logic, $sub, $result);
            }
            // normal query
            if ($query_group['normal']) {
                $this->combineNormalQuery($query_group['normal'], $result);
            }
            if ($result) {
                if ($logic === 'OR' && count($result) === 1) {
                    $logic = 'AND';
                }
                if ($this->trackScores === false && $is_query && $sub === false) {
                    foreach ($result as $key => $value) {
                        if (is_numeric($key) && isset($value['bool'])) {
                            $value = [
                                'constant_score' => [
                                    'filter' => $value,
                                ],
                            ];
                            $result[$key] = $value;
                            continue;
                        }
                        if (is_numeric($key)) {
                            $value = [
                                'constant_score' => [
                                    'filter' => ['bool' => ['must' => $value]],
                                ],
                            ];
                            $result[$key] = $value;
                        }
                    }
                }
                $result = [
                    'bool' => [
                        ELASTIC_SEARCH::LOGIC_MAP[$logic] => $result,
                    ],
                ];
                if ($logic === 'OR') {
                    $result['bool']['minimum_should_match'] = 1;
                }
            }
        }

        return $result;
    }

    private static function reduceClauseDepth(&$query): void
    {
        $should_param = [];
        if (isset($query['bool']['minimum_should_match'])) {
            $should_param = ['minimum_should_match' => 1];
            unset($query['bool']['minimum_should_match']);
        }
        $query_meta = [];
        foreach (ELASTIC_SEARCH::LOGIC_MAP as $v) {
            if (isset($query['bool'][$v]['_meta_'])) {
                $query_meta = $query['bool'][$v]['_meta_'];
                unset($query['bool'][$v]['_meta_']);
            }
        }
        if ($should_param) {
            $query['bool'] = array_merge($query['bool'], $should_param);
        }
        if ($query_meta) {
            $query['_meta_'] = $query_meta;
        }
    }

    private static function translateFieldName($field, $field_type, $value, $operator, $analyzer, $exact_match = false, $es_version = 8.13): array
    {
        $query_struct = [];
        if (!$exact_match && in_array($analyzer, [ELASTIC_SEARCH::ANALYZER_MATCH, ELASTIC_SEARCH::ANALYZER_MP], true) && !in_array($operator, ['terms', 'term'])) {
            $value = [
                'query' => $value,
                'operator' => 'and',
            ];
        }
        // special field tags
        if ($field === ELASTIC_SEARCH::INNER_FIELDS) {
            $query_struct = ['match_all' => ['boost' => 1]];
        } elseif ($value === ELASTIC_SEARCH::EXIST_FIELD) {
            if ('id' !== $field) {
                $query_struct = ['exists' => ['field' => $field]];
            }
        } elseif ($operator === 'geo_distance') {
            $query_struct = [
                $operator => [
                    $field => $value['geo_distance'],
                    'distance' => empty($value['distance'])
                        ? '1km' : $value['distance'],
                    'distance_type' => 'plane',
                ],
            ];
        } else {
            if ($operator === 'terms' && is_array($value)) {
                $value = array_values(array_unique($value));
            }
            if (in_array($field_type, ELASTIC_SEARCH::NUMERIC_TYPE, true)) {
                if ($operator === 'range') {
                    foreach ($value as $range_value) {
                        if (!is_numeric($range_value)) {
                            goto ret;
                        }
                    }
                } elseif (!is_array($value) && !is_numeric($value)) {
                    goto ret;
                }
                $query_struct = [$operator => [$field => $value]];
            } elseif (in_array($field_type, ELASTIC_SEARCH::DATE_TYPE, true) && in_array($operator, ['terms', 'term'])) {
                $value = !is_array($value) ? [$value] : $value;
                if ($es_version > 7) {
                    foreach ($value as &$val) {
                        if ('0000-01-01' === $val) {
                            $val = 0;
                        }
                    }
                    unset($val);
                } else if (in_array(0, $value)) {
                    // 修复线上由于格式不正确导致的报错
                    if ($field_type === ELASTIC_SEARCH::TYPE_DATE_YM) {
                        $value[] = '0000-01';
                    } else {
                        $value[] = '0000-01-01';
                    }
                }
                $value = array_values(array_unique($value));
                if (count($value) > 1) {
                    $query_struct = ['terms' => [$field => $value]];
                } elseif (count($value) === 1) {
                    $query_struct = ['term' => [$field => $value[0]]];
                } else {
                    $query_struct = ['term' => [$field => 0]];
                }
            } else {
                $query_struct = [$operator => [$field => $value]];
            }
        }
        ret:
        return $query_struct;
    }

    public function plainSearch($request)
    {
        $this->parseRequest($request);
        $params = [
            'index' => $this->index,
            'rest_total_hits_as_int' => true,
            'from' => $this->pageSize * $this->pageNum,
            'size' => $this->pageSize,
            'timeout' => $this->queryTimeout . 'ms',
            'client' => $this->clientCfg,
            'body' => [
                'track_scores' => $this->trackScores,
                '_source' => !empty($this->fields) ? $this->fields : ['*'],
            ],
        ];
        if (empty($this->split_index) && isset($this->split_segments)) {
            foreach ($this->split_segments as $key => $segment) {
                $this->split_index[] = $this->index . '-' . $key;
            }
        }
        if (!empty($this->split_index)) {
            $params['index'] = implode(',', array_keys(array_flip($this->split_index)));
        }
        if ($this->rawQuery) {
            $params['body']['query'] = $this->parseClause($this->rawQuery, false, true);
        }
        if ($this->rawFilter) {
            $params['body']['query']['bool']['filter'] = $this->parseClause($this->rawFilter);
        }
        if ($this->rawAggs) {
            $params['body']['aggs'] = $this->parseAggregate($this->rawAggs, $this->aggregateSize);
        }
        if ($this->rawDistinct) {
            $distinctions = $this->parseDistinct($this->rawDistinct);
            if (array_key_exists('aggs', $params['body'])) {
                $params['body']['aggs'] = array_merge($params['body']['aggs'], $distinctions);
            } else {
                $params['body']['aggs'] = $distinctions;
            }
        }
        if ($this->rawSort) {
            $params['body']['sort'] = $this->parseSort($this->rawSort);
        }
        if ($this->verbose) {
            $params['client'] = ['verbose' => true];
        }
        try {
            $client = $this->getClient();
            if ($client !== null) {
                $results = $client->search($params);
                if (isset($result['timed_out']) && $result['timed_out'] === true) {
                    try {
                        Log::write('elasticSearch查询超时，' . json_encode($params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                    } catch (JsonException $e) {
                        Log::write($e->getMessage());
                    }
                }
                try {
                    return json_decode($results->asString(), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    Log::write($e->getMessage());
                }
            }
        } catch (ClientResponseException|ServerResponseException $e) {
            Log::write($e->getMessage());
        }
        return [];
    }

}