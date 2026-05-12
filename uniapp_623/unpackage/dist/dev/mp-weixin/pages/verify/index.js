"use strict";
const common_vendor = require("../../common/vendor.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      statusBarHeight: 44,
      showFrom: false,
      from: {
        name: "",
        phone: "",
        address: ""
      }
    };
  },
  onLoad() {
    const systemInfo = common_vendor.index.getSystemInfoSync ? common_vendor.index.getSystemInfoSync() : {};
    this.statusBarHeight = systemInfo.statusBarHeight || 22;
  },
  methods: {
    showVerifyFrom() {
      this.showFrom = true;
    },
    closeVerifyFrom() {
      this.showFrom = false;
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
    a: common_assets._imports_0$1,
    b: common_assets._imports_1,
    c: common_vendor.o((...args) => $options.showVerifyFrom && $options.showVerifyFrom(...args), "d0"),
    d: common_assets._imports_2,
    e: common_vendor.o((...args) => $options.goToGroup && $options.goToGroup(...args), "47"),
    f: `${$data.statusBarHeight + 12}px`,
    g: $data.showFrom
  }, $data.showFrom ? {
    h: common_vendor.o((...args) => $options.closeVerifyFrom && $options.closeVerifyFrom(...args), "0b"),
    i: $data.from.name,
    j: common_vendor.o(($event) => $data.from.name = $event.detail.value, "86"),
    k: $data.from.phone,
    l: common_vendor.o(($event) => $data.from.phone = $event.detail.value, "70"),
    m: common_vendor.o((...args) => $options.closeVerifyFrom && $options.closeVerifyFrom(...args), "c1"),
    n: common_vendor.o((...args) => $options.verifyFrom && $options.verifyFrom(...args), "dc")
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-64565cbf"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/verify/index.js.map
