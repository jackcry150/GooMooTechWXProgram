"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      bannerList: [],
      productListHot: [],
      productListRecom: [],
      countdownTimers: []
    };
  },
  onLoad() {
    this.loadInfo();
    common_vendor.index.setStorageSync("token", "U013YUN6UDZFdWlhd1UyRzE4QkRvQkdrS2tzRjZuWVRlcUtuZ1V6TnJWNXVKQS9hZnpxNXI4V29pYkNyNkl5dmhOZ05zcml0MCtMQnd4Vmc2SGNjcjViQWRiUm51aFNub09icEg3NDhTVmc9");
  },
  onUnload() {
    this.countdownTimers.forEach((timer) => {
      if (timer) {
        clearInterval(timer);
      }
    });
    this.countdownTimers = [];
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
      this.getBannerList();
      this.getProductList();
    },
    async getBannerList() {
      try {
        const response = await utils_request.api.banner.list();
        this.bannerList = response.data;
      } catch (error) {
        common_vendor.index.__f__("error", "at pages/index/index.vue:232", "getBannerList error", error);
        common_vendor.index.showToast({ title: "banner加载失败", icon: "none" });
      }
    },
    async getProductList() {
      try {
        const response = await utils_request.api.product.list();
        this.productListHot = response.data.hot || [];
        this.productListRecom = response.data.recom || [];
        this.countdownTimers.forEach((timer) => {
          if (timer) {
            clearInterval(timer);
          }
        });
        this.countdownTimers = [];
        this.startCountdowns();
      } catch (error) {
      }
    },
    // 启动所有预售商品的倒计时
    startCountdowns() {
      this.productListHot.forEach((product, index) => {
        if (product.type == 2 && product.endTimeStamp) {
          this.startCountdown(product, "hot", index);
        }
      });
      this.productListRecom.forEach((product, index) => {
        if (product.type == 2 && product.endTimeStamp) {
          this.startCountdown(product, "recom", index);
        }
      });
    },
    // 为单个商品启动倒计时
    startCountdown(product, listType, index) {
      if (!product.endTimeStamp) {
        this.$set(product, "countdown", "");
        return;
      }
      const updateCountdown = () => {
        const now = Math.floor(Date.now() / 1e3);
        const remaining = product.endTimeStamp - now;
        if (remaining <= 0) {
          this.$set(product, "countdown", "已结束");
          return;
        }
        const days = Math.floor(remaining / 86400);
        const hours = Math.floor(remaining % 86400 / 3600);
        const minutes = Math.floor(remaining % 3600 / 60);
        const seconds = remaining % 60;
        const pad = (n) => n < 10 ? "0" + n : n;
        let countdownText = "";
        if (days > 0) {
          countdownText = `${days} 天 ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        } else {
          countdownText = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }
        this.$set(product, "countdown", countdownText);
      };
      updateCountdown();
      const timer = setInterval(updateCountdown, 1e3);
      this.countdownTimers.push(timer);
    },
    onBannerClick(e) {
      if (e.link) {
        common_vendor.index.navigateTo({
          url: e.link
        });
      }
    },
    goToAbout() {
      common_vendor.index.navigateTo({
        url: "/pages/about/about"
      });
    },
    goToCollect() {
      common_vendor.index.navigateTo({
        url: "/pages/collect/collect"
      });
    },
    goToCustomer() {
      common_vendor.index.navigateTo({
        url: "/pages/customer/customer"
      });
    },
    goToSell() {
      common_vendor.index.navigateTo({
        url: "/pages/sell/sell"
      });
    },
    goToProductDetail(id) {
      common_vendor.index.navigateTo({
        url: `/pages/product/detail?id=${id}`
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.f($data.bannerList, (banner, index, i0) => {
      return {
        a: banner.image,
        b: common_vendor.o(($event) => $options.onBannerClick(banner), index),
        c: index
      };
    }),
    b: common_assets._imports_0,
    c: common_vendor.o(($event) => $options.goToAbout()),
    d: common_assets._imports_1,
    e: common_vendor.o(($event) => $options.goToCollect()),
    f: common_assets._imports_2,
    g: common_vendor.o(($event) => $options.goToCustomer()),
    h: common_assets._imports_0,
    i: common_vendor.o(($event) => $options.goToSell()),
    j: common_vendor.f($data.productListHot, (productHot, index, i0) => {
      return common_vendor.e({
        a: productHot.image[0],
        b: productHot.type == 2 && productHot.countdown
      }, productHot.type == 2 && productHot.countdown ? {
        c: common_vendor.t(productHot.countdown)
      } : {}, {
        d: productHot.type == 2
      }, productHot.type == 2 ? {} : {}, {
        e: productHot.type == 1
      }, productHot.type == 1 ? {} : {}, {
        f: common_vendor.t(productHot.subtitle),
        g: common_vendor.t(productHot.title),
        h: productHot.type == 2
      }, productHot.type == 2 ? {
        i: common_vendor.t(productHot.deposit),
        j: common_vendor.t(productHot.price)
      } : {}, {
        k: productHot.type == 1
      }, productHot.type == 1 ? common_vendor.e({
        l: common_vendor.t(productHot.price),
        m: productHot.deduct > 0
      }, productHot.deduct > 0 ? {
        n: common_vendor.t(productHot.price - productHot.deduct)
      } : {}) : {}, {
        o: index,
        p: common_vendor.o(($event) => $options.goToProductDetail(productHot.id), index)
      });
    }),
    k: common_assets._imports_3,
    l: common_assets._imports_4,
    m: common_assets._imports_5,
    n: common_vendor.f($data.productListRecom, (productRecom, index, i0) => {
      return common_vendor.e({
        a: productRecom.image[0],
        b: productRecom.type == 2 && productRecom.countdown
      }, productRecom.type == 2 && productRecom.countdown ? {
        c: common_vendor.t(productRecom.countdown)
      } : {}, {
        d: productRecom.type == 2
      }, productRecom.type == 2 ? {} : {}, {
        e: productRecom.type == 1
      }, productRecom.type == 1 ? {} : {}, {
        f: common_vendor.t(productRecom.subtitle),
        g: common_vendor.t(productRecom.title),
        h: productRecom.type == 2
      }, productRecom.type == 2 ? {
        i: common_vendor.t(productRecom.deposit),
        j: common_vendor.t(productRecom.price)
      } : {}, {
        k: productRecom.type == 1
      }, productRecom.type == 1 ? {
        l: common_vendor.t(productRecom.price)
      } : {}, {
        m: productRecom.deduct > 0
      }, productRecom.deduct > 0 ? {
        n: common_vendor.t(productRecom.price - productRecom.deduct)
      } : {}, {
        o: index,
        p: common_vendor.o(($event) => $options.goToProductDetail(productRecom.id), index)
      });
    }),
    o: common_assets._imports_3,
    p: common_assets._imports_4,
    q: common_assets._imports_5
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-1cf27b2a"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/index/index.js.map
