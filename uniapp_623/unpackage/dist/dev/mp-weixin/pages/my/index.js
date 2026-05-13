"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const ARRIVAL_SUBSCRIBE_TEMPLATE_ID = "PSTyqbj2wf1P74dSDb1qfh0ErUGegNQ8DFS6-SKM4_M";
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
  computed: {
    displayName() {
      return this.userInfo.nickName || "小橘酱";
    }
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
            common_vendor.index.__f__("log", "at pages/my/index.vue:235", "user cancel login");
            return;
          }
        }
      });
    },
    async getProfileInfo() {
      try {
        const response = await utils_request.api.user.profile();
        const profile = response && response.data ? response.data : null;
        if (!profile || !profile.id) {
          throw new Error("invalid profile");
        }
        this.userInfo = profile;
        this.tryAskArrivalSubscribe();
      } catch (error) {
        common_vendor.index.removeStorageSync("token");
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
    a: common_assets._imports_0$1,
    b: common_assets._imports_1$1,
    c: $data.userInfo.avatar || "/static/image/default_avatar.jpg",
    d: common_vendor.t($options.displayName),
    e: common_vendor.t($data.collectCount),
    f: common_vendor.o((...args) => $options.goToCollect && $options.goToCollect(...args), "00"),
    g: common_vendor.t($data.userInfo.snailShells || 0),
    h: common_vendor.o((...args) => $options.goToBill && $options.goToBill(...args), "3f"),
    i: common_vendor.o(($event) => $options.goToProfile(), "a3"),
    j: common_assets._imports_2$1,
    k: common_vendor.o((...args) => $options.goToGroup && $options.goToGroup(...args), "f3"),
    l: common_vendor.o(($event) => $options.goToOrders(0), "f3"),
    m: common_assets._imports_3,
    n: common_vendor.o(($event) => $options.goToOrders(1), "c2"),
    o: common_assets._imports_4$1,
    p: common_vendor.o(($event) => $options.goToOrders(8), "0c"),
    q: common_assets._imports_5,
    r: common_vendor.o(($event) => $options.goToOrders(2), "01"),
    s: common_assets._imports_6,
    t: common_vendor.o(($event) => $options.goToOrders(6), "c4"),
    v: common_assets._imports_7,
    w: common_vendor.o(($event) => $options.goToOrders(7), "dd"),
    x: common_assets._imports_8,
    y: $data.cartCount > 0
  }, $data.cartCount > 0 ? {
    z: common_vendor.t($data.cartCount)
  } : {}, {
    A: common_vendor.o(($event) => $options.goToCart(), "e0"),
    B: common_assets._imports_9,
    C: common_vendor.o(($event) => $options.goToBill(), "26"),
    D: common_assets._imports_10,
    E: common_vendor.o(($event) => $options.goToProfile(), "fb"),
    F: common_assets._imports_11,
    G: common_vendor.o(($event) => $options.goToCustomer(), "3d"),
    H: common_assets._imports_12,
    I: common_vendor.o(($event) => $options.goToAfterSales(), "60"),
    J: common_assets._imports_9,
    K: common_vendor.o(($event) => $options.goToAgreement(), "9f")
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-f97bc692"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/index.js.map
