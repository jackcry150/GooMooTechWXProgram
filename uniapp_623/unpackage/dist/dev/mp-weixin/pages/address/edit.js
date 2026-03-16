"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const cityPicker = () => "../../uni_modules/piaoyi-cityPicker/components/piaoyi-cityPicker/piaoyi-cityPicker.js";
const _sfc_main = {
  name: "AddressEdit",
  data() {
    return {
      form: {
        name: "",
        phone: "",
        province: "",
        city: "",
        region: "",
        detail: "",
        isDefault: false
      },
      region: "",
      visible: false,
      maskCloseAble: true,
      defaultValue: ["北京市", "北京市", "东城区"],
      column: 3,
      isEdit: false,
      addressId: null,
      isSave: false
    };
  },
  onLoad(options) {
    this.loadInfo();
    if (options.id) {
      this.isEdit = true;
      this.addressId = options.id;
      this.loadAddress(options.id);
    }
    if (options.address) {
      this.loadWxAddress(JSON.parse(options.address));
    }
  },
  components: {
    cityPicker
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
    loadInfo() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      }
    },
    async loadAddress(id) {
      try {
        const params = {
          id
        };
        const response = await utils_request.api.address.detail(params);
        this.form = response.data;
        this.form.isDefault = this.form.isDefault == 1 ? true : false;
        this.region = this.form.province + " " + this.form.city + " " + this.form.region;
      } catch (error) {
      }
    },
    getWxAddress() {
      common_vendor.index.chooseAddress({
        success: (res) => {
          this.form = {
            name: res.userName,
            phone: res.telNumber,
            province: res.provinceName,
            city: res.cityName,
            region: res.countyName,
            detail: res.detailInfo
          };
          this.region = this.form.province + " " + this.form.city + " " + this.form.region;
          this.getFromValue();
          common_vendor.index.showToast({
            title: "地址选择成功",
            icon: "success"
          });
        },
        fail: (err) => {
          common_vendor.index.showModal({
            title: "提示",
            content: "获取地址失败，请检查权限设置"
          });
        }
      });
    },
    loadWxAddress(e) {
      this.form = {
        name: e.userName,
        phone: e.telNumber,
        province: e.provinceName,
        city: e.cityName,
        region: e.countyName,
        detail: e.detailInfo
      };
      this.region = this.form.province + " " + this.form.city + " " + this.form.region;
      this.getFromValue();
    },
    selectAddress() {
      this.visible = true;
    },
    confirm(e) {
      this.form.province = e.provinceName;
      this.form.city = e.cityName;
      this.form.region = e.areaName;
      this.region = this.form.province + " " + this.form.city + " " + this.form.region;
      this.getFromValue();
      this.visible = false;
    },
    cancel() {
      this.visible = false;
      this.getFromValue();
    },
    toggleDefault(e) {
      this.form.isDefault = e.detail.value;
      this.getFromValue();
    },
    getFromValue() {
      if (this.form.name && this.form.phone && this.form.province && this.form.city && this.form.region && this.form.detail) {
        this.isSave = true;
      } else {
        this.isSave = false;
      }
    },
    // 表单验证
    validateForm() {
      if (!this.form.name || this.form.name.trim() === "") {
        common_vendor.index.showToast({
          title: "请输入收货人姓名",
          icon: "none"
        });
        return false;
      }
      if (!this.form.phone || this.form.phone.trim() === "") {
        common_vendor.index.showToast({
          title: "请输入收货人手机号",
          icon: "none"
        });
        return false;
      }
      const phoneReg = /^1[3-9]\d{9}$/;
      if (!phoneReg.test(this.form.phone)) {
        common_vendor.index.showToast({
          title: "请正确输入收货人手机号",
          icon: "none"
        });
        return false;
      }
      if (!this.form.province || !this.form.city || !this.form.region) {
        common_vendor.index.showToast({
          title: "请选择收货人所在地区",
          icon: "none"
        });
        return false;
      }
      if (!this.form.detail || this.form.detail.trim() === "") {
        common_vendor.index.showToast({
          title: "请输入详细地址",
          icon: "none"
        });
        return false;
      }
      return true;
    },
    async saveAddress() {
      if (!this.validateForm()) {
        return;
      }
      common_vendor.index.showLoading({
        title: "保存中..."
      });
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
      } else {
        try {
          if (this.isEdit) {
            this.form.type = 2;
            this.form.id = this.addressId;
            const response = await utils_request.api.address.edit(this.form);
            common_vendor.index.hideLoading();
            if (response.code === 200) {
              common_vendor.index.showToast({
                title: response.msg || "保存成功",
                icon: "success"
              });
              setTimeout(() => {
                common_vendor.index.navigateBack();
              }, 1500);
            } else {
              common_vendor.index.showToast({
                title: response.msg || "保存失败",
                icon: "none"
              });
            }
          } else {
            const response = await utils_request.api.address.create(this.form);
            common_vendor.index.hideLoading();
            if (response.code === 200) {
              common_vendor.index.showToast({
                title: response.msg || "添加成功",
                icon: "success"
              });
              setTimeout(() => {
                common_vendor.index.navigateBack();
              }, 1500);
            } else {
              common_vendor.index.showToast({
                title: response.msg || "添加失败",
                icon: "none"
              });
            }
          }
        } catch (error) {
          common_vendor.index.hideLoading();
          common_vendor.index.showToast({
            title: "操作失败，请重试",
            icon: "none"
          });
        }
      }
    }
  }
};
if (!Array) {
  const _component_cityPicker = common_vendor.resolveComponent("cityPicker");
  _component_cityPicker();
}
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.o((...args) => $options.getWxAddress && $options.getWxAddress(...args)),
    b: common_vendor.o((...args) => $options.getFromValue && $options.getFromValue(...args)),
    c: $data.form.name,
    d: common_vendor.o(($event) => $data.form.name = $event.detail.value),
    e: common_vendor.o((...args) => $options.getFromValue && $options.getFromValue(...args)),
    f: $data.form.phone,
    g: common_vendor.o(($event) => $data.form.phone = $event.detail.value),
    h: $data.region,
    i: common_vendor.o(($event) => $data.region = $event.detail.value),
    j: common_vendor.o((...args) => $options.selectAddress && $options.selectAddress(...args)),
    k: common_vendor.o((...args) => $options.getFromValue && $options.getFromValue(...args)),
    l: $data.form.detail,
    m: common_vendor.o(($event) => $data.form.detail = $event.detail.value),
    n: $data.form.isDefault,
    o: common_vendor.o((...args) => $options.toggleDefault && $options.toggleDefault(...args)),
    p: common_vendor.o((...args) => $options.saveAddress && $options.saveAddress(...args)),
    q: !$data.isSave ? 1 : "",
    r: common_vendor.o($options.confirm),
    s: common_vendor.o($options.cancel),
    t: common_vendor.p({
      column: $data.column,
      ["default-value"]: $data.defaultValue,
      ["mask-close-able"]: $data.maskCloseAble,
      visible: $data.visible
    })
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-dcb1f0d8"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/address/edit.js.map
