<?php

namespace app\adm\controller;

use Exception;
use think\facade\Request;

class Upfile
{
    public function image()
    {
        $data = [
            'code' => 0,
            'msg' => '上传失败，请联系管理员',
        ];

        try {
            if (!Request::isPost()) {
                return json($data);
            }

            if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
                $data['msg'] = '未接收到上传文件';
                return json($data);
            }

            $file = $_FILES['file'];
            if (!isset($file['error']) || intval($file['error']) !== UPLOAD_ERR_OK) {
                $data['msg'] = $this->uploadErrorMessage(intval($file['error'] ?? -1));
                return json($data);
            }

            $tmpName = $file['tmp_name'] ?? '';
            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                $data['msg'] = '上传临时文件无效';
                return json($data);
            }

            $imageInfo = @getimagesize($tmpName);
            if (!$imageInfo || empty($imageInfo['mime'])) {
                $data['msg'] = '图片文件无效';
                return json($data);
            }

            $mimeMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];
            $mime = strtolower((string) $imageInfo['mime']);
            if (!isset($mimeMap[$mime])) {
                $data['msg'] = '仅支持 JPG 和 PNG 图片';
                return json($data);
            }

            $ext = $mimeMap[$mime];
            $relativeDir = 'uploads/' . date('Ymd');
            $publicRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public';
            $targetDir = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

            if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                $data['msg'] = '创建上传目录失败';
                return json($data);
            }

            if (!is_writable($targetDir)) {
                $data['msg'] = '上传目录不可写';
                return json($data);
            }

            $fileName = round(microtime(true) * 1000) . mt_rand(1000, 9999) . '.' . $ext;
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                $data['msg'] = '保存上传文件失败';
                return json($data);
            }

            $data['code'] = 1;
            $data['msg'] = '上传成功';
            $data['data'] = '/' . $relativeDir . '/' . $fileName;
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    private function uploadErrorMessage($errorCode)
    {
        $map = [
            UPLOAD_ERR_INI_SIZE => '上传文件超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '上传文件超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件上传不完整',
            UPLOAD_ERR_NO_FILE => '请选择要上传的文件',
            UPLOAD_ERR_NO_TMP_DIR => '服务器缺少临时目录',
            UPLOAD_ERR_CANT_WRITE => '服务器写入文件失败',
            UPLOAD_ERR_EXTENSION => '上传被服务器扩展拦截',
        ];

        return $map[$errorCode] ?? '上传失败，请重试';
    }
}
