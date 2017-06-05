<?php
/**
 * 这是使用 curl方式 接口对接与调用的程序
 * Created by PhpStorm.
 * User: 20108
 * Date: 2017/5/7
 * Time: 20:33
 */

namespace App;
use Exception;

class curlApiGetData
{
    //get请求方式
    const METHOD_GET  = 'get';
    //post请求方式
    const METHOD_POST = 'post';
    //设置url请求地址
    const HTTP_URL = 'http://localhost:8086/test/hh';
    //真实的接口地址
    const TRUE_HTTP_URL = 'http://zhenshi...............';

    /**
     * 发起一个get或post请求
     * @param $url 请求的url
     * @param int $method 请求方式
     * @param array $params 请求参数
     * @param array $extra_conf curl配置, 高级需求可以用, 如
     * $extra_conf = array(
     *    CURLOPT_HEADER => true,
     *    CURLOPT_RETURNTRANSFER = false
     * )
     * @return bool|mixed 成功返回数据，失败返回false
     * @throws Exception
     */
    public static function exec($params = array(),   $method = self::METHOD_GET, $url = self::HTTP_URL,$extra_conf = array())
    {
        $params = is_array($params)? http_build_query($params): $params;
        //如果是get请求，直接将参数附在url后面
        if($method == self::METHOD_GET)
        {
            $url .= (strpos($url, '?') === false ? '?':'&') . $params;
        }
        $header_config = config('syzjapi');
        $appid = $header_config['auth']['appid'];
        $appsecret = $header_config['auth']['appsecret'];
        $timestamp = time();
        $nonce = (int)substr(microtime(true), 0, 6);;
        $signature = md5($appid.$timestamp.$nonce.$appsecret);
        //设置头信息
        $header = array(
            "Content-Type: application/json",//以json格式传输
            "appid: $appid",
            "timestamp: $timestamp",
            "nonce: $nonce",
            "signature: $signature",
        );
        //默认配置
        $curl_conf = array(
            CURLOPT_URL => $url,  //请求url
            CURLOPT_HEADER => false,  //不输出头信息
            CURLOPT_RETURNTRANSFER => true, //以字符串返回，返回数据
            CURLOPT_CONNECTTIMEOUT => 10, // 连接超时时间
            CURLOPT_HTTPHEADER => $header//设置请求头信息
        );

        //配置post请求额外需要的配置项
        if($method == self::METHOD_POST)
        {
            //使用post方式
            $curl_conf[CURLOPT_POST] = true;
            //post参数
            $curl_conf[CURLOPT_POSTFIELDS] = $params;
        }

        //添加额外的配置
        foreach($extra_conf as $k => $v)
        {
            $curl_conf[$k] = $v;
        }

        $data = false;
        try
        {
            //初始化一个curl句柄
            $curl_handle = curl_init();
            //设置curl的配置项
            curl_setopt_array($curl_handle, $curl_conf);
            //发起请求
            $data = curl_exec($curl_handle);
            if ($data === false)
            {
                throw new Exception('CURL ERROR: ' . curl_error($curl_handle));
            }
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        curl_close($curl_handle);

        return $data;
    }

    //调用例子
    Public function example($options)
    {
        $url = self::TRUE_HTTP_URL.'/v0/bc/syncUserInfo';
        $data = self::exec($options,'post',$url);
        return $data;//返回给调用处，如果有问题，就记录一下log
    }

}