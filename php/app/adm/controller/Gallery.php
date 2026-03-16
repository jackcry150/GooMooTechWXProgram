<?php

namespace app\adm\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use think\facade\Session;

class Gallery
{
    public function __construct()
    {
        $systemUserId = Session::get('systemUserId');
        if (!$systemUserId) {
            header('Location: /adm/login');
            exit;
        }
    }
    
    /**
     * 图库分类列表
     */
    public function category()
    {
        $categoryName = Request::get('categoryName');
        $where = [];

        if ($categoryName) {
            $where[] = ['categoryName', 'like', '%' . $categoryName . '%'];
        }

        $list = Db::name('gallery_category')->where($where)
            ->order('sort desc, id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];

        // 获取每个分类的封面图和图片数量
        foreach ($list as &$category) {
            // 获取该分类的第一张图片作为封面
            $coverImage = Db::name('gallery')
                ->where('categoryId', $category['id'])
                ->order('id desc')
                ->value('imageUrl');
            $category['coverImage'] = $coverImage ? $coverImage : '/static/images/default_avatar.jpg';
            
            // 获取该分类的图片数量
            $category['imageCount'] = Db::name('gallery')
                ->where('categoryId', $category['id'])
                ->count();
        }

        View::assign('list', $list);
        View::assign('categoryName', $categoryName);

        return View::fetch();
    }

    /**
     * 添加图库分类
     */
    public function categoryAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();

            $res = Db::name('gallery_category')->insert($post);
            if ($res) {
                $data['msg'] = "添加成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "添加失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    /**
     * 编辑图库分类
     */
    public function categoryEdit()
    {
        if (Request::isPost()) {
            $post = Request::post();

            $res = Db::name('gallery_category')->where('id', $post['id'])->update($post);
            if ($res) {
                $data['msg'] = "修改成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "修改失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            $id = Request::get('id');
            $info = Db::name('gallery_category')->where('id', $id)->find();
            View::assign('info', $info);

            return View::fetch();
        }
    }

    /**
     * 删除图库分类
     */
    public function categoryDel()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            
            // 检查该分类下是否有图片
            $imageCount = Db::name('gallery')->where('categoryId', $id)->count();
            if ($imageCount > 0) {
                $data['msg'] = "该分类下还有图片，无法删除！";
                $data['code'] = 0;
                return json($data);
            }
            
            $res = Db::name('gallery_category')->delete($id);
            if ($res) {
                $data['msg'] = "删除成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "删除失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    /**
     * 图库图片列表
     */
    public function image()
    {
        $categoryId = Request::get('categoryId');
        $imageName = Request::get('imageName');
        $where = [];

        if ($categoryId) {
            $where[] = ['categoryId', '=', $categoryId];
        }
        if ($imageName) {
            $where[] = ['imageName', 'like', '%' . $imageName . '%'];
        }

        $list = Db::name('gallery')->where($where)
            ->order('id desc')
            ->paginate(20, false, [
                'query' => request()->param()
            ]);

        View::assign('page', $list->render());

        $list = $list->toArray();
        $list = $list['data'];
        
        // 获取分类信息
        $categoryList = Db::name('gallery_category')->select()->toArray();
        $categoryMap = [];
        foreach ($categoryList as $category) {
            $categoryMap[$category['id']] = $category['categoryName'];
        }
        
        foreach ($list as &$val) {
            $val['categoryName'] = $categoryMap[$val['categoryId']] ?? '未分类';
        }

        View::assign('list', $list);
        View::assign('categoryId', $categoryId);
        View::assign('imageName', $imageName);
        View::assign('categoryList', $categoryList);

        return View::fetch();
    }

    /**
     * 添加图库图片
     */
    public function imageAdd()
    {
        if (Request::isPost()) {
            $post = Request::post();

            $res = Db::name('gallery')->insert($post);
            if ($res) {
                $data['msg'] = "添加成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "添加失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            // 获取分类列表
            $categoryList = Db::name('gallery_category')->order('sort desc, id desc')->select()->toArray();
            View::assign('categoryList', $categoryList);

            return View::fetch();
        }
    }

    /**
     * 删除图库图片
     */
    public function imageDel()
    {
        if (Request::isPost()) {
            $post = Request::post();
            $id = $post['id'] ?? 0;
            
            if (!$id) {
                $data['msg'] = "参数错误！";
                $data['code'] = 0;
                return json($data);
            }
            
            // 获取图片信息
            $imageInfo = Db::name('gallery')->where('id', $id)->find();
            if (!$imageInfo) {
                $data['msg'] = "图片不存在！";
                $data['code'] = 0;
                return json($data);
            }
            
            // 删除文件
            $fileDeleted = false;
            if ($imageInfo['imageUrl']) {
                // 处理文件路径：支持 /uploads/... 或 uploads/... 格式
                $imageUrl = $imageInfo['imageUrl'];
                if (strpos($imageUrl, '/') === 0) {
                    // 以 / 开头，去掉第一个 /
                    $filePath = '.' . $imageUrl;
                } else {
                    // 不以 / 开头，直接使用
                    $filePath = './' . $imageUrl;
                }
                
                // 转换为绝对路径
                $absolutePath = realpath($filePath);
                if ($absolutePath === false) {
                    // 如果 realpath 失败，尝试使用相对路径
                    $absolutePath = $filePath;
                }
                
                // 检查文件是否存在并删除
                if (file_exists($absolutePath) && is_file($absolutePath)) {
                    if (unlink($absolutePath)) {
                        $fileDeleted = true;
                    }
                } else {
                    // 文件不存在，可能是已经被删除，继续删除数据库记录
                    $fileDeleted = true;
                }
            } else {
                // 没有文件路径，直接删除数据库记录
                $fileDeleted = true;
            }
            
            // 删除数据库记录
            $res = Db::name('gallery')->delete($id);
            if ($res) {
                $data['msg'] = "删除成功！";
                $data['code'] = 1;
                return json($data);
            } else {
                $data['msg'] = "删除失败！";
                $data['code'] = 0;
                return json($data);
            }
        } else {
            return View::fetch();
        }
    }

    /**
     * 获取图库图片列表（用于选择）
     */
    public function getImageList()
    {
        $categoryId = Request::get('categoryId', 0);
        $page = Request::get('page', 1);
        $perPage = Request::get('perPage', 20);

        if ($perPage < 1) {
            $perPage = 20;
        }
        
        $where = [];
        
        if ($categoryId) {
            $where[] = ['categoryId', '=', $categoryId];
        }
        
        $list = Db::name('gallery')->where($where)->order('id desc')
            ->paginate([
                'list_rows' => $perPage,
                'page' => $page
            ]);
        
        $listArray = $list->toArray();
        
        // 获取分类信息
        $categoryList = Db::name('gallery_category')->order('sort desc, id desc')->select()->toArray();
        
        $data['code'] = 1;
        $data['msg'] = '获取成功';
        $data['data'] = [
            'list' => $listArray['data'],
            'categoryList' => $categoryList,
            'total' => $listArray['total'],
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => $listArray['last_page']
        ];
        
        return json($data);
    }
}

