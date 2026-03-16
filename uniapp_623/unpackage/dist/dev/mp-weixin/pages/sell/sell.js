"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "Sell",
  data() {
    return {
      sellInfo: null,
      sellState: "ended"
      // canBuy | notStarted | ended
    };
  },
  computed: {
    sellStateText() {
      if (this.sellState === "canBuy")
        return "立即购买";
      if (this.sellState === "notStarted")
        return "贩售未开始";
      return "贩售已结束";
    },
    canBuy() {
      return this.sellState === "canBuy";
    }
  },
  onLoad() {
    this.fetchLatestSell();
  },
  methods: {
    // 获取最新贩售数据
    async fetchLatestSell() {
      try {
        const res = await utils_request.api.sell.latest();
        if (res.code === 1 && res.data) {
          this.sellInfo = res.data;
          this.checkSellStatus();
        } else {
          common_vendor.index.showModal({
            title: "提示",
            content: "暂无贩售活动",
            showCancel: false,
            success: () => {
              common_vendor.index.navigateBack({
                fail: () => {
                  common_vendor.index.switchTab({
                    url: "/pages/index/index"
                  });
                }
              });
            }
          });
        }
      } catch (e) {
        common_vendor.index.__f__("error", "at pages/sell/sell.vue:88", "获取贩售数据失败", e);
        common_vendor.index.showModal({
          title: "提示",
          content: "获取数据失败",
          showCancel: false,
          success: () => {
            common_vendor.index.navigateBack({
              fail: () => {
                common_vendor.index.switchTab({
                  url: "/pages/index/index"
                });
              }
            });
          }
        });
      }
    },
    // 根据开始时间、结束时间、库存、是否上架 判断贩售状态
    checkSellStatus() {
      if (!this.sellInfo) {
        this.sellState = "ended";
        return;
      }
      const now = (/* @__PURE__ */ new Date()).getTime();
      const { stock, sellStatus, productStatus, startTime, endTime } = this.sellInfo;
      const statusOk = (sellStatus == 1 || sellStatus == null) && (productStatus == 1 || productStatus == null);
      if (startTime) {
        const start = new Date(startTime.replace(/-/g, "/")).getTime();
        if (now < start) {
          this.sellState = "notStarted";
          return;
        }
      }
      if (stock <= 0 || !statusOk) {
        this.sellState = "ended";
        return;
      }
      if (endTime) {
        const end = new Date(endTime.replace(/-/g, "/")).getTime();
        if (now > end) {
          this.sellState = "ended";
          return;
        }
      }
      this.sellState = "canBuy";
    },
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
            common_vendor.index.__f__("log", "at pages/sell/sell.vue:152", "用户点击取消");
            return;
          }
        }
      });
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
    // 跳转到商品详情页（仅可购买时）
    goToProductDetail() {
      if (!this.sellInfo || !this.sellInfo.productId) {
        common_vendor.index.showToast({ title: "商品信息不存在", icon: "none" });
        return;
      }
      common_vendor.index.navigateTo({
        url: `/pages/product/detail?id=${this.sellInfo.productId}`
      });
    },
    // 购买按钮点击：仅可购买时跳转商品详情
    handleBuyClick() {
      if (this.sellState !== "canBuy") {
        common_vendor.index.showToast({
          title: this.sellState === "notStarted" ? "贩售未开始" : "贩售已结束",
          icon: "none"
        });
        return;
      }
      this.goToProductDetail();
    },
    // 底部按钮点击
    handleSubmit() {
      if (this.sellState !== "canBuy") {
        common_vendor.index.showToast({
          title: this.sellState === "notStarted" ? "贩售未开始" : "贩售已结束",
          icon: "none"
        });
        return;
      }
      this.goToProductDetail();
    },
    // 预览图片
    previewImage(images, current) {
      common_vendor.index.previewImage({
        urls: images,
        current
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_assets._imports_0$6,
    b: common_vendor.o(($event) => $options.goToOrders(0)),
    c: $data.sellInfo && $data.sellInfo.promoImages && $data.sellInfo.promoImages.length > 0
  }, $data.sellInfo && $data.sellInfo.promoImages && $data.sellInfo.promoImages.length > 0 ? {
    d: common_vendor.f($data.sellInfo.promoImages, (img, index, i0) => {
      return {
        a: img,
        b: index
      };
    }),
    e: common_vendor.t($options.sellStateText),
    f: common_vendor.o((...args) => $options.handleBuyClick && $options.handleBuyClick(...args)),
    g: common_assets._imports_1$4,
    h: common_vendor.o((...args) => $options.handleBuyClick && $options.handleBuyClick(...args))
  } : {}, {
    i: $data.sellInfo && $data.sellInfo.reservationNotice && $data.sellInfo.reservationNotice.length > 0
  }, $data.sellInfo && $data.sellInfo.reservationNotice && $data.sellInfo.reservationNotice.length > 0 ? {
    j: common_assets._imports_2$2,
    k: common_vendor.f($data.sellInfo.reservationNotice, (img, index, i0) => {
      return {
        a: index,
        b: img,
        c: common_vendor.o(($event) => $options.previewImage($data.sellInfo.reservationNotice, index), index)
      };
    })
  } : {}, {
    l: common_vendor.t($options.sellStateText),
    m: $options.canBuy ? 1 : "",
    n: common_vendor.o((...args) => $options.handleSubmit && $options.handleSubmit(...args))
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/sell/sell.js.map
