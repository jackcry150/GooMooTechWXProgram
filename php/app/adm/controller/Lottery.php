<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class Lottery
{
    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    private function formatImage($image)
    {
        if (!$image) {
            return '';
        }
        if (strpos($image, 'http') === 0) {
            return $image;
        }
        return Request::domain() . $image;
    }

    public function index()
    {
        $name = Request::get('name', '');
        $status = Request::get('status', '');
        $where = [];

        if ($name !== '') {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($status !== '' && $status !== null) {
            $where[] = ['status', '=', $status];
        }

        $list = Db::name('lottery_prize')
            ->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, ['query' => request()->param()]);

        View::assign('page', $list->render());
        $rows = $list->toArray()['data'];
        foreach ($rows as &$row) {
            $row['imageDisplay'] = $this->formatImage($row['image']);
            $row['rewardTypeText'] = lotteryRewardType($row['rewardType']);
            $row['statusText'] = status($row['status']);
            $row['stockText'] = lotteryStockText($row['stock']);
        }
        unset($row);

        View::assign('list', $rows);
        View::assign('name', $name);
        View::assign('status', $status);
        View::assign('statusType', status());
        return View::fetch();
    }

    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $res = Db::name('lottery_prize')->insert($post);
            return json([
                'code' => $res ? 1 : 0,
                'msg' => $res ? '添加成功！' : '添加失败！',
            ]);
        }

        View::assign('rewardType', lotteryRewardType());
        View::assign('statusType', status());
        return View::fetch();
    }

    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            if (!$id) {
                return json(['code' => 0, 'msg' => '参数错误！']);
            }
            unset($post['id']);
            $res = Db::name('lottery_prize')->where('id', $id)->update($post);
            return json([
                'code' => $res !== false ? 1 : 0,
                'msg' => $res !== false ? '修改成功！' : '修改失败！',
            ]);
        }

        $id = intval(Request::get('id', 0));
        $info = Db::name('lottery_prize')->where('id', $id)->find();
        if (!$info) {
            return '奖品不存在';
        }
        $info['imageDisplay'] = $this->formatImage($info['image']);
        View::assign('info', $info);
        View::assign('rewardType', lotteryRewardType());
        View::assign('statusType', status());
        return View::fetch();
    }

    public function del()
    {
        if (!Request::isPost()) {
            return json(['code' => 0, 'msg' => '请求错误']);
        }
        $id = intval(Request::post('id', 0));
        $res = Db::name('lottery_prize')->where('id', $id)->delete();
        return json([
            'code' => $res ? 1 : 0,
            'msg' => $res ? '删除成功！' : '删除失败！',
        ]);
    }

    public function record()
    {
        $nickName = Request::get('nickName', '');
        $prizeName = Request::get('prizeName', '');

        $list = Db::name('lottery_record')
            ->alias('r')
            ->leftJoin('user u', 'u.id = r.userId')
            ->field('r.*, u.nickName')
            ->when($nickName !== '', function ($query) use ($nickName) {
                $query->where('u.nickName', 'like', '%' . $nickName . '%');
            })
            ->when($prizeName !== '', function ($query) use ($prizeName) {
                $query->where('r.prizeName', 'like', '%' . $prizeName . '%');
            })
            ->order('r.id desc')
            ->paginate(20, false, ['query' => request()->param()]);

        View::assign('page', $list->render());
        $rows = $list->toArray()['data'];
        foreach ($rows as &$row) {
            $row['rewardTypeText'] = lotteryRewardType($row['rewardType']);
            $row['prizeImageDisplay'] = $this->formatImage($row['prizeImage']);
            $row['nickName'] = $row['nickName'] ?: '-';
        }
        unset($row);

        View::assign('list', $rows);
        View::assign('nickName', $nickName);
        View::assign('prizeName', $prizeName);
        return View::fetch();
    }
}
