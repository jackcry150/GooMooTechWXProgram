/**
 * 图库选择器公共组件
 * 使用方法：
 * 1. 引入CSS和JS文件
 * 2. 调用 GallerySelector.init(options) 初始化
 * 3. 调用 GallerySelector.open(callback) 打开选择器
 */

var GallerySelector = {
    // 配置选项
    options: {
        multiple: false, // 是否多选
        maxSelect: 0, // 最大选择数量，0表示不限制
        onSelect: null, // 选择回调函数 function(selectedImages)
        selectedImages: [] // 已选择的图片数组
    },
    
    // 当前选中的图片
    currentSelected: [],
    
    // 当前图片列表
    currentImageList: [],
    
    // 分页信息
    pagination: {
        currentPage: 1,
        perPage: 20,
        total: 0,
        lastPage: 1
    },
    
    // 当前分类ID
    currentCategoryId: 0,
    
    // 初始化
    init: function(options) {
        this.options = $.extend({}, this.options, options);
        this.currentSelected = this.options.selectedImages || [];
        this.initModal();
    },
    
    // 初始化模态框
    initModal: function() {
        if ($('#gallerySelectorModal').length === 0) {
            var modalHtml = `
                <div id="gallerySelectorModal" class="gallery-modal">
                    <div class="gallery-modal-content">
                        <div class="gallery-header">
                            <h3>选择图片</h3>
                            <span style="font-size: 28px; font-weight: bold; cursor: pointer;" onclick="GallerySelector.close()">&times;</span>
                        </div>
                        <div class="gallery-filter" id="galleryCategoryFilter"></div>
                        <div class="gallery-images" id="galleryImages"></div>
                        <div class="gallery-pagination" id="galleryPagination"></div>
                        <div style="margin-top: 20px; text-align: right;">
                            <button type="button" class="btn btn-primary radius" onclick="GallerySelector.confirm()">确定</button>
                            <button type="button" class="btn btn-default radius ml-10" onclick="GallerySelector.close()">取消</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            
            // 点击模态框外部关闭
            $('#gallerySelectorModal').on('click', function(e) {
                if (e.target === this) {
                    GallerySelector.close();
                }
            });
        }
    },
    
    // 打开选择器
    open: function(callback) {
        if (callback) {
            this.options.onSelect = callback;
        }
        // 重置分页
        this.pagination.currentPage = 1;
        this.currentCategoryId = 0;
        $('#gallerySelectorModal').show();
        this.loadGalleryImages(0, 1);
    },
    
    // 关闭选择器
    close: function() {
        $('#gallerySelectorModal').hide();
    },
    
    // 确认选择
    confirm: function() {
        if (this.options.onSelect) {
            // 传递当前选中的图片数组的副本
            this.options.onSelect(this.currentSelected.slice());
        }
        this.close();
    },
    
    // 加载图库图片
    loadGalleryImages: function(categoryId, page) {
        categoryId = categoryId !== undefined ? categoryId : this.currentCategoryId;
        page = page || this.pagination.currentPage;
        
        this.currentCategoryId = categoryId;
        this.pagination.currentPage = page;
        
        var self = this;
        $.ajax({
            type: 'GET',
            url: '/adm/gallery/getImageList',
            data: {
                categoryId: categoryId,
                page: page,
                perPage: this.pagination.perPage
            },
            dataType: 'json',
            success: function (data) {
                if (data.code == 1) {
                    // 更新分页信息
                    if (data.data.total !== undefined) {
                        self.pagination.total = data.data.total;
                        self.pagination.lastPage = data.data.lastPage || 1;
                        self.pagination.currentPage = data.data.currentPage || page;
                    }
                    
                    self.renderCategoryFilter(data.data.categoryList);
                    self.renderGalleryImages(data.data.list);
                    self.renderPagination();
                }
            }
        });
    },
    
    // 渲染分类筛选
    renderCategoryFilter: function(categoryList) {
        var html = '<select class="select" onchange="GallerySelector.loadGalleryImages(this.value, 1)" style="width:200px;">';
        html += '<option value="0" ' + (this.currentCategoryId == 0 ? 'selected' : '') + '>全部分类</option>';
        for (var i = 0; i < categoryList.length; i++) {
            html += '<option value="' + categoryList[i].id + '" ' + (this.currentCategoryId == categoryList[i].id ? 'selected' : '') + '>' + categoryList[i].categoryName + '</option>';
        }
        html += '</select>';
        $('#galleryCategoryFilter').html(html);
    },
    
    // 渲染图库图片
    renderGalleryImages: function(images) {
        this.currentImageList = images;
        var html = '';
        var self = this;
        for (var i = 0; i < images.length; i++) {
            var img = images[i];
            var isSelected = this.isImageSelected(img.id);
            html += '<div class="gallery-image-item ' + (isSelected ? 'selected' : '') + '" onclick="GallerySelector.selectImage(' + img.id + ', \'' + img.imageUrl.replace(/'/g, "\\'") + '\', \'' + img.imageName.replace(/'/g, "\\'") + '\')">';
            html += '<img src="' + img.imageUrl + '" alt="' + img.imageName + '">';
            html += '<div class="gallery-image-name">' + img.imageName + '</div>';
            html += '</div>';
        }
        $('#galleryImages').html(html);
    },
    
    // 判断图片是否已选中
    isImageSelected: function(imageId) {
        for (var i = 0; i < this.currentSelected.length; i++) {
            if (this.currentSelected[i].id == imageId) {
                return true;
            }
        }
        return false;
    },
    
    // 选择图片
    selectImage: function(imageId, imageUrl, imageName) {
        if (!this.options.multiple) {
            // 单选模式，替换当前选择
            this.currentSelected = [{
                id: imageId,
                url: imageUrl,
                name: imageName
            }];
        } else {
            // 多选模式
            var found = false;
            for (var i = 0; i < this.currentSelected.length; i++) {
                if (this.currentSelected[i].id == imageId) {
                    this.currentSelected.splice(i, 1);
                    found = true;
                    break;
                }
            }
            if (!found) {
                // 检查是否超过最大选择数量
                if (this.options.maxSelect > 0 && this.currentSelected.length >= this.options.maxSelect) {
                    layer.msg('最多只能选择' + this.options.maxSelect + '张图片', {time: 2000});
                    return;
                }
                this.currentSelected.push({
                    id: imageId,
                    url: imageUrl,
                    name: imageName
                });
            }
        }
        this.renderGalleryImages(this.currentImageList);
    },
    
    // 渲染分页
    renderPagination: function() {
        var html = '<div style="margin-top: 20px; text-align: center;">';
        
        if (this.pagination.lastPage <= 1) {
            // 只有一页或没有数据，不显示分页
            html += '<span style="color: #999;">共 ' + this.pagination.total + ' 张图片</span>';
        } else {
            // 显示分页信息
            html += '<span style="margin-right: 10px; color: #666;">共 ' + this.pagination.total + ' 张图片，第 ' + this.pagination.currentPage + ' / ' + this.pagination.lastPage + ' 页</span>';
            
            // 上一页按钮
            if (this.pagination.currentPage > 1) {
                html += '<button type="button" class="btn btn-default radius" onclick="GallerySelector.loadGalleryImages(' + this.currentCategoryId + ', ' + (this.pagination.currentPage - 1) + ')">上一页</button>';
            } else {
                html += '<button type="button" class="btn btn-default radius" disabled>上一页</button>';
            }
            
            // 页码显示（最多显示5个页码）
            var startPage = Math.max(1, this.pagination.currentPage - 2);
            var endPage = Math.min(this.pagination.lastPage, startPage + 4);
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            html += '&nbsp;';
            for (var i = startPage; i <= endPage; i++) {
                if (i == this.pagination.currentPage) {
                    html += '<button type="button" class="btn btn-primary radius" style="margin: 0 2px;">' + i + '</button>';
                } else {
                    html += '<button type="button" class="btn btn-default radius" style="margin: 0 2px;" onclick="GallerySelector.loadGalleryImages(' + this.currentCategoryId + ', ' + i + ')">' + i + '</button>';
                }
            }
            html += '&nbsp;';
            
            // 下一页按钮
            if (this.pagination.currentPage < this.pagination.lastPage) {
                html += '<button type="button" class="btn btn-default radius" onclick="GallerySelector.loadGalleryImages(' + this.currentCategoryId + ', ' + (this.pagination.currentPage + 1) + ')">下一页</button>';
            } else {
                html += '<button type="button" class="btn btn-default radius" disabled>下一页</button>';
            }
            
            // 跳转到指定页面
            html += '&nbsp;&nbsp;<span style="margin: 0 5px;">跳转到</span>';
            html += '<input type="number" id="galleryPageInput" value="' + this.pagination.currentPage + '" min="1" max="' + this.pagination.lastPage + '" style="width: 60px; height: 30px; text-align: center; border: 1px solid #ddd; border-radius: 4px;">';
            html += '<button type="button" class="btn btn-default radius" onclick="GallerySelector.goToPage()" style="margin-left: 5px;">跳转</button>';
        }
        
        html += '</div>';
        $('#galleryPagination').html(html);
    },
    
    // 跳转到指定页面
    goToPage: function() {
        var page = parseInt($('#galleryPageInput').val());
        if (isNaN(page) || page < 1) {
            page = 1;
        }
        if (page > this.pagination.lastPage) {
            page = this.pagination.lastPage;
        }
        this.loadGalleryImages(this.currentCategoryId, page);
    },
    
    // 重置选择
    reset: function() {
        this.currentSelected = [];
    }
};

