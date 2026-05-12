"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  data() {
    return {
      statusBarHeight: 44,
      userInfo: {
        nickName: "",
        id: "",
        avatar: "/static/image/default_avatar.jpg",
        gender: "",
        birthday: "",
        province: "",
        city: "",
        district: ""
      },
      uploadResult: false
    };
  },
  computed: {
    addressText() {
      const addressParts = [
        this.userInfo.province,
        this.userInfo.city,
        this.userInfo.district
      ].filter(Boolean);
      return addressParts.length ? addressParts.join(" ") : "未设置";
    }
  },
  onLoad() {
    const systemInfo = common_vendor.index.getSystemInfoSync ? common_vendor.index.getSystemInfoSync() : {};
    this.statusBarHeight = systemInfo.statusBarHeight || 22;
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
            gender: profile.gender || "",
            birthday: profile.birthday || "",
            province: profile.province || "",
            city: profile.city || "",
            district: profile.district || "",
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
    focusNickname() {
      const query = common_vendor.index.createSelectorQuery().in(this);
      query.select("#profile-nickname-input").fields({ node: true }, (res) => {
        var _a, _b;
        (_b = (_a = res == null ? void 0 : res.node) == null ? void 0 : _a.focus) == null ? void 0 : _b.call(_a);
      }).exec();
    },
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
      const nickName = String(event.detail.value || "").trim();
      if (nickName === this.userInfo.nickName) {
        return;
      }
      try {
        const params = {
          nickName
        };
        const response = await utils_request.api.user.nickName(params);
        this.userInfo.nickName = nickName;
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
    b: common_assets._imports_0$6,
    c: common_vendor.o((...args) => $options.onChooseAvatar && $options.onChooseAvatar(...args), "49"),
    d: common_assets._imports_1$5,
    e: common_vendor.o((...args) => $options.handleBlur && $options.handleBlur(...args), "3d"),
    f: $data.userInfo && $data.userInfo.nickName ? $data.userInfo.nickName : "",
    g: common_vendor.o((...args) => $options.focusNickname && $options.focusNickname(...args), "5d"),
    h: common_assets._imports_2$4,
    i: common_vendor.t($options.addressText),
    j: common_vendor.o((...args) => $options.goToAddress && $options.goToAddress(...args), "53"),
    k: `${$data.statusBarHeight + 18}px`
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-daa3bc30"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/profile.js.map
