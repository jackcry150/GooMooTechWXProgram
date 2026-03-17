"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const ARRIVAL_SUBSCRIBE_TEMPLATE_ID = "064jbSrGui-nwHcDSAxE-laUCzY5cgbqciU3aeyAhig";
const ARRIVAL_SUBSCRIBE_ASKED_KEY = "arrival_subscribe_asked_v1";
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
      this.tryAskArrivalSubscribe();
      this.getProfileInfo();
      this.getCartCount();
      this.getCollectCount();
    }
  },
  methods: {
    tryAskArrivalSubscribe() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        return;
      }
      const asked = common_vendor.index.getStorageSync(ARRIVAL_SUBSCRIBE_ASKED_KEY);
      if (asked) {
        return;
      }
      common_vendor.index.showModal({
        title: "到货通知",
        content: "是否开启到货后通知？",
        confirmText: "开启",
        cancelText: "稍后",
        success: (res) => {
          if (!res.confirm) {
            common_vendor.index.showToast({
              title: "未开启通知，到货后请在我的订单手动查看",
              icon: "none"
            });
            return;
          }
          common_vendor.wx$1.requestSubscribeMessage({
            tmplIds: [ARRIVAL_SUBSCRIBE_TEMPLATE_ID],
            success: (ret) => {
              const accepted = ret[ARRIVAL_SUBSCRIBE_TEMPLATE_ID] === "accept";
              if (accepted) {
                common_vendor.index.showToast({ title: "已开启通知", icon: "none" });
              } else {
                common_vendor.index.showToast({ title: "未开启通知，到货后请在我的订单手动查看", icon: "none" });
              }
              common_vendor.index.setStorageSync(ARRIVAL_SUBSCRIBE_ASKED_KEY, 1);
            },
            fail: () => {
              common_vendor.index.showToast({ title: "订阅调用失败", icon: "none" });
            }
          });
        }
      });
    },
    goLogin() {
      common_vendor.index.showModal({
        content: "使用当前功能需要先登录，是否去登录？",
        success: function(res) {
          if (res.confirm) {
            common_vendor.index.navigateTo({ url: "/pages/login/login" });
            return;
          } else if (res.cancel) {
            common_vendor.index.__f__("log", "at pages/my/index.vue:176", "user cancel login");
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
    async goToProfile() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      try {
        const profileRes = await utils_request.api.user.profile();
        if (!profileRes || !profileRes.data || !profileRes.data.id) {
          throw new Error("invalid profile");
        }
        common_vendor.index.navigateTo({
          url: "/pages/my/profile"
        });
      } catch (error) {
        common_vendor.index.removeStorageSync("token");
        this.goLogin();
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
