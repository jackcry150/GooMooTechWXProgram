"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const STATUS_COLORS = {
  "待支付": "linear-gradient(135deg, #e53935, #ef5350)",
  "支付失败": "linear-gradient(135deg, #78909c, #90a4ae)",
  "待发货": "linear-gradient(135deg, #00897b, #26a69a)",
  "待收货": "linear-gradient(135deg, #00897b, #26a69a)",
  "订单完成": "linear-gradient(135deg, #00897b, #26a69a)",
  "申请退款": "linear-gradient(135deg, #f57c00, #ffb74d)",
  "同意退款": "linear-gradient(135deg, #00897b, #26a69a)",
  "拒绝退款": "linear-gradient(135deg, #c62828, #e57373)",
  "已付定金待付尾款": "linear-gradient(135deg, #f57c00, #ffb74d)",
  "已预定": "linear-gradient(135deg, #f57c00, #ffb74d)"
};
const STATUS_MESSAGES = {
  "待支付": "请尽快完成支付",
  "待发货": "商家正在准备商品",
  "待收货": "商品已发货，请注意查收",
  "订单完成": "感谢您的购买",
  "申请退款": "退款申请已提交，等待处理",
  "已付定金待付尾款": "请及时支付尾款",
  "已预定": "请先支付定金"
};
const _sfc_main = {
  name: "OrderDetail",
  data() {
    return {
      orderId: "",
      loading: true,
      order: {},
      balanceCountdown: "",
      countdownTimer: null
    };
  },
  computed: {
    productList() {
      return this.order.productList || [];
    },
    statusBgColor() {
      return STATUS_COLORS[this.order.statusText] || "linear-gradient(135deg, #546e7a, #78909c)";
    },
    statusMessage() {
      return STATUS_MESSAGES[this.order.statusText] || "";
    },
    remarkText() {
      const r = this.order.remarks;
      if (!r || typeof r === "string" && !r.trim())
        return "";
      return r.trim() === "无" ? "" : r;
    },
    showPayBtn() {
      const s = this.order.status;
      return !this.order.isPresale && (s === 1 || s === 8);
    },
    showCancelBtn() {
      const s = this.order.status;
      return s === 1 || s === 8 || this.order.isPresale && this.order.canPayDeposit;
    },
    showRefundBtn() {
      return false;
    },
    showConfirmReceiptBtn() {
      return this.order.status === 6;
    },
    showDeleteBtn() {
      return this.order.status === 4;
    }
  },
  onLoad(options) {
    if (options.id) {
      this.orderId = options.id;
      this.loadOrderDetail(options.id);
    }
  },
  onPullDownRefresh() {
    this.loadOrderDetail(this.orderId).finally(() => {
      common_vendor.index.stopPullDownRefresh();
    });
  },
  onUnload() {
    if (this.countdownTimer) {
      clearInterval(this.countdownTimer);
      this.countdownTimer = null;
    }
  },
  methods: {
    async loadOrderDetail(orderId) {
      if (!orderId)
        return Promise.resolve();
      this.loading = true;
      try {
        const res = await utils_request.api.order.detail({ id: orderId });
        if (res.code === 200 && res.data) {
          this.order = res.data;
          if (this.order.balanceDueTimeStamp > 0 && this.order.balancePaid == 0) {
            this.startBalanceCountdown();
          }
        } else {
          common_vendor.index.showToast({ title: res.msg || "加载失败", icon: "none" });
        }
      } catch (e) {
        common_vendor.index.showToast({ title: "加载失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },
    startBalanceCountdown() {
      if (this.countdownTimer)
        clearInterval(this.countdownTimer);
      const tick = () => {
        if (!this.order.balanceDueTimeStamp || this.order.balancePaid == 1) {
          this.balanceCountdown = "";
          if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
          }
          return;
        }
        const now = Math.floor(Date.now() / 1e3);
        const rest = this.order.balanceDueTimeStamp - now;
        if (rest <= 0) {
          this.balanceCountdown = "已过期";
          if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
          }
          return;
        }
        const d = Math.floor(rest / 86400);
        const h = Math.floor(rest % 86400 / 3600);
        const m = Math.floor(rest % 3600 / 60);
        const s = rest % 60;
        const pad = (n) => n < 10 ? "0" + n : n;
        this.balanceCountdown = d > 0 ? `${d}天 ${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(h)}:${pad(m)}:${pad(s)}`;
      };
      tick();
      this.countdownTimer = setInterval(tick, 1e3);
    },
    copyOrderNumber() {
      common_vendor.index.setClipboardData({
        data: this.order.orderNo,
        success: () => common_vendor.index.showToast({ title: "订单号已复制", icon: "success" })
      });
    },
    copyFreightNo() {
      if (!this.order.freightNo)
        return;
      common_vendor.index.setClipboardData({
        data: this.order.freightNo,
        success: () => common_vendor.index.showToast({ title: "物流单号已复制", icon: "success" })
      });
    },
    goProduct(id) {
      if (!id)
        return;
      common_vendor.index.navigateTo({ url: `/pages/product/detail?id=${id}` });
    },
    getAppId() {
      let appid = "";
      try {
        appid = common_vendor.index.getAccountInfoSync().miniProgram.appId;
      } catch (e) {
      }
      return appid;
    },
    requestPayment(paymentData, payType) {
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
          setTimeout(() => this.loadOrderDetail(this.orderId), 1500);
        },
        fail: (err) => {
          const msg = err.errMsg && err.errMsg.indexOf("cancel") !== -1 ? "支付已取消" : "支付失败，请重试";
          common_vendor.index.showToast({ title: msg, icon: "none" });
        }
      });
    },
    async handlePay() {
      common_vendor.index.showLoading({ title: "获取支付参数..." });
      try {
        const res = await utils_request.api.order.pay({ id: this.order.id, payType: "full", appid: this.getAppId() });
        common_vendor.index.hideLoading();
        if (res.code === 200 && res.data && res.data.payment) {
          this.requestPayment(res.data.payment, res.data.payType || "full");
        } else {
          common_vendor.index.showToast({ title: res.msg || "获取支付参数失败", icon: "none" });
        }
      } catch (e) {
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({ title: "支付失败", icon: "none" });
      }
    },
    async handlePayDeposit() {
      common_vendor.index.showModal({
        title: "支付定金",
        content: `确认支付定金 ¥${this.order.depositAmount} 吗？`,
        success: async (res) => {
          if (!res.confirm)
            return;
          common_vendor.index.showLoading({ title: "获取支付参数..." });
          try {
            const result = await utils_request.api.order.pay({ id: this.order.id, payType: "deposit", appid: this.getAppId() });
            common_vendor.index.hideLoading();
            if (result.code === 200 && result.data && result.data.payment) {
              this.requestPayment(result.data.payment, "deposit");
            } else {
              common_vendor.index.showToast({ title: result.msg || "获取支付参数失败", icon: "none" });
            }
          } catch (e) {
            common_vendor.index.hideLoading();
            common_vendor.index.showToast({ title: "支付失败", icon: "none" });
          }
        }
      });
    },
    async handlePayBalance() {
      common_vendor.index.showModal({
        title: "支付尾款",
        content: `确认支付尾款 ¥${this.order.balanceAmount} 吗？`,
        success: async (res) => {
          if (!res.confirm)
            return;
          common_vendor.index.showLoading({ title: "获取支付参数..." });
          try {
            const result = await utils_request.api.order.pay({ id: this.order.id, payType: "balance", appid: this.getAppId() });
            common_vendor.index.hideLoading();
            if (result.code === 200 && result.data && result.data.payment) {
              this.requestPayment(result.data.payment, "balance");
            } else {
              common_vendor.index.showToast({ title: result.msg || "获取支付参数失败", icon: "none" });
            }
          } catch (e) {
            common_vendor.index.hideLoading();
            common_vendor.index.showToast({ title: "支付失败", icon: "none" });
          }
        }
      });
    },
    async handleCancel() {
      common_vendor.index.showModal({
        title: "提示",
        content: "确定要取消该订单吗？",
        success: async (res) => {
          if (!res.confirm)
            return;
          common_vendor.index.showLoading({ title: "处理中..." });
          try {
            const result = await utils_request.api.order.cancel({ id: this.order.id });
            common_vendor.index.hideLoading();
            if (result.code === 200) {
              common_vendor.index.showToast({ title: result.msg || "取消成功", icon: "success" });
              setTimeout(() => this.loadOrderDetail(this.orderId), 1500);
            } else {
              common_vendor.index.showToast({ title: result.msg || "取消失败", icon: "none" });
            }
          } catch (e) {
            common_vendor.index.hideLoading();
            common_vendor.index.showToast({ title: "取消失败", icon: "none" });
          }
        }
      });
    },
    handleRefund() {
      common_vendor.index.navigateTo({ url: `/pages/order/refund?id=${this.order.id}` });
    },
    async handleDelete() {
      common_vendor.index.showModal({
        title: "提示",
        content: "确定要删除该订单吗？删除后将无法恢复",
        success: async (res) => {
          if (!res.confirm)
            return;
          common_vendor.index.showLoading({ title: "删除中..." });
          try {
            const result = await utils_request.api.order.delete({ id: this.order.id });
            common_vendor.index.hideLoading();
            if (result.code === 200) {
              common_vendor.index.showToast({ title: result.msg || "删除成功", icon: "success" });
              setTimeout(() => common_vendor.index.navigateBack(), 1500);
            } else {
              common_vendor.index.showToast({ title: result.msg || "删除失败", icon: "none" });
            }
          } catch (e) {
            common_vendor.index.hideLoading();
            common_vendor.index.showToast({ title: "删除失败", icon: "none" });
          }
        }
      });
    },
    async handleConfirmReceipt() {
      common_vendor.index.showModal({
        title: "确认收货",
        content: "确认已收到商品？确认后将获得蜗壳奖励",
        success: async (res) => {
          if (!res.confirm)
            return;
          common_vendor.index.showLoading({ title: "处理中..." });
          try {
            const result = await utils_request.api.order.confirmReceipt({ id: this.order.id });
            common_vendor.index.hideLoading();
            if (result.code === 200) {
              common_vendor.index.showToast({ title: result.msg || "确认收货成功", icon: "success" });
              setTimeout(() => this.loadOrderDetail(this.orderId), 1500);
            } else {
              common_vendor.index.showToast({ title: result.msg || "操作失败", icon: "none" });
            }
          } catch (e) {
            common_vendor.index.hideLoading();
            common_vendor.index.showToast({ title: "操作失败", icon: "none" });
          }
        }
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: $data.loading && !$data.order.id
  }, $data.loading && !$data.order.id ? {} : common_vendor.e({
    b: common_vendor.t($data.order.statusText || "未知状态"),
    c: $options.statusMessage
  }, $options.statusMessage ? {
    d: common_vendor.t($options.statusMessage)
  } : {}, {
    e: $options.statusBgColor,
    f: $data.order.addressInfo && $data.order.addressInfo.name
  }, $data.order.addressInfo && $data.order.addressInfo.name ? {
    g: common_vendor.t($data.order.addressInfo.name),
    h: common_vendor.t($data.order.addressInfo.phone),
    i: common_vendor.t($data.order.addressInfo.province),
    j: common_vendor.t($data.order.addressInfo.city),
    k: common_vendor.t($data.order.addressInfo.region || $data.order.addressInfo.area),
    l: common_vendor.t($data.order.addressInfo.detail)
  } : {}, {
    m: $options.productList.length
  }, $options.productList.length ? {
    n: common_vendor.f($options.productList, (item, index, i0) => {
      return common_vendor.e({
        a: item.image,
        b: common_vendor.t(item.subtitle || ""),
        c: common_vendor.t(item.title || item.name),
        d: item.version
      }, item.version ? {
        e: common_vendor.t(item.version)
      } : {}, {
        f: common_vendor.t(item.quantity),
        g: common_vendor.t(item.price),
        h: index,
        i: common_vendor.o(($event) => $options.goProduct(item.productId), index)
      });
    })
  } : {}, {
    o: $options.productList.length
  }, $options.productList.length ? {
    p: common_vendor.t($data.order.totalPrice != null ? $data.order.totalPrice : "0.00")
  } : {}, {
    q: $data.order.isPresale
  }, $data.order.isPresale ? common_vendor.e({
    r: $data.order.depositAmount > 0
  }, $data.order.depositAmount > 0 ? common_vendor.e({
    s: common_vendor.t($data.order.depositAmount),
    t: common_vendor.t($data.order.depositPaid == 1 ? "已支付" : "未支付"),
    v: common_vendor.n($data.order.depositPaid == 1 ? "tag-success" : "tag-warn"),
    w: $data.order.depositPayTime
  }, $data.order.depositPayTime ? {
    x: common_vendor.t($data.order.depositPayTime)
  } : {}) : {}, {
    y: $data.order.balanceAmount > 0
  }, $data.order.balanceAmount > 0 ? common_vendor.e({
    z: common_vendor.t($data.order.balanceAmount),
    A: common_vendor.t($data.order.balancePaid == 1 ? "已支付" : "未支付"),
    B: common_vendor.n($data.order.balancePaid == 1 ? "tag-success" : "tag-warn"),
    C: $data.order.balancePayTime
  }, $data.order.balancePayTime ? {
    D: common_vendor.t($data.order.balancePayTime)
  } : {}, {
    E: $data.order.balanceDueTime && !$data.order.balancePaid
  }, $data.order.balanceDueTime && !$data.order.balancePaid ? {
    F: common_vendor.t($data.order.balanceDueTime)
  } : {}, {
    G: $data.order.balanceDueTimeStamp > 0 && !$data.order.balancePaid && $data.balanceCountdown
  }, $data.order.balanceDueTimeStamp > 0 && !$data.order.balancePaid && $data.balanceCountdown ? {
    H: common_vendor.t($data.balanceCountdown)
  } : {}) : {}, {
    I: common_vendor.t($data.order.totalPrice != null ? $data.order.totalPrice : "0.00")
  }) : {}, {
    J: common_vendor.t($data.order.orderNo),
    K: common_vendor.o((...args) => $options.copyOrderNumber && $options.copyOrderNumber(...args)),
    L: common_vendor.t($data.order.createDate),
    M: $data.order.payDate
  }, $data.order.payDate ? {
    N: common_vendor.t($data.order.payDate)
  } : {}, {
    O: common_vendor.t($data.order.paymentMethod || "微信支付"),
    P: $options.remarkText
  }, $options.remarkText ? {
    Q: common_vendor.t($options.remarkText)
  } : {}, {
    R: $data.order.refundStatus > 0
  }, $data.order.refundStatus > 0 ? common_vendor.e({
    S: common_vendor.t($data.order.refundStatusText),
    T: $data.order.refundReason
  }, $data.order.refundReason ? {
    U: common_vendor.t($data.order.refundReason)
  } : {}, {
    V: $data.order.refundAmount > 0
  }, $data.order.refundAmount > 0 ? {
    W: common_vendor.t($data.order.refundAmount)
  } : {}, {
    X: $data.order.refundRemark
  }, $data.order.refundRemark ? {
    Y: common_vendor.t($data.order.refundRemark)
  } : {}, {
    Z: $data.order.refundApplyTime
  }, $data.order.refundApplyTime ? {
    aa: common_vendor.t($data.order.refundApplyTime)
  } : {}, {
    ab: $data.order.refundTime
  }, $data.order.refundTime ? {
    ac: common_vendor.t($data.order.refundTime)
  } : {}) : {}, {
    ad: $data.order.freightName
  }, $data.order.freightName ? common_vendor.e({
    ae: common_vendor.t($data.order.freightName),
    af: common_vendor.t($data.order.freightNo),
    ag: common_vendor.o((...args) => $options.copyFreightNo && $options.copyFreightNo(...args)),
    ah: $data.order.freightTime
  }, $data.order.freightTime ? {
    ai: common_vendor.t($data.order.freightTime)
  } : {}) : {}), {
    aj: !$data.loading && $data.order.id
  }, !$data.loading && $data.order.id ? common_vendor.e({
    ak: $options.showCancelBtn
  }, $options.showCancelBtn ? {
    al: common_vendor.o((...args) => $options.handleCancel && $options.handleCancel(...args))
  } : {}, {
    am: $options.showDeleteBtn
  }, $options.showDeleteBtn ? {
    an: common_vendor.o((...args) => $options.handleDelete && $options.handleDelete(...args))
  } : {}, {
    ao: $data.order.isPresale && $data.order.canPayDeposit
  }, $data.order.isPresale && $data.order.canPayDeposit ? {
    ap: common_vendor.t($data.order.depositAmount != null ? $data.order.depositAmount : "0"),
    aq: common_vendor.o((...args) => $options.handlePayDeposit && $options.handlePayDeposit(...args))
  } : {}, {
    ar: $data.order.isPresale && $data.order.canPayBalance
  }, $data.order.isPresale && $data.order.canPayBalance ? {
    as: common_vendor.t($data.order.balanceAmount != null ? $data.order.balanceAmount : "0"),
    at: common_vendor.o((...args) => $options.handlePayBalance && $options.handlePayBalance(...args))
  } : {}, {
    av: $options.showPayBtn
  }, $options.showPayBtn ? {
    aw: common_vendor.o((...args) => $options.handlePay && $options.handlePay(...args))
  } : {}, {
    ax: $options.showConfirmReceiptBtn
  }, $options.showConfirmReceiptBtn ? {
    ay: common_vendor.o((...args) => $options.handleConfirmReceipt && $options.handleConfirmReceipt(...args))
  } : {}) : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-6b23c96c"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/order/detail.js.map
