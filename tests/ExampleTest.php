<?php
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->json('GET', '/market/list/usdt,btc')
             ->seeJsonStructure([
                'data'=>[
                    '*'=>[
                        'usdt'=>[
                            'sell',
                            'buy'
                        ],
                        'btc'=>[
                            'sell',
                            'buy'
                        ]
                    ]
                ]
             ]);
    }
}
