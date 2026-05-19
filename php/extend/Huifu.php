<?php

/**
 * 汇付天下支付类
 */
class Huifu
{
    // 汇付天下配置 - 需要根据实际情况配置
    private $merchantId = '6666000184930633'; // 商户号

    /**
     * 创建支付订单
     * @param $params 支付参数
     * @param $acctInfos 分帐参数
     * @return bool|string
     */
    public function create($params, $acctInfos = [])
    {

        $url = 'https://api.huifu.com/v3/trade/payment/jspay';

        $headers = [
            'Content-Type:application/json;charset=UTF-8',
        ];

        $dataParameter = [
            'req_date' => date('Ymd'),
            'req_seq_id' => $params['tradeNo'],
            'huifu_id' => $this->merchantId,
            'goods_desc' => $params['goodsDesc'],
            'trade_type' => $params['tradeType'],
            'trans_amt' => $params['transAmt'],
            'notify_url' => $params['notifyUrl'],
            'wx_data' => [
                'sub_openid' => $params['openId']
            ]
        ];
        if ($acctInfos) {
            $acctData[] = [
                'div_amt' => $params['profit'],
                'huifu_id' => $this->merchantId,
            ];
            $dataParameter['acct_split_bunch'] = [
                'acct_infos' => array_merge($acctData, $acctInfos)
            ];
        }
        $parameter = [
            'sys_id' => $this->merchantId,
            'product_id' => 'PAYUN',
            'data' => $dataParameter,
            'sign' => $this->generateSign($dataParameter)
        ];

        return self::postUrl($url, $headers, json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }


    /**
     * 生成签名
     * @param array $data 签名数据
     * @return string
     */
    private function generateSign($data)
    {
        ksort($data);
        $signString = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCwvMCvNABXgEjoZz4V92gxnb4MsnPYaNibIev8mt8jTT2Ry8PtJgw83XTeX3CpqJRMhnKUSWGYK6xkF9LEAN+w0jzi9X5PelwsmIVIYVHuq/aF8JjsEJGEpeEEqeIRcSLR36xjmROpYlGsAL8A+rVngI/ReQ+KP71rgfGhl78D+4Fen8tzkuzC27Uc8E4/GIX07MMiLXteRNERsBEs/EtlTpOKHruSryFfzbOv9UN4GIAtG1Z2AJJA2UQISqzN8lvdB15fvaLIGAYsEM7rc9xYjZL9oqLk5gQaWzqBCKnTbZZRg9ItA7lkbkA58mknyPpyElc3c6nUjP0Gm+jUo7+ZAgMBAAECggEAeg1wF86WqdbrAqxB4ROhvhoMpGIcxIfrhn7PpPwjOxLdgTEyFjUfYG0jh2pruI62E38J48QlhNwsSld1c2yTDl6yM56L40FCJH4UFV84INZbAjactPHgPLX9hqX3fEogXMXWHFYbkO9YLau6PGfnHRpYt7Wd+MB6BKORhiHwhUNlYnr8D5bQ6hS3ET0mZtqCWMEkU3i1G+k2slwHjetGMjSVbzXUe5+q92gh9tJi/DdtcisRhozsSHK87VYy9MfzpWQIYzOzcPKQPjHxqOW8TZ7tS3RT4TpK1YLaBw1DpGAkhCq3a3/iHlvj7nctjFa3ywdQk4tTzGpyEzpQ3zL+jQKBgQDmZjZOstw56fLnahh8bV+mpp3rZLm/hSQk2lnXRzXrcG9KkhJLx8+AGWMXE3evfDETZD7xDQ5iplYhgkDetMHFnTAFHD9qGsScKLJjvxE1d0qjxdyRhGNyuabWSCcXXL6VZTdsoa1uZgl0V9FrRgPknZF+SLkOr94C8i08iHEQKwKBgQDEYBuHVpQ0FXODT/r8sEVnG3jEW6R/2RG5qebZCHuuqtfmX+LUl2fddrQ6EpNpoD8WtXTwGBYSF6WufqrZvT8h8SEl63aJVSv+ee+SgifgMv6CoBtegOzt4e5X3orMz4u7N+H4LBUsefe07Bg/OJCWjX47Ehqr151U/YFGsheJSwKBgDRg2knL6bsUz48ClKx85Pjq+g3QqVW7+/qQ1UnWu5BZ0ENAr/4gX7D8lIVjfJsdfb6t1I1SbYnJzNzzUrIOn9rAIHGY+WWyzi2+Jsf8YPops4NF8R333e/v+tjOMGzkPcOS7iW5H8dwVQfpCwf7M9leZPwzpjaLjspWqigP0LxfAoGAaSu7spjo2JggfFQqbIiPMvBglnEqQZpkxtW6n8POUbaH2IbD+e14oABB99QZBPngr+3QygFsWJY3kqOeJu0W0bvNb/ySSGzIGgr+Bq4UYxuMeTL8VXmg6qoUtxSlq5kAEe2U5Q/e6yoLQucq+Tj3Htp6n3JLvHrGdU0rnkwlFM8CgYEAosYlyJtOUFDO+9m9e+xhaFCAHTIVipZ6zfS/kciBcAg0Wc2EePcs8AHWSQE/bn3+TrWXf4uyxf8K83IJQa7aYDzqLuInusFrbblXTklbwbxaxI9CHU+YqreB7BvyOg2WX/Vyr4sWx5g4gGwVVmtcHqVU6vUIHDS4h0DqYuk6Qxo=';
        $key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($rsaPrivateKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        $signature = '';
        try {
            openssl_sign($signString, $signature, $key, OPENSSL_ALGO_SHA256);
            $signature = base64_encode($signature);
        } catch (Exception $e) {
//            echo $e->getMessage();
            $signature = '';
        }
        return $signature;
    }

    /**
     * @param $url
     * @param $headers
     * @param $body
     * @return bool|string
     */
    function postUrl($url, $headers, $body): bool|string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
        }
        curl_close($curl);
        return $result;
    }

    /**
     * 验证签名
     * @param array $data 回调数据
     * @return bool
     */
    public function verifySign($data)
    {
        $sign = $data['sign'] ?? '';
        $calculatedSign = $this->generateSign($data);

        return $sign === $calculatedSign;
    }
}