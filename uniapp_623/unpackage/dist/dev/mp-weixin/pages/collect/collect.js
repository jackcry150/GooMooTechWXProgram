"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "Collect",
  data() {
    return {
      collectList: [],
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
    this.getCollectList();
  },
  onPullDownRefresh() {
    this.getCollectList().finally(() => {
      common_vendor.index.stopPullDownRefresh();
    });
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
    async getCollectList() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.showLoading({
          title: "加载中"
        });
        try {
          const response = await utils_request.api.collect.list();
          this.collectList = response.data;
          common_vendor.index.hideLoading();
        } catch (error) {
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
              this.deleteCollect(v, i);
            }
          }
        });
      }
    },
    async deleteCollect(v, i) {
      common_vendor.index.showLoading({
        title: "加载中"
      });
      try {
        const params = {
          id: v.id
        };
        const response = await utils_request.api.collect.cancel(params);
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({
          title: response.msg,
          icon: "success"
        });
        this.collectList.splice(i, 1);
      } catch (error) {
      }
    },
    goToProductDetail(id) {
      common_vendor.index.navigateTo({
        url: `/pages/product/detail?id=${id}`
      });
    },
    async cancelCollect() {
      if (this.selectedCount === 0) {
        common_vendor.index.showToast({
          title: "请选择商品",
          icon: "none"
        });
        return;
      }
      const selectedItems = this.collectList.filter((item) => item.selected);
      try {
        const response = await utils_request.api.collect.cancel(selectedItems);
        common_vendor.index.showToast({
          title: response.msg,
          icon: "success"
        });
        this.collectList = response.data;
      } catch (error) {
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
    a: common_vendor.f($data.collectList, (item, index, i0) => {
      return {
        a: item.image,
        b: common_vendor.t(item.title),
        c: common_vendor.t(item.subtitle),
        d: common_vendor.t(item.type),
        e: common_vendor.t(item.price),
        f: common_vendor.o(($event) => $options.goToProductDetail(item.productId), index),
        g: index,
        h: common_vendor.o(($event) => $options.onSwipeClick($event, item, index), index),
        i: "b24c290b-1-" + i0 + ",b24c290b-0"
      };
    }),
    b: common_vendor.p({
      ["right-options"]: $data.swipeOptions
    }),
    c: $data.collectList.length === 0
  }, $data.collectList.length === 0 ? {
    d: common_assets._imports_0$1
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-b24c290b"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/collect/collect.js.map
