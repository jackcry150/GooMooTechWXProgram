<?php

namespace app\api\controller;

use think\facade\Db;
use think\facade\Request;

class Sell
{
    /**
     * 查询产品表中「是否特别贩售」的最后一条，用于前端 sell 页
     */
    public function latest()
    {
        $data = ['code' => 0, 'msg' => '暂无贩售', 'data' => null];

        $row = Db::name('product')
            ->field('id, productId, stock, status as productStatus, promoImages, reservationNotice, startTime, endTime, sort')
            ->where('isSpecialSale', 1)
            ->where('status', 1)
            ->order('sort desc, id desc')
            ->limit(1)
            ->find();

        if (!$row) {
            return json($data);
        }

        $row['productId'] = $row['id']; // 产品ID，用于跳转商品详情
        $row['sellStatus'] = 1;
        $domain = Request::domain();

        $promo = [];
        if (!empty($row['promoImages'])) {
            $arr = json_decode($row['promoImages'], true);
            if (is_array($arr)) {
                foreach ($arr as $u) {
                    $promo[] = strpos($u, 'http') === 0 ? $u : $domain . $u;
                }
            }
        }
        $row['promoImages'] = $promo;

        $notice = [];
        if (!empty($row['reservationNotice'])) {
            $arr = json_decode($row['reservationNotice'], true);
            if (is_array($arr)) {
                foreach ($arr as $u) {
                    $notice[] = strpos($u, 'http') === 0 ? $u : $domain . $u;
                }
            }
        }
        $row['reservationNotice'] = $notice;

        if (!empty($row['productImage'])) {
            $imgs = json_decode($row['productImage'], true);
            $row['productImage'] = is_array($imgs) && !empty($imgs) ? (strpos($imgs[0], 'http') === 0 ? $imgs[0] : $domain . $imgs[0]) : '';
        } else {
            $row['productImage'] = '';
        }

        $data['code'] = 1;
        $data['msg'] = '成功';
        $data['data'] = $row;
        return json($data);
    }
}
