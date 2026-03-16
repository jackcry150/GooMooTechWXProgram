"use strict";
const common_vendor = require("../../common/vendor.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      showFrom: false,
      from: {
        name: "",
        phone: "",
        address: ""
      }
    };
  },
  onLoad() {
    this.loadInfo();
  },
  methods: {
    loadInfo() {
    },
    showVerifyFrom() {
      this.showFrom = true;
    },
    verifyFrom() {
      common_vendor.index.showToast({
        title: "提交成功",
        icon: "success"
      });
      this.showFrom = false;
    },
    goToGroup() {
      common_vendor.index.navigateTo({
        url: "/pages/my/group"
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_assets._imports_0$2,
    b: common_vendor.o((...args) => $options.showVerifyFrom && $options.showVerifyFrom(...args)),
    c: common_assets._imports_1$1,
    d: common_vendor.o((...args) => $options.goToGroup && $options.goToGroup(...args)),
    e: $data.showFrom
  }, $data.showFrom ? {
    f: $data.from.name,
    g: common_vendor.o(($event) => $data.from.name = $event.detail.value),
    h: $data.from.phone,
    i: common_vendor.o(($event) => $data.from.phone = $event.detail.value),
    j: common_vendor.o((...args) => $options.verifyFrom && $options.verifyFrom(...args))
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-64565cbf"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/verify/index.js.map
