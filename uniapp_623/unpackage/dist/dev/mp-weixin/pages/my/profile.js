"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  data() {
    return {
      userInfo: {
        nickName: "小小蜗",
        id: "",
        avatar: "/static/image/default_avatar.jpg"
      }
    };
  },
  onLoad() {
    this.getProfileInfo();
  },
  methods: {
    async getProfileInfo() {
      const token = common_vendor.index.getStorageSync("token");
      if (token) {
        try {
          const response = await utils_request.api.user.profile();
          this.userInfo = response.data;
        } catch (error) {
        }
      } else {
        common_vendor.index.navigateBack();
      }
    },
    // 选择头像回调
    async onChooseAvatar(event) {
      common_vendor.index.showLoading({
        title: "上传中"
      });
      let that = this;
      const tmpFilePath = event.detail.avatarUrl;
      try {
        const fileManager = common_vendor.wx$1.getFileSystemManager();
        const base64Data = fileManager.readFileSync(tmpFilePath, "base64");
        that.avatar = "data:image/jpeg;base64," + base64Data;
        this.uploadResult = true;
      } catch (error) {
        common_vendor.index.__f__("error", "at pages/my/profile.vue:66", "Base64转换失败:", error);
        common_vendor.index.hideLoading();
        common_vendor.index.showToast({
          title: "头像处理失败",
          icon: "none"
        });
      }
      if (this.userInfo.avatar !== that.avatar && this.uploadResult) {
        try {
          const params = {
            avatar: that.avatar
          };
          const response = await utils_request.api.user.avatar(params);
          common_vendor.index.hideLoading();
          common_vendor.index.showToast({
            title: response.msg,
            icon: "success"
          });
          this.userInfo.avatar = that.avatar;
        } catch (error) {
        }
      }
    },
    async handleBlur(event) {
      try {
        const params = {
          nickName: event.detail.value
        };
        const response = await utils_request.api.user.nickName(params);
        this.userInfo.nickName = event.detail.value;
        common_vendor.index.showToast({
          title: response.msg,
          icon: "success"
        });
      } catch (error) {
      }
    },
    goToAddress() {
      common_vendor.index.navigateTo({
        url: "/pages/address/list"
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: $data.userInfo.avatar,
    b: common_vendor.o((...args) => $options.onChooseAvatar && $options.onChooseAvatar(...args)),
    c: common_vendor.o((...args) => $options.handleBlur && $options.handleBlur(...args)),
    d: $data.userInfo.nickName,
    e: common_vendor.o((...args) => $options.goToAddress && $options.goToAddress(...args))
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-daa3bc30"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/profile.js.map
