<?php

namespace app\api\controller;

use Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class Product
{
    public function list()
    {
        $data['code'] = 100;
        $data['msg'] = '操作失败';
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $domain = Request::domain();
            $cacheKey = 'product:list:' . current_app_code() . ':' . md5($domain);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $data['code'] = 200;
                $data['msg'] = "成功！";
                $data['data'] = $cached;
                return json($data);
            }

            $listHot = [];
            $listRecom = [];
            $where = [
                ['startT', '<=', time()],
                ['endT', '>=', time()],
                ['status', '=', 1]
            ];

            $query = Db::name('product')
                ->field('id, title, subtitle, type, mode, image, price, deposit, deduct, endT, endTime')
                ->where($where);
            apply_app_code_scope($query, 'product');
            $list = $query->order('sort desc, id desc')->select()->toArray();

            foreach ($list as &$v) {
                $images = json_decode($v['image'], true);
                $img = [];
                foreach ($images as $image) {
                    $img[] = $domain . $image;
                }
                $v['image'] = $img;
                $v['endTimeStamp'] = isset($v['endT']) ? intval($v['endT']) : 0;
                if ($v['mode'] == 2) {
                    $listHot[] = $v;
                } else {
                    $listRecom[] = $v;
                }
            }

            $payload = [
                'hot' => $listHot,
                'recom' => $listRecom
            ];
            Cache::set($cacheKey, $payload, 60);

            $data['code'] = 200;
            $data['msg'] = "成功！";
            $data['data'] = $payload;
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

            $id = Request::get('id');
            if (!$id) {
                return json($data);
            }
            $where = [
                [
                    'id',
                    '=',
                    $id
                ],
                [
                    'startT',
                    '<=',
                    time()
                ],
                [
                    'endT',
                    '>=',
                    time()
                ],
                [
                    'status',
                    '=',
                    1  // 只显示上架的商品
                ]
            ];
            $query = Db::name('product')
                ->field('id, productId, title, subtitle, type, image, price, deposit, deduct, version, content, purchaseNotice, stock, limitStock, startT, startTime, endT, endTime, shippingTemplateId, proportion, dimensions, material, copyright')
                ->where($where);
            apply_app_code_scope($query, 'product');
            $info = $query->find();
            
            // 如果产品有运费模板，获取模板信息
            if ($info && $info['shippingTemplateId']) {
                $shippingTemplate = Db::name('shipping_template')->where('id', $info['shippingTemplateId'])->find();
                if ($shippingTemplate) {
                    $info['shippingTemplate'] = $shippingTemplate;
                }
            }
            
            // 添加开始和结束时间戳（前端倒计时使用）
            if ($info && isset($info['startT'])) {
                $info['startTimeStamp'] = intval($info['startT']);
            }
            if ($info && isset($info['endT'])) {
                $info['endTimeStamp'] = intval($info['endT']);
            }
            if (!$info) {
                return json($data);
            }

            $domain = Request::domain();

            $images = json_decode($info['image'], true);
            $info['image'] = [];
            foreach ($images as $image) {
                $info['image'][] = $domain . $image;
            }

            $info['version'] = explode(',', $info['version']);

            $contents = json_decode($info['content'], true);
            $info['content'] = [];
            foreach ($contents as $content) {
                $info['content'][] = $domain . $content;
            }

            $purchaseNotices = json_decode($info['purchaseNotice'], true);
            $info['purchaseNotice'] = [];
            foreach ($purchaseNotices as $purchaseNotice) {
                $info['purchaseNotice'][] = $domain . $purchaseNotice;
            }

            $data['code'] = 200;
            $data['msg'] = "成功！";
            $data['data'] = $info;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}
