"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      statusBarHeight: 44,
      bannerList: [],
      productListHot: [],
      productListRecom: [],
      cartProductMap: {},
      countdownTimers: [],
      placeholderImage: "/static/image/600_694.png",
      quickActions: [
        {
          title: "品牌介绍",
          desc: "了解我们",
          icon: "/static/image/icon_brand.png",
          url: "/pages/about/about"
        },
        {
          title: "线上收藏卡",
          desc: "收集专属回忆",
          icon: "/static/image/icon_collect.png",
          url: "/pages/collect/collect"
        },
        {
          title: "联系客服",
          desc: "贴心为您服务",
          icon: "/static/image/icon_customer.png",
          url: "/pages/customer/customer"
        },
        {
          title: "特别贩售",
          desc: "限时发售中",
          icon: "/static/image/icon_shop.png",
          url: "/pages/sell/sell"
        }
      ]
    };
  },
  computed: {
    featuredProduct() {
      return this.productListHot[0] || this.productListRecom[0] || null;
    },
    heroProductImage() {
      return this.getProductImage(this.featuredProduct);
    },
    heroBannerImage() {
      return this.bannerList[0] && this.bannerList[0].image || this.heroProductImage;
    },
    heroSubtitle() {
      if (this.featuredProduct && this.featuredProduct.subtitle) {
        return this.featuredProduct.subtitle;
      }
      return "萌力全开 · 猫系穿搭系列";
    },
    heroDotCount() {
      const count = this.bannerList.length || 3;
      return Math.min(count, 3);
    },
    displayProducts() {
      const merged = [...this.productListHot, ...this.productListRecom];
      const seen = /* @__PURE__ */ new Set();
      return merged.filter((item) => {
        const key = item.id || `${item.title}-${item.price}`;
        if (seen.has(key)) {
          return false;
        }
        seen.add(key);
        return true;
      }).slice(0, 4);
    }
  },
  onLoad() {
    const systemInfo = common_vendor.index.getSystemInfoSync ? common_vendor.index.getSystemInfoSync() : {};
    this.statusBarHeight = systemInfo.statusBarHeight || 22;
    this.loadInfo();
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
      return Promise.allSettled([
        this.getBannerList(),
        this.getProductList(),
        this.getHomeCartState()
      ]);
    },
    async getBannerList() {
      try {
        const response = await utils_request.api.banner.list();
        this.bannerList = response.data || [];
      } catch (error) {
        common_vendor.index.__f__("error", "at pages/index/index.vue:205", "getBannerList error", error);
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
        common_vendor.index.__f__("error", "at pages/index/index.vue:224", "getProductList error", error);
      }
    },
    async getHomeCartState() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.cartProductMap = {};
        return;
      }
      try {
        const response = await utils_request.api.cart.list();
        const nextMap = {};
        const list = Array.isArray(response.data) ? response.data : [];
        list.forEach((item) => {
          const key = this.getCartKey(item.productId, item.version);
          nextMap[key] = {
            id: item.id,
            productId: item.productId,
            version: item.version,
            quantity: item.quantity
          };
        });
        this.cartProductMap = nextMap;
      } catch (error) {
        this.cartProductMap = {};
      }
    },
    startCountdowns() {
      this.productListHot.forEach((product, index) => {
        if (product.type == 2 && product.endTimeStamp) {
          this.startCountdown(product, index);
        }
      });
      this.productListRecom.forEach((product, index) => {
        if (product.type == 2 && product.endTimeStamp) {
          this.startCountdown(product, index + this.productListHot.length);
        }
      });
    },
    startCountdown(product) {
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
        const pad = (value) => value < 10 ? `0${value}` : value;
        const countdownText = days > 0 ? `${days}天 ${pad(hours)}:${pad(minutes)}:${pad(seconds)}` : `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        this.$set(product, "countdown", countdownText);
      };
      updateCountdown();
      const timer = setInterval(updateCountdown, 1e3);
      this.countdownTimers.push(timer);
    },
    getProductImage(product) {
      if (product && product.image && product.image.length && product.image[0]) {
        return product.image[0];
      }
      return this.placeholderImage;
    },
    getProductTag(product, index) {
      if (product && product.type == 2) {
        return "预售";
      }
      const tags = ["新品", "热门", "推荐", "精选"];
      return tags[index % tags.length];
    },
    getProductDescription(product) {
      if (product && product.subtitle) {
        return product.subtitle;
      }
      if (product && product.type == 2) {
        return "限量预定款";
      }
      return "软萌可爱系列";
    },
    getProductPrice(product) {
      if (!product) {
        return "¥0.00";
      }
      if (product.type == 2 && product.deposit) {
        return `定金 ¥${product.deposit}`;
      }
      return `¥${Number(product.price || 0).toFixed(2)}`;
    },
    getProductHeat(index) {
      return `${(2.4 + index * 0.7).toFixed(1)}k`;
    },
    getDefaultVersion(product) {
      if (!product) {
        return "默认规格";
      }
      if (Array.isArray(product.version) && product.version.length) {
        return product.version[0] || "默认规格";
      }
      if (typeof product.version === "string" && product.version.trim()) {
        return product.version.trim();
      }
      return "默认规格";
    },
    getCartKey(productId, version) {
      return `${productId || ""}::${String(version || "默认规格").trim() || "默认规格"}`;
    },
    isProductInCart(product) {
      if (!product || !product.id) {
        return false;
      }
      return Object.values(this.cartProductMap).some((entry) => {
        return String(entry.productId) === String(product.id);
      });
    },
    getCartEntry(product) {
      if (!product || !product.id) {
        return null;
      }
      const defaultKey = this.getCartKey(product.id, this.getDefaultVersion(product));
      if (this.cartProductMap[defaultKey]) {
        return this.cartProductMap[defaultKey];
      }
      return Object.values(this.cartProductMap).find((entry) => {
        return String(entry.productId) === String(product.id);
      }) || null;
    },
    handleHeroClick() {
      if (this.featuredProduct && this.featuredProduct.id) {
        this.goToProductDetail(this.featuredProduct.id);
        return;
      }
      this.goToSell();
    },
    goLogin() {
      common_vendor.index.showModal({
        content: "使用当前功能需要您进行登录，是否去登录?",
        success: function(res) {
          if (res.confirm) {
            common_vendor.index.navigateTo({
              url: "/pages/login/login"
            });
          }
        }
      });
    },
    async handleAddToCart(product) {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      if (!product || !product.id) {
        common_vendor.index.showToast({
          title: "商品信息缺失",
          icon: "none"
        });
        return;
      }
      const version = this.getDefaultVersion(product);
      try {
        const cartEntry = this.getCartEntry(product);
        if (cartEntry == null ? void 0 : cartEntry.id) {
          const cancelResponse = await utils_request.api.cart.cancel({ id: cartEntry.id });
          if (cancelResponse.code !== 200) {
            common_vendor.index.showToast({
              title: cancelResponse.msg || "取消加入失败",
              icon: "none"
            });
            return;
          }
          const cartKey2 = this.getCartKey(product.id, version);
          const nextMap = { ...this.cartProductMap };
          delete nextMap[cartKey2];
          this.cartProductMap = nextMap;
          common_vendor.index.showToast({
            title: cancelResponse.msg || "已移出购物车",
            icon: "success"
          });
          return;
        }
        const response = await utils_request.api.cart.create({
          productId: product.id,
          version,
          quantity: 1
        });
        if (response.code !== 200) {
          common_vendor.index.showToast({
            title: response.msg || "加入购物车失败",
            icon: "none"
          });
          return;
        }
        const cartKey = this.getCartKey(product.id, version);
        this.cartProductMap = {
          ...this.cartProductMap,
          [cartKey]: {
            id: null,
            productId: product.id,
            version,
            quantity: 1
          }
        };
        await this.getHomeCartState();
        common_vendor.index.showToast({
          title: response.msg || "加入购物车成功",
          icon: "success"
        });
      } catch (error) {
        common_vendor.index.showToast({
          title: "加入购物车失败",
          icon: "none"
        });
      }
    },
    goToPage(url) {
      common_vendor.index.navigateTo({ url });
    },
    goToSell() {
      common_vendor.index.navigateTo({
        url: "/pages/sell/sell"
      });
    },
    goToProductDetail(id) {
      if (!id) {
        return;
      }
      common_vendor.index.navigateTo({
        url: `/pages/product/detail?id=${id}`
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_assets._imports_0$1,
    b: $options.heroBannerImage,
    c: common_vendor.f($data.quickActions, (item, k0, i0) => {
      return {
        a: item.icon,
        b: common_vendor.t(item.title),
        c: common_vendor.t(item.desc),
        d: item.title,
        e: common_vendor.o(($event) => $options.goToPage(item.url), item.title)
      };
    }),
    d: common_vendor.o((...args) => $options.goToSell && $options.goToSell(...args), "02"),
    e: common_vendor.f($options.displayProducts, (product, index, i0) => {
      return common_vendor.e({
        a: $options.getProductImage(product),
        b: common_vendor.t($options.getProductTag(product, index)),
        c: common_vendor.n(index % 2 === 0 ? "product-tag-dark" : "product-tag-light"),
        d: product.type == 2 && product.countdown
      }, product.type == 2 && product.countdown ? {
        e: common_vendor.t(product.countdown)
      } : {}, {
        f: common_vendor.t(product.title || "新品系列"),
        g: common_vendor.t($options.getProductDescription(product)),
        h: common_vendor.t($options.getProductPrice(product)),
        i: common_vendor.t($options.getProductHeat(index)),
        j: $options.isProductInCart(product) ? 1 : "",
        k: common_vendor.o(($event) => $options.handleAddToCart(product), product.id || index),
        l: product.id || index,
        m: common_vendor.o(($event) => $options.goToProductDetail(product.id), product.id || index)
      });
    }),
    f: common_assets._imports_4,
    g: !$options.displayProducts.length
  }, !$options.displayProducts.length ? {
    h: common_assets._imports_0
  } : {}, {
    i: `${$data.statusBarHeight + 12}px`
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-1cf27b2a"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/index/index.js.map
