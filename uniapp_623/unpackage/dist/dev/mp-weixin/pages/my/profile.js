"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  data() {
    return {
      userInfo: {
        nickName: "",
        id: "",
        avatar: "/static/image/default_avatar.jpg"
      },
      uploadResult: false
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
          const profile = response && response.data ? response.data : null;
          if (!profile || !profile.id) {
            throw new Error("invalid profile");
          }
          this.userInfo = {
            nickName: profile.nickName || "",
            id: profile.id || "",
            avatar: profile.avatar || "/static/image/default_avatar.jpg",
            ...profile
          };
        } catch (error) {
          common_vendor.index.removeStorageSync("token");
          common_vendor.index.showToast({
            title: "登录已失效",
            icon: "none"
          });
          setTimeout(() => {
            common_vendor.index.reLaunch({
              url: "/pages/login/login"
            });
          }, 500);
        }
      } else {
        common_vendor.index.reLaunch({
          url: "/pages/login/login"
        });
      }
    },
    // 上传头像并更新到后台
    async onChooseAvatar(event) {
      common_vendor.index.showLoading({
        title: "上传中..."
      });
      let that = this;
      const tmpFilePath = event.detail.avatarUrl;
      try {
        const fileManager = common_vendor.wx$1.getFileSystemManager();
        const base64Data = fileManager.readFileSync(tmpFilePath, "base64");
        that.avatar = "data:image/jpeg;base64," + base64Data;
        this.uploadResult = true;
      } catch (error) {
        common_vendor.index.__f__("error", "at pages/my/profile.vue:87", "Base64 转换失败:", error);
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
          common_vendor.index.hideLoading();
          common_vendor.index.showToast({
            title: "头像更新失败",
            icon: "none"
          });
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
        common_vendor.index.showToast({
          title: "昵称更新失败",
          icon: "none"
        });
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
    a: $data.userInfo && $data.userInfo.avatar ? $data.userInfo.avatar : "/static/image/default_avatar.jpg",
    b: common_vendor.o((...args) => $options.onChooseAvatar && $options.onChooseAvatar(...args)),
    c: common_vendor.o((...args) => $options.handleBlur && $options.handleBlur(...args)),
    d: $data.userInfo && $data.userInfo.nickName ? $data.userInfo.nickName : "",
    e: common_vendor.o((...args) => $options.goToAddress && $options.goToAddress(...args))
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-daa3bc30"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/profile.js.map
