"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  data() {
    return {
      customerServiceList: []
    };
  },
  onLoad() {
    this.getCustomerService();
  },
  methods: {
    goToCustomer(i) {
      common_vendor.wx$1.openCustomerServiceChat({
        extInfo: {
          url: i.linkUrl
        },
        corpId: i.corpId,
        success: (res) => {
          common_vendor.index.__f__("log", "at pages/customer/customer.vue:29", " openCustomerServiceChat success:" + JSON.stringify(res));
        },
        fail: (err) => {
          common_vendor.index.__f__("log", "at pages/customer/customer.vue:32", " openCustomerServiceChat fail:" + JSON.stringify(err));
        }
      });
    },
    async getCustomerService() {
      try {
        const res = await utils_request.api.server.list({ type: 3 });
        if (res.code === 200 && Array.isArray(res.data)) {
          this.customerServiceList = res.data.map((item) => ({
            id: item.id,
            title: item.title,
            corpId: item.corpId || "",
            image: Array.isArray(item.image) && item.image.length ? item.image[0] : "",
            linkUrl: item.link || ""
          }));
        }
      } catch (e) {
        common_vendor.index.showToast({ title: "加载客服失败", icon: "none" });
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.f($data.customerServiceList, (item, index, i0) => {
      return {
        a: item.image,
        b: index,
        c: common_vendor.o(($event) => $options.goToCustomer(item), index)
      };
    })
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-02222c4a"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/customer/customer.js.map
