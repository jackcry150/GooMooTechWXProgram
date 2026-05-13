<?php

namespace app\adm\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class Servers
{

    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    public function index()
    {
        $appCode = trim((string) Request::get('app_code', ''));
        $type = Request::get('type');
        $title = Request::get('title');
        $status = Request::get('status');
        $where = [];
        if ($appCode !== '' && table_has_app_code('server')) {
            $where[] = ['app_code', '=', normalize_app_code_value($appCode)];
        }
        if ($type) {
            $searchWhere = [
                [
                    'type',
                    '=',
                    $type
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        if ($title) {
            $searchWhere = [
                [
                    'title',
                    'like',
                    '%' . $title . '%'
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        if ($status) {
            $searchWhere = [
                [
                    'status',
                    '=',
                    $status
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        $list = Db::name('server')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        foreach ($list as &$val) {
            $val['type'] = serverType($val['type']);
            $val['status'] = status($val['status']);
            $images = json_decode($val['image'], true);
            $val['image'] = isset($images[0]) ? $images[0] : '';
            $val['appCodeName'] = app_code_text($val['app_code'] ?? '');
        }

        View::assign('list', $list);

        View::assign('type', $type);
        View::assign('serverType', serverType());
        View::assign('title', $title);
        View::assign('status', $status);
        View::assign('statusType', status());
        View::assign('app_code', $appCode);
        View::assign('appCodeOptions', app_code_options());

        return View::fetch();
    }

    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $appCode = normalize_app_code_value($post['app_code'] ?? 'goomoo');
            if (table_has_app_code('server')) {
                $post['app_code'] = $appCode;
            } else {
                unset($post['app_code']);
            }
            $res = Db::name('server')->insert($post);
            if ($res) {
                self::updateCacheServer($post['type'] ?? 1, $appCode);
                $data['msg'] = "添加成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "添加失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            View::assign('serverType', serverType());
            View::assign('statusType', status());
            View::assign('app_code', normalize_app_code_value(Request::get('app_code', 'goomoo')));
            View::assign('appCodeOptions', app_code_options());

            return View::fetch();
        }
    }

    function updateCacheServer($type = 1, $appCode = '')
    {
        foreach (app_code_cache_targets($appCode) as $cacheAppCode) {
            Cache::delete('server:' . $cacheAppCode . ':' . $type);
        }
        if ($type == 2){
            $list = Db::name('server_online')
                ->field('id, type, title, image, link, corpId')
                ->where('status', 1)
                ->order('sort desc, id desc')
                ->select()
                ->toArray();
            Cache::set('serverOnline', $list);
        }

    }

    public function del()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $info = Db::name('server')->where('id', $post['id'])->find();
            $res = Db::name('server')->delete($post);
            if ($res) {
                self::updateCacheServer($info['type'] ?? 1, $info['app_code'] ?? '');
                $data['msg'] = "成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $appCode = normalize_app_code_value($post['app_code'] ?? 'goomoo');
            if (table_has_app_code('server')) {
                $post['app_code'] = $appCode;
            } else {
                unset($post['app_code']);
            }
            $res = Db::name('server')->where('id', $post['id'])->update($post);
            if ($res !== false) {
                self::updateCacheServer($post['type'] ?? 1, $appCode);
                $data['msg'] = "修改成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "修改失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            $id = Request::get('id');
            $info = Db::name('server')->where('id', $id)->find();
            $info['imageArr'] =  json_decode($info['image'], true);
            
            // 处理图片路径，确保能正确显示
            if (!empty($info['image'])) {
                $images = json_decode($info['image'], true);
                if (is_array($images) && !empty($images)) {
                    $domain = Request::domain();
                    $info['imageDisplay'] = [];
                    foreach ($images as $img) {
                        if (strpos($img, 'http') === false && strpos($img, 'data:image') === false) {
                            $info['imageDisplay'][] = $domain . $img;
                        } else {
                            $info['imageDisplay'][] = $img;
                        }
                    }
                } else {
                    $info['imageDisplay'] = [];
                }
            } else {
                $info['imageDisplay'] = [];
            }
            
            View::assign('info', $info);
            View::assign('selectedAppCode', normalize_app_code_value($info['app_code'] ?? 'goomoo'));

            View::assign('serverType', serverType());
            View::assign('statusType', status());
            View::assign('appCodeOptions', app_code_options());

            return View::fetch();
        }
    }

    public function online()
    {
        $type = Request::get('type');
        $title = Request::get('title');
        $status = Request::get('status');
        $where = [];
        if ($type) {
            $searchWhere = [
                [
                    'type',
                    '=',
                    $type
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        if ($title) {
            $searchWhere = [
                [
                    'title',
                    'like',
                    '%' . $title . '%'
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        if ($status) {
            $searchWhere = [
                [
                    'status',
                    '=',
                    $status
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        $list = Db::name('server_online')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        foreach ($list as &$val) {
            $val['type'] = productMode($val['type']);
            $val['status'] = status($val['status']);
            $images = json_decode($val['image'], true);
            $val['image'] = isset($images[0]) ? $images[0] : '';
        }

        View::assign('list', $list);

        View::assign('type', $type);
        View::assign('serverType', productMode());
        View::assign('title', $title);
        View::assign('status', $status);
        View::assign('statusType', status());

        return View::fetch();
    }

    public function onlineAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('server_online')->insert($post);
            if ($res) {
                self::updateCacheServer(2);
                $data['msg'] = "添加成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "添加失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            View::assign('serverType', productMode());
            View::assign('statusType', status());

            return View::fetch();
        }
    }

    public function onlineDel()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('server_online')->delete($post);
            if ($res) {
                self::updateCacheServer(2);
                $data['msg'] = "成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    public function onlineEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('server_online')->where('id', $post['id'])->update($post);
            if ($res) {
                self::updateCacheServer(2);
                $data['msg'] = "修改成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "修改失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {

            $id = Request::get('id');
            $info = Db::name('server_online')->where('id', $id)->find();
            $info['imageArr'] =  json_decode($info['image'], true);

            // 处理图片路径，确保能正确显示
            if (!empty($info['image'])) {
                $images = json_decode($info['image'], true);
                if (is_array($images) && !empty($images)) {
                    $domain = Request::domain();
                    $info['imageDisplay'] = [];
                    foreach ($images as $img) {
                        if (strpos($img, 'http') === false && strpos($img, 'data:image') === false) {
                            $info['imageDisplay'][] = $domain . $img;
                        } else {
                            $info['imageDisplay'][] = $img;
                        }
                    }
                } else {
                    $info['imageDisplay'] = [];
                }
            } else {
                $info['imageDisplay'] = [];
            }

            View::assign('info', $info);

            View::assign('serverType', productMode());
            View::assign('statusType', status());

            return View::fetch();
        }
    }

}
