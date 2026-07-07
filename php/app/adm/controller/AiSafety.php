<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class AiSafety
{
    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }

    public function words()
    {
        $appCode = trim((string) Request::get('app_code', ''));
        $category = trim((string) Request::get('category', ''));
        $keyword = trim((string) Request::get('keyword', ''));
        $status = Request::get('status', '');

        $query = Db::name('ai_sensitive_word')->order('id desc');
        if ($appCode !== '') {
            $query->where('app_code', normalize_app_code_value($appCode));
        }
        if ($category !== '') {
            $query->where('category', $category);
        }
        if ($keyword !== '') {
            $query->where('word', 'like', '%' . $keyword . '%');
        }
        if ($status !== '') {
            $query->where('status', intval($status));
        }

        $list = $query->paginate(20, false, ['query' => request()->param()]);
        View::assign('list', $list->items());
        View::assign('page', $list->render());
        View::assign('appCodeOptions', app_code_options());
        View::assign('app_code', $appCode);
        View::assign('category', $category);
        View::assign('keyword', $keyword);
        View::assign('status', $status);
        return View::fetch();
    }
    public function wordAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $word = trim((string) ($post['word'] ?? ''));
            if ($word === '') {
                return json(['code' => 0, 'msg' => '敏感词不能为空']);
            }
            Db::name('ai_sensitive_word')->insert([
                'app_code' => normalize_app_code_value($post['app_code'] ?? 'common'),
                'word' => $word,
                'category' => trim((string) ($post['category'] ?? '')),
                'level' => max(1, intval($post['level'] ?? 1)),
                'action' => $this->normalizeAction($post['action'] ?? 'block'),
                'status' => intval($post['status'] ?? 1),
                'remark' => trim((string) ($post['remark'] ?? '')),
                'createTime' => date('Y-m-d H:i:s'),
                'updateTime' => date('Y-m-d H:i:s'),
            ]);
            return json(['code' => 1, 'msg' => '添加成功']);
        }

        View::assign('appCodeOptions', app_code_options());
        View::assign('selectedAppCode', 'common');
        return View::fetch('word_add');
    }

    public function wordEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = intval($post['id'] ?? 0);
            $word = trim((string) ($post['word'] ?? ''));
            if ($id <= 0 || $word === '') {
                return json(['code' => 0, 'msg' => '参数不完整']);
            }
            $res = Db::name('ai_sensitive_word')->where('id', $id)->update([
                'app_code' => normalize_app_code_value($post['app_code'] ?? 'common'),
                'word' => $word,
                'category' => trim((string) ($post['category'] ?? '')),
                'level' => max(1, intval($post['level'] ?? 1)),
                'action' => $this->normalizeAction($post['action'] ?? 'block'),
                'status' => intval($post['status'] ?? 1),
                'remark' => trim((string) ($post['remark'] ?? '')),
                'updateTime' => date('Y-m-d H:i:s'),
            ]);
            return json(['code' => $res === false ? 0 : 1, 'msg' => $res === false ? '保存失败' : '保存成功']);
        }

        $id = intval(Request::get('id', 0));
        $info = Db::name('ai_sensitive_word')->where('id', $id)->find();
        if (!$info) {
            return json(['code' => 0, 'msg' => '敏感词不存在']);
        }
        View::assign('info', $info);
        View::assign('appCodeOptions', app_code_options());
        View::assign('selectedAppCode', normalize_app_code_value($info['app_code'] ?? 'common'));
        return View::fetch('word_edit');
    }

    public function wordStatus()
    {
        $id = intval(Request::post('id', 0));
        $status = intval(Request::post('status', 1));
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '缺少ID']);
        }
        $res = Db::name('ai_sensitive_word')->where('id', $id)->update([
            'status' => $status === 1 ? 1 : 2,
            'updateTime' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => $res === false ? 0 : 1, 'msg' => $res === false ? '操作失败' : '操作成功']);
    }

    public function wordDelete()
    {
        $id = intval(Request::post('id', 0));
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '缺少ID']);
        }
        $res = Db::name('ai_sensitive_word')->where('id', $id)->delete();
        return json(['code' => $res ? 1 : 0, 'msg' => $res ? '删除成功' : '删除失败']);
    }

    public function logs()
    {
        $appCode = trim((string) Request::get('app_code', ''));
        $action = trim((string) Request::get('action', ''));
        $category = trim((string) Request::get('category', ''));
        $keyword = trim((string) Request::get('keyword', ''));
        $finalRoute = trim((string) Request::get('finalRoute', ''));
        $reviewStatus = Request::get('reviewStatus', '');
        $hasBoundaryTrace = $this->tableHasColumn('ai_safety_log', 'finalRoute');
        $hasReviewStatus = $this->tableHasColumn('ai_safety_log', 'reviewStatus');

        $query = Db::name('ai_safety_log')->order('id desc');
        if ($appCode !== '') {
            $query->where('app_code', normalize_app_code_value($appCode));
        }
        if ($action !== '') {
            $query->where('action', $action);
        }
        if ($category !== '') {
            $query->where('category', 'like', '%' . $category . '%');
        }
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('question', 'like', '%' . $keyword . '%')
                    ->whereOr('reply', 'like', '%' . $keyword . '%')
                    ->whereOr('hitWords', 'like', '%' . $keyword . '%');
            });
        }
        if ($hasBoundaryTrace && $finalRoute !== '') {
            $query->where('finalRoute', $finalRoute);
        }
        if ($hasReviewStatus && $reviewStatus !== '') {
            $query->where('reviewStatus', intval($reviewStatus));
        }

        $list = $query->paginate(20, false, ['query' => request()->param()]);
        $rows = $list->items();
        $reviewMap = $this->reviewStatusMap();
        foreach ($rows as &$row) {
            $row['reviewStatusText'] = $reviewMap[intval($row['reviewStatus'] ?? 0)] ?? '未复盘';
        }
        View::assign('list', $rows);
        View::assign('page', $list->render());
        View::assign('appCodeOptions', app_code_options());
        View::assign('app_code', $appCode);
        View::assign('action', $action);
        View::assign('category', $category);
        View::assign('keyword', $keyword);
        View::assign('finalRoute', $finalRoute);
        View::assign('reviewStatus', $reviewStatus);
        View::assign('hasBoundaryTrace', $hasBoundaryTrace);
        View::assign('hasReviewStatus', $hasReviewStatus);
        View::assign('reviewStatusMap', $this->reviewStatusMap());
        return View::fetch();
    }

    public function logReviewStatus()
    {
        if (!$this->tableHasColumn('ai_safety_log', 'reviewStatus')) {
            return json(['code' => 0, 'msg' => '复盘字段尚未初始化，请先执行数据库 patch']);
        }
        $id = intval(Request::post('id', 0));
        $status = intval(Request::post('reviewStatus', 0));
        if ($id <= 0 || !array_key_exists($status, $this->reviewStatusMap())) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        $res = Db::name('ai_safety_log')->where('id', $id)->update(['reviewStatus' => $status]);
        return json(['code' => $res === false ? 0 : 1, 'msg' => $res === false ? '标记失败' : '标记成功']);
    }

    private function reviewStatusMap(): array
    {
        return [0 => '未复盘', 1 => '误杀', 2 => '漏拦截', 3 => '已补词', 4 => '已转人工'];
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return function_exists('table_has_column') && table_has_column($table, $column);
    }
    private function normalizeAction($action): string
    {
        $action = strtolower(trim((string) $action));
        return in_array($action, ['block', 'transfer', 'allow'], true) ? $action : 'block';
    }
}