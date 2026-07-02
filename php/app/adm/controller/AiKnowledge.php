<?php

namespace app\adm\controller;

use app\common\service\KnowledgeIndexer;
use app\common\service\RagRetriever;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class AiKnowledge
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
        $title = trim((string) Request::get('title', ''));
        $sourceType = trim((string) Request::get('sourceType', ''));
        $reviewStatus = Request::get('reviewStatus', '');
        $hasReview = $this->reviewSupported();
        $query = Db::name('ai_knowledge_source')->order('id desc');
        if ($title !== '') {
            $query->where('title', 'like', '%' . $title . '%');
        }
        if ($sourceType !== '') {
            $query->where('sourceType', $sourceType);
        }
        if ($hasReview && $reviewStatus !== '') {
            $query->where('reviewStatus', intval($reviewStatus));
        }

        $list = $query->paginate(20, false, ['query' => request()->param()]);
        $rows = $list->items();
        foreach ($rows as &$row) {
            $row['chunkCount'] = Db::name('ai_knowledge_chunk')->where('sourceId', $row['id'])->count();
            $chunkIds = Db::name('ai_knowledge_chunk')->where('sourceId', $row['id'])->column('id');
            $row['pendingJobCount'] = empty($chunkIds)
                ? 0
                : Db::name('ai_embedding_job')->whereIn('chunkId', $chunkIds)->where('status', 0)->count();
            $row['reviewStatusText'] = $this->reviewStatusText(intval($row['reviewStatus'] ?? 2));
        }

        View::assign('list', $rows);
        View::assign('page', $list->render());
        View::assign('title', $title);
        View::assign('sourceType', $sourceType);
        View::assign('reviewStatus', $reviewStatus);
        View::assign('hasReview', $hasReview);
        View::assign('reviewStatusMap', $this->reviewStatusMap());
        return View::fetch();
    }

    public function add()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $title = trim((string) ($post['title'] ?? ''));
            $content = trim((string) ($post['content'] ?? ''));
            if ($title === '' || $content === '') {
                return json(['code' => 0, 'msg' => '标题和内容不能为空']);
            }

            $tempSourceId = intval(date('His') . mt_rand(100, 999));
            $insert = [
                'sourceType' => 'manual',
                'sourceId' => $tempSourceId,
                'app_code' => normalize_app_code_value($post['app_code'] ?? 'goomoo'),
                'title' => $title,
                'content' => $content,
                'status' => intval($post['status'] ?? 1),
                'contentHash' => hash('sha256', $title . "\n" . $content),
            ];
            if ($this->reviewSupported()) {
                $insert['reviewStatus'] = 1;
                $insert['reviewerId'] = 0;
                $insert['reviewerName'] = '';
                $insert['reviewedAt'] = null;
                $insert['reviewRemark'] = '';
            }
            $id = Db::name('ai_knowledge_source')->insertGetId($insert);
            Db::name('ai_knowledge_source')->where('id', $id)->update(['sourceId' => $id]);
            $this->queueManualSync((int) $id);
            return json(['code' => 1, 'msg' => '添加成功，待审核后才会进入向量库']);
        }

        View::assign('appCodeOptions', app_code_options());
        View::assign('selectedAppCode', 'goomoo');
        return View::fetch();
    }

    public function edit()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = intval($post['id'] ?? 0);
            $info = Db::name('ai_knowledge_source')->where('id', $id)->find();
            if (!$info || ($info['sourceType'] ?? '') !== 'manual') {
                return json(['code' => 0, 'msg' => '仅人工知识可编辑']);
            }

            $title = trim((string) ($post['title'] ?? ''));
            $content = trim((string) ($post['content'] ?? ''));
            if ($title === '' || $content === '') {
                return json(['code' => 0, 'msg' => '标题和内容不能为空']);
            }

            $contentHash = hash('sha256', $title . "\n" . $content);
            $update = [
                'app_code' => normalize_app_code_value($post['app_code'] ?? 'goomoo'),
                'title' => $title,
                'content' => $content,
                'status' => intval($post['status'] ?? 1),
                'contentHash' => $contentHash,
            ];
            if ($this->reviewSupported() && $contentHash !== (string) ($info['contentHash'] ?? '')) {
                $update['reviewStatus'] = 1;
                $update['reviewerId'] = 0;
                $update['reviewerName'] = '';
                $update['reviewedAt'] = null;
                $update['reviewRemark'] = '';
            }
            Db::name('ai_knowledge_source')->where('id', $id)->update($update);
            $this->queueManualSync($id);
            return json(['code' => 1, 'msg' => '保存成功' . (isset($update['reviewStatus']) ? '，内容变更后已重置为待审核' : '')]);
        }

        $id = intval(Request::get('id', 0));
        $info = Db::name('ai_knowledge_source')->where('id', $id)->find();
        if (!$info) {
            return json(['code' => 0, 'msg' => '知识不存在']);
        }
        View::assign('info', $info);
        View::assign('appCodeOptions', app_code_options());
        View::assign('selectedAppCode', normalize_app_code_value($info['app_code'] ?? 'goomoo'));
        return View::fetch();
    }

    public function approve()
    {
        return $this->review(2);
    }

    public function reject()
    {
        return $this->review(3);
    }

    public function del()
    {
        $id = intval(Request::post('id', 0));
        $info = Db::name('ai_knowledge_source')->where('id', $id)->find();
        if (!$info || ($info['sourceType'] ?? '') !== 'manual') {
            return json(['code' => 0, 'msg' => '仅人工知识可删除']);
        }
        Db::name('ai_knowledge_source')->where('id', $id)->delete();
        Db::name('ai_knowledge_chunk')->where('sourceId', $id)->delete();
        return json(['code' => 1, 'msg' => '删除成功']);
    }

    public function sync()
    {
        $id = intval(Request::post('id', Request::get('id', 0)));
        $result = (new KnowledgeIndexer())->enqueueChunks($id);
        $queued = intval($result['queued'] ?? 0);
        $msg = !empty($result['ok']) ? ('已处理，入队 ' . $queued . ' 个切片') : ($result['error'] ?? '同步失败');
        return json(['code' => !empty($result['ok']) ? 1 : 0, 'msg' => $msg]);
    }

    public function chunks()
    {
        $sourceId = intval(Request::get('id', 0));
        $source = Db::name('ai_knowledge_source')->where('id', $sourceId)->find();
        $chunks = Db::name('ai_knowledge_chunk')->where('sourceId', $sourceId)->order('chunkIndex asc')->select()->toArray();
        View::assign('source', $source);
        View::assign('chunks', $chunks);
        return View::fetch();
    }

    public function runJobs()
    {
        $limit = intval(Request::post('limit', 20));
        $result = (new KnowledgeIndexer())->runPendingJobs($limit);
        return json(['code' => !empty($result['ok']) ? 1 : 0, 'msg' => '处理 ' . intval($result['processed'] ?? 0) . ' 个，失败 ' . intval($result['failed'] ?? 0) . ' 个' . (!empty($result['error']) ? '：' . $result['error'] : '')]);
    }

    public function testSearch()
    {
        $question = trim((string) Request::post('question', ''));
        if ($question === '') {
            return json(['code' => 0, 'msg' => '请输入测试问题']);
        }
        $result = (new RagRetriever())->retrieve($question, [
            'app_code' => normalize_app_code_value(Request::post('app_code', 'goomoo')),
        ]);
        return json(['code' => 1, 'msg' => 'success', 'data' => $result]);
    }

    private function review(int $status)
    {
        if (!$this->reviewSupported()) {
            return json(['code' => 0, 'msg' => '审核字段尚未初始化，请先执行数据库 patch']);
        }
        $id = intval(Request::post('id', 0));
        $remark = trim((string) Request::post('remark', ''));
        $info = Db::name('ai_knowledge_source')->where('id', $id)->find();
        if (!$info || ($info['sourceType'] ?? '') !== 'manual') {
            return json(['code' => 0, 'msg' => '仅人工知识可审核']);
        }
        $update = [
            'reviewStatus' => $status,
            'reviewerId' => intval(Session::get('systemUserId')),
            'reviewerName' => (string) Session::get('name', 'admin'),
            'reviewedAt' => date('Y-m-d H:i:s'),
            'reviewRemark' => $remark,
        ];
        Db::name('ai_knowledge_source')->where('id', $id)->update($update);
        $this->queueManualSync($id);
        return json(['code' => 1, 'msg' => $status === 2 ? '审核已通过，已加入向量化队列' : '已驳回，未进入向量库']);
    }

    private function queueManualSync(int $sourceId): void
    {
        try {
            (new KnowledgeIndexer())->syncManualSource($sourceId);
        } catch (\Throwable $e) {
        }
    }

    private function reviewSupported(): bool
    {
        return function_exists('table_has_column') && table_has_column('ai_knowledge_source', 'reviewStatus');
    }

    private function reviewStatusMap(): array
    {
        return [1 => '待审核', 2 => '已通过', 3 => '已驳回'];
    }

    private function reviewStatusText(int $status): string
    {
        $map = $this->reviewStatusMap();
        return $map[$status] ?? '未设置';
    }
}