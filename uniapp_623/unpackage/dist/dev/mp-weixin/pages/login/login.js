"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      protocol: false,
      showPassword: false,
      rememberMe: false,
      loading: false,
      loginCode: "",
      sessionKey: ""
    };
  },
  onLoad() {
  },
  methods: {
    ChangeIsDefault(e) {
      this.$set(this, "protocol", !this.protocol);
    },
    goToBack() {
      common_vendor.index.navigateBack();
    },
    agraeement() {
      common_vendor.index.navigateTo({
        url: "/pages/my/agraeement"
      });
    },
    privacy() {
      common_vendor.index.navigateTo({
        url: "/pages/my/privacy"
      });
    },
    // 获取用户手机号
    getPhoneNumber(e) {
      if (e.detail.errMsg == "getPhoneNumber:ok") {
        common_vendor.index.login({
          provider: "weixin",
          success: (res) => {
            if (res.errMsg === "login:ok") {
              this.phoneLogin(res, e);
              return;
            } else {
              common_vendor.index.showToast({
                title: "登录失败",
                icon: "none"
              });
            }
          },
          fail: (err) => {
            common_vendor.index.showToast({
              title: "登录失败",
              icon: "none"
            });
            return;
          }
        });
      } else {
        common_vendor.index.showToast({
          title: "用户拒绝授权",
          icon: "none"
        });
      }
    },
    async phoneLogin(l, e) {
      try {
        const params = {
          encryptedData: e.detail.encryptedData,
          iv: e.detail.iv,
          code: l.code
        };
        const response = await utils_request.api.auth.phone(params);
        common_vendor.index.setStorageSync("token", response.data.token);
        common_vendor.index.showToast({
          title: "登录成功",
          icon: "success"
        });
        common_vendor.index.switchTab({
          url: "/pages/my/index"
        });
      } catch (error) {
        common_vendor.index.showToast({
          title: "登录失败",
          icon: "none"
        });
        return;
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_assets._imports_0$8,
    b: common_vendor.o((...args) => $options.getPhoneNumber && $options.getPhoneNumber(...args)),
    c: !$data.protocol,
    d: common_vendor.o(($event) => $options.goToBack()),
    e: $data.protocol ? true : false,
    f: common_vendor.o(($event) => $options.agraeement()),
    g: common_vendor.o(($event) => $options.privacy()),
    h: common_vendor.o((...args) => $options.ChangeIsDefault && $options.ChangeIsDefault(...args))
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-e4e4508d"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/login/login.js.map
