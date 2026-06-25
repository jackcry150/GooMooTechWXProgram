<?php

namespace app\adm\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Setting
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
        $appCode = normalize_app_code_value(Request::get('app_code', 'goomoo'));
        if (table_has_app_code('setting')) {
            $info = Db::name('setting')->where('app_code', $appCode)->find();
            if (!$info) {
                $fallback = Db::name('setting');
                apply_app_code_scope($fallback, 'setting', $appCode, true);
                $fallback = $fallback->orderRaw(build_app_code_priority_order($appCode))
                    ->order('id asc')
                    ->find();
                $info = $fallback ?: [];
                $info['id'] = '';
                $info['app_code'] = $appCode;
            }
        } else {
            $info = Db::name('setting')->where('id', 1)->find();
        }
        $info = $info ?: [];
        foreach (['id', 'name', 'link', 'contactUs', 'address', 'email', 'lotteryCost', 'lotteryRule'] as $column) {
            $info[$column] = $info[$column] ?? '';
        }
        if ($info['lotteryCost'] === '') {
            $info['lotteryCost'] = 10;
        }
        $info['wechatMiniAppId'] = $info['wechatMiniAppId'] ?? '';
        $info['wechatMiniSecret'] = $info['wechatMiniSecret'] ?? '';
        $info['huifuMerchantId'] = $info['huifuMerchantId'] ?? '';
        $info['huifuPrivateKey'] = $info['huifuPrivateKey'] ?? '';
        $info['huifuNotifyUrl'] = $info['huifuNotifyUrl'] ?? '';
        $info['paymentSplitEnabled'] = $info['paymentSplitEnabled'] ?? 0;
        View::assign('info', $info);
        View::assign('app_code', $appCode);
        View::assign('appCodeOptions', app_code_options());
        View::assign('currentAppCodeName', app_code_text($appCode));

        return View::fetch();
    }



    function updateCacheSetting($appCode = '')
    {
        foreach (app_code_cache_targets($appCode) as $cacheAppCode) {
            Cache::delete('setting:' . $cacheAppCode);
        }
    }


    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $appCode = normalize_app_code_value($post['app_code'] ?? 'goomoo');
            if (table_has_app_code('setting')) {
                $post['app_code'] = $appCode;
            } else {
                unset($post['app_code']);
            }
            foreach (['wechatMiniAppId', 'wechatMiniSecret', 'huifuMerchantId', 'huifuPrivateKey', 'huifuNotifyUrl', 'paymentSplitEnabled'] as $column) {
                if (!table_has_column('setting', $column)) {
                    unset($post[$column]);
                }
            }

            $id = $post['id'] ?? '';
            $res = false;
            if ($id) {
                $res = Db::name('setting')->where('id', $id)->update($post);
            } else {
                unset($post['id']);
                $res = Db::name('setting')->insert($post);
            }

            if ($res !== false) {
                self::updateCacheSetting($appCode);
                $data['msg'] = "修改成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "修改失败！";
                $data['code'] = 0;
                return json($data);
            }
        }
    }
}
