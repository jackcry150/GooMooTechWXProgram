"use strict";
const utils_request = require("../../utils/request.js");
const common_vendor = require("../../common/vendor.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "BrandIntro",
  data() {
    return {
      wechatGroup: [],
      followUs: [],
      webSetting: [],
      aboutContent: ""
    };
  },
  onLoad() {
    this.loadInfo();
  },
  methods: {
    loadInfo() {
      this.getWechatGroup();
      this.getFollowUs();
      this.getWebSetting();
      this.getAbout();
    },
    async getWechatGroup() {
      try {
        const params = {
          type: 1
        };
        const response = await utils_request.api.server.list(params);
        this.wechatGroup = response.data;
      } catch (error) {
      }
    },
    async getFollowUs() {
      try {
        const params = {
          type: 2
        };
        const response = await utils_request.api.server.list(params);
        this.followUs = response.data;
      } catch (error) {
      }
    },
    async getWebSetting() {
      try {
        const response = await utils_request.api.setting.info();
        this.webSetting = response.data;
      } catch (error) {
      }
    },
    async getAbout() {
      try {
        const res = await utils_request.api.news.detail({ code: "about" });
        if (res.code === 200 && res.data) {
          this.aboutContent = res.data.content || "";
        }
      } catch (e) {
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.f($data.wechatGroup, (item, index, i0) => {
      return {
        a: item.image[0],
        b: common_vendor.t(item.title)
      };
    }),
    b: common_assets._imports_0$4,
    c: common_assets._imports_1$3,
    d: common_vendor.f($data.followUs, (item, index, i0) => {
      return {
        a: item.image[0],
        b: common_vendor.t(item.title)
      };
    }),
    e: common_vendor.t($data.aboutContent),
    f: common_vendor.t($data.webSetting.contactUs),
    g: common_assets._imports_0$5,
    h: common_vendor.t($data.webSetting.address),
    i: common_assets._imports_3$2,
    j: common_vendor.t($data.webSetting.email)
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-13a78ac6"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/about/about.js.map
