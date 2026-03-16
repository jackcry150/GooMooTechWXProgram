"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  data() {
    return {
      content: ""
    };
  },
  onLoad() {
    this.loadContent();
  },
  methods: {
    async loadContent() {
      try {
        const res = await utils_request.api.news.detail({ code: "after_sale" });
        if (res.code === 200 && res.data) {
          this.content = res.data.content || "";
        }
      } catch (e) {
        common_vendor.index.showToast({ title: "加载失败", icon: "none" });
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.t($data.content)
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-cd5df659"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/sales.js.map
