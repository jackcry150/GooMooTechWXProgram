"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "Cart",
  data() {
    return {
      cartList: [],
      swipeOptions: [{
        text: "删除",
        style: {
          backgroundColor: "#dc0000",
          color: "#ffffff"
        }
      }]
    };
  },
  onLoad() {
  },
  onShow() {
    this.getCartList();
  },
  onPullDownRefresh() {
    this.getCartList().finally(() => {
      common_vendor.index.stopPullDownRefresh();
    });
  },
  computed: {
    allSelected() {
      return this.cartList.length > 0 && this.cartList.every((item) => item.selected);
    },
    selectedCount() {
      return this.cartList.filter((item) => item.selected).length;
    },
    totalPrice() {
      return this.cartList.filter((item) => item.selected).reduce((total, item) => total + item.price * item.quantity, 0).toFixed(2);
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
    async getCartList() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.showLoading({
          title: "加载中"
        });
        try {
          const response = await utils_request.api.cart.list();
          this.cartList = response.data;
          common_vendor.index.hideLoading();
        } catch (error) {
          common_vendor.index.hideLoading();
        }
      }
    },
    async onSwipeClick(e, v, i) {
      if (e.position === "right" && e.content.text === "删除") {
        common_vendor.index.showModal({
          title: "提示",
          content: "确定要删除这款商品吗？",
          success: (res) => {
            if (res.confirm) {
              this.deleteCart(v, i);
            }
          }
        });
      }
    },
    async deleteCart(v, i) {
      common_vendor.index.showLoading({
        title: "加载中"
      });
      try {
        const params = {
          id: v.id
        };
        const response = await utils_request.api.cart.cancel(params);
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({
          title: response.msg,
          icon: "success"
        });
        this.cartList.splice(i, 1);
      } catch (error) {
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({
          title: "删除失败",
          icon: "none"
        });
      }
    },
    toggleSelect(index) {
      this.cartList[index].selected = !this.cartList[index].selected;
    },
    toggleSelectAll() {
      const shouldSelectAll = !this.allSelected;
      this.cartList.forEach((item) => {
        item.selected = shouldSelectAll;
      });
    },
    increaseQuantity(index) {
      this.cartList[index].quantity++;
      this.quantity(index);
    },
    decreaseQuantity(index) {
      if (this.cartList[index].quantity > 1) {
        this.cartList[index].quantity--;
        this.quantity(index);
      }
    },
    checkout() {
      if (this.selectedCount === 0) {
        common_vendor.index.showToast({
          title: "请选择商品",
          icon: "none"
        });
        return;
      }
      const selectedItems = this.cartList.filter((item) => item.selected);
      const cartIds = selectedItems.map((item) => item.id).join(",");
      const productIds = selectedItems.map((item) => item.productId).join(",");
      common_vendor.index.navigateTo({
        url: `/pages/order/create?cartIds=${encodeURIComponent(cartIds)}&productIds=${encodeURIComponent(productIds)}`
      });
    },
    async quantity(index) {
      try {
        const params = {
          id: this.cartList[index].id,
          quantity: this.cartList[index].quantity
        };
        const res = await utils_request.api.cart.quantity(params);
        if (res.code !== 200) {
          common_vendor.index.showToast({
            title: res.msg || "操作失败",
            icon: "none"
          });
          setTimeout(() => {
            this.getCartList();
          }, 1500);
        }
      } catch (error) {
        common_vendor.index.showToast({ title: "操作失败", icon: "none" });
        this.getCartList();
      }
    }
  }
};
if (!Array) {
  const _easycom_uni_swipe_action_item2 = common_vendor.resolveComponent("uni-swipe-action-item");
  const _easycom_uni_swipe_action2 = common_vendor.resolveComponent("uni-swipe-action");
  (_easycom_uni_swipe_action_item2 + _easycom_uni_swipe_action2)();
}
const _easycom_uni_swipe_action_item = () => "../../uni_modules/uni-swipe-action/components/uni-swipe-action-item/uni-swipe-action-item.js";
const _easycom_uni_swipe_action = () => "../../uni_modules/uni-swipe-action/components/uni-swipe-action/uni-swipe-action.js";
if (!Math) {
  (_easycom_uni_swipe_action_item + _easycom_uni_swipe_action)();
}
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.f($data.cartList, (item, index, i0) => {
      return common_vendor.e({
        a: item.selected
      }, item.selected ? {} : {}, {
        b: item.selected ? 1 : "",
        c: common_vendor.o(($event) => $options.toggleSelect(index), index),
        d: item.image,
        e: item.type == 2
      }, item.type == 2 ? {} : item.type == 1 ? {} : {}, {
        f: item.type == 1,
        g: common_vendor.t(item.title),
        h: common_vendor.t(item.subtitle),
        i: common_vendor.t(item.version),
        j: common_vendor.t(item.price),
        k: common_vendor.o(($event) => $options.decreaseQuantity(index), index),
        l: common_vendor.t(item.quantity),
        m: common_vendor.o(($event) => $options.increaseQuantity(index), index),
        n: index,
        o: common_vendor.o(($event) => $options.onSwipeClick($event, item, index), index),
        p: "c91e7611-1-" + i0 + ",c91e7611-0"
      });
    }),
    b: common_vendor.p({
      ["right-options"]: $data.swipeOptions
    }),
    c: $data.cartList.length === 0
  }, $data.cartList.length === 0 ? {
    d: common_assets._imports_0$1
  } : {}, {
    e: $options.allSelected
  }, $options.allSelected ? {} : {}, {
    f: $options.allSelected ? 1 : "",
    g: common_vendor.o((...args) => $options.toggleSelectAll && $options.toggleSelectAll(...args)),
    h: common_vendor.t($options.totalPrice),
    i: $options.selectedCount > 0
  }, $options.selectedCount > 0 ? {
    j: common_vendor.t($options.selectedCount)
  } : {}, {
    k: $options.selectedCount === 0 ? 1 : "",
    l: common_vendor.o((...args) => $options.checkout && $options.checkout(...args))
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-c91e7611"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/cart/cart.js.map
