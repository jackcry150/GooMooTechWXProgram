"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "Bill",
  data() {
    return {
      billist: []
    };
  },
  onLoad() {
  },
  // onShow() {
  // 	this.getCartList()
  // },
  // onPullDownRefresh() {
  // 	this.getCartList().finally(() => {
  // 		uni.stopPullDownRefresh()
  // 	})
  // },
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
        try {
          const response = await utils_request.api.cart.list();
          this.cartList = response.data;
        } catch (error) {
        }
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
    },
    decreaseQuantity(index) {
      if (this.cartList[index].quantity > 1) {
        this.cartList[index].quantity--;
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
      common_vendor.index.navigateTo({
        url: `/pages/order/create?items=${JSON.stringify(selectedItems)}`
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: $data.billist.length === 0
  }, $data.billist.length === 0 ? {
    b: common_assets._imports_0$1
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-8ba69474"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/bill.js.map
