<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class Xhs
{
    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    public function bind()
    {
        $orderId = trim((string) Request::get('orderId', ''));
        $phone = trim((string) Request::get('phone', ''));
        $status = Request::get('status', '0');

        $query = Db::name('xhs_user_bind')
            ->alias('b')
            ->leftJoin('user u', 'b.userId = u.id')
            ->field('b.*, u.nickName, u.phone as userPhone, u.snailShells');

        if ($orderId !== '') {
            $query->where('b.firstOrderId', 'like', '%' . $orderId . '%');
        }
        if ($phone !== '') {
            $query->where(function ($q) use ($phone) {
                $q->where('b.phone', 'like', '%' . $phone . '%')
                    ->whereOr('u.phone', 'like', '%' . $phone . '%');
            });
        }
        if ($status !== '') {
            $query->where('b.status', intval($status));
        }

        $list = $query->orderRaw('b.status = 0 desc, b.id desc')->paginate(20, false, [
            'query' => request()->param(),
        ]);

        View::assign('page', $list->render());
        $rows = $list->toArray()['data'];
        foreach ($rows as &$row) {
            $row['statusValue'] = intval($row['status'] ?? 0);
            $row['statusText'] = $this->bindStatusText($row['statusValue']);
            $row['statusColor'] = $this->bindStatusColor($row['statusValue']);
            $row['phoneDisplay'] = $row['phone'] ?: ($row['userPhone'] ?? '');
            $row['nickName'] = $row['nickName'] ?: '-';
            $row['snailShells'] = intval($row['snailShells'] ?? 0);
        }
        unset($row);

        View::assign('list', $rows);
        View::assign('orderId', $orderId);
        View::assign('phone', $phone);
        View::assign('status', $status);
        View::assign('statusMap', $this->bindStatusMap());
        return View::fetch();
    }

    public function review()
    {
        if (!Request::isPost()) {
            return json(['code' => 0, 'msg' => 'invalid request']);
        }

        $id = intval(Request::post('id', 0));
        $action = strtolower(trim((string) Request::post('action', 'approve')));
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '缺少绑定记录ID']);
        }

        $bind = Db::name('xhs_user_bind')->where('id', $id)->find();
        if (!$bind) {
            return json(['code' => 0, 'msg' => '绑定记录不存在']);
        }

        $status = in_array($action, ['reject', 'rejected', 'deny'], true) ? -1 : 1;
        $update = [
            'status' => $status,
            'updateTime' => date('Y-m-d H:i:s'),
        ];
        if ($status === 1 && empty($bind['bindTime'])) {
            $update['bindTime'] = date('Y-m-d H:i:s');
        }

        $res = Db::name('xhs_user_bind')->where('id', $id)->update($update);
        if ($res === false) {
            return json(['code' => 0, 'msg' => '审核操作失败']);
        }

        return json([
            'code' => 1,
            'msg' => $status === 1 ? '审核已通过' : '审核已拒绝',
            'data' => [
                'id' => $id,
                'status' => $status,
                'statusText' => $this->bindStatusText($status),
            ],
        ]);
    }

    private function bindStatusMap()
    {
        return [
            -1 => '已拒绝',
            0 => '待审核',
            1 => '已通过',
        ];
    }

    private function bindStatusText($status)
    {
        $map = $this->bindStatusMap();
        return $map[intval($status)] ?? '未知';
    }

    private function bindStatusColor($status)
    {
        $status = intval($status);
        if ($status === 1) {
            return '#1ccf00';
        }
        if ($status === -1) {
            return '#dc0000';
        }
        return '#f39c12';
    }
}