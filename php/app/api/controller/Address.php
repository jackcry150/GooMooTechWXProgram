<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Address
{

    public function create()
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

            $insert['userId'] = $userToken['userId'];
            $insert['name'] = Request::post('name');
            $insert['phone'] = Request::post('phone');
            $insert['province'] = Request::post('province');
            $insert['city'] = Request::post('city');
            $insert['region'] = Request::post('region');
            $insert['detail'] = Request::post('detail');
            $insert['isDefault'] = Request::post('isDefault');
            $insert['isDelete'] = 1;
            if (empty($insert['userId']) || empty($insert['name']) || empty($insert['phone']) || empty($insert['province']) || empty($insert['detail'])) {
                return json($data);
            }
            if (!$insert['isDefault']) {
                $where = [
                    'userId' => $userToken['userId'],
                    'isDelete' => 1
                ];
                $count = Db::name('address')->where($where)->count();
                if ($count == 0) {
                    $insert['isDefault'] = true;
                }
            }
            $res = Db::name('address')->insert($insert);
            if ($res) {
                $data['code'] = 200;
                $data['msg'] = '添加成功';
            } else {
                $data['code'] = 100;
                $data['msg'] = "添加失败";
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function list()
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

            $where = [
                'userId' => $userToken['userId'],
                'isDelete' => 1
            ];

            $list = Db::name('address')->field('id, name, phone, province, city, region, detail, isDefault')->where($where)->order('id desc')->select();

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = $list;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function default()
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

            $where = [
                'userId' => $userToken['userId'],
                'isDelete' => 1,
                'isDefault' => 1
            ];

            $info = Db::name('address')->field('id, name, phone, province, city, region, detail, isDefault')->where($where)->find();
            if (!empty($info)) {
                $where = [
                    'userId' => $userToken['userId'],
                    'isDelete' => 1,
                ];
                $info = Db::name('address')->field('id, name, phone, province, city, region, detail, isDefault')->where($where)->order('id desc')->find();
                $where = [
                    'id' => $info['id'],
                ];
                $update = [
                    'isDefault' => true
                ];
                Db::name('address')->where($where)->update($update);
            }

            $data['code'] = 200;
            $data['msg'] = '成功';
            $data['data'] = $info;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function detail()
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

            $where = [
                'userId' => $userToken['userId'],
                'id' => Request::get('id'),
                'isDelete' => 1
            ];

            $info = Db::name('address')->field('id, name, phone, province, city, region, detail, isDefault')->where($where)->find();
            if ($info) {
                $data['code'] = 200;
                $data['msg'] = '成功';
                $data['data'] = $info;
                return json($data);
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function edit()
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

            $type = Request::post('type');
            if ($type == 1) {
                Db::startTrans();
                try {
                    $where = [
                        'userId' => $userToken['userId'],
                        'isDelete' => 1
                    ];
                    $update = [
                        'isDefault' => false
                    ];
                    Db::name('address')->where($where)->update($update);
                    $where = [
                        'userId' => $userToken['userId'],
                        'id' => Request::post('addressId')
                    ];
                    $update = [
                        'isDefault' => true
                    ];
                    Db::name('address')->where($where)->update($update);
                    // 提交事务
                    Db::commit();

                    $data['code'] = 200;
                    $data['msg'] = '修改成功';
                } catch (Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $data['code'] = 100;
                    $data['msg'] = "修改失败";
                }
                return json($data);
            } elseif ($type == 2) {
                Db::startTrans();
                try {
                    $isDefault = Request::post('isDefault');
                    if ($isDefault === true) {
                        if (Request::post('isDefault')) {
                            $where = [
                                'userId' => $userToken['userId'],
                                'isDelete' => 1
                            ];
                            $update = [
                                'isDefault' => false
                            ];
                            Db::name('address')->where($where)->update($update);
                        }
                    } else {
                        $where = [
                            'userId' => $userToken['userId'],
                            'isDelete' => 1
                        ];
                        $lastInfo = Db::name('address')->where($where)->order('id desc')->find();
                        if ($lastInfo) {
                            $where = [
                                'id' => $lastInfo['id']
                            ];
                            $update = [
                                'isDefault' => true
                            ];
                            Db::name('address')->where($where)->update($update);
                        }
                    }

                    $where = [
                        'userId' => $userToken['userId'],
                        'id' => Request::post('id')
                    ];
                    $update = [
                        'name' => Request::post('name'),
                        'phone' => Request::post('phone'),
                        'province' => Request::post('province'),
                        'city' => Request::post('city'),
                        'region' => Request::post('region'),
                        'detail' => Request::post('detail'),
                        'isDefault' => Request::post('isDefault'),
                    ];
                    Db::name('address')->where($where)->update($update);
                    // 提交事务
                    Db::commit();

                    $data['code'] = 200;
                    $data['msg'] = '修改成功';
                } catch (Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $data['code'] = 100;
                    $data['msg'] = "修改失败";
                }
                return json($data);
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function del()
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

            Db::startTrans();
            try {
                $where = [
                    'userId' => $userToken['userId'],
                    'id' => Request::post('addressId')
                ];
                $update = [
                    'isDelete' => 2,
                    'isDefault' => false
                ];
                Db::name('address')->where($where)->update($update);

                $where = [
                    'userId' => $userToken['userId'],
                    'isDelete' => 1,
                    'isDefault' => 1
                ];
                $count = Db::name('address')->where($where)->count();
                if ($count == 0) {
                    $where = [
                        'userId' => $userToken['userId'],
                        'isDelete' => 1
                    ];
                    $lastInfo = Db::name('address')->where($where)->order('id desc')->find();
                    if ($lastInfo) {
                        $where = [
                            'id' => $lastInfo['id']
                        ];
                        $update = [
                            'isDefault' => true
                        ];
                        Db::name('address')->where($where)->update($update);
                    }
                }

                $where = [
                    'userId' => $userToken['userId'],
                    'isDelete' => 1
                ];

                $list = Db::name('address')->field('id, name, phone, province, city, region, detail, isDefault')->where($where)->order('id desc')->select();

                // 提交事务
                Db::commit();

                $data['code'] = 200;
                $data['msg'] = '删除成功';
                $data['data'] = $list;
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
                $data['code'] = 100;
                $data['msg'] = "删除失败";
            }
            return json($data);

        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}
