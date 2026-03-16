"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "OrderList",
  data() {
    return {
      currentTab: 0,
      tabs: [
        { key: 0, label: "全部" },
        { key: 1, label: "待支付" },
        { key: 8, label: "已预定" },
        { key: 2, label: "待发货" },
        { key: 6, label: "待收货" },
        { key: 7, label: "已完成" }
      ],
      orderList: []
    };
  },
  onLoad(options) {
    if (options.status !== void 0) {
      this.currentTab = parseInt(options.status);
    }
    this.loadOrders(this.currentTab);
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
            common_vendor.index.navigateBack();
            return;
          }
        }
      });
    },
    switchTab(tabKey) {
      this.currentTab = tabKey;
      this.loadOrders(tabKey);
    },
    async loadOrders(status) {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        try {
          const params = {
            status
          };
          const response = await utils_request.api.order.list(params);
          this.orderList = response.data;
        } catch (error) {
          common_vendor.index.showToast({
            title: "加载失败",
            icon: "none"
          });
        }
      }
    },
    async orderCancel(o) {
      try {
        const params = {
          id: o.id
        };
        const response = await utils_request.api.order.cancel(params);
        common_vendor.index.showToast({
          title: response.msg,
          icon: "success"
        });
        this.orderList.forEach((item) => {
          if (item.id == o.id) {
            item.status = response.data.status;
            item.statusVal = response.data.statusVal;
            item.statusClass = response.data.statusClass;
          }
        });
      } catch (error) {
        common_vendor.index.showToast({
          title: "操作失败",
          icon: "none"
        });
      }
    },
    getAppId() {
      let appid = "";
      try {
        appid = common_vendor.index.getAccountInfoSync().miniProgram.appId;
      } catch (e) {
      }
      return appid;
    },
    requestPayment(paymentData, orderId, payType = "full") {
      common_vendor.index.requestPayment({
        provider: "wxpay",
        timeStamp: paymentData.timeStamp || "",
        nonceStr: paymentData.nonceStr || "",
        package: paymentData.package || "",
        signType: paymentData.signType || "RSA",
        paySign: paymentData.paySign || "",
        success: () => {
          const msg = payType === "deposit" ? "定金支付成功" : payType === "balance" ? "尾款支付成功" : "支付成功";
          common_vendor.index.showToast({ title: msg, icon: "success" });
          setTimeout(() => {
            this.loadOrders(this.currentTab);
            common_vendor.index.redirectTo({ url: `/pages/order/detail?id=${orderId}` });
          }, 1500);
        },
        fail: (err) => {
          const msg = err.errMsg && err.errMsg.indexOf("cancel") !== -1 ? "支付已取消" : "支付失败，请重试";
          common_vendor.index.showToast({ title: msg, icon: "none" });
        }
      });
    },
    async orderPay(o) {
      try {
        const payType = o.status == 8 ? "deposit" : "full";
        common_vendor.index.showLoading({ title: "获取支付参数..." });
        const params = { id: o.id, payType, appid: this.getAppId() };
        const response = await utils_request.api.order.pay(params);
        common_vendor.index.hideLoading();
        if (response.code === 200 && response.data && response.data.payment) {
          const payment = { ...response.data.payment, orderId: response.data.orderId || o.id };
          this.requestPayment(payment, o.id, response.data.payType || payType);
        } else {
          common_vendor.index.showToast({ title: response.msg || "获取支付参数失败", icon: "none" });
        }
      } catch (error) {
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({ title: "支付失败", icon: "none" });
      }
    },
    goToOrderDetail(order) {
      common_vendor.index.navigateTo({
        url: `/pages/order/detail?id=${order.id}`
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.f($data.tabs, (tab, k0, i0) => {
      return {
        a: common_vendor.t(tab.label),
        b: $data.currentTab == tab.key ? 1 : "",
        c: tab.key,
        d: common_vendor.o(($event) => $options.switchTab(tab.key), tab.key)
      };
    }),
    b: common_vendor.f($data.orderList, (order, index, i0) => {
      return common_vendor.e({
        a: common_vendor.t(order.orderNo),
        b: common_vendor.t(order.statusVal),
        c: common_vendor.s(order.statusClass),
        d: common_vendor.f(order.product, (product, index2, i1) => {
          return {
            a: product.image,
            b: common_vendor.t(product.title),
            c: common_vendor.t(product.subtitle),
            d: common_vendor.t(product.version),
            e: common_vendor.t(product.price),
            f: common_vendor.t(product.quantity),
            g: index2
          };
        }),
        e: common_vendor.o(($event) => $options.goToOrderDetail(order), index),
        f: order.status == 1 || order.status == 8
      }, order.status == 1 || order.status == 8 ? {
        g: common_vendor.t(order.totalPrice),
        h: common_vendor.o(($event) => $options.orderCancel(order), index),
        i: common_vendor.o(($event) => $options.orderPay(order), index)
      } : {}, {
        j: index
      });
    }),
    c: $data.orderList.length === 0
  }, $data.orderList.length === 0 ? {
    d: common_assets._imports_0$1
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-456ecf67"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/order/list.js.map
