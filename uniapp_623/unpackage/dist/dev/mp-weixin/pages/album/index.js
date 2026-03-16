"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      albumList: []
    };
  },
  onLoad() {
    this.loadInfo();
  },
  onShow() {
    this.loadInfo();
  },
  onPullDownRefresh() {
    this.loadInfo().finally(() => {
      common_vendor.index.stopPullDownRefresh();
    });
  },
  methods: {
    loadInfo() {
      this.getAlbumList();
    },
    async getAlbumList() {
      try {
        const response = await utils_request.api.album.list();
        this.albumList = response.data;
      } catch (error) {
      }
    },
    goToAlbumDetail(a) {
      common_vendor.index.navigateTo({
        url: `/pages/album/details?id=${a.id}`
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.f($data.albumList, (item, index, i0) => {
      return {
        a: item.image,
        b: common_vendor.t(item.title),
        c: common_vendor.f(item.labels, (item2, index2, i1) => {
          return {
            a: common_vendor.t(item2),
            b: index2
          };
        }),
        d: index,
        e: common_vendor.o(($event) => $options.goToAlbumDetail(item), index)
      };
    }),
    b: $data.albumList.length === 0
  }, $data.albumList.length === 0 ? {
    c: common_assets._imports_0$1
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-2956ad5a"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/album/index.js.map
