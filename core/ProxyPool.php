<?php

/**
 *  ProxyPool核心文件
 */

namespace ProxyPool\core;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/config.php';

use ProxyPool\core\Requests;
use ProxyPool\core\Queue;

class ProxyPool
{
    private $redis;
    private $httpClient;
    private $queueObj;

    function __construct()
    {
        $redis = new \Redis();
        $redis->connect(config("database.redis_host"), config("database.redis_port"));
        $this->redis = $redis;
        if (config('database.redis_password')) {
            $this->redis->auth(config('database.redis_password'));
        }
        $this->httpClient = new Requests(['headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36 SocketLog(tabid=746&client_id=)']]);
        $this->queueObj = new Queue();
    }

    public function run()
    {
        echo "start to spider ip...." . PHP_EOL;
        $ip_arr = $this->get_ip();
        echo "select IP num: " . count($ip_arr) . PHP_EOL;
        echo "start to check ip...." . PHP_EOL;
        $this->check_ip($ip_arr);
        $ip_pool = $this->redis->smembers('ip_pool');
        echo "end check ip...." . PHP_EOL;
        print_r($ip_pool);
        die;
    }

    //获取各大网站代理IP
    private function get_ip()
    {
        $ip_arr = [];
        // $ip_arr = $this->get_xici_ip($ip_arr);
        // $this->get_3366_ip($ip_arr);
        $this->get_kxdl_ip($ip_arr);
        $this->get_89_ip($ip_arr);
        $this->get_kuaidaili_ip($ip_arr);
        return $ip_arr;
    }

    private function get_xici_ip($ip_arr)
    {
        for ($i = 1; $i <= config('spider.page_num'); $i++) {
            list($infoRes, $msg) = $this->httpClient->request(
                'GET',
                'http://www.xicidaili.com/nn/' . $i,
                []
            );
            if (!$infoRes) {
                print_r($msg);
                exit();
            }
            $infoContent = $infoRes->getBody();
            $this->convert_encoding($infoContent);
            preg_match_all('/<tr.*>[\s\S]*?<td class="country">[\s\S]*?<\/td>[\s\S]*?<td>(.*?)<\/td>[\s\S]*?<td>(.*?)<\/td>/', $infoContent, $match);

            $host_arr = $match[1];
            $port_arr = $match[2];
            foreach ($host_arr as $key => $value) {
                $ip_arr[] = $host_arr[$key] . ":" . $port_arr[$key];
            }
        }

        return $ip_arr;
    }

    private function get_kuaidaili_ip(&$ip_arr)
    {
        for ($i = 1; $i <= config('spider.page_num'); $i++) {
            list($infoRes, $msg) = $this->httpClient->request(
                'GET',
                'https://www.kuaidaili.com/free/inha/' . $i,
                []
            );
            if (!$infoRes) {
                print_r($msg) . PHP_EOL;
                break;
            }
            $infoContent = $infoRes->getBody();
            $this->convert_encoding($infoContent);

            preg_match_all('/<td data-title="IP">(.*?)<\/td>/', $infoContent, $host);
            preg_match_all('/<td data-title="PORT">(.*?)<\/td>/', $infoContent, $port);

            $host_arr = $host[1];
            $port_arr = $port[1];
            foreach ($host_arr as $key => $value) {
                $ip_arr[] = $host_arr[$key] . ":" . $port_arr[$key];
            }
            sleep(2);
        }
    }

    private function get_89_ip(&$ip_arr)
    {
        for ($i = 1; $i <= config('spider.page_num'); $i++) {
            list($infoRes, $msg) = $this->httpClient->request(
                'GET',
                'https://www.89ip.cn/index_' . $i . '.html',
                []
            );
            if (!$infoRes) {
                print_r($msg) . PHP_EOL;
                break;
            }
            $infoContent = $infoRes->getBody();
            $this->convert_encoding($infoContent);
            preg_match_all('/<tr>[\s\S]*?<td>(\s.*\s?)<\/td>[\s\S]*?<td>(\s.*\s?)<\/td>/', $infoContent, $match);

            $host_arr = $match[1];
            $port_arr = $match[2];
            foreach ($host_arr as $key => $value) {
                $ip_arr[] = trim($host_arr[$key]) . ":" . trim($port_arr[$key]);
            }
            sleep(2);
        }
    }

    private function get_3366_ip(&$ip_arr)
    {
        for ($i = 1; $i <= config('spider.page_num'); $i++) {
            list($infoRes, $msg) = $this->httpClient->request(
                'GET',
                'http://www.ip3366.net/?stype=1&page=' . $i,
                []
            );
            if (!$infoRes) {
                print_r($msg) . PHP_EOL;
                break;
            }
            $infoContent = $infoRes->getBody();
            $this->convert_encoding($infoContent);
            preg_match_all('/<tr>[\s\S]*?<td>(.*?)<\/td>[\s\S]*?<td>(.*?)<\/td>/', $infoContent, $match);
            
            $host_arr = $match[1];
            $port_arr = $match[2];
            foreach ($host_arr as $key => $value) {
                $ip_arr[] = trim($host_arr[$key]) . ":" . trim($port_arr[$key]);
            }
            sleep(2);
        }
    }

    private function get_kxdl_ip(&$ip_arr)
    {
        
        for ($i = 1; $i <= config('spider.page_num'); $i++) {
            list($infoRes, $msg) = $this->httpClient->request(
                'GET',
                'http://www.kxdaili.com/dailiip/1/'.$i.'.html',
                []
            );
            if (!$infoRes) {
                print_r($msg) . PHP_EOL;
                break;
            }
            $infoContent = $infoRes->getBody();
            $this->convert_encoding($infoContent);
            preg_match_all('/<tr>[\s\S]*?<td>(.*?)<\/td>[\s\S]*?<td>(.*?)<\/td>/', $infoContent, $match);
            
            $host_arr = $match[1];
            $port_arr = $match[2];
            foreach ($host_arr as $key => $value) {
                $ip_arr[] = trim($host_arr[$key]) . ":" . trim($port_arr[$key]);
            }
            sleep(2);
        }
    }



    //检测IP可用性
    private function check_ip($ip_arr)
    {
        $this->queueObj = $this->queueObj->arr2queue($ip_arr);
        $queue = $this->queueObj->getQueue();

        foreach ($queue as $key => $value) {
            //用百度网和腾讯网测试IP地址的可用性
            for ($i = 0; $i < config('spider.examine_round'); $i++) {
                $response = $this->httpClient->test_request('GET', 'https://www.baidu.com', ['proxy' => 'https://' . $value]);
                if (!$response) {
                    $response = $this->httpClient->test_request('GET', 'http://www.qq.com', ['proxy' => 'http://' . $value]);
                    if ($response && $response->getStatusCode() == 200) {
                        break;
                    }
                } else if ($response->getStatusCode() == 200) {
                    break;
                }
            }
            //将结果存入数据库
            if ($response && $response->getStatusCode() == 200) {
                if (config("database.type") == 'redis') {
                    $this->set_ip2redis($value);
                } else if (config("database.redis_host") == 'mysql') {
                    $this->set_ip2mysql($value);
                }
                echo $value . " success!!!!!!!!!!!!!!!!!!!!!!!! " . PHP_EOL;
            } else {
                echo $value . " error...  " . PHP_EOL;
            }
        }
    }

    //将可用ip存进redis
    private function set_ip2redis($ip)
    {
        $this->redis->sadd('ip_pool', $ip);
    }

    //将可用ip存进mysql
    private function set_ip2mysql($ip)
    {
    }

    private function convert_encoding(&$str)
    {
        $encode = mb_detect_encoding($str, ['GB2312', 'UTF-8', 'GBK', 'ASCII']);
        $str = iconv($encode, 'utf-8//TRANSLIT//IGNORE', $str);
    }
}
