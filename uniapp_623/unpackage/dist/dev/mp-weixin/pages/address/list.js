"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const _sfc_main = {
  name: "AddressList",
  data() {
    return {
      addressList: [],
      t: ""
    };
  },
  onLoad(options) {
    if (options.t) {
      this.t = options.t;
    }
    this.getAddressList();
  },
  onShow() {
    this.getAddressList();
  },
  onPullDownRefresh() {
    this.getAddressList().finally(() => {
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
    async getAddressList() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        try {
          const response = await utils_request.api.address.list();
          this.addressList = response.data;
        } catch (error) {
        }
      }
    },
    selesctAddress(address) {
      if (this.t == 1) {
        common_vendor.index.setStorageSync("selectedAddress", address);
        common_vendor.index.navigateBack();
      }
    },
    async defaultSelect(index, id, isDefault) {
      if (isDefault) {
        common_vendor.index.__f__("log", "at pages/address/list.vue:108", "当前选中，不需要设置");
        return;
      }
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        try {
          const params = {
            addressId: id,
            isDefault: true,
            type: 1
          };
          const response = await utils_request.api.address.edit(params);
          const shouldSelectAll = false;
          this.addressList.forEach((item) => {
            item.isDefault = shouldSelectAll;
          });
          this.addressList[index].isDefault = true;
        } catch (error) {
        }
      }
    },
    editAddress(address) {
      common_vendor.index.navigateTo({
        url: `/pages/address/edit?id=${address.id}`
      });
    },
    deleteAddress(address) {
      common_vendor.index.showModal({
        title: "确认删除",
        content: "确定要删除这个地址吗？",
        success: (res) => {
          if (res.confirm) {
            this.delAddress(address);
          }
        }
      });
    },
    async delAddress(address) {
      const params = {
        addressId: address.id,
        isDefault: address.isDefault
      };
      const response = await utils_request.api.address.del(params);
      this.addressList = response.data;
      common_vendor.index.showToast({
        title: "删除成功",
        icon: "success"
      });
    },
    addAddress() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        common_vendor.index.navigateTo({
          url: "/pages/address/edit"
        });
      }
    },
    importWechatAddress() {
      common_vendor.index.chooseAddress({
        success: (res) => {
          common_vendor.index.showToast({
            title: "地址选择成功",
            icon: "success"
          });
          common_vendor.index.navigateTo({
            url: `/pages/address/edit?address=${JSON.stringify(res)}`
          });
        },
        fail: (err) => {
          common_vendor.index.__f__("log", "at pages/address/list.vue:186", "获取地址失败:", err);
          common_vendor.index.showModal({
            title: "提示",
            content: "获取地址失败，请检查权限设置"
          });
        }
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.f($data.addressList, (address, index, i0) => {
      return common_vendor.e({
        a: common_vendor.t(address.name),
        b: common_vendor.t(address.phone),
        c: common_vendor.t(address.province),
        d: common_vendor.t(address.city),
        e: common_vendor.t(address.region),
        f: common_vendor.t(address.detail),
        g: address.isDefault
      }, address.isDefault ? {} : {}, {
        h: address.isDefault ? 1 : "",
        i: common_vendor.o(($event) => $options.defaultSelect(index, address.id, address.isDefault), index),
        j: common_vendor.o(($event) => $options.editAddress(address), index),
        k: common_vendor.o(($event) => $options.deleteAddress(address), index),
        l: common_vendor.o(($event) => $options.selesctAddress(address), index),
        m: index
      });
    }),
    b: $data.addressList.length === 0
  }, $data.addressList.length === 0 ? {
    c: common_assets._imports_0$1
  } : {}, {
    d: common_vendor.o((...args) => $options.addAddress && $options.addAddress(...args)),
    e: common_vendor.o((...args) => $options.importWechatAddress && $options.importWechatAddress(...args))
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-90a3874e"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/address/list.js.map
