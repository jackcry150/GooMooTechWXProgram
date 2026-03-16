<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Auth
{
    private $appid = 'wxfcc20942c4074693';
    private $secret = 'fac8bbc449de6a99c4fa96ad8b6729e0';

    public function phoneLogin()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $code = Request::post('code');
            $encryptedData = Request::post('encryptedData');
            $iv = Request::post('iv');

            if (empty($code) || empty($encryptedData) || empty($iv)) {
                return json($data);
            }

            // 1. 获取session_key和openid
            $sessionInfo = self::getSessionInfo($code);

            if (!$sessionInfo || isset($sessionInfo['errcode'])) {
                $data['msg'] = '获取session_key失败';
                return json($data);
            }

            // 2. 解密手机号
            $phoneInfo = self::decryptPhoneNumber($encryptedData, $iv, $sessionInfo['session_key']);
            if (!$phoneInfo) {
                $data['msg'] = '解密手机号失败';
                return json($data);
            }

            // 3. 处理用户登录/注册
            $where = [
                'openId' => $sessionInfo['openid'],
            ];
            $userInfo = Db::name('user')->field('id, openId')->where($where)->find();
            if (!$userInfo) {
                $userInsert = [
                    'avatar' => '/static/images/default_avatar.jpg',
                    'openId' => $sessionInfo['openid'],
                    'phone' => $phoneInfo['purePhoneNumber'] ?? '',
                    'regDate' => date('Y-m-d H:i:s'),
                    'regTime' => time(),
                    'regIp' => Request::ip(),
                    'loginDate' => date('Y-m-d H:i:s'),
                    'loginTime' => time(),
                    'loginIp' => Request::ip(),
                ];
                $userId = Db::name('user')->insertGetId($userInsert);
                if (!$userId) {
                    $data['msg'] = '登录失败';
                    return json($data);
                }
                $userWhere = [
                    'id' => $userId
                ];
                $userUpdate = [
                    'nickName' => '小小蜗' . $userId,
                ];
                Db::name('user')->where($userWhere)->update($userUpdate);
                $userInfo = [
                    'id' => $userId,
                    'openId' => $userInsert['openId'],
                ];
            } else {
                $userWhere = [
                    'id' => $userInfo['id']
                ];
                $userUpdate = [
                    'loginDate' => date('Y-m-d H:i:s'),
                    'loginTime' => time(),
                    'loginIp' => Request::ip(),
                ];
                Db::name('user')->where($userWhere)->update($userUpdate);
                $userInfo = [
                    'id' => $userInfo['id'],
                    'openId' => $userInfo['openId'],
                ];
            }
            // 4. 生成token
            $token = self::generateToken($userInfo);

            $data['code'] = 200;
            $data['msg'] = "登录成功！";
            $data['data'] = [
                'token' => $token
            ];
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    function generateToken($userInfo)
    {
        $payload = [
            'userId' => $userInfo['id'],
            'openId' => $userInfo['openId'],
            'exp' => time() + 7200
        ];
        return base64_encode(encrypt(json_encode($payload)));
    }

    /**
     * 获取session_key和openid
     */
    function getSessionInfo($code)
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->appid . '&secret=' . $this->secret . '&js_code=' . $code . '&grant_type=authorization_code';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * 解密手机号
     */
    function decryptPhoneNumber($encryptedData, $iv, $sessionKey)
    {
        if (strlen($sessionKey) != 24) {
            return false;
        }
        if (strlen($iv) != 24) {
            return false;
        }

        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        if (!$result) {
            return false;
        }

        $dataArr = json_decode($result, true);
        if (!$dataArr || !isset($dataArr['purePhoneNumber'])) {
            return false;
        }

        return $dataArr;
    }
}