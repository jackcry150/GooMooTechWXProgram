<?php

namespace app\adm\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Product
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
        $type = Request::get('type');
        $title = Request::get('title');
        $mode = Request::get('mode');
        $isSpecialSale = Request::get('isSpecialSale');
        $where = [];
        if ($type !== '' && $type !== null) {
            $where[] = ['type', '=', $type];
        }
        if ($title) {
            $where[] = ['title', 'like', '%' . $title . '%'];
        }
        if ($mode !== '' && $mode !== null) {
            $where[] = ['mode', '=', $mode];
        }
        if ($isSpecialSale !== '' && $isSpecialSale !== null) {
            $where[] = ['isSpecialSale', '=', $isSpecialSale];
        }
        $list = Db::name('product')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];

        // 处理图片和域名
        $domain = Request::domain();
        foreach ($list as &$val) {
            $val['type'] = productType($val['type']);
            $val['mode'] = productMode($val['mode']);
            $val['statusText'] = status($val['status'] ?? 1);
            $val['statusValue'] = $val['status'] ?? 1;
            $val['isSpecialSaleText'] = ($val['isSpecialSale'] ?? 0) == 1 ? '是' : '否';

            // 处理主图：从JSON中提取第一张图片
            $val['mainImage'] = '';
            if (!empty($val['image'])) {
                $images = json_decode($val['image'], true);
                if (is_array($images) && !empty($images)) {
                    $firstImage = $images[0];
                    if (strpos($firstImage, 'http') === false) {
                        $val['mainImage'] = $domain . $firstImage;
                    } else {
                        $val['mainImage'] = $firstImage;
                    }
                }
            }
        }

        View::assign('list', $list);

        View::assign('type', $type);
        View::assign('productType', productType());
        View::assign('title', $title);
        View::assign('statusType', status());
        View::assign('mode', $mode);
        View::assign('productMode', productMode());
        View::assign('isSpecialSale', $isSpecialSale);

        return View::fetch();
    }

    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $post['startT'] = strtotime($post['startTime']);
            $post['endT'] = strtotime($post['endTime']);
            // 新添加的商品默认上架
            if (!isset($post['status'])) {
                $post['status'] = 1;
            }
            $res = Db::name('product')->insert($post);
            if ($res) {
                $data['msg'] = '添加成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '添加失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {

            $productId = Request::get('iidd');
            $fullName = Request::get('fullname');
            $name = Request::get('name');
            $price = Request::get('price');

            View::assign('productId', $productId);
            View::assign('fullName', $fullName);
            View::assign('name', $name);
            View::assign('price', $price);

            // 获取运费模板列表
            $shippingTemplates = Db::name('shipping_template')->field('id, name')->order('id desc')->select()->toArray();
            View::assign('shippingTemplates', $shippingTemplates);

            View::assign('productType', productType());
            View::assign('statusType', status());
            View::assign('productMode', productMode());

            return View::fetch();
        }
    }

    public function del()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('product')->delete($post);
            if ($res) {
                $data['msg'] = '成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '失败！';
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
            $id = $post['id'] ?? 0;

            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            unset($post['id']);
            $post['startT'] = strtotime($post['startTime']);
            $post['endT'] = strtotime($post['endTime']);
            $res = Db::name('product')->where('id', $id)->update($post);
            if ($res) {
                $data['msg'] = '修改成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '修改失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            $id = Request::get('id');
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            $info = Db::name('product')->where('id', $id)->find();
            if (!$info) {
                $data['msg'] = '商品不存在！';
                $data['code'] = 0;
                return json($data);
            }



            // 处理图片数据，转换为数组格式供前端使用
            $info['imageArray'] = [];
            $info['contentArray'] = [];
            $info['purchaseNoticeArray'] = [];

            if ($info['image']) {
                $images = json_decode($info['image'], true);
                if (is_array($images)) {
                    foreach ($images as $imgUrl) {
                        $info['imageArray'][] = ['url' => $imgUrl];
                    }
                }
            }

            if ($info['content']) {
                $contents = json_decode($info['content'], true);
                if (is_array($contents)) {
                    foreach ($contents as $imgUrl) {
                        $info['contentArray'][] = ['url' => $imgUrl];
                    }
                }
            }

            if ($info['purchaseNotice']) {
                $purchaseNotices = json_decode($info['purchaseNotice'], true);
                if (is_array($purchaseNotices)) {
                    foreach ($purchaseNotices as $imgUrl) {
                        $info['purchaseNoticeArray'][] = ['url' => $imgUrl];
                    }
                }
            }

            $info['promoImagesArray'] = [];
            $info['reservationNoticeArray'] = [];
            if (!empty($info['promoImages'])) {
                $imgs = json_decode($info['promoImages'], true);
                if (is_array($imgs)) {
                    foreach ($imgs as $idx => $url) {
                        $info['promoImagesArray'][] = ['id' => 'p' . $idx, 'url' => $url, 'name' => ''];
                    }
                }
            }
            if (!empty($info['reservationNotice'])) {
                $imgs = json_decode($info['reservationNotice'], true);
                if (is_array($imgs)) {
                    foreach ($imgs as $idx => $url) {
                        $info['reservationNoticeArray'][] = ['id' => 'r' . $idx, 'url' => $url, 'name' => ''];
                    }
                }
            }
            $info['promoImagesArrayJson'] = json_encode($info['promoImagesArray']);
            $info['reservationNoticeArrayJson'] = json_encode($info['reservationNoticeArray']);

            // 将数组转换为JSON字符串供前端使用
            $info['imageArrayJson'] = json_encode($info['imageArray']);
            $info['contentArrayJson'] = json_encode($info['contentArray']);
            $info['purchaseNoticeArrayJson'] = json_encode($info['purchaseNoticeArray']);

            // 获取运费模板列表
            $shippingTemplates = Db::name('shipping_template')->field('id, name')->order('id desc')->select()->toArray();
            View::assign('shippingTemplates', $shippingTemplates);

            View::assign('info', $info);
            View::assign('productType', productType());
            View::assign('statusType', status());
            View::assign('productMode', productMode());

            return View::fetch();
        }
    }

    public function sell()
    {
        $type = Request::get('type');
        $title = Request::get('title');
        $status = Request::get('status');
        $mode = Request::get('mode');
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
        if ($mode) {
            $searchWhere = [
                [
                    'mode',
                    '=',
                    $mode
                ]
            ];
            $where = array_merge($where, $searchWhere);
        }
        $list = Db::name('product_sell')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        foreach ($list as &$val) {
            $val['type'] = productType($val['type']);
            $val['status'] = status($val['status']);
            $val['mode'] = productMode($val['mode']);
        }

        View::assign('list', $list);

        View::assign('type', $type);
        View::assign('productType', productType());
        View::assign('title', $title);
        View::assign('status', $status);
        View::assign('statusType', status());
        View::assign('mode', $mode);
        View::assign('productMode', productMode());

        return View::fetch();
    }

    public function sellAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('product_sell')->insert($post);
            if ($res) {
                $data['msg'] = '添加成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '添加失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {

            View::assign('productType', productType());

            return View::fetch();
        }
    }

    public function sellDel()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('product_sell')->delete($post);
            if ($res) {
                $data['msg'] = '成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    public function sellEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('product_sell')->where('id', $post['id'])->update($post);
            if ($res) {
                $data['msg'] = '修改成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '修改失败！';
                $data['code'] = 0;
                return json($data);
            }
        } else {

            $id = Request::get('id');
            $info = Db::name('product_sell')->where('id', $id)->find();
            View::assign('info', $info);

            View::assign('adType', adType());
            View::assign('statusType', status());

            return View::fetch();
        }
    }

    /**
     * 商品上架
     */
    public function onSale()
    {
        if (Request::isPost()) {
            $id = Request::post('id', 0);
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            $res = Db::name('product')->where('id', $id)->update(['status' => 1]);
            if ($res) {
                $data['msg'] = '上架成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '上架失败！';
                $data['code'] = 0;
                return json($data);
            }
        }
    }

    /**
     * 商品下架
     */
    public function offSale()
    {
        if (Request::isPost()) {
            $id = Request::post('id', 0);
            if (!$id) {
                $data['msg'] = '参数错误！';
                $data['code'] = 0;
                return json($data);
            }

            $res = Db::name('product')->where('id', $id)->update(['status' => 2]);
            if ($res) {
                $data['msg'] = '下架成功！';
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = '下架失败！';
                $data['code'] = 0;
                return json($data);
            }
        }
    }

    public function gjp()
    {
        // 获取搜索参数
        $perPage = Request::get('perPage', 20);
        $page = Request::get('page', 1);
        $startTime = Request::get('startTime', date('Y-m-d H:i:s', strtotime('-1 year')));
        $endTime = Request::get('endTime', date('Y-m-d H:i:s'));

        // 验证每页数量：默认20，最大值100
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        // 验证页数：从1开始
        if ($page < 1) {
            $page = 1;
        }

        $data = [
            'perPage' => $perPage,
            'page' => $page,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ];

        $list = [];
        // 调用外部API获取商品列表
        try {
            $token = Cache::get('token');
            if ($token) {
                $Guanjiapo = new \Guanjiapo();
                $goodsList = $Guanjiapo->goodsList($token, $data);
                // $goodsList = Cache::get('goodsList1');
                if ($goodsList && $goodsList['iserror'] === false && isset($goodsList['response'])) {
                    $list = $goodsList['response']['ptypes'] ?? [];
                }
            }
        } catch (\Exception $e) {
            $list = [];
        }

        View::assign('list', $list);
        View::assign('perPage', $perPage);
        View::assign('page', $page);
        View::assign('startTime', $startTime);
        View::assign('endTime', $endTime);

        return View::fetch();
    }
}