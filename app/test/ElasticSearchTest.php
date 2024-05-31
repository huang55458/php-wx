<?php

namespace app\test;

use app\cnsts\ELASTIC_SEARCH;
use Elastic\Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

class ElasticSearchTest extends TestCase
{
    public string $index_name = 'user';
    public ?object $mysqli = null;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->mysqli = mysqli_connect("127.0.0.1", "root", "123456", "test");
    }

    public function getClient(): ?\Elastic\Elasticsearch\Client
    {
        $client = ClientBuilder::create()
//            ->setHosts(env('ELASTIC_SEARCH_HOST'))
            ->setBasicAuthentication('elastic', 'aQHaRYPfidp*-AxzL9IY')
            ->setRetries(1)
            ->build();
        return $client;
    }

    public function testcreateIndex()
    {
        $response = $this->getClient()->indices()->create(ELASTIC_SEARCH::getMapping($this->index_name));
        $this->assertNotEmpty($response);
    }

    public function testdeleteIndex()
    {
        $response = $this->getClient()->indices()->delete(['index' => $this->index_name]);
        $this->assertNotEmpty($response);
    }

    public function testbulk()
    {
        $data = mysqli_query($this->mysqli, 'select * from user where id in (1008,1009);');
        $this->assertNotEmpty($data);

        $params = [];
        while ($row = mysqli_fetch_assoc($data)) {
            $row['amount'] = 1.234;
            $row['uuid'] = $row['id'];
            $row['ext'] = json_decode($row['ext'], true);
            $row['ext']['env_name'] = 'test';
            $row['ext']['pc_name'] = 'desktop';


            $params['body'][] = [
                'index' => [
                    '_index' => $this->index_name,
                    '_id' => $row['id']
                ]
            ];
            $params['body'][] = $row;
        }
        $response = $this->getClient()->bulk($params);
        $this->assertNotEmpty($response);
    }

    public function testget()
    {
        $params = [
            'index' => 'user',
            'id' => '1009'
        ];
        $response = $this->getClient()->get($params);
        $this->assertNotEmpty($response);
        echo $response->asString();
    }

    public function testsearch()
    {
        $params = [
            'index' => 'user',
            'body' => [
                'query' => [
                    'match' => [
                        'telephone' => '17618717324'
                    ]
                ]
            ]
        ];

        $results = $this->getClient()->search($params);
        $this->assertNotEmpty($results);
        echo $results->asString();
    }

    public function testupdate()
    {
        $params = [
            'index' => 'user',
            'id' => '1008',
            'body' => [
                'doc' => [
                    'name' => '哈哈哈'
                ]
            ]
        ];

        $response = $this->getClient()->update($params);
        $this->assertNotEmpty($response);
        echo $response->asString();
    }

    public function testdelete()
    {
        $params = [
            'index' => 'user',
            'id' => '1009'
        ];

        $response = $this->getClient()->delete($params);
        $this->assertNotEmpty($response);
        echo $response->asString();
    }

    public function testgetSetting()
    {
        $params = [
            'index' => 'user',
        ];
        $response = $this->getClient()->indices()->getMapping($params);
        $this->assertNotEmpty($response);
        echo $response->asString();
    }
}
