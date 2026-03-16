"use strict";
const utils_request = require("../../utils/request.js");
const common_vendor = require("../../common/vendor.js");
const _sfc_main = {
  data() {
    return {
      adList: []
    };
  },
  onLoad() {
    this.loadAdList();
  },
  methods: {
    async loadAdList() {
      try {
        const params = {
          type: 2
        };
        const response = await utils_request.api.banner.list(params);
        this.adList = response.data;
      } catch (error) {
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.f($data.adList, (ad, index, i0) => {
      return {
        a: index,
        b: ad.image
      };
    })
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/group.js.map
