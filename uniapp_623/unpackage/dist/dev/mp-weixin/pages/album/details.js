"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  data() {
    return {
      albumInfo: [],
      groupItem: 1,
      imageMask: false,
      viewImageUrl: "",
      curViewImage: 0
    };
  },
  onLoad(options) {
    if (options.id) {
      this.getAlbumDetails(options.id);
    } else {
      common_vendor.index.redirectTo({
        url: "/pages/index/index"
      });
    }
  },
  methods: {
    async getAlbumDetails(id) {
      try {
        const params = {
          id
        };
        const response = await utils_request.api.album.detail(params);
        this.albumInfo = response.data;
      } catch (error) {
      }
    },
    switchGroup(item) {
      this.groupItem = item;
    },
    viewImage(k) {
      this.curViewImage = k;
      this.viewImageUrl = this.albumInfo.images[this.curViewImage];
      this.imageMask = true;
    },
    closeMask() {
      this.imageMask = false;
    },
    leftImg() {
      let num = this.albumInfo.images.length - 1;
      if (this.curViewImage <= 0) {
        this.curViewImage = num;
      } else {
        this.curViewImage--;
      }
      this.viewImageUrl = this.albumInfo.images[this.curViewImage];
    },
    rightImg() {
      let num = this.albumInfo.images.length - 1;
      if (this.curViewImage >= num) {
        this.curViewImage = 0;
      } else {
        this.curViewImage++;
      }
      this.viewImageUrl = this.albumInfo.images[this.curViewImage];
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: $data.groupItem != 4
  }, $data.groupItem != 4 ? {
    b: $data.albumInfo.image
  } : {}, {
    c: $data.groupItem == 4
  }, $data.groupItem == 4 ? {
    d: common_vendor.f($data.albumInfo.images, (list, index, i0) => {
      return {
        a: list,
        b: index,
        c: index === 0 ? 1 : "",
        d: common_vendor.o(($event) => $options.viewImage(index), index)
      };
    })
  } : {}, {
    e: $data.imageMask
  }, $data.imageMask ? {
    f: common_vendor.o((...args) => $options.closeMask && $options.closeMask(...args)),
    g: common_vendor.o((...args) => $options.leftImg && $options.leftImg(...args)),
    h: common_vendor.o((...args) => $options.rightImg && $options.rightImg(...args)),
    i: $data.viewImageUrl,
    j: common_vendor.o(() => {
    })
  } : {}, {
    k: $data.groupItem == 1
  }, $data.groupItem == 1 ? common_vendor.e({
    l: $data.albumInfo.type == 2
  }, $data.albumInfo.type == 2 ? {} : {}, {
    m: common_vendor.t($data.albumInfo.title),
    n: $data.groupItem == 1
  }, $data.groupItem == 1 ? {
    o: common_vendor.f($data.albumInfo.labels, (item, index, i0) => {
      return {
        a: common_vendor.t(item),
        b: index
      };
    })
  } : {}) : {}, {
    p: $data.groupItem == 2
  }, $data.groupItem == 2 ? {
    q: common_vendor.t($data.albumInfo.title),
    r: common_vendor.t($data.albumInfo.proportion),
    s: common_vendor.t($data.albumInfo.size),
    t: common_vendor.t($data.albumInfo.material),
    v: common_vendor.t($data.albumInfo.copyright),
    w: common_vendor.t($data.albumInfo.price)
  } : {}, {
    x: $data.groupItem == 3
  }, $data.groupItem == 3 ? {
    y: common_vendor.t($data.albumInfo.title),
    z: common_vendor.f($data.albumInfo.content, (item, index, i0) => {
      return {
        a: common_vendor.t(item),
        b: index
      };
    })
  } : {}, {
    A: common_vendor.t($data.albumInfo.title),
    B: $data.groupItem === 1 ? 1 : "",
    C: common_vendor.o(($event) => $options.switchGroup(1)),
    D: $data.groupItem === 2 ? 1 : "",
    E: common_vendor.o(($event) => $options.switchGroup(2)),
    F: $data.groupItem === 3 ? 1 : "",
    G: common_vendor.o(($event) => $options.switchGroup(3)),
    H: $data.groupItem === 4 ? 1 : "",
    I: common_vendor.o(($event) => $options.switchGroup(4))
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-f928acc5"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/album/details.js.map
