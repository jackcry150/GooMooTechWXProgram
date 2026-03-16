"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "CreateOrder",
  data() {
    return {
      product: [],
      address: [],
      remarks: "",
      shippingFee: 0,
      shippingTemplates: []
      // 运费模板列表
    };
  },
  onLoad(options) {
    if (options.cartIds) {
      const cartIds = decodeURIComponent(options.cartIds || "").split(",").filter(Boolean);
      const productIds = (options.productIds ? decodeURIComponent(options.productIds || "") : "").split(",").filter(Boolean);
      this.loadFromCart(cartIds, productIds);
    } else if (options.productId) {
      this.loadBuyNow(options.productId, decodeURIComponent(options.version || ""), parseInt(options.quantity || 1));
    }
  },
  onShow() {
    const selectedAddress = common_vendor.index.getStorageSync("selectedAddress");
    if (selectedAddress) {
      this.address = selectedAddress;
    } else {
      this.getAddressdeDault();
    }
  },
  computed: {
    hasPresale() {
      return this.product.some((item) => item.type == 2);
    },
    totalDepositAmount() {
      let total = 0;
      this.product.forEach((item) => {
        if (item.type == 2 && item.deposit) {
          total += parseFloat(item.deposit) * (item.quantity || 1);
        }
      });
      return total;
    },
    totalBalanceAmount() {
      let total = 0;
      this.product.forEach((item) => {
        if (item.type == 2 && item.price && item.deposit) {
          const balance = parseFloat(item.price) - parseFloat(item.deposit);
          total += balance * (item.quantity || 1);
        }
      });
      return total;
    },
    totalPrice() {
      if (this.hasPresale && this.totalDepositAmount > 0) {
        return (parseFloat(this.totalDepositAmount) + parseFloat(this.shippingFee)).toFixed(2);
      }
      const productTotal = this.product.reduce((total, item) => total + item.price * item.quantity, 0);
      return (parseFloat(productTotal) + parseFloat(this.shippingFee)).toFixed(2);
    },
    shippingFeeText() {
      if (this.shippingFee <= 0) {
        return "包邮";
      }
      return "¥" + parseFloat(this.shippingFee).toFixed(2);
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
            common_vendor.index.navigateBack();
            return;
          }
        }
      });
    },
    loadCartItems(items) {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        this.product = items;
        this.calculateShippingFee();
      }
    },
    async loadFromCart(cartIds, productIds) {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      if (!cartIds || cartIds.length === 0) {
        common_vendor.index.showToast({ title: "请选择商品", icon: "none" });
        setTimeout(() => common_vendor.index.navigateBack(), 1500);
        return;
      }
      common_vendor.index.showLoading({ title: "加载中..." });
      try {
        const res = await utils_request.api.cart.list();
        common_vendor.index.hideLoading();
        if (res.code !== 200 || !res.data) {
          common_vendor.index.showToast({ title: res.msg || "加载失败", icon: "none" });
          setTimeout(() => common_vendor.index.navigateBack(), 1500);
          return;
        }
        const ids = cartIds.map((id) => parseInt(id)).filter((n) => n > 0);
        const items = (res.data || []).filter((item) => ids.includes(parseInt(item.id)));
        if (items.length === 0) {
          common_vendor.index.showToast({ title: "购物车商品已失效，请重新选择", icon: "none" });
          setTimeout(() => common_vendor.index.navigateBack(), 1500);
          return;
        }
        this.product = items;
        this.calculateShippingFee();
      } catch (e) {
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({ title: "加载失败", icon: "none" });
        setTimeout(() => common_vendor.index.navigateBack(), 1500);
      }
    },
    async loadBuyNow(productId, version, quantity) {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      common_vendor.index.showLoading({ title: "加载中..." });
      try {
        const res = await utils_request.api.product.detail({ id: productId });
        common_vendor.index.hideLoading();
        if (res.code !== 200 || !res.data) {
          common_vendor.index.showToast({ title: res.msg || "商品不存在", icon: "none" });
          setTimeout(() => common_vendor.index.navigateBack(), 1500);
          return;
        }
        const p = res.data;
        const versionStr = version || p.version && p.version[0] || "";
        const item = {
          id: "",
          productId: p.id,
          productCode: p.productId || "",
          title: p.title,
          subtitle: p.subtitle || "",
          image: Array.isArray(p.image) ? p.image[0] : p.image,
          price: p.type == 2 ? parseFloat(p.deposit || 0) : parseFloat(p.price || 0),
          version: versionStr,
          quantity,
          selected: true,
          type: p.type,
          stock: p.stock,
          limitStock: p.limitStock || 0,
          deposit: p.deposit,
          shippingTemplateId: p.shippingTemplateId,
          shippingTemplate: p.shippingTemplate
        };
        this.product = [item];
        this.calculateShippingFee();
      } catch (e) {
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({ title: "加载失败", icon: "none" });
        setTimeout(() => common_vendor.index.navigateBack(), 1500);
      }
    },
    // 计算运费
    calculateShippingFee() {
      let totalFee = 0;
      const templateGroups = {};
      const templateMap = {};
      this.product.forEach((item) => {
        if (item.shippingTemplate && item.shippingTemplate.id) {
          const templateId = item.shippingTemplate.id;
          if (!templateGroups[templateId]) {
            templateGroups[templateId] = {
              template: item.shippingTemplate,
              quantity: 0,
              weight: 0
            };
            templateMap[templateId] = item.shippingTemplate;
          }
          templateGroups[templateId].quantity += item.quantity || 1;
          templateGroups[templateId].weight += (item.quantity || 1) * 0.1;
        }
      });
      Object.values(templateGroups).forEach((group) => {
        const template = group.template;
        let fee = 0;
        if (template.type == 1) {
          const firstPiece = parseInt(template.firstPiece) || 1;
          const firstFee = parseFloat(template.firstFee) || 0;
          const continuePiece = parseInt(template.continuePiece) || 1;
          const continueFee = parseFloat(template.continueFee) || 0;
          if (group.quantity <= firstPiece) {
            fee = firstFee;
          } else {
            const continueCount = Math.ceil((group.quantity - firstPiece) / continuePiece);
            fee = firstFee + continueCount * continueFee;
          }
        } else if (template.type == 2) {
          const firstWeight = parseFloat(template.firstWeight) || 1;
          const firstFee = parseFloat(template.firstFee) || 0;
          const continueWeight = parseFloat(template.continueWeight) || 1;
          const continueFee = parseFloat(template.continueFee) || 0;
          if (group.weight <= firstWeight) {
            fee = firstFee;
          } else {
            const continueCount = Math.ceil((group.weight - firstWeight) / continueWeight);
            fee = firstFee + continueCount * continueFee;
          }
        }
        totalFee += fee;
      });
      this.shippingFee = totalFee;
      this.shippingTemplates = Object.values(templateMap);
    },
    async getAddressdeDault() {
      if (!this.address) {
        try {
          const response = await utils_request.api.address.default();
          this.address = response.data;
        } catch (error) {
        }
      }
    },
    selectAddress() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: "/pages/address/list?t=1"
        });
      }
    },
    async submitOrder() {
      if (!this.address) {
        common_vendor.index.showToast({
          title: "请选择收货地址",
          icon: "none"
        });
        return;
      }
      common_vendor.index.showLoading({
        title: "提交中..."
      });
      try {
        const productMinimal = this.product.map((p) => ({
          productId: p.productId,
          version: p.version || "",
          quantity: p.quantity || 1,
          id: p.id
        }));
        const params = {
          product: productMinimal,
          address: { id: this.address.id },
          remarks: this.remarks,
          shippingFee: this.shippingFee
        };
        const response = await utils_request.api.order.create(params);
        common_vendor.index.hideLoading();
        if (response.code === 200 && response.data) {
          const orderId = response.data.orderId;
          const orderNo = response.data.orderNo;
          if (!orderId || !orderNo) {
            common_vendor.index.showToast({ title: "订单创建成功", icon: "success" });
            setTimeout(() => common_vendor.index.redirectTo({ url: "/pages/order/list" }), 1500);
            return;
          }
          common_vendor.index.showLoading({ title: "获取支付参数..." });
          const payRes = await utils_request.api.order.pay({ id: orderId, payType: "full" });
          common_vendor.index.hideLoading();
          if (payRes.code === 200 && payRes.data && payRes.data.payment) {
            const payment = { ...payRes.data.payment, orderId: payRes.data.orderId || orderId };
            this.requestPayment(payment, orderNo);
          } else {
            common_vendor.index.showToast({ title: payRes.msg || "获取支付参数失败", icon: "none" });
            setTimeout(() => {
              common_vendor.index.redirectTo({ url: "/pages/order/detail?id=" + orderId });
            }, 1500);
          }
        } else {
          common_vendor.index.showToast({ title: response.msg || "提交失败", icon: "none" });
        }
      } catch (error) {
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({ title: "提交失败", icon: "none" });
      }
    },
    // 调用小程序支付
    requestPayment(paymentData, orderNo) {
      common_vendor.index.requestPayment({
        provider: "wxpay",
        timeStamp: paymentData.timeStamp || "",
        nonceStr: paymentData.nonceStr || "",
        package: paymentData.package || "",
        signType: paymentData.signType || "RSA",
        paySign: paymentData.paySign || "",
        success: (res) => {
          common_vendor.index.showToast({
            title: "支付成功",
            icon: "success"
          });
          setTimeout(() => {
            common_vendor.index.redirectTo({
              url: "/pages/order/detail?id=" + (paymentData.orderId || "")
            });
          }, 1500);
        },
        fail: (err) => {
          if (err.errMsg && err.errMsg.indexOf("cancel") !== -1) {
            common_vendor.index.showToast({
              title: "支付已取消",
              icon: "none"
            });
            setTimeout(() => {
              common_vendor.index.redirectTo({
                url: "/pages/order/list"
              });
            }, 1500);
          } else {
            common_vendor.index.showToast({
              title: "支付失败，请稍后重试",
              icon: "none"
            });
            setTimeout(() => {
              common_vendor.index.redirectTo({
                url: "/pages/order/list"
              });
            }, 1500);
          }
        }
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: $data.address
  }, $data.address ? {
    b: common_assets._imports_0$5,
    c: common_vendor.o((...args) => $options.selectAddress && $options.selectAddress(...args)),
    d: common_vendor.t($data.address.name),
    e: common_vendor.t($data.address.phone),
    f: common_vendor.t($data.address.province),
    g: common_vendor.t($data.address.city),
    h: common_vendor.t($data.address.area),
    i: common_vendor.t($data.address.detail)
  } : {
    j: common_assets._imports_0$5,
    k: common_vendor.o((...args) => $options.selectAddress && $options.selectAddress(...args))
  }, {
    l: common_vendor.f($data.product, (item, index, i0) => {
      return common_vendor.e({
        a: item.image,
        b: item.type == 2
      }, item.type == 2 ? {} : item.type == 1 ? {} : {}, {
        c: item.type == 1,
        d: common_vendor.t(item.title),
        e: common_vendor.t(item.subtitle),
        f: common_vendor.t(item.version),
        g: common_vendor.t(item.price),
        h: common_vendor.t(item.quantity),
        i: index
      });
    }),
    m: $options.hasPresale && $options.totalDepositAmount > 0
  }, $options.hasPresale && $options.totalDepositAmount > 0 ? {
    n: common_vendor.t($options.totalDepositAmount.toFixed(2))
  } : {}, {
    o: $options.hasPresale && $options.totalBalanceAmount > 0
  }, $options.hasPresale && $options.totalBalanceAmount > 0 ? {
    p: common_vendor.t($options.totalBalanceAmount.toFixed(2))
  } : {}, {
    q: common_vendor.t($options.shippingFeeText),
    r: $data.shippingTemplates.length > 0 && $data.shippingFee > 0
  }, $data.shippingTemplates.length > 0 && $data.shippingFee > 0 ? {
    s: common_vendor.f($data.shippingTemplates, (template, index, i0) => {
      return {
        a: common_vendor.t(template.name),
        b: common_vendor.t(template.type == 1 ? "按件计费" : "按重量计费"),
        c: common_vendor.t(template.type == 1 ? `首${template.firstPiece}件¥${parseFloat(template.firstFee).toFixed(2)}，续${template.continuePiece}件¥${parseFloat(template.continueFee).toFixed(2)}` : `首${template.firstWeight}kg¥${parseFloat(template.firstFee).toFixed(2)}，续${template.continueWeight}kg¥${parseFloat(template.continueFee).toFixed(2)}`),
        d: index
      };
    })
  } : {}, {
    t: $data.remarks,
    v: common_vendor.o(($event) => $data.remarks = $event.detail.value),
    w: $options.hasPresale && $options.totalDepositAmount > 0
  }, $options.hasPresale && $options.totalDepositAmount > 0 ? {
    x: common_vendor.t($options.totalPrice),
    y: common_vendor.t($options.totalBalanceAmount.toFixed(2))
  } : {
    z: common_vendor.t($options.totalPrice)
  }, {
    A: common_vendor.o((...args) => $options.submitOrder && $options.submitOrder(...args))
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-8837ac90"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/order/create.js.map
