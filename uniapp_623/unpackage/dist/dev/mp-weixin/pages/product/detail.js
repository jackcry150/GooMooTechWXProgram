"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "ProductDetail",
  data() {
    return {
      product: [],
      config: [],
      countdown: "--",
      countdownTimer: null,
      contentTab: 1,
      showDeduct: false,
      showPopup: false,
      currentVersion: 0,
      quantity: 1,
      orderType: 1,
      cartNumbers: 0,
      productCollect: 0,
      specExpanded: {
        proportion: false,
        dimensions: false,
        material: false,
        copyright: false
      },
      showSpecDetail: false,
      currentSpecType: "",
      currentSpecValue: ""
    };
  },
  computed: {
    hasProductSpecs() {
      return !!(this.product.proportion || this.product.dimensions || this.product.material || this.product.copyright);
    },
    // 计算时间轴进度百分比
    timelineProgress() {
      if (!this.product.startTimeStamp || !this.product.endTimeStamp) {
        return 0;
      }
      const now = Math.floor(Date.now() / 1e3);
      const start = this.product.startTimeStamp;
      const end = this.product.endTimeStamp;
      if (now < start) {
        return 0;
      }
      if (now >= end) {
        return 100;
      }
      const total = end - start;
      const passed = now - start;
      const percentage = passed / total * 100;
      return Math.min(100, Math.max(0, percentage));
    }
  },
  onLoad(options) {
    if (options.id) {
      this.getProductDetail(options.id);
      this.getCartCount(options.id);
      this.getConfig();
    } else {
      common_vendor.index.redirectTo({
        url: "/pages/index/index"
      });
    }
  },
  watch: {
    // 监听数量变化，确保不超过库存和限购
    quantity(newVal) {
      if (this.product.id) {
        const maxAllowed = this.getMaxAllowedQuantity();
        if (newVal > maxAllowed) {
          this.$nextTick(() => {
            this.quantity = maxAllowed;
            common_vendor.index.showToast({
              title: `最多可购买${maxAllowed}件`,
              icon: "none"
            });
          });
        }
      }
    }
  },
  onUnload() {
    if (this.countdownTimer) {
      clearInterval(this.countdownTimer);
      this.countdownTimer = null;
    }
  },
  methods: {
    async getProductDetail(id) {
      try {
        const params = {
          id
        };
        const response = await utils_request.api.product.detail(params);
        this.product = response.data;
        if (this.product.type == 2 && this.product.endTimeStamp) {
          this.startCountdown();
        }
      } catch (error) {
        common_vendor.index.showToast({
          title: "加载失败",
          icon: "none"
        });
      }
    },
    // 开始倒计时
    startCountdown() {
      if (this.countdownTimer) {
        clearInterval(this.countdownTimer);
      }
      const updateCountdown = () => {
        if (!this.product.endTimeStamp) {
          this.countdown = "--";
          return;
        }
        const endTime = this.product.endTimeStamp;
        const now = Math.floor(Date.now() / 1e3);
        const remaining = endTime - now;
        if (remaining <= 0) {
          this.countdown = "已结束";
          clearInterval(this.countdownTimer);
          this.countdownTimer = null;
          return;
        }
        const days = Math.floor(remaining / 86400);
        const hours = Math.floor(remaining % 86400 / 3600);
        const minutes = Math.floor(remaining % 3600 / 60);
        const seconds = remaining % 60;
        const pad = (num) => {
          return num < 10 ? "0" + num : String(num);
        };
        if (days > 0) {
          this.countdown = `${days}天 ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        } else {
          this.countdown = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }
      };
      updateCountdown();
      this.countdownTimer = setInterval(updateCountdown, 1e3);
    },
    async getConfig() {
      try {
        const response = await utils_request.api.setting.info();
        this.config = response.data;
      } catch (error) {
      }
    },
    onShareAppMessage() {
      return {
        title: this.product.title + " " + this.product.subtitle,
        path: "/pages/product/detail?id=" + this.product.id,
        imageUrl: this.product.image[0]
      };
    },
    onShareTimeline() {
      return {
        title: this.product.title + " " + this.product.subtitle,
        path: "id=" + this.product.id,
        imageUrl: this.product.image[0]
      };
    },
    async getCartCount(id) {
      const token = common_vendor.index.getStorageSync("token");
      if (token) {
        try {
          const params = { id };
          const response = await utils_request.api.cart.count(params);
          this.cartNumbers = response.data.count;
          this.productCollect = response.data.collect;
        } catch (error) {
        }
      }
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
            return;
          }
        }
      });
    },
    goToCustomer() {
      if (this.config) {
        common_vendor.wx$1.openCustomerServiceChat({
          extInfo: {
            url: this.config.customerLink
          },
          corpId: this.config.corpId,
          success: (res) => {
            common_vendor.index.__f__("log", "at pages/product/detail.vue:457", " openCustomerServiceChat success:" + JSON.stringify(res));
          },
          fail: (err) => {
            common_vendor.index.__f__("log", "at pages/product/detail.vue:460", " openCustomerServiceChat fail:" + JSON.stringify(err));
          }
        });
      }
    },
    async goToCollect() {
      try {
        const params = {
          id: this.product.id,
          collect: this.productCollect
        };
        const response = await utils_request.api.collect.edit(params);
        common_vendor.index.showToast({
          title: response.msg,
          icon: "success"
        });
        this.productCollect = response.data;
      } catch (error) {
        common_vendor.index.showToast({
          title: "操作失败",
          icon: "none"
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
    showDeductPopup() {
      this.showDeduct = true;
    },
    hideDeductPopup() {
      this.showDeduct = false;
    },
    showSpecDetailPopup(type, value) {
      if (value && value.length > 20) {
        this.currentSpecType = type;
        this.currentSpecValue = value;
        this.showSpecDetail = true;
      }
    },
    hideSpecDetail() {
      this.showSpecDetail = false;
      this.currentSpecType = "";
      this.currentSpecValue = "";
    },
    getSpecLabel(type) {
      const labels = {
        proportion: "比例",
        dimensions: "尺寸",
        material: "材质",
        copyright: "版权所属"
      };
      return labels[type] || "";
    },
    // 格式化预售开始日期：2026.01.15
    formatPresaleDate(dateStr) {
      if (!dateStr)
        return "";
      const date = new Date(dateStr.replace(/-/g, "/"));
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}.${month}.${day}`;
    },
    // 格式化预售结束日期：2026年9月30日
    formatPresaleEndDate(dateStr) {
      if (!dateStr)
        return "";
      const date = new Date(dateStr.replace(/-/g, "/"));
      const year = date.getFullYear();
      const month = date.getMonth() + 1;
      const day = date.getDate();
      return `${year}年${month}月${day}日`;
    },
    hideDeductPopup() {
      this.showDeduct = false;
    },
    //详情切换
    switchTab(tabKey) {
      this.contentTab = tabKey;
    },
    // 显示弹窗
    showCartPopup(type) {
      this.showPopup = true;
      this.orderType = type;
    },
    // 处理预售订单（立即预约）
    handlePresaleOrder() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      this.showCartPopup(2);
    },
    // 隐藏弹窗
    hideCartPopup() {
      this.showPopup = false;
    },
    // 选择规格
    selectSpec(index) {
      this.currentVersion = index;
    },
    // 减少数量
    decreaseQuantity() {
      if (this.quantity > 1) {
        this.quantity--;
      }
    },
    // 增加数量
    increaseQuantity() {
      if (this.product.type != 2 && this.product.stock > 0) {
        if (this.quantity >= this.product.stock) {
          common_vendor.index.showToast({
            title: "库存不足",
            icon: "none"
          });
          return;
        }
      }
      if (this.product.limitStock > 0) {
        const maxAllowed = this.getMaxAllowedQuantity();
        if (this.quantity >= maxAllowed) {
          common_vendor.index.showToast({
            title: `限购${this.product.limitStock}件`,
            icon: "none"
          });
          return;
        }
      }
      this.quantity++;
    },
    // 获取最大允许购买数量
    getMaxAllowedQuantity() {
      if (this.product.limitStock <= 0) {
        return this.product.type == 2 ? 999 : this.product.stock || 999;
      }
      return this.product.limitStock;
    },
    // 检查库存和限购
    checkStockAndLimit() {
      if (this.product.type != 2) {
        if (this.product.stock <= 0) {
          common_vendor.index.showToast({
            title: "商品已售罄",
            icon: "none"
          });
          return false;
        }
        if (this.quantity > this.product.stock) {
          common_vendor.index.showToast({
            title: `库存不足，最多可购买${this.product.stock}件`,
            icon: "none"
          });
          return false;
        }
      }
      if (this.product.limitStock > 0) {
        if (this.quantity > this.product.limitStock) {
          common_vendor.index.showToast({
            title: `限购${this.product.limitStock}件，您已超过限购数量`,
            icon: "none"
          });
          return false;
        }
      }
      return true;
    },
    async addToOrder() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      if (!this.checkStockAndLimit()) {
        return;
      }
      try {
        if (this.orderType === 1) {
          const params = {
            productId: this.product.id,
            version: this.product.version[this.currentVersion],
            quantity: this.quantity
          };
          const response = await utils_request.api.cart.create(params);
          if (response.code !== 200) {
            common_vendor.index.showToast({
              title: response.msg || "加入购物车失败",
              icon: "none"
            });
            return;
          }
          this.cartNumbers = response.data.count;
          common_vendor.index.showToast({
            title: response.msg,
            icon: "success"
          });
        } else if (this.orderType === 2) {
          const productId = this.product.id;
          const version = encodeURIComponent(this.product.version[this.currentVersion] || "");
          const quantity = this.quantity;
          common_vendor.index.navigateTo({
            url: `/pages/order/create?productId=${productId}&version=${version}&quantity=${quantity}`
          });
        }
        this.hideCartPopup();
      } catch (error) {
        common_vendor.index.showToast({
          title: "操作失败",
          icon: "none"
        });
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.f($data.product.image, (image, index, i0) => {
      return {
        a: image,
        b: index
      };
    }),
    b: $data.product.type == 2
  }, $data.product.type == 2 ? {} : {}, {
    c: $data.product.type == 2
  }, $data.product.type == 2 ? common_vendor.e({
    d: common_vendor.t($data.product.deposit),
    e: common_vendor.t($data.product.price),
    f: $data.product.endTimeStamp
  }, $data.product.endTimeStamp ? {
    g: common_vendor.t($data.countdown)
  } : {}) : {}, {
    h: $data.product.deduct > 0
  }, $data.product.deduct > 0 ? {
    i: common_vendor.t($data.product.price - $data.product.deduct),
    j: common_vendor.o((...args) => $options.showDeductPopup && $options.showDeductPopup(...args))
  } : {}, {
    k: $data.product.type == 1
  }, $data.product.type == 1 ? {
    l: common_vendor.t($data.product.price)
  } : {}, {
    m: $data.product.type == 1
  }, $data.product.type == 1 ? {} : $data.product.type == 2 ? {} : {}, {
    n: $data.product.type == 2,
    o: common_vendor.t($data.product.subtitle),
    p: common_vendor.t($data.product.title),
    q: $options.hasProductSpecs
  }, $options.hasProductSpecs ? common_vendor.e({
    r: $data.product.proportion
  }, $data.product.proportion ? common_vendor.e({
    s: common_vendor.t($data.product.proportion),
    t: $data.product.proportion.length > 20 ? 1 : "",
    v: $data.product.proportion.length > 20
  }, $data.product.proportion.length > 20 ? {} : {}, {
    w: common_vendor.o(($event) => $options.showSpecDetailPopup("proportion", $data.product.proportion))
  }) : {}, {
    x: $data.product.dimensions
  }, $data.product.dimensions ? common_vendor.e({
    y: common_vendor.t($data.product.dimensions),
    z: $data.product.dimensions.length > 20 ? 1 : "",
    A: $data.product.dimensions.length > 20
  }, $data.product.dimensions.length > 20 ? {} : {}, {
    B: common_vendor.o(($event) => $options.showSpecDetailPopup("dimensions", $data.product.dimensions))
  }) : {}, {
    C: $data.product.material
  }, $data.product.material ? common_vendor.e({
    D: common_vendor.t($data.product.material),
    E: $data.product.material.length > 20 ? 1 : "",
    F: $data.product.material.length > 20
  }, $data.product.material.length > 20 ? {} : {}, {
    G: common_vendor.o(($event) => $options.showSpecDetailPopup("material", $data.product.material))
  }) : {}, {
    H: $data.product.copyright
  }, $data.product.copyright ? common_vendor.e({
    I: common_vendor.t($data.product.copyright),
    J: $data.product.copyright.length > 20 ? 1 : "",
    K: $data.product.copyright.length > 20
  }, $data.product.copyright.length > 20 ? {} : {}, {
    L: common_vendor.o(($event) => $options.showSpecDetailPopup("copyright", $data.product.copyright))
  }) : {}) : {}, {
    M: $data.showSpecDetail
  }, $data.showSpecDetail ? {
    N: common_vendor.o((...args) => $options.hideSpecDetail && $options.hideSpecDetail(...args)),
    O: common_vendor.t($options.getSpecLabel($data.currentSpecType)),
    P: common_vendor.t($data.currentSpecValue),
    Q: common_vendor.o((...args) => $options.hideSpecDetail && $options.hideSpecDetail(...args))
  } : {}, {
    R: $data.product.type == 2 && $data.product.startTime && $data.product.endTime
  }, $data.product.type == 2 && $data.product.startTime && $data.product.endTime ? {
    S: common_vendor.t($options.formatPresaleDate($data.product.startTime)),
    T: common_vendor.t($options.formatPresaleEndDate($data.product.endTime)),
    U: $options.timelineProgress + "%"
  } : {}, {
    V: common_vendor.n($data.contentTab == 1 ? "active" : ""),
    W: common_vendor.o(($event) => $options.switchTab(1)),
    X: common_vendor.n($data.contentTab == 2 ? "active" : ""),
    Y: common_vendor.o(($event) => $options.switchTab(2)),
    Z: $data.contentTab == 1
  }, $data.contentTab == 1 ? {
    aa: common_vendor.f($data.product.content, (image, index, i0) => {
      return {
        a: index,
        b: image
      };
    })
  } : {}, {
    ab: $data.contentTab == 2
  }, $data.contentTab == 2 ? {
    ac: common_vendor.f($data.product.purchaseNotice, (image, index, i0) => {
      return {
        a: index,
        b: image
      };
    })
  } : {}, {
    ad: common_assets._imports_0$7,
    ae: common_assets._imports_1$5,
    af: common_vendor.o((...args) => $options.goToCustomer && $options.goToCustomer(...args)),
    ag: $data.productCollect === 1
  }, $data.productCollect === 1 ? {
    ah: common_assets._imports_2$3
  } : {
    ai: common_assets._imports_3$3
  }, {
    aj: common_vendor.o((...args) => $options.goToCollect && $options.goToCollect(...args)),
    ak: common_assets._imports_4$2,
    al: $data.cartNumbers > 0
  }, $data.cartNumbers > 0 ? {
    am: common_vendor.t($data.cartNumbers)
  } : {}, {
    an: common_vendor.o((...args) => $options.goToCart && $options.goToCart(...args)),
    ao: $data.product.type != 2
  }, $data.product.type != 2 ? {
    ap: common_vendor.o(($event) => $options.showCartPopup(1)),
    aq: common_vendor.o(($event) => $options.showCartPopup(2))
  } : {}, {
    ar: $data.product.type == 2
  }, $data.product.type == 2 ? {
    as: common_vendor.o((...args) => $options.handlePresaleOrder && $options.handlePresaleOrder(...args))
  } : {}, {
    at: $data.showDeduct
  }, $data.showDeduct ? {
    av: common_vendor.t($data.product.deduct),
    aw: common_vendor.o((...args) => $options.hideDeductPopup && $options.hideDeductPopup(...args))
  } : {}, {
    ax: $data.showPopup
  }, $data.showPopup ? common_vendor.e({
    ay: common_vendor.o((...args) => $options.hideCartPopup && $options.hideCartPopup(...args)),
    az: $data.product.image[0],
    aA: common_vendor.t($data.product.subtitle),
    aB: common_vendor.t($data.product.title),
    aC: $data.product.type == 2
  }, $data.product.type == 2 ? {
    aD: common_vendor.t($data.product.deposit)
  } : {
    aE: common_vendor.t($data.product.price)
  }, {
    aF: common_vendor.f($data.product.version, (spec, index, i0) => {
      return {
        a: common_vendor.t(spec),
        b: index,
        c: common_vendor.n($data.currentVersion === index ? "active" : ""),
        d: common_vendor.o(($event) => $options.selectSpec(index), index)
      };
    }),
    aG: common_vendor.o((...args) => $options.decreaseQuantity && $options.decreaseQuantity(...args)),
    aH: common_vendor.n($data.quantity <= 1 ? "disabled" : ""),
    aI: common_vendor.t($data.quantity),
    aJ: common_vendor.o((...args) => $options.increaseQuantity && $options.increaseQuantity(...args)),
    aK: $data.orderType == 1
  }, $data.orderType == 1 ? {} : $data.orderType == 2 && $data.product.type == 2 ? {} : $data.orderType == 2 ? {} : {}, {
    aL: $data.orderType == 2 && $data.product.type == 2,
    aM: $data.orderType == 2,
    aN: common_vendor.o((...args) => $options.addToOrder && $options.addToOrder(...args))
  }) : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-acf502d9"]]);
_sfc_main.__runtimeHooks = 6;
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/product/detail.js.map
