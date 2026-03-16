<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class User
{

    public function profile()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $userWhere = [
                'id' => $userToken['userId']
            ];
            $userInfo = Db::name('user')->field('id, nickName, avatar, collectionCards, snailShells')->where($userWhere)->find();
            if (!$userInfo) {
                return json($data);
            }

            if (strpos($userInfo['avatar'], 'data:image') === false) {
                $userInfo['avatar'] = Request::domain() . $userInfo['avatar'];
            }

            $data['code'] = 200;
            $data['msg'] = '成功！';
            $data['data'] = $userInfo;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function nickName()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $nickName = Request::post('nickName');
            if (empty($nickName)) {
                return json($data);
            }

            $userWhere = [
                'id' => $userToken['userId']
            ];
            $userUpdate = [
                'nickName' => $nickName,
            ];
            $userRes = Db::name('user')->where($userWhere)->update($userUpdate);
            if ($userRes) {
                $data['code'] = 200;
                $data['msg'] = "修改成功！";
            } else {
                $data['code'] = 201;
                $data['msg'] = "修改失败";
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function avatar()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $headers = Request::header();

            if (!$headers || !isset($headers['authorization'])) {
                return json($data);
            }
            $token = $headers['authorization'];
            $userToken = json_decode(decrypt(base64_decode($token)), true);
            if (!$userToken || !isset($userToken['userId'])) {
                return json($data);
            }

            $avatar = Request::post('avatar');
            if (empty($avatar)) {
                return json($data);
            }

            $userWhere = [
                'id' => $userToken['userId']
            ];
            $userUpdate = [
                'avatar' => $avatar,
            ];
            $userRes = Db::name('user')->where($userWhere)->update($userUpdate);
            if ($userRes) {
                $data['code'] = 200;
                $data['msg'] = "修改成功！";
            } else {
                $data['code'] = 201;
                $data['msg'] = "修改失败";
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}