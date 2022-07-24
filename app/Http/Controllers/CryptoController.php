<?php
namespace App\Http\Controllers;

include_once dirname(dirname(dirname(__DIR__))) .DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.'KalmanFilter.php';

use KalmanFilter;
use WSSC\WebSocketClient;
use \WSSC\Components\ClientConfig;

use PHPHtmlParser\Dom;

use Phpml\Regression\SVR;
use Phpml\Regression\LeastSquares;
use Phpml\SupportVectorMachine\Kernel;

/**
 *
 */
class CryptoController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * @return array
     */
    public function test() {
        return [];
    }

    /**
     * @param $sign
     * @return array|void
     */
    function nobitex($sign=null) {
        try {
            if (empty($sign)) {
                $coins = 'btc,eth,etc,usdt,ada,bch,ltc,bnb,eos,xlm,xrp,trx,doge,uni,link,dai,dot,shib,aave,ftm,matic,axs,mana,sand,avax,pmn';
            } else {
                $coins = strtolower($sign);
            }

            $url = "https://api.nobitex.ir/market/stats?srcCurrency=$coins&dstCurrency=rls";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();
            $data_type = $response->getHeaderLine('content-type');

            $data = [];
            if ($status=='200' and $data_type=='application/json') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            if (substr_count($sign,',')>0 or empty($sign)) {
                $coins_arr = explode(',',$coins);
                foreach ($coins_arr as $coin) {
                    $prices = null;
                    $index = "$coin-rls";
                    if (isset($data['stats'][$index]['bestSell']) and isset($data['stats'][$index]['bestBuy'])) {
                        $prices[] = floatval($data['stats'][$index]['bestSell']);
                        $prices[] = floatval($data['stats'][$index]['bestBuy']);
                        $ret[$coin]['buy'] = min($prices);
                        $ret[$coin]['sell'] = max($prices);
                    }
                }
            } else {
                $index = strtolower($sign).'-rls';
                $ret['sell'] = floatval($data['stats'][$index]['bestSell'])/10;
                $ret['buy'] = floatval($data['stats'][$index]['bestBuy'])/10;
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function ramzinex($sign=null) {
        try {
            $url = "https://publicapi.ramzinex.com/exchange/api/v1.0/exchange/pairs";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data['data'] as $rec) {
                $prices = null;
                $prices[]=floatval($rec['buy']) / 10;
                $prices[]=floatval($rec['sell']) / 10;
                $ret[$rec['base_currency_symbol']['en']]['buy']=min($prices);
                $ret[$rec['base_currency_symbol']['en']]['sell']=max($prices);
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function abantether($sign=null) {
        try {
            $client = new WebSocketClient('wss://abantether.com/wss/ws/priceticker/', new ClientConfig());
            $client->send('');
            $data = json_decode($client->receive(),true);
            $ret = [];

            foreach ($data as $rec) {
                $symbol = strtolower($rec['symbol']);
                $prices=null;
                $prices[] = floatval($rec['priceBuy']);
                $prices[] = floatval($rec['priceSell']);
                $ret[$symbol]['buy']=min($prices);
                $ret[$symbol]['sell']=max($prices);
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function wallex($sign=null) {
        try {
            $url = "https://api.wallex.ir/v1/markets";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data['result']['symbols'] as $rec) {
                $symbol = $rec['baseAsset'];
                preg_match('/\w+/', $symbol, $output_array);
                if (count($output_array)) {
                    $prices=null;
                    $prices[]=floatval($rec['stats']['bidPrice']);
                    $prices[]=floatval($rec['stats']['askPrice']);
                    $ret[strtolower($output_array[0])]['buy']=min($prices);
                    $ret[strtolower($output_array[0])]['sell']=max($prices);
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function donyacoin($sign=null) {
        try {
            $url = "https://panel.donyacoin.com/-/publish/v1/currency";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data['data']['currencies'] as $rec) {
                $symbol = $rec['abbreviation'];
                preg_match('/\w+/', $symbol, $output_array);
                if (count($output_array)) {
                    $prices=null;
                    $prices[]=floatval($rec['buy']);
                    $prices[]=floatval($rec['sell']);
                    $ret[strtolower($output_array[0])]['buy']=min($prices);
                    $ret[strtolower($output_array[0])]['sell']=max($prices);
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function arzfi($sign=null) {
        try {
            $url = "https://arzfi.com/devapi/v1/market/assets";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data['data'] as $rec) {
                $prices = null;
                $prices[] = floatval($rec['arzfi_buy_price']);
                $prices[] = floatval($rec['arzfi_price']);
                $ret[strtolower($rec['symbol'])]['buy']=min($prices);
                $ret[strtolower($rec['symbol'])]['sell']=max($prices);
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function exnovin($sign=null) {
        try {
            $url = "https://admin.exnovin.io/api/markets?quote=TMN&page=1&per_page=100";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data['markets'] as $rec) {
                $prices = null;
                $prices[]=floatval(str_replace(',','',$rec['bestBuy']));
                $prices[]=floatval(str_replace(',','',$rec['bestSell']));
                $ret[strtolower($rec['base'])]['buy']=min($prices);
                $ret[strtolower($rec['base'])]['sell']=max($prices);
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function irpm($sign=null) {
        try {
            $url = "https://irpm.me/api/coins/100/pg:1";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data as $rec) {
                $prices = null;
                $prices[]=floatval($rec['buy_rial']);
                $prices[]=floatval($rec['sell_rial']);
                $price = floatval($rec['price']);
                $ret[strtolower($rec['symbol'])]['buy']=min($prices) > 0 ? min($prices) : $price;
                $ret[strtolower($rec['symbol'])]['sell']=max($prices) > 0 ? max($prices) : $price;
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function arzicoin($sign=null) {
        try {
            $dom = new Dom;
            $dom->loadFromUrl('https://arzicoin.com');
            $rows = $dom->find('#example')[0]->find('#myTable')[0]->find('tr');
            $ret = [];
            foreach ($rows as $row) {
                $symbol = trim(strtolower($row->getAttribute('symbol')));
                preg_match('/\w+/', $symbol, $output_array);

                if (count($output_array)) {
                    $prices = null;
                    $prices[] = floatval(str_replace([',',' '],'',$row->find('td.buy-price')[0]->text));
                    $prices[] = floatval(str_replace([',',' '],'',$row->find('td.sell-price')[0]->text));

                    $ret[$output_array[0]]['sell']=max($prices);
                    $ret[$output_array[0]]['buy']=min($prices);
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function bitgrand($sign=null) {
        try {
            $client = new \GuzzleHttp\Client();
            $src = file_get_contents('https://bitgrand.ir/');

            //== Parse nounce
            $tag1 = 'var ccpw_js_objects = {"ajax_url":"https:\/\/bitgrand.ir\/wp-admin\/admin-ajax.php","wp_nonce":"';
            $start = strpos($src,$tag1);
            $end = strpos($src,'"};',$start+strlen($tag1));
            $nounce = str_replace('"','',substr($src,$start+strlen($tag1),$end-$start-strlen($tag1)));

            $response = $client->request('POST', 'https://bitgrand.ir/wp-admin/admin-ajax.php', [
                'headers' => [
                    'User-Agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
                    'Accept'            => 'application/json, text/javascript, */*; q=0.01',
                    'Accept-Language'   => 'en-US,en;q=0.5',
                    'Content-Type'      => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With'  => 'XMLHttpRequest',
                    'Alt-Used'          => 'bitgrand.ir',
                    'Pragma'            => 'no-cache',
                    'Cache-Control'     => 'no-cache'
                ],
                'form_params' => [
                    "draw"=>"1",
                    "columns[0][data]"=>"rank",
                    "columns[0][name]"=>"rank",
                    "columns[0][searchable]"=>"true",
                    "columns[0][orderable]"=>"false",
                    "columns[0][search][value]"=>"",
                    "columns[0][search][regex]"=>"false",
                    "columns[1][data]"=>"name",
                    "columns[1][name]"=>"name",
                    "columns[1][searchable]"=>"true",
                    "columns[1][orderable]"=>"false",
                    "columns[1][search][value]"=>"",
                    "columns[1][search][regex]"=>"false",
                    "columns[2][data]"=>"price",
                    "columns[2][name]"=>"price",
                    "columns[2][searchable]"=>"true",
                    "columns[2][orderable]"=>"false",
                    "columns[2][search][value]"=>"",
                    "columns[2][search][regex]"=>"false",
                    "columns[3][data]"=>"change_percentage_24h",
                    "columns[3][name]"=>"change_percentage_24h",
                    "columns[3][searchable]"=>"true",
                    "columns[3][orderable]"=>"false",
                    "columns[3][search][value]"=>"",
                    "columns[3][search][regex]"=>"false",
                    "columns[4][data]"=>"Price_buy_ir",
                    "columns[4][name]"=>"Price_buy_ir",
                    "columns[4][searchable]"=>"true",
                    "columns[4][orderable]"=>"false",
                    "columns[4][search][value]"=>"",
                    "columns[4][search][regex]"=>"false",
                    "columns[5][data]"=>"Price_sell_ir",
                    "columns[5][name]"=>"Price_sell_ir",
                    "columns[5][searchable]"=>"true",
                    "columns[5][orderable]"=>"false",
                    "columns[5][search][value]"=>"",
                    "columns[5][search][regex]"=>"false",
                    "columns[6][data]"=>"market_cap",
                    "columns[6][name]"=>"market_cap",
                    "columns[6][searchable]"=>"true",
                    "columns[6][orderable]"=>"false",
                    "columns[6][search][value]"=>"",
                    "columns[6][search][regex]"=>"false",
                    "columns[7][data]"=>"total_volume",
                    "columns[7][name]"=>"total_volume",
                    "columns[7][searchable]"=>"true",
                    "columns[7][orderable]"=>"false",
                    "columns[7][search][value]"=>"",
                    "columns[7][search][regex]"=>"false",
                    "columns[8][data]"=>"supply",
                    "columns[8][name]"=>"supply",
                    "columns[8][searchable]"=>"true",
                    "columns[8][orderable]"=>"false",
                    "columns[8][search][value]"=>"",
                    "columns[8][search][regex]"=>"false",
                    "start"=>"0",
                    "length"=>"10",
                    "search[value]"=>"",
                    "search[regex]"=>"false",
                    "action"=>"ccpwp_get_coins_list",
                    "currency"=>"USD",
                    "nonce"=>"$nounce",
                    "currencyRate"=>"1",
                    "requiredCurrencies"=>"top-10"
                ]
            ]);

            $status = $response->getStatusCode();
            if ($status=='200') {
                $body = $response->getBody()->getContents();
                $data = json_decode($body,true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            if (isset($data['data']) and is_array($data['data'])) {
                foreach ($data['data'] as $rec) {
                    $symbol = $rec['symbol'];
                    preg_match('/\w+/', $symbol, $output_array);
                    if (count($output_array)) {
                        $prices = null;
                        $prices[]=floatval(str_replace(',','',$rec['Price_buy_ir']));
                        $prices[]=floatval(str_replace(',','',$rec['Price_sell_ir']));
                        $ret[strtolower($output_array[0])]['buy']=min($prices);
                        $ret[strtolower($output_array[0])]['sell']=max($prices);
                    }
                }

                if (!empty($sign)) {
                    if (substr_count($sign,',')>0) {
                        $coins = explode(',',$sign);
                        $new_ret = [];
                        foreach ($coins as $coin) {
                            $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                        }

                        $ret = $new_ret;
                    } else {
                        $new_ret = [];
                        $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                        $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                        $ret = $new_ret;
                    }
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|string[]|void
     */
    function arzinja($sign=null) {
        try {
            $url = "https://arzinja.app/stream/prices";

            $client = new \GuzzleHttp\Client(['stream' => true, 'read_timeout' => 1]);
            $response = $client->get($url, [
                'events' => [
                    'before' => [
                        'fn'       => function () {},
                        'priority' => 100,
                        'once'     => true
                    ]
                ]
            ]);

            $arr_data = explode("\n",$response->getBody()->getContents());
            foreach ($arr_data as $k => $rec) {
                if (empty(trim($rec))) unset($arr_data[$k]);
            }
            if (count($arr_data)<1) return ['error' => 'data not found'];

            $arr_data = json_decode(str_replace('data: ', '', end($arr_data)),true)['prices'];
            $data = json_decode($arr_data,true);

            $ret = [];
            foreach ($data as $k => $rec) {
                $symbol = strtolower($k);
                $prices = null;
                $prices[]=floatval($rec['user_buy_price']);
                $prices[]=floatval($rec['sell_price']);

                $ret[$symbol]['buy']=min($prices);
                $ret[$symbol]['sell']=max($prices);
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Sell = Buy
     *
     * @param $sign
     * @return array|void
     */
    function adabex($sign=null) {
        try {
            if (substr_count($sign,',')>0 or empty($sign)) {
                $search = '';
            } else {
                $search = strtoupper("{$sign}IRT");
            }

            $url = "https://adabex.com/api/HomeApi/GetSymbolPrice?Symbol=$search";

            $client = new \GuzzleHttp\Client([
                'headers' => ['Content-Type' => 'application/json']
            ]);
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data as $rec) {
                if ($rec['QuoteAsset']=='IRT') {
                    $price = floatval($rec['price']);
                    $ret[strtolower(str_replace('IRT','',$rec['symbol']))]['buy']=$price;
                    $ret[strtolower(str_replace('IRT','',$rec['symbol']))]['sell']=$price;
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower(str_replace('IRT','',$rec['symbol']))]['buy'];
                    $new_ret['sell']=$ret[strtolower(str_replace('IRT','',$rec['symbol']))]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Sell = Buy
     *
     * @param $sign
     * @return array|void
     */
    function arzif($sign=null) {
        try {
            $dom = new Dom;
            $dom->loadFromUrl("https://arzif.com/coins?market=IRT");
            $rows = $dom->find('table.table tbody')[0]->find('tr');
            $ret = [];
            foreach ($rows as $row) {
                $symbol = strtolower($row->find('.coin_icon img')[0]->getAttribute('alt'));
                preg_match('/\w+/', $symbol, $output_array);

                if (count($output_array)) {
                    $price = floatval(str_replace([',',' '],'',$row->find('td')[1]->text));

                    $ret[$output_array[0]]['sell']=$price;
                    $ret[$output_array[0]]['buy']=$price;
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $price = floatval($ret[strtolower($sign)]['sell']);
                    $new_ret['buy']=$price;
                    $new_ret['sell']=$price;
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Sell = Buy
     *
     * @param $sign
     * @return array|void
     */
    function pay98($sign=null) {
        try {
            $dom = new Dom;
            $dom->loadFromUrl("https://pay98.app/%D9%82%DB%8C%D9%85%D8%AA-%D8%A7%D8%B1%D8%B2%D9%87%D8%A7%DB%8C-%D8%AF%DB%8C%D8%AC%DB%8C%D8%AA%D8%A7%D9%84");
            $rows = $dom->find('#tableprices tbody')[0]->find('tr');
            $ret = [];
            foreach ($rows as $row) {
                $symbol = strtolower($row->getAttribute('id'));
                $symbol = strtolower(explode('-',$symbol)[0]);
                preg_match('/\w+/', $symbol, $output_array);

                if (count($output_array)) {
                    $price = floatval(str_replace([',',' '],'',$row->find('td.price_irt')[0]->text));

                    $ret[$output_array[0]]['buy']=$price;
                    $ret[$output_array[0]]['sell']=$price;
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Sell = Buy
     *
     * @param $sign
     * @return array|void
     */
    function ariomex($sign=null) {
        try {
            $url = "https://ariomex.com/home_page/get_home_page_data";

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data['message']['market_data']['irt'] as $rec) {
                $price = floatval($rec['last_price']);
                $ret[strtolower($rec['coin'])]['buy']=$price;
                $ret[strtolower($rec['coin'])]['sell']=$price;
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * @param $sign
     * @return array|void
     */
    function arkaex($sign=null) {
        try {
            $url = "https://arkaex.com/index.php?option=com_currencies&format=json&task=configs";
            $config = new \GuzzleHttp\Client();
            $config_response = $config->request('GET', $url);
            $config_data = json_decode($config_response->getBody(),true);

            $usd = 0;
            foreach ($config_data as $config_rec) {
                if ($config_rec['key']=='usd_price') {
                    $usd = floatval($config_rec['value']);
                    break;
                }
            }

            $url = "https://arkaex.com/index.php?option=com_currencies&format=json&task=currencyList";
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url);
            $status = $response->getStatusCode();

            $data = [];
            if ($status=='200') {
                $data = json_decode($response->getBody(),true);
            } else {
                $data = ['error' => 'data is not valid'];
            }

            $ret = [];
            foreach ($data as $rec) {
                $symbol = strtolower($this->getCoins(strtoupper($rec['key']),true));
                if ($rec['resourceprice']=='1') {
                    $price = floatval($rec['price']);
                    $ret[strtolower($symbol)]['sell'] = (floatval($price) + floatval($price) * (floatval($rec['purchase_fee'])) / 100);
                    $ret[strtolower($symbol)]['buy'] = (floatval($price) - floatval($price) * (floatval($rec['sales_fee'])) / 100);
                } elseif ($rec['resourceprice']=='2') {
                    $price = floatval($rec['pricebinance']);
                    $ret[strtolower($symbol)]['sell'] = (floatval($price) + floatval($price) * (floatval($rec['purchase_fee'])) / 100) * $usd;
                    $ret[strtolower($symbol)]['buy'] = (floatval($price) - floatval($price) * (floatval($rec['sales_fee'])) / 100) * $usd;
                } elseif ($rec['resourceprice']=='3') {
                    $price = floatval($rec['priceramzinex']);
                    $ret[strtolower($symbol)]['sell'] = (floatval($price) + floatval($price) * (floatval($rec['purchase_fee'])) / 100);
                    $ret[strtolower($symbol)]['buy'] = (floatval($price) - floatval($price) * (floatval($rec['sales_fee'])) / 100);
                }
            }

            if (!empty($sign)) {
                if (substr_count($sign,',')>0) {
                    $coins = explode(',',$sign);
                    $new_ret = [];
                    foreach ($coins as $coin) {
                        $new_ret[strtolower($coin)] = $ret[strtolower($coin)];
                    }

                    $ret = $new_ret;
                } else {
                    $new_ret = [];
                    $new_ret['buy']=$ret[strtolower($sign)]['buy'];
                    $new_ret['sell']=$ret[strtolower($sign)]['sell'];
                    $ret = $new_ret;
                }
            }

            return $ret;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }





    /**
     * @param $search
     * @param $by_name
     * @return string|string[]|void
     */
    function getCoins($search=null,$by_name=false) {
        $search = strtoupper($search);
        $ct = [
            'BCH'=>'Bitcoin Cash',
            'BTG'=>'Bitcoin Gold',
            'BCC'=>'BitConnect',
            'BTS'=>'BitShares',
            'BCN'=>'Bytecoin',
            'DASH'=>'Dash',
            'DOGE'=>'Dogecoin',
            'ETH'=>'Ethereum',
            'ETC'=>'Ethereum Classic',
            'ARDR'=>'Ardor',
            'ARK'=>'Ark',
            'BTC'=>'Bitcoin',
            'ADA'=>'Cardano',
            'CVC'=>'Civic',
            'DCR'=>'Decred',
            'EOS'=>'EOS',
            'FUN'=>'FunFair',
            'GNT'=>'Golem',
            'PIVX'=>'PIVX',
            'NANO'=>'Nano',
            'SAFEX'=>'Safex',
            'SALT'=>'SALT',
            'XLM'=>'Stellar',
            'XVG'=>'Verge',
            'VTC'=>'Vertcoin',
            'MIOTA'=>'IOTA',
            'LSK'=>'Lisk',
            'LTC'=>'Litecoin',
            'XMR'=>'Monero',
            'XEM'=>'NEM',
            'NEO'=>'NEO',
            'OMG'=>'OmiseGO',
            'POWR'=>'Power Ledger',
            'QTUM'=>'Qtum',
            'XRP'=>'Ripple',
            'SC'=>'Siacoin',
            'STEEM'=>'Steem',
            'STRAT'=>'Stratis',
            'USDT'=>'Tether',
            'WAVES'=>'Waves',
            'ZEC'=>'Zcash',
            '1337'=>'1337',
            'ZRX'=>'0x',
            '10MT'=>'10M Token',
            '2GIVE'=>'2GIVE',
            '300'=>'300 Token',
            '42'=>'42-coin',
            '808'=>'808Coin',
            '8BIT'=>'8Bit',
            '9COIN'=>'9COIN',
            'ABJ'=>'Abjcoin',
            'ABN'=>'Abncoin',
            'ACC'=>'Accelerator Network',
            'ACE'=>'Ace',
            'ACES'=>'Aces',
            'ACT'=>'Achain',
            'ACOIN'=>'Acoin',
            'ACC'=>'AdCoin',
            'ADL'=>'Adelphoi',
            'ADX'=>'AdEx',
            'ADST'=>'AdShares',
            'ADT'=>'adToken',
            'AIB'=>'Advanced Internet Blocks',
            'ADZ'=>'Adzcoin',
            'ELF'=>'aelf',
            'AEON'=>'Aeon',
            'AERM'=>'Aerium',
            'ARN'=>'Aeron',
            'AE'=>'Aeternity',
            'AGRS'=>'Agoras Tokens',
            'DLT'=>'Agrello',
            'AGLC'=>'AgrolifeCoin',
            'ADK'=>'Aidos Kuneen',
            'AION'=>'Aion',
            'AST'=>'AirSwap',
            'AIR'=>'AirToken',
            'AKY'=>'Akuya Coin',
            'ALIS'=>'ALIS',
            'ALL'=>'Allion',
            'ASAFE2'=>'AllSafe',
            'APC'=>'AlpaCoin',
            'ABC'=>'Alphabit',
            'ALQO'=>'ALQO',
            'ALT'=>'Altcoin',
            'ALTCOM'=>'AltCommunity',
            'AMBER'=>'AmberCoin',
            'AMB'=>'Ambrosus',
            'AMMO'=>'Ammo Rewards',
            'AMS'=>'AmsterdamCoin',
            'ACP'=>'AnarchistsPrime',
            'ANI'=>'Animecoin',
            'ANC'=>'Anoncoin',
            'RYZ'=>'ANRYZE',
            'ANTI'=>'AntiBitcoin',
            'ALTC'=>'Antilitecoin',
            'ANTX'=>'Antimatter',
            'APW'=>'AppleCoin',
            'APX'=>'APX',
            'ARCO'=>'AquariusCoin',
            'ANT'=>'Aragon',
            'ARB'=>'ARbit',
            'ARC'=>'Arcade Token',
            'ARC'=>'ArcticCoin',
            'ARG'=>'Argentum',
            'ARGUS'=>'Argus',
            'ARI'=>'Aricoin',
            'ABY'=>'ArtByte',
            'ATX'=>'Artex Coin',
            'XAS'=>'Asch',
            'ASN'=>'Aseancoin',
            'AC'=>'AsiaCoin',
            'ADCN'=>'Asiadigicoin',
            'ASTRO'=>'Astro',
            'ATB'=>'ATBCoin',
            'ATL'=>'ATLANT',
            'ATM'=>'ATMChain',
            'ATMC'=>'ATMCoin',
            'ATMS'=>'Atmos',
            'ATOM'=>'Atomic Coin',
            'ADC'=>'AudioCoin',
            'REP'=>'Augur',
            'AUR'=>'Auroracoin',
            'AU'=>'AurumCoin',
            'ATS'=>'Authorship',
            'NIO'=>'Autonio',
            'AV'=>'AvatarCoin',
            'AVT'=>'Aventus',
            'ACN'=>'Avoncoin',
            'AXIOM'=>'Axiom',
            'B2B'=>'B2B',
            'B3'=>'B3Coin',
            'BNT'=>'Bancor',
            'B@'=>'Bankcoin',
            'BAT'=>'Basic Attention',
            'BSN'=>'Bastonet',
            'BTA'=>'Bata',
            'BAT'=>'BatCoin',
            'BCAP'=>'BCAP',
            'XBTS'=>'Beatcoin',
            'BVC'=>'BeaverCoin',
            'BELA'=>'Bela',
            'BENJI'=>'BenjiRolls',
            'BERN'=>'BERNcash',
            'BEST'=>'BestChain',
            'BET'=>'BetaCoin',
            'BBP'=>'BiblePay',
            'BIX'=>'Bibox Token',
            'BIGUP'=>'BigUp',
            'BLRY'=>'BillaryCoin',
            'XBL'=>'Billionaire Token',
            'BNB'=>'Binance Coin',
            'BIOB'=>'BioBar',
            'BIOS'=>'BiosCrypto',
            'BIP'=>'BipCoin',
            'BIRDS'=>'Birds',
            'BIS'=>'Bismuth',
            'BTWTY'=>'Bit20',
            'BTCA'=>'Bitair',
            'BAC'=>'BitAlphaCoin',
            'BAS'=>'BitAsean',
            'BTB'=>'BitBar',
            'BTBc'=>'Bitbase',
            'BAY'=>'BitBay',
            'BITB'=>'BitBean',
            'BBT'=>'BitBoost',
            'BITBTC'=>'bitBTC',
            'BXC'=>'Bitcedi',
            'BTDX'=>'Bitcloud',
            'BITCNY'=>'bitCNY',
            'COAL'=>'BitCoal',
            'XBTC21'=>'Bitcoin 21',
            'BCD'=>'Bitcoin Diamond',
            'BCF'=>'Bitcoin Fast',
            'BTPL'=>'Bitcoin Planet',
            'XBC'=>'Bitcoin Plus',
            'BTCRED'=>'Bitcoin Red',
            'BTCS'=>'Bitcoin Scrypt',
            'BTCS'=>'Bitcoin Silver',
            'BTU'=>'Bitcoin Unlimited Protocol',
            'BTC2X'=>'Bitcoin2x',
            'BTCD'=>'BitcoinDark',
            'BCX'=>'BitcoinX [Futures]',
            'BTCZ'=>'BitcoinZ',
            'BTX'=>'Bitcore',
            'BCY'=>'Bitcrystals',
            'BTCR'=>'Bitcurrency',
            'BDL'=>'Bitdeal',
            'CSNO'=>'BitDice',
            'BITEUR'=>'bitEUR',
            'FID'=>'BITFID',
            'BTG'=>'Bitgem',
            'BITGOLD'=>'bitGold',
            'STU'=>'bitJob',
            'BTM'=>'Bitmark',
            'BITOK'=>'Bitok',
            'BPC'=>'Bitpark Coin',
            'BTQ'=>'BitQuark',
            'BQ'=>'bitqy',
            'BRO'=>'Bitradio',
            'BSD'=>'BitSend',
            'BTE'=>'BitSerial',
            'BITSILVER'=>'bitSilver',
            'BSR'=>'BitSoar',
            'BITS'=>'Bitstar',
            'SWIFT'=>'Bitswift',
            'BXT'=>'BitTokens',
            'BITUSD'=>'bitUSD',
            'VOLT'=>'Bitvolt',
            'BITZ'=>'Bitz',
            'ZNY'=>'Bitzeny',
            'BLK'=>'BlackCoin',
            'BMC'=>'Blackmoon Crypto',
            'BSTAR'=>'Blackstar',
            'BLC'=>'Blakecoin',
            'BLAS'=>'BlakeStar',
            'BLZ'=>'BlazeCoin',
            'BLAZR'=>'BlazerCoin',
            'BLITZ'=>'Blitzcash',
            'CAT'=>'BlockCAT',
            'BCDN'=>'BlockCDN',
            'BLX'=>'Blockchain Index',
            'BCPT'=>'Blockmason Credit Protocol',
            'BLOCK'=>'Blocknet',
            'BLOCKPAY'=>'BlockPay',
            'BPL'=>'Blockpool',
            'TIX'=>'Blocktix',
            'VEE'=>'BLOCKv',
            'BLUE'=>'BLUE',
            'BLU'=>'BlueCoin',
            'BNX'=>'BnrtxCoin',
            'BOAT'=>'BOAT',
            'BOT'=>'Bodhi',
            'BLN'=>'Bolenum',
            'BOLI'=>'Bolivarcoin',
            'BGR'=>'Bongger',
            'BON'=>'Bonpay',
            'BBR'=>'Boolberry',
            'BOST'=>'BoostCoin',
            'BOS'=>'BOScoin',
            'BNTY'=>'Bounty0x',
            'AHT'=>'Bowhead',
            'BSC'=>'BowsCoin',
            'BRAIN'=>'Braincoin',
            'BRD'=>'Bread',
            'BRK'=>'Breakout',
            'BRX'=>'Breakout Stake',
            'BRIA'=>'BriaCoin',
            'BCO'=>'BridgeCoin',
            'BRIT'=>'BritCoin',
            'BRAT'=>'BROTHER',
            'BT1'=>'BT1 [CST]',
            'BT2'=>'BT2 [CST]',
            'BTCM'=>'BTCMoon',
            'TALK'=>'BTCtalkcoin',
            'BTSR'=>'BTSR',
            'BUB'=>'Bubble',
            'BWK'=>'Bulwark',
            'BUMBA'=>'BumbaCoin',
            'BUN'=>'BunnyCoin',
            'BURST'=>'Burst',
            'OCEAN'=>'BurstOcean',
            'BUZZ'=>'BuzzCoin',
            'GBYTE'=>'Byteball Bytes',
            'BYC'=>'Bytecent',
            'BTM'=>'Bytom',
            'CAB'=>'Cabbage',
            'CACH'=>'CacheCoin',
            'CF'=>'Californium',
            'CALC'=>'CaliphCoin',
            'CMPCO'=>'CampusCoin',
            'CDN'=>'Canada eCoin',
            'CANN'=>'CannabisCoin',
            'CCN'=>'CannaCoin',
            'CNNC'=>'Cannation',
            'CAPP'=>'Cappasity',
            'CPC'=>'Capricoin',
            'CARBON'=>'Carboncoin',
            'CTX'=>'CarTaxi Token',
            'CASH'=>'Cash Poker Pro',
            'CASH'=>'Cashcoin',
            'CME'=>'Cashme',
            'CASINO'=>'Casino',
            'CSC'=>'CasinoCoin',
            'CAT'=>'Catcoin',
            'CBD'=>'CBD Crystals',
            'XCT'=>'C-Bit',
            'CCM100'=>'CCMiner',
            'CCO'=>'Ccore',
            'CTR'=>'Centra',
            'CNT'=>'Centurion',
            'CHC'=>'ChainCoin',
            'LINK'=>'ChainLink',
            '4CHN'=>'ChanCoin',
            'CAG'=>'Change',
            'CHEAP'=>'Cheapcoin',
            'CHESS'=>'ChessCoin',
            'CHIPS'=>'CHIPS',
            'TIME'=>'Chronobank',
            'DAY'=>'Chronologic',
            'CRX'=>'Chronos',
            'CND'=>'Cindicator',
            'COVAL'=>'Circuits of Value',
            'CLAM'=>'Clams',
            'POLL'=>'ClearPoll',
            'CLINT'=>'Clinton',
            'CLOAK'=>'CloakCoin',
            'CLUB'=>'ClubCoin',
            'COB'=>'Cobinhood',
            'COXST'=>'CoExistCoin',
            'CFI'=>'Cofound.it',
            'CTIC2'=>'Coimatic 2.0',
            'CTIC3'=>'Coimatic 3.0',
            'CNO'=>'Coin(O)',
            'C2'=>'Coin2.1',
            'CDT'=>'CoinDash',
            'CXT'=>'Coinonat',
            'XCXT'=>'CoinonatX',
            'CV2'=>'Colossuscoin V2',
            'COLX'=>'ColossusCoinXT',
            'CMT'=>'Comet',
            'CMP'=>'Compcoin',
            'CPN'=>'CompuCoin',
            'CMS'=>'COMSA [ETH]',
            'CMS'=>'COMSA [XEM]',
            'CONX'=>'Concoin',
            'RAIN'=>'Condensate',
            'CFD'=>'Confido',
            'XCPO'=>'Copico',
            'CRTM'=>'Corethum',
            'CORG'=>'CorgiCoin',
            'COR'=>'CORION',
            'COSS'=>'COSS',
            'XCP'=>'Counterparty',
            'COUPE'=>'Coupecoin',
            'CRAVE'=>'Crave',
            'CRM'=>'Cream',
            'XCRE'=>'Creatio',
            'CREA'=>'Creativecoin',
            'CRDNC'=>'Credence Coin',
            'CRB'=>'Creditbit',
            'CREDO'=>'Credo',
            'CREVA'=>'CrevaCoin',
            'CRC'=>'CrowdCoin',
            'CRW'=>'Crown',
            'CRT'=>'CRTCoin',
            'CRYPT'=>'CryptCoin',
            'CTO'=>'Crypto',
            'CBX'=>'Crypto Bullion',
            'CCRB'=>'CryptoCarbon',
            'CESC'=>'CryptoEscudo',
            'CFT'=>'CryptoForecast',
            'TKR'=>'CryptoInsight',
            'CJ'=>'Cryptojacks',
            'CNX'=>'Cryptonex',
            'XCN'=>'Cryptonite',
            'CPAY'=>'Cryptopay',
            'PING'=>'CryptoPing',
            'CWXT'=>'CryptoWorldX Token',
            'CCT'=>'Crystal Clear',
            'OFF'=>'Cthulhu Offerings',
            'QBT'=>'Cubits',
            'CURE'=>'Curecoin',
            'CVCOIN'=>'CVCoin',
            'XCS'=>'CybCSec',
            'CC'=>'CyberCoin',
            'CMT'=>'CyberMiles',
            'CYC'=>'Cycling Coin',
            'CYDER'=>'Cyder',
            'CYP'=>'Cypher',
            'DAI'=>'Dai',
            'DALC'=>'Dalecoin',
            'BET'=>'DAO.Casino',
            'DLISK'=>'DAPPSTER',
            'DAR'=>'Darcrus',
            'DISK'=>'DarkLisk',
            'KED'=>'Darsek',
            'DSH'=>'Dashcoin',
            'DASHS'=>'Dashs',
            'DTB'=>'Databits',
            'DAT'=>'Datum',
            'DAV'=>'DavorCoin',
            'DAXX'=>'DaxxCoin',
            'DRP'=>'DCORP',
            'DBTC'=>'Debitcoin',
            'DCT'=>'DECENT',
            'DBET'=>'DecentBet',
            'MANA'=>'Decentraland',
            'HST'=>'Decision Token',
            'DBC'=>'DeepBrain Chain',
            'ONION'=>'DeepOnion',
            'DPY'=>'Delphy',
            'DCRE'=>'DeltaCredits',
            'DNR'=>'Denarius',
            'DENT'=>'Dent',
            'DCN'=>'Dentacoin',
            'DSR'=>'Desire',
            'DES'=>'Destiny',
            'DEUS'=>'DeusCoin',
            'DEM'=>'Deutsche eMark',
            'DEW'=>'DEW',
            'DFS'=>'DFSCoin',
            'DMD'=>'Diamond',
            'DIBC'=>'DIBCOIN',
            'DGB'=>'DigiByte',
            'CUBE'=>'DigiCube',
            'DGPT'=>'DigiPulse',
            'DBG'=>'Digital Bullion',
            'DGCS'=>'Digital Credits',
            'DMB'=>'Digital Money Bullion',
            'DRS'=>'Digital Rupees',
            'DGC'=>'Digitalcoin',
            'DDF'=>'Digital Development Foundation',
            'XDN'=>'DigitalNote',
            'DP'=>'DigitalPrice',
            'DGD'=>'DigixDAO',
            'DIM'=>'DIMCOIN',
            'DIME'=>'Dimecoin',
            'FUDD'=>'DimonCoin',
            'DCY'=>'Dinastycoin',
            'DNT'=>'district0x',
            'DIVX'=>'Divi',
            'DIX'=>'Dix Asset',
            'NOTE'=>'DNotes',
            'DOLLAR'=>'Dollar Online',
            'DLC'=>'Dollarcoin',
            'DRT'=>'DomRaider',
            'DON'=>'Donationcoin',
            'DOPE'=>'DopeCoin',
            'DOT'=>'Dotcoin',
            'DOVU'=>'Dovu',
            'DPAY'=>'DPAY',
            'DFT'=>'DraftCoin',
            'DRGN'=>'Dragonchain',
            'DRM'=>'Dreamcoin',
            'DRXNE'=>'DROXNE',
            'DBIX'=>'DubaiCoin',
            'DUB'=>'Dubstep',
            'DUTCH'=>'Dutch Coin',
            'DYN'=>'Dynamic',
            'DTR'=>'Dynamic Trading',
            'DMC'=>'DynamicCoin',
            'E4ROW'=>'E4ROW',
            'EAG'=>'EA Coin',
            'EAGLE'=>'EagleCoin',
            'EBIT'=>'eBIT',
            'EBTC'=>'eBitcoin',
            'EBCH'=>'eBitcoinCash',
            'EBT'=>'Ebittree Coin',
            'EBST'=>'eBoost',
            'ECC'=>'ECC',
            'ECOB'=>'Ecobit',
            'ECO'=>'EcoCoin',
            'ECN'=>'E-coin',
            'EDG'=>'Edgeless',
            'EDR'=>'E-Dinar Coin',
            'EDRC'=>'EDRCoin',
            'EGG'=>'EggCoin',
            'EGO'=>'EGO',
            'EGOLD'=>'eGold',
            'EFL'=>'e-Gulden',
            'EDO'=>'Eidoo',
            'EMC2'=>'Einsteinium',
            'ELC'=>'Elacoin',
            'XEL'=>'Elastic',
            'EL'=>'Elcoin',
            'ECA'=>'Electra',
            'ETN'=>'Electroneum',
            'ELE'=>'Elementrem',
            'ELIX'=>'Elixir',
            'ELLA'=>'Ellaism',
            'ELTC2'=>'eLTC',
            'ELTCOIN'=>'ELTCOIN',
            'ELS'=>'Elysium',
            'EMB'=>'EmberCoin',
            'MBRS'=>'Embers',
            'EMD'=>'Emerald Crypto',
            'EMC'=>'Emercoin',
            'EPY'=>'Emphy',
            'DNA'=>'EncrypGen',
            'ETT'=>'EncryptoTel [WAVES]',
            'TSL'=>'Energo',
            'ENRG'=>'Energycoin',
            'ENG'=>'Enigma',
            'XNG'=>'Enigma',
            'ENJ'=>'Enjin Coin',
            'EOT'=>'EOT Token',
            'EQT'=>'EquiTrader',
            'ERC20'=>'ERC20',
            'EREAL'=>'eREAL',
            'EFYT'=>'Ergo',
            'ERO'=>'Eroscoin',
            'ERY'=>'Eryllium',
            'ESP'=>'Espers',
            'ENT'=>'Eternity',
            'EBET'=>'EthBet',
            'ETBS'=>'Ethbits',
            'ECASH'=>'Ethereum Cash',
            'ETHD'=>'Ethereum Dark',
            'ETG'=>'Ethereum Gold',
            'ELITE'=>'Ethereum Lite',
            'EMV'=>'Ethereum Movie Venture',
            'RIYA'=>'Etheriya',
            'DICE'=>'Etheroll',
            'FUEL'=>'Etherparty',
            'EGAS'=>'ETHGAS',
            'LEND'=>'ETHLend',
            'ETHOS'=>'Ethos',
            'EUC'=>'Eurocoin',
            'ERC'=>'EuropeCoin',
            'EUSD'=>'eUSD',
            'EVC'=>'EventChain',
            'EVX'=>'Everex',
            'EGC'=>'EverGreenCoin',
            'EVR'=>'Everus',
            'EVIL'=>'Evil Coin',
            'EVO'=>'Evotion',
            'XUC'=>'Exchange Union',
            'EXN'=>'ExchangeN',
            'EXCL'=>'ExclusiveCoin',
            'EXP'=>'Expanse',
            'XP'=>'Experience Points',
            'EXRN'=>'EXRNchain',
            'FBL'=>'Faceblock',
            'FC'=>'Facecoin',
            'FCT'=>'Factom',
            'FAIR'=>'FairCoin',
            'FCN'=>'Fantomcoin',
            'FAP'=>'FAPcoin',
            'FRD'=>'Farad',
            'FRGC'=>'Fargocoin',
            'FRCT'=>'Farstcoin',
            'FST'=>'Fastcoin',
            'FAZZ'=>'Fazzcoin',
            'FTC'=>'Feathercoin',
            'TIPS'=>'FedoraCoin',
            'FIL'=>'Filecoin [Futures]',
            'FIMK'=>'FIMKrypto',
            'FNC'=>'FinCoin',
            'FIRE'=>'Firecoin',
            'FFC'=>'FireFlyCoin',
            'BIT'=>'First Bitcoin',
            'BITCF'=>'First Bitcoin Capital Corp',
            '1ST'=>'FirstBlood',
            'FRST'=>'FirstCoin',
            'FLAP'=>'FlappyCoin',
            'FLASH'=>'Flash',
            'FLVR'=>'FlavorCoin',
            'FLAX'=>'Flaxscript',
            'FLIK'=>'FLiK',
            'FLIXX'=>'Flixxo',
            'FLO'=>'FlorinCoin',
            'FLT'=>'FlutterCoin',
            'FLY'=>'Flycoin',
            'FYP'=>'FlypMe',
            'FLDC'=>'FoldingCoin',
            'FONZ'=>'Fonziecoin',
            'XFT'=>'Footy Cash',
            'FOR'=>'FORCE',
            'FRN'=>'Francs',
            'FRK'=>'Franko',
            'FRWC'=>'FrankyWillCoin',
            'FRAZ'=>'Frazcoin',
            'FRC'=>'Freicoin',
            'FUCK'=>'FuckToken',
            'FC2'=>'FuelCoin',
            'FJC'=>'FujiCoin',
            'NTO'=>'Fujinto',
            'FUNC'=>'FUNCoin',
            'FYN'=>'FundYourselfNow',
            'FUTC'=>'FutCoin',
            'FXE'=>'FuturXe',
            'FUZZ'=>'FuzzBalls',
            'G3N'=>'G3N',
            'GAIA'=>'GAIA',
            'GAM'=>'Gambit',
            'GBT'=>'GameBet Coin',
            'GAME'=>'GameCredits',
            'GML'=>'GameLeagueCoin',
            'UNITS'=>'GameUnits',
            'MRJA'=>'GanjaCoin',
            'GAP'=>'Gapcoin',
            'GAS'=>'Gas',
            'GAY'=>'GAY Money',
            'GBC'=>'GBCGoldCoin',
            'GCN'=>'GCoin',
            'GEERT'=>'GeertCoin',
            'GNX'=>'Genaro Network',
            'GVT'=>'Genesis Vision',
            'GEO'=>'GeoCoin',
            'GSR'=>'GeyserCoin',
            'GTO'=>'Gifto',
            'WTT'=>'Giga Watt Token',
            'GIM'=>'Gimli',
            'GLS'=>'GlassCoin',
            'GBRC'=>'Global Business Revolution',
            'GCR'=>'Global Currency Reserve',
            'GTC'=>'Global Tour Coin',
            'BSTY'=>'GlobalBoost-Y',
            'GLC'=>'GlobalCoin',
            'GLT'=>'GlobalToken',
            'GNO'=>'Gnosis',
            'GBX'=>'GoByte',
            'GPL'=>'Gold Pressed Latinum',
            'GRX'=>'GOLD Reward Token',
            'GB'=>'GoldBlocks',
            'GLD'=>'GoldCoin',
            'GMX'=>'GoldMaxCoin',
            'GP'=>'GoldPieces',
            'XGR'=>'GoldReserve',
            'GUC'=>'GoldUnionCoin',
            'GOLF'=>'Golfcoin',
            'GOLOS'=>'Golos',
            'GBG'=>'Golos Gold',
            'GOOD'=>'Goodomy',
            'GPU'=>'GPU Coin',
            'GRN'=>'Granite',
            'GRT'=>'Grantcoin',
            'GRE'=>'Greencoin',
            'GRID'=>'Grid+',
            'GRC'=>'GridCoin',
            'GRIM'=>'Grimcoin',
            'GRS'=>'Groestlcoin',
            'GRWI'=>'Growers International',
            'GCC'=>'GuccioneCoin',
            'NLG'=>'Gulden',
            'GUN'=>'Guncoin',
            'GXS'=>'GXShares',
            'HAL'=>'Halcyon',
            'HALLO'=>'Halloween Coin',
            'HCC'=>'Happy Creator Coin',
            'HPC'=>'Happycoin',
            'HMC'=>'HarmonyCoin',
            'HAT'=>'Hawala.Today',
            'WORM'=>'HealthyWormCoin',
            'HEAT'=>'HEAT',
            'HDG'=>'Hedge',
            'HNC'=>'Helleniccoin',
            'HGT'=>'HelloGold',
            'THC'=>'HempCoin',
            'HMP'=>'HempCoin',
            'HXX'=>'Hexx',
            'XHI'=>'HiCoin',
            'HIGH'=>'High Gain',
            'HVCO'=>'High Voltage',
            'HTC'=>'HitCoin',
            'HVN'=>'Hive',
            'HBN'=>'HoboNickels',
            'HDLB'=>'HODL Bucks',
            'HODL'=>'HOdlcoin',
            'HWC'=>'HollyWoodCoin',
            'HBC'=>'HomeBlockCoin',
            'HONEY'=>'Honey',
            'HSR'=>'Hshare',
            'HTML5'=>'HTML5COIN',
            'HTML'=>'HTMLCOIN',
            'HBT'=>'Hubii Network',
            'HMQ'=>'Humaniq',
            'HNC'=>'Huncoin',
            'HUC'=>'HunterCoin',
            'HUSH'=>'Hush',
            'HYPER'=>'Hyper',
            'HYTV'=>'Hyper TV',
            'HYP'=>'HyperStake',
            'IOC'=>'I/O Coin',
            'I0C'=>'I0Coin',
            'IBANK'=>'iBank',
            'IBTC'=>'iBTC',
            'ICOO'=>'ICO OpenLedger',
            'ICOB'=>'ICOBID',
            'ICN'=>'iCoin',
            'ICX'=>'ICON',
            'ICX'=>'ICON [Futures]',
            'ICON'=>'Iconic',
            'ICN'=>'Iconomi',
            'ICOS'=>'ICOS',
            'ICE'=>'iDice',
            'IETH'=>'iEthereum',
            'RLC'=>'iExec RLC',
            'IGNIS'=>'Ignis [Futures]',
            'IMX'=>'Impact',
            'IMPS'=>'ImpulseCoin',
            'NKA'=>'IncaKoin',
            'INCNT'=>'Incent',
            'IMS'=>'Independent Money System',
            'INDIA'=>'India Coin',
            'IND'=>'Indorse Token',
            'INF'=>'InfChain',
            'IFC'=>'Infinitecoin',
            'XIN'=>'Infinity Economics',
            'IPY'=>'Infinity Pay',
            'IFLT'=>'InflationCoin',
            'INFX'=>'Influxcoin',
            'INK'=>'Ink',
            'INN'=>'Innova',
            'INPAY'=>'InPay',
            'INSN'=>'InsaneCoin',
            'ITT'=>'Intelligent Trading Foundation',
            'ITNS'=>'IntenseCoin',
            'XID'=>'International Diamond',
            'IOP'=>'Internet of People',
            'XOT'=>'Internet of Things',
            'INXT'=>'Internxt',
            'HOLD'=>'Interstellar Holdings',
            'ITZ'=>'Interzone',
            'IFT'=>'InvestFeed',
            'IVZ'=>'InvisibleCoin',
            'ION'=>'ION',
            'ITC'=>'IoT Chain',
            'IQT'=>'iQuant',
            'IRL'=>'IrishCoin',
            'ISL'=>'IslaCoin',
            'ITI'=>'iTicoin',
            'IXC'=>'Ixcoin',
            'IXT'=>'iXledger',
            'JNS'=>'Janus',
            'JS'=>'JavaScript Token',
            'JET'=>'Jetcoin',
            'JWL'=>'Jewels',
            'JIN'=>'Jin Coin',
            'JINN'=>'Jinn',
            'JOBS'=>'JobsCoin',
            'J'=>'Joincoin',
            'XJO'=>'Joulecoin',
            'KRB'=>'Karbo',
            'KARMA'=>'Karmacoin',
            'KASHH'=>'KashhCoin',
            'KAYI'=>'Kayicoin',
            'KEK'=>'KekCoin',
            'KICK'=>'KickCoin',
            'KLC'=>'KiloCoin',
            'KIN'=>'Kin',
            'KNC'=>'KingN Coin',
            'MEOW'=>'Kittehcoin',
            'KOBO'=>'Kobocoin',
            'KLN'=>'Kolion',
            'KMD'=>'Komodo',
            'KORE'=>'Kore',
            'KRONE'=>'Kronecoin',
            'KBR'=>'Kubera Coin',
            'KCS'=>'KuCoin Shares',
            'KURT'=>'Kurrent',
            'KUSH'=>'KushCoin',
            'KNC'=>'Kyber Network',
            'PIX'=>'Lampix',
            'LANA'=>'LanaCoin',
            'LDCN'=>'LandCoin',
            'LTH'=>'LAthaan',
            'LA'=>'LAToken',
            'LAZ'=>'Lazaruscoin',
            'LBC'=>'LBRY Credits',
            'LEA'=>'LeaCoin',
            'LGD'=>'Legends Room',
            'LEO'=>'LEOcoin',
            'LEPEN'=>'LePen',
            'LIR'=>'LetItRide',
            'XLC'=>'LeviarCoin',
            'LVPS'=>'LevoPlus',
            'LEX'=>'Lex4All',
            'LIFE'=>'LIFE',
            'LINDA'=>'Linda',
            'LNK'=>'Link Platform',
            'LKC'=>'LinkedCoin',
            'LINX'=>'Linx',
            'LTB'=>'LiteBar',
            'LBTC'=>'LiteBitcoin',
            'LTG'=>'LiteCoin Gold',
            'LCP'=>'Litecoin Plus',
            'LTCU'=>'LiteCoin Ultra',
            'LTCR'=>'Litecred',
            'LDOGE'=>'LiteDoge',
            'LLT'=>'LLToken',
            'LOC'=>'LockChain',
            'LMC'=>'LoMoCoin',
            'LRC'=>'Loopring',
            'LOT'=>'LottoCoin',
            'BASH'=>'LuckChain',
            'LUNA'=>'Luna Coin',
            'LUN'=>'Lunyr',
            'LUX'=>'LUXCoin',
            'LKK'=>'Lykke',
            'MAC'=>'Machinecoin',
            'MCR'=>'Macro',
            'MCRN'=>'MACRON',
            'ART'=>'Maecenas',
            'XMG'=>'Magi',
            'MAGE'=>'MagicCoin',
            'MAG'=>'Magnet',
            'MAGN'=>'Magnetcoin',
            'MGM'=>'Magnum',
            'MAID'=>'MaidSafeCoin',
            'MKR'=>'Maker',
            'MAO'=>'Mao Zedong',
            'MAR'=>'Marijuanacoin',
            'MARS'=>'Marscoin',
            'MXT'=>'MarteXcoin',
            'MARX'=>'MarxCoin',
            'MSCN'=>'Master Swiscoin',
            'MTNC'=>'Masternodecoin',
            'GUP'=>'Matchpool',
            'MAVRO'=>'Mavro',
            'MAX'=>'MaxCoin',
            'MZC'=>'MazaCoin',
            'MCAP'=>'MCAP',
            'MED'=>'Medibloc',
            'MDS'=>'MediShares',
            'MEC'=>'Megacoin',
            'MLN'=>'Melon',
            'MEME'=>'Memetic / PepeCoin',
            'MER'=>'Mercury',
            'GMT'=>'Mercury Protocol',
            'MGC'=>'MergeCoin',
            'MTL'=>'Metal',
            'MTLMC3'=>'Metal Music Coin',
            'METAL'=>'MetalCoin',
            'ETP'=>'Metaverse ETP',
            'AMM'=>'MicroMoney',
            'MILO'=>'MiloCoin',
            'MNC'=>'Mincoin',
            'MND'=>'MindCoin',
            'MNE'=>'Minereum',
            'MRT'=>'Miners\' Reward Token',
            'MNM'=>'Mineum',
            'MINEX'=>'Minex',
            'MNX'=>'MinexCoin',
            'MINT'=>'Mintcoin',
            'MMXVI'=>'MMXVI',
            'MBL'=>'MobileCash',
            'MGO'=>'MobileGo',
            'MOD'=>'Modum',
            'MDA'=>'Moeda Loyalty Points',
            'MOIN'=>'Moin',
            'MOJO'=>'MojoCoin',
            'MCO'=>'Monaco',
            'MONA'=>'MonaCoin',
            'MONETA'=>'Moneta',
            'MUE'=>'MonetaryUnit',
            'MTH'=>'Monetha',
            '$$$'=>'Money',
            'MONEY'=>'MoneyCoin',
            'MONK'=>'Monkey Project',
            'XMCC'=>'Monoeci',
            'MBI'=>'Monster Byte',
            'MOON'=>'Mooncoin',
            'MRNG'=>'MorningStar',
            'MSP'=>'Mothership',
            'MOTO'=>'Motocoin',
            'MSD'=>'MSD',
            'MTM'=>'MTMGaming',
            'MUSIC'=>'Musicoin',
            'MCI'=>'Musiconomi',
            'MST'=>'MustangCoin',
            'MUT'=>'Mutual Coin',
            'MYB'=>'MyBit Token',
            'XMY'=>'Myriad',
            'MYST'=>'Mysterium',
            'WISH'=>'MyWish',
            'NGC'=>'NAGA',
            'NMC'=>'Namecoin',
            'NAMO'=>'NamoCoin',
            'NTC'=>'Natcoin',
            'NAV'=>'NAV Coin',
            'NEBL'=>'Neblio',
            'NAS'=>'Nebulas',
            'NUKO'=>'Nekonium',
            'NEOG'=>'NEO GOLD',
            'NEOS'=>'NeosCoin',
            'NBIT'=>'netBit',
            'NET'=>'NetCoin',
            'NETKO'=>'Netko',
            'NTWK'=>'Network Token',
            'NEU'=>'Neumark',
            'NRO'=>'Neuro',
            'NTRN'=>'Neutron',
            'NEVA'=>'NevaCoin',
            'NDC'=>'NEVERDIE',
            'NEWB'=>'Newbium',
            'NYC'=>'NewYorkCoin',
            'NXC'=>'Nexium',
            'NXS'=>'Nexus',
            'NET'=>'Nimiq',
            'NOBL'=>'NobleCoin',
            'NODC'=>'NodeCoin',
            'NLC2'=>'NoLimitCoin',
            'NVC'=>'Novacoin',
            'USNBT'=>'NuBits',
            'NULS'=>'Nuls',
            'NMR'=>'Numeraire',
            'NSR'=>'NuShares',
            'NVST'=>'NVO',
            'NXT'=>'Nxt',
            'NYAN'=>'Nyancoin',
            'OAX'=>'OAX',
            'OBITS'=>'OBITS',
            'ODN'=>'Obsidian',
            'OCL'=>'Oceanlab',
            'OCOW'=>'OCOW',
            'OTX'=>'Octanox',
            '888'=>'OctoCoin',
            'OK'=>'OKCash',
            'OMC'=>'Omicron',
            'OMNI'=>'Omni',
            'ONG'=>'onG.social',
            'ONX'=>'Onix',
            'OPAL'=>'Opal',
            'OTN'=>'Open Trading Network',
            'OP'=>'Operand',
            'OPES'=>'Opescoin',
            'OPT'=>'Opus',
            'OCT'=>'OracleChain',
            'ORB'=>'Orbitcoin',
            'ORLY'=>'Orlycoin',
            'ORME'=>'Ormeus Coin',
            'OS76'=>'OsmiumCoin',
            'OX'=>'OX Fina',
            'OXY'=>'Oxycoin',
            'PRL'=>'Oyster Pearl',
            'P7C'=>'P7Coin',
            'PCS'=>'Pabyosi Coin (Special)',
            'PAC'=>'Paccoin',
            'PAK'=>'Pakcoin',
            'PND'=>'Pandacoin',
            'PRG'=>'Paragon',
            'DUO'=>'ParallelCoin',
            'PKB'=>'ParkByte',
            'PART'=>'Particl',
            'PASC'=>'Pascal Coin',
            'PASL'=>'Pascal Lite',
            'PTOY'=>'Patientory',
            'XPY'=>'PayCoin',
            'CON'=>'PayCon',
            'PFR'=>'Payfair',
            'PAYP'=>'PayPeer',
            'PAYX'=>'Paypex',
            'PPP'=>'PayPie',
            'PEC'=>'Peacecoin',
            'PCN'=>'PeepCoin',
            'PPC'=>'Peercoin',
            'PPY'=>'Peerplays',
            'MEN'=>'PeopleCoin',
            'PEPECASH'=>'Pepe Cash',
            'PTC'=>'Pesetacoin',
            'XPD'=>'PetroDollar',
            'PNX'=>'Phantomx',
            'PHS'=>'Philosopher Stones',
            'PXC'=>'Phoenixcoin',
            'PHR'=>'Phore',
            'PHO'=>'Photon',
            'PIE'=>'PIECoin',
            'PIGGY'=>'Piggycoin',
            'PLR'=>'Pillar',
            'PINK'=>'PinkCoin',
            'PDG'=>'PinkDog',
            'PCOIN'=>'Pioneer Coin',
            'PIPL'=>'PiplCoin',
            'SKULL'=>'Pirate Blocks',
            'PIRL'=>'Pirl',
            'PIZZA'=>'PizzaCoin',
            'XPTX'=>'PlatinumBAR',
            'PLACO'=>'PlayerCoin',
            'PKT'=>'Playkey',
            'PLX'=>'PlexCoin',
            'PLNC'=>'PLNcoin',
            'PLC'=>'PlusCoin',
            'PLU'=>'Pluton',
            'POE'=>'Po.et',
            'POKE'=>'PokeCoin',
            'AI'=>'POLY AI',
            'PLBT'=>'Polybius',
            'PONZI'=>'PonziCoin',
            'POP'=>'PopularCoin',
            'PPT'=>'Populous',
            'PEX'=>'PosEx',
            'POST'=>'PostCoin',
            'POS'=>'PoSToken',
            'POSW'=>'PoSW Coin',
            'POT'=>'PotCoin',
            'PRC'=>'PRCoin',
            'PRE'=>'Presearch',
            'GARY'=>'President Johnson',
            'PRES'=>'President Trump',
            'PBT'=>'Primalbase Token',
            'PST'=>'Primas',
            'XPM'=>'Primecoin',
            'PXI'=>'Prime-XI',
            'PRIMU'=>'Primulon',
            'PRX'=>'Printerium',
            'PRM'=>'PrismChain',
            'PRIX'=>'Privatix',
            'PZM'=>'PRIZM',
            'PRO'=>'ProChain',
            'PROC'=>'ProCurrency',
            'PDC'=>'Project Decorum',
            'NANOX'=>'Project-X',
            'PRO'=>'Propy',
            'PGL'=>'Prospectors Gold',
            'PRN'=>'Protean',
            'PR'=>'Prototanium',
            'PSY'=>'Psilocybin',
            'PBL'=>'Publica',
            'PULSE'=>'Pulse',
            'PURA'=>'Pura',
            'PURE'=>'Pure',
            'VIDZ'=>'PureVidz',
            'PUT'=>'PutinCoin',
            'PX'=>'PX',
            'QASH'=>'QASH',
            'QBT'=>'Qbao',
            'QC'=>'QCash',
            'QBK'=>'Qibuck Asset',
            'QLC'=>'QLINK',
            'QORA'=>'Qora',
            'QSP'=>'Quantstamp',
            'QAU'=>'Quantum',
            'QRL'=>'Quantum Resistant Ledger',
            'QRK'=>'Quark',
            'QTL'=>'Quatloo',
            'QCN'=>'QuazarCoin',
            'Q2C'=>'QubitCoin',
            'QBC'=>'Quebecoin',
            'XQN'=>'Quotient',
            'QVT'=>'Qvolta',
            'QWARK'=>'Qwark',
            'RBBT'=>'RabbitCoin',
            'RADS'=>'Radium',
            'RDN'=>'Raiden Network',
            'ROC'=>'Rasputin Online Coin',
            'XRA'=>'Ratecoin',
            'XRC'=>'Rawcoin',
            'RHOC'=>'RChain',
            'RCN'=>'Rcoin',
            'REAL'=>'REAL',
            'RPX'=>'Red Pulse',
            'RED'=>'RedCoin',
            'RDD'=>'ReddCoin',
            'REE'=>'ReeCoin',
            'REGA'=>'Regacoin',
            'REC'=>'Regalcoin',
            'RMC'=>'Remicoin',
            'RNS'=>'Renos',
            'REQ'=>'Request Network',
            'R'=>'Revain',
            'XRE'=>'RevolverCoin',
            'REX'=>'REX',
            'RHFC'=>'RHFCoin',
            'XRL'=>'Rialto',
            'RICHX'=>'RichCoin',
            'RIDE'=>'Ride My Car',
            'RIC'=>'Riecoin',
            'RBT'=>'Rimbit',
            'RCN'=>'Ripio Credit Network',
            'RBX'=>'Ripto Bux',
            'RISE'=>'Rise',
            'RVT'=>'Rivetz',
            'RPC'=>'RonPaulCoin',
            'ROOFS'=>'Roofs',
            'RLT'=>'RouletteToken',
            'RKC'=>'Royal Kingdom Coin',
            'ROYAL'=>'RoyalCoin',
            'XRY'=>'Royalties',
            'RSGP'=>'RSGPcoin',
            'RBIES'=>'Rubies',
            'RUBIT'=>'RubleBit',
            'RBY'=>'Rubycoin',
            'RUNNERS'=>'Runners',
            'RUPX'=>'Rupaya',
            'RUPX'=>'Rupaya [OLD]',
            'RUP'=>'Rupee',
            'RC'=>'RussiaCoin',
            'RMC'=>'Russian Miner Coin',
            'RUSTBITS'=>'Rustbits',
            'SAC'=>'SACoin',
            'XSTC'=>'Safe Trade Coin',
            'SFE'=>'SafeCoin',
            'SAGA'=>'SagaCoin',
            'SKR'=>'Sakuracoin',
            'SLS'=>'SaluS',
            'SND'=>'Sand Coin',
            'STC'=>'Santa Coin',
            'SAN'=>'Santiment Network Token',
            'STV'=>'Sativacoin',
            'MAD'=>'SatoshiMadness',
            'SANDG'=>'Save and Gain',
            'SCORE'=>'Scorecoin',
            'SCRT'=>'SecretCoin',
            'SRC'=>'SecureCoin',
            'B2X'=>'SegWit2x [Futures]',
            'SLFI'=>'Selfiecoin',
            'SDRN'=>'Senderon',
            'SEQ'=>'Sequence',
            'SXC'=>'Sexcoin',
            'SHA'=>'SHACoin',
            'SHDW'=>'Shadow Token',
            'SDC'=>'ShadowCash',
            'SSS'=>'Sharechain',
            'SAK'=>'Sharkcoin',
            'SHELL'=>'ShellCoin',
            'XSH'=>'SHIELD',
            'SHIFT'=>'Shift',
            'SH'=>'Shilling',
            'SHORTY'=>'Shorty',
            'SIB'=>'SIBCoin',
            'SIGMA'=>'SIGMAcoin',
            'SIGT'=>'Signatum',
            'OST'=>'Simple Token',
            'SNGLS'=>'SingularDTV',
            'SRN'=>'SIRIN LABS Token',
            'SISA'=>'SISA',
            '611'=>'SixEleven',
            'SKC'=>'Skeincoin',
            'SKIN'=>'SkinCoin',
            'SKY'=>'Skycoin',
            'SLEVIN'=>'Slevin',
            'SLING'=>'Sling',
            'SIFT'=>'Smart Investment Fund Token',
            'SMART'=>'SmartBillions',
            'SMART'=>'SmartCash',
            'SMC'=>'SmartCoin',
            'SMT'=>'SmartMesh',
            'SMLY'=>'SmileyCoin',
            'SNAKE'=>'SnakeEyes',
            'SNOV'=>'Snovio',
            'SOAR'=>'Soarcoin',
            'SCL'=>'Social',
            'SEND'=>'Social Send',
            'SOCC'=>'SocialCoin',
            'SOIL'=>'SOILcoin',
            'SOJ'=>'Sojourn',
            'SLR'=>'SolarCoin',
            'SFC'=>'Solarflarecoin',
            'XLR'=>'Solaris',
            'SCT'=>'Soma',
            'SONG'=>'SongCoin',
            'SNM'=>'SONM',
            'SOON'=>'SoonCoin',
            'SPHTX'=>'SophiaTX',
            'HERO'=>'Sovereign Hero',
            'SPACE'=>'SpaceCoin',
            'SPANK'=>'SpankChain',
            'XSPEC'=>'Spectrecoin',
            'SCS'=>'Speedcash',
            'SPHR'=>'Sphere',
            'XID'=>'Sphre AIR',
            'SPORT'=>'SportsCoin',
            'SPF'=>'SportyFi',
            'SPT'=>'Spots',
            'SPR'=>'SpreadCoin',
            'SPRTS'=>'Sprouts',
            'SPEX'=>'SproutsExtreme',
            'STAR'=>'Starbase',
            'STARS'=>'StarCash Network',
            'STRC'=>'StarCredits',
            'STA'=>'Starta',
            'START'=>'Startcoin',
            'SNT'=>'Status',
            'XST'=>'Stealthcoin',
            'SBD'=>'Steem Dollars',
            'STEPS'=>'Steps',
            'SLG'=>'Sterlingcoin',
            'STEX'=>'STEX',
            'STORJ'=>'Storj',
            'SJCX'=>'Storjcoin X',
            'STORM'=>'Storm',
            'STX'=>'Stox',
            'DATA'=>'Streamr DATAcoin',
            'STS'=>'Stress',
            'SBC'=>'StrikeBitClub',
            'SHND'=>'StrongHands',
            'SUB'=>'Substratum',
            'SGR'=>'Sugar Exchange',
            'SUMO'=>'Sumokoin',
            'SNC'=>'SunContract',
            'SBTC'=>'Super Bitcoin',
            'SUPER'=>'SuperCoin',
            'UNITY'=>'SuperNET',
            'SUR'=>'Suretly',
            'BUCKS'=>'SwagBucks',
            'SWP'=>'Swapcoin',
            'TOKEN'=>'SwapToken',
            'SWT'=>'Swarm City',
            'SWING'=>'Swing',
            'SDP'=>'SydPak',
            'SYNX'=>'Syndicate',
            'AMP'=>'Synereo',
            'SNRG'=>'Synergy',
            'SYS'=>'Syscoin',
            'TAAS'=>'TaaS',
            'TAG'=>'TagCoin',
            'TAGR'=>'TAGRcoin',
            'TAJ'=>'TajCoin',
            'XTO'=>'Tao',
            'TGT'=>'Target Coin',
            'TLE'=>'Tattoocoin (Limited Edition)',
            'TSE'=>'Tattoocoin (Standard Edition)',
            'TCOIN'=>'T-coin',
            'TEAM'=>'TeamUp',
            'THS'=>'TechShares',
            'TEK'=>'TEKcoin',
            'TELL'=>'Tellurion',
            'PAY'=>'TenX',
            'TERA'=>'TeraCoin',
            'TRC'=>'Terracoin',
            'TER'=>'TerraNova',
            'TESLA'=>'TeslaCoilCoin',
            'TES'=>'TeslaCoin',
            'XTZ'=>'Tezos',
            'TCC'=>'The ChampCoin',
            'FUNK'=>'The Cypherfunks',
            'XVE'=>'The Vegan Initiative',
            'TCR'=>'TheCreed',
            'GCC'=>'TheGCCcoin',
            'MAY'=>'Theresa May Coin',
            'TNT'=>'Tierion',
            'TIE'=>'TIES Network',
            'TGC'=>'Tigercoin',
            'TNB'=>'Time New Bank',
            'TIT'=>'Titcoin',
            'TTC'=>'TittieCoin',
            'TOA'=>'ToaCoin',
            'TODAY'=>'TodayCoin',
            'TKN'=>'TokenCard',
            'TKS'=>'Tokes',
            'TOK'=>'Tokugawa',
            'TOPAZ'=>'Topaz Coin',
            'TOP'=>'TopCoin',
            'TOR'=>'Torcoin',
            'TRCT'=>'Tracto',
            'TX'=>'TransferCoin',
            'TZC'=>'TrezarCoin',
            'TRIA'=>'Triaconta',
            'TRI'=>'Triangles',
            'TRICK'=>'TrickyCoin',
            'TRDT'=>'Trident Group',
            'TRIG'=>'Triggers',
            'TSTR'=>'Tristar Coin',
            'TROLL'=>'Trollcoin',
            'TRX'=>'TRON',
            'TRK'=>'Truckcoin',
            'TFL'=>'TrueFlip',
            'TRUMP'=>'TrumpCoin',
            'TRUST'=>'TrustPlus',
            'TURBO'=>'TurboCoin',
            'TYCHO'=>'Tychocoin',
            'UAHPAY'=>'UAHPay',
            'UBQ'=>'Ubiq',
            'UFO'=>'UFO Coin',
            'UGT'=>'UG Token',
            'GAIN'=>'UGAIN',
            'ULA'=>'Ulatech',
            'USC'=>'Ultimate Secure Cash',
            'UTC'=>'UltraCoin',
            'UNB'=>'UnbreakableCoin',
            'UNC'=>'UNCoin',
            'UNIC'=>'UniCoin',
            'UNIFY'=>'Unify',
            'UKG'=>'Unikoin Gold',
            'UBTC'=>'United Bitcoin',
            'UIS'=>'Unitus',
            'UNY'=>'Unity Ingot',
            'UNIT'=>'Universal Currency',
            'UNRC'=>'Universal Royal Coin',
            'UNI'=>'Universe',
            'UNO'=>'Unobtanium',
            'URC'=>'Unrealcoin',
            'UFR'=>'Upfiring',
            'UQC'=>'Uquid Coin',
            'UR'=>'UR',
            'URO'=>'Uro',
            'USDE'=>'USDe',
            'UET'=>'Useless Ethereum Token',
            'UTA'=>'UtaCoin',
            'UTK'=>'UTRUST',
            'VAL'=>'Valorbit',
            'VPRC'=>'VapersCoin',
            'VLTC'=>'Vault Coin',
            'XVC'=>'Vcash',
            'VEN'=>'VeChain',
            'VEC2'=>'VectorAI',
            'VLT'=>'Veltor',
            'VRC'=>'VeriCoin',
            'CRED'=>'Verify',
            'VERI'=>'Veritaseum',
            'VRM'=>'VeriumReserve',
            'VRS'=>'Veros',
            'V'=>'Version',
            'VIA'=>'Viacoin',
            'VIBE'=>'VIBE',
            'VIB'=>'Viberate',
            'VIP'=>'VIP Tokens',
            'VUC'=>'Virta Unique Coin',
            'VTA'=>'Virtacoin',
            'XVP'=>'Virtacoinplus',
            'VC'=>'VirtualCoin',
            'VISIO'=>'Visio',
            'VIU'=>'Viuly',
            'VIVO'=>'VIVO',
            'VOISE'=>'Voise',
            'VOT'=>'VoteCoin',
            'VOX'=>'Voxels',
            'VOYA'=>'Voyacoin',
            'VASH'=>'VPNCoin',
            'VSL'=>'vSlice',
            'VSX'=>'Vsync',
            'VTR'=>'vTorrent',
            'VULC'=>'Vulcano',
            'WA'=>'WA Space',
            'WABI'=>'WaBi',
            'WGR'=>'Wagerr',
            'WTC'=>'Walton',
            'WAND'=>'WandX',
            'WARP'=>'WARP',
            'WCT'=>'Waves Community Token',
            'WGO'=>'WavesGo',
            'WAX'=>'WAX',
            'WAY'=>'WayGuide',
            'WSX'=>'WeAreSatoshi',
            'TRST'=>'WeTrust',
            'WHL'=>'WhaleCoin',
            'XWC'=>'WhiteCoin',
            'WIC'=>'Wi Coin',
            'WBB'=>'Wild Beast Block',
            'WILD'=>'Wild Crypto',
            'WC'=>'WINCOIN',
            'WINGS'=>'Wings',
            'WINK'=>'Wink',
            'WMC'=>'WMCoin',
            'WOMEN'=>'WomenCoin',
            'LOG'=>'Woodcoin',
            'WDC'=>'WorldCoin',
            'WRC'=>'Worldcore',
            'WOW'=>'Wowcoin',
            'WYV'=>'Wyvern',
            'X2'=>'X2',
            'XAU'=>'Xaucoin',
            'XAUR'=>'Xaurum',
            'XCO'=>'X-Coin',
            'XDE2'=>'XDE II',
            'XNN'=>'Xenon',
            'XGOX'=>'XGOX',
            'XIOS'=>'Xios',
            'XOC'=>'Xonecoin',
            'XPA'=>'XPlay',
            'XTD'=>'XTD Coin',
            'XBY'=>'XTRABYTES',
            'XYLO'=>'XYLO',
            'YAC'=>'Yacoin',
            'YASH'=>'YashCoin',
            'YEL'=>'Yellow Token',
            'YTN'=>'YENTEN',
            'YES'=>'Yescoin',
            'YOC'=>'Yocoin',
            'YOYOW'=>'YOYOW',
            'ZYD'=>'Zayedcoin',
            'ZCG'=>'ZCash Gold',
            'ZCL'=>'ZClassic',
            'XZC'=>'ZCoin',
            'ZEIT'=>'Zeitcoin',
            'ZEN'=>'ZenCash',
            'ZENGOLD'=>'ZenGold',
            'ZENI'=>'Zennies',
            'ZEPH'=>'Zephyr',
            'ZER'=>'Zero',
            'ZET'=>'Zetacoin',
            'ZMC'=>'ZetaMicron',
            'ZSC'=>'Zeusshield',
            'ZBC'=>'Zilbercoin',
            'ZOI'=>'Zoin',
            'ZNE'=>'Zonecoin',
            'ZZC'=>'ZoZoCoin',
            'ZRC'=>'ZrCoin',
            'ZSE'=>'ZSEcoin',
            'ZUR'=>'Zurcoin'
        ];

        if (!$by_name) {
            if (empty($search)) {
                return $ct;
            } else {
                return $ct[$search];
            }
        } else {
            foreach ($ct as $k => $t) {
                if (strtolower($t)==strtolower($search)) return $k;
            }
        }
    }

    /**
     * @return string[]
     */
    function getMarkets() {
        $mt = [
            'nobitex',
            'ramzinex',
            'abantether',
            'wallex',
            'donyacoin',
            'arzfi',
            'exnovin',
            'irpm',
            'arzicoin',
            'bitgrand',
            'arzinja',
            'adabex',
            'arzif',
            'pay98',
            'ariomex',
            'arkaex'
        ];
        return $mt;
    }

    /**
     * @param $market
     * @param $sign
     * @return mixed
     */
    function getPrice($market, $sign) {
        return call_user_func_array(array($this, "$market"), [$sign]);
    }

    /**
     * @param $sign
     * @param $sort
     * @param $sort_order
     * @return array[]
     */
    function getAllPrices($sign,$sort=null,$sort_order=null) {
        $ret = [];
        foreach ($this->getMarkets() as $market) {
            $result = $this->$market($sign);
            $ret[$market] = $result;
        }
        if (!empty($sort)) {
            if ($sort=='sell') {
                $col = array_column( $ret, "sell" );
            } elseif ($sort=='buy') {
                $col = array_column( $ret, "buy" );
            }

            if ($sort_order=='desc') {
                array_multisort( $col, SORT_DESC, $ret );
            } else {
                array_multisort( $col, SORT_ASC, $ret );
            }
        }
        return array('data'=>$ret);
    }

    /**
     * @param $prices
     * @param $price_avg
     * @return array
     */
    function priceFilter($prices, $price_avg) {
        $new_price = [];
        $min_price = min($prices);
        $max_price = max($prices);
        $limit1 = ($max_price-$min_price)/10;
        $limit2 = ($max_price-$min_price)/20;

        //== Check prices =================
        foreach ($prices as $i => $price) {
            $dif = $price - $price_avg;

            //== Filter conditions ===========
            if ($price > $min_price+$limit1 and $price < $max_price-$limit1 and abs($dif) < $limit2) {
                $new_price['prices'][$i]=$price;
                $new_price['differences'][$i]=array($dif);
            }
        }

        return $new_price;
    }

    /**
     * @param $new_price
     * @return array|false
     */
    function predictPrice($new_price) {
        $ret = [];
        if (count($new_price['differences']) == count($new_price['prices'])) {
            $regression1 = new SVR(Kernel::LINEAR);
            $regression1->train($new_price['differences'], $new_price['prices']);
            $ret['SVR'] = (int)$regression1->predict([0]);

            $regression2 = new LeastSquares();
            $regression2->train($new_price['differences'], $new_price['prices']);
            $ret['LeastSquares'] = (int)$regression2->predict([0]);
            return $ret;
        } else {
            return false;
        }
    }

    function analyse($sign) {
        $markets = $this->getAllPrices($sign);
        $prices = [];
        $kf = new KalmanFilter();
        foreach ($markets['data'] as $k => $market) {
            if (isset($market['sell']) and !empty($market['sell'])) {
                $prices['sell'][]=$kf->filter($market['sell']);
            }
            if (isset($market['buy']) and !empty($market['buy'])) {
                $prices['buy'][]=$kf->filter($market['buy']);
            }
        }

        $sell_price_avg = array_sum($prices['sell']) / count($prices['sell']);
        $buy_price_avg = array_sum($prices['buy']) / count($prices['buy']);

        $new_sell_prices = $this->priceFilter($prices['sell'],$sell_price_avg);
        $new_buy_prices = $this->priceFilter($prices['buy'],$buy_price_avg);

        $ret['sell'] = $this->predictPrice($new_sell_prices);
        $ret['buy'] = $this->predictPrice($new_buy_prices);
        if ($ret['sell']!=false and $ret['buy']!=false) {
            return response()->json(['data' => array(
                'sell'=>$ret['sell'],
                'buy'=>$ret['buy']
            )]);
        } else {
            return ['error'=>'request has been failed.'];
        }
    }
}
