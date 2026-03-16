<?php

namespace app\adm\controller;

use Exception;
use think\facade\Request;

class Upfile
{

    public function image()
    {
        $data['code'] = 0;
        $data['msg'] = '上传失败，请联系客服！';
        try {
            if (Request::isPost()) {

                if ($_FILES['file']['tmp_name'] !== false) {

                    $srcFile = $_FILES['file']['tmp_name'];
                    $imageType = exif_imagetype($srcFile);

                    $supportType = [
                        IMAGETYPE_JPEG,
                        IMAGETYPE_PNG
                    ];

                    if (!in_array($imageType, $supportType, true)) {
                        $data['msg'] = '图片格式不正确！';
                        return json($data);
                    }

                    $nameArr = explode('.', $_FILES['file']['name']);

                    $filePath = 'uploads/' . date('Ymd');
                    if (!is_dir($filePath)) {
                        mkdir($filePath, 0777, TRUE);
                    }

                    $fileName = $filePath . '/' . round(microtime(true) * 1000) . '.' . $nameArr[1];

                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $fileName)) {
                        $data['code'] = 1;
                        $data['msg'] = '上传成功！';
                        $data['data'] = '/' . $fileName;
                        return json($data);
                    }
                }
            }
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}