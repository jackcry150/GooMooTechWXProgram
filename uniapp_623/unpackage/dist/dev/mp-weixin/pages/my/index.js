"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "Profile",
  data() {
    return {
      userInfo: {
        nickName: "",
        id: "",
        avatar: "/static/image/default_avatar.jpg",
        snailShells: 0
      },
      cartCount: 0,
      collectCount: 0
    };
  },
  onLoad() {
  },
  onShow() {
    const token = common_vendor.index.getStorageSync("token");
    if (token) {
      this.getProfileInfo();
      this.getCartCount();
      this.getCollectCount();
    }
  },
  methods: {
    goLogin() {
      common_vendor.index.showModal({
        content: "使用当前功能需要您进行登录，是否去登录?",
        success: function(res) {
          if (res.confirm) {
            common_vendor.index.navigateTo({
              url: "/pages/login/login"
            });
            return;
          } else if (res.cancel) {
            common_vendor.index.__f__("log", "at pages/my/index.vue:158", "用户点击取消");
            return;
          }
        }
      });
    },
    async getProfileInfo() {
      try {
        const response = await utils_request.api.user.profile();
        this.userInfo = response.data;
      } catch (error) {
      }
    },
    async getCartCount() {
      try {
        const response = await utils_request.api.cart.count();
        this.cartCount = response.data.count;
      } catch (error) {
      }
    },
    async getCollectCount() {
      try {
        const response = await utils_request.api.collect.count();
        this.collectCount = response.data.count;
      } catch (error) {
      }
    },
    goToCollect() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: "/pages/collect/collect"
        });
      }
    },
    goToBill() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: "/pages/my/bill"
        });
      }
    },
    goToOrders(status = "") {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: `/pages/order/list?status=${status}`
        });
      }
    },
    goToCart() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: "/pages/cart/cart"
        });
      }
    },
    goToProfile() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: "/pages/my/profile"
        });
      }
    },
    goToCustomer() {
      common_vendor.index.navigateTo({
        url: "/pages/customer/customer"
      });
    },
    goToAfterSales() {
      common_vendor.index.navigateTo({
        url: "/pages/my/sales"
      });
    },
    goToAgreement() {
      common_vendor.index.navigateTo({
        url: "/pages/my/agraeement"
      });
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
    a: $data.userInfo.avatar,
    b: common_vendor.t($data.userInfo.nickName),
    c: common_vendor.t($data.userInfo.id),
    d: common_vendor.o(($event) => $options.goToProfile()),
    e: common_vendor.t($data.collectCount),
    f: common_vendor.o((...args) => $options.goToCollect && $options.goToCollect(...args)),
    g: common_vendor.t($data.userInfo.snailShells),
    h: common_vendor.o((...args) => $options.goToBill && $options.goToBill(...args)),
    i: common_vendor.o((...args) => $options.goToGroup && $options.goToGroup(...args)),
    j: common_assets._imports_0$3,
    k: common_vendor.o(($event) => $options.goToOrders(0)),
    l: common_assets._imports_1$2,
    m: common_vendor.o(($event) => $options.goToOrders(1)),
    n: common_assets._imports_2$1,
    o: common_vendor.o(($event) => $options.goToOrders(8)),
    p: common_assets._imports_3$1,
    q: common_vendor.o(($event) => $options.goToOrders(2)),
    r: common_assets._imports_4$1,
    s: common_vendor.o(($event) => $options.goToOrders(6)),
    t: common_assets._imports_5$1,
    v: common_vendor.o(($event) => $options.goToOrders(7)),
    w: common_assets._imports_6,
    x: $data.cartCount > 0
  }, $data.cartCount > 0 ? {
    y: common_vendor.t($data.cartCount)
  } : {}, {
    z: common_vendor.o(($event) => $options.goToCart()),
    A: common_assets._imports_7,
    B: common_vendor.o(($event) => $options.goToProfile()),
    C: common_assets._imports_8,
    D: common_vendor.o(($event) => $options.goToCustomer()),
    E: common_assets._imports_9,
    F: common_vendor.o(($event) => $options.goToAfterSales()),
    G: common_assets._imports_10,
    H: common_vendor.o(($event) => $options.goToAgreement())
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-f97bc692"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/index.js.map
