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
        $query = Db::name('ai_knowledge_source')->order('id desc');
        if ($title !== '') {
            $query->where('title', 'like', '%' . $title . '%');
        }
        if ($sourceType !== '') {
            $query->where('sourceType', $sourceType);
        }

        $list = $query->paginate(20, false, ['query' => request()->param()]);
        $rows = $list->items();
        foreach ($rows as &$row) {
            $row['chunkCount'] = Db::name('ai_knowledge_chunk')->where('sourceId', $row['id'])->count();
            $chunkIds = Db::name('ai_knowledge_chunk')->where('sourceId', $row['id'])->column('id');
            $row['pendingJobCount'] = empty($chunkIds)
                ? 0
                : Db::name('ai_embedding_job')->whereIn('chunkId', $chunkIds)->where('status', 0)->count();
        }

        View::assign('list', $rows);
        View::assign('page', $list->render());
        View::assign('title', $title);
        View::assign('sourceType', $sourceType);
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
            $id = Db::name('ai_knowledge_source')->insertGetId([
                'sourceType' => 'manual',
                'sourceId' => $tempSourceId,
                'app_code' => normalize_app_code_value($post['app_code'] ?? 'goomoo'),
                'title' => $title,
                'content' => $content,
                'status' => intval($post['status'] ?? 1),
                'contentHash' => hash('sha256', $title . "\n" . $content),
            ]);
            Db::name('ai_knowledge_source')->where('id', $id)->update(['sourceId' => $id]);
            $this->queueManualSync((int) $id);
            return json(['code' => 1, 'msg' => '添加成功']);
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

            Db::name('ai_knowledge_source')->where('id', $id)->update([
                'app_code' => normalize_app_code_value($post['app_code'] ?? 'goomoo'),
                'title' => $title,
                'content' => $content,
                'status' => intval($post['status'] ?? 1),
                'contentHash' => hash('sha256', $title . "\n" . $content),
            ]);
            $this->queueManualSync($id);
            return json(['code' => 1, 'msg' => '保存成功']);
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
        return json(['code' => !empty($result['ok']) ? 1 : 0, 'msg' => !empty($result['ok']) ? '已加入向量化队列' : ($result['error'] ?? '同步失败')]);
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

    private function queueManualSync(int $sourceId): void
    {
        try {
            (new KnowledgeIndexer())->syncManualSource($sourceId);
        } catch (\Throwable $e) {
        }
    }
}
