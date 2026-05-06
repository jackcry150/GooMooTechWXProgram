"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  data() {
    return {
      loading: true,
      submitting: false,
      orderId: "",
      order: {},
      remark: "",
      addressForm: {
        name: "",
        phone: "",
        province: "",
        city: "",
        area: "",
        region: "",
        detail: ""
      }
    };
  },
  computed: {
    regionText() {
      return [
        this.addressForm.province,
        this.addressForm.city,
        this.addressForm.region || this.addressForm.area
      ].filter(Boolean).join(" ");
    }
  },
  onLoad(options) {
    this.orderId = options.id || "";
    this.loadOrderDetail();
  },
  onPullDownRefresh() {
    this.loadOrderDetail().finally(() => {
      common_vendor.index.stopPullDownRefresh();
    });
  },
  methods: {
    validateForm() {
      if (!this.addressForm.name.trim()) {
        common_vendor.index.showToast({ title: "请填写收货人", icon: "none" });
        return false;
      }
      if (!/^1\d{10}$/.test((this.addressForm.phone || "").trim())) {
        common_vendor.index.showToast({ title: "请填写正确手机号", icon: "none" });
        return false;
      }
      if (!this.addressForm.detail.trim()) {
        common_vendor.index.showToast({ title: "请填写详细地址", icon: "none" });
        return false;
      }
      return true;
    },
    async loadOrderDetail() {
      if (!this.orderId) {
        this.loading = false;
        return Promise.resolve();
      }
      this.loading = true;
      try {
        const res = await utils_request.api.order.detail({ id: this.orderId });
        if (res.code === 200 && res.data) {
          this.order = res.data;
          const address = res.data.addressInfo || {};
          this.addressForm = {
            name: address.name || "",
            phone: address.phone || "",
            province: address.province || "",
            city: address.city || "",
            area: address.area || "",
            region: address.region || "",
            detail: address.detail || ""
          };
        } else {
          common_vendor.index.showToast({ title: res.msg || "加载订单失败", icon: "none" });
        }
      } catch (e) {
        common_vendor.index.showToast({ title: "加载订单失败", icon: "none" });
      } finally {
        this.loading = false;
      }
    },
    async submitConfirm() {
      if (this.submitting || !this.validateForm())
        return;
      this.submitting = true;
      try {
        const res = await utils_request.api.order.confirmArrivalInfo({
          id: this.orderId,
          name: this.addressForm.name,
          phone: this.addressForm.phone,
          province: this.addressForm.province,
          city: this.addressForm.city,
          area: this.addressForm.area,
          detail: this.addressForm.detail,
          remark: this.remark
        });
        if (res.code === 200) {
          common_vendor.index.showToast({ title: res.msg || "确认成功", icon: "success" });
          setTimeout(() => {
            this.loadOrderDetail();
          }, 900);
        } else {
          common_vendor.index.showToast({ title: res.msg || "提交失败", icon: "none" });
        }
      } catch (e) {
        common_vendor.index.showToast({ title: "提交失败", icon: "none" });
      } finally {
        this.submitting = false;
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: $data.loading
  }, $data.loading ? {} : $data.order.id ? {
    c: common_vendor.t($data.order.orderNo || "-"),
    d: common_vendor.t($data.order.statusText || "-"),
    e: $data.addressForm.name,
    f: common_vendor.o(($event) => $data.addressForm.name = $event.detail.value, "9d"),
    g: $data.addressForm.phone,
    h: common_vendor.o(($event) => $data.addressForm.phone = $event.detail.value, "5f"),
    i: $data.addressForm.province,
    j: common_vendor.o(($event) => $data.addressForm.province = $event.detail.value, "46"),
    k: $data.addressForm.city,
    l: common_vendor.o(($event) => $data.addressForm.city = $event.detail.value, "78"),
    m: $data.addressForm.area,
    n: common_vendor.o(($event) => $data.addressForm.area = $event.detail.value, "90"),
    o: $data.addressForm.detail,
    p: common_vendor.o(($event) => $data.addressForm.detail = $event.detail.value, "b7"),
    q: $data.remark,
    r: common_vendor.o(($event) => $data.remark = $event.detail.value, "a5"),
    s: common_vendor.t($data.submitting ? "提交中..." : "确认收货信息"),
    t: $data.submitting,
    v: common_vendor.o((...args) => $options.submitConfirm && $options.submitConfirm(...args), "dc")
  } : {}, {
    b: $data.order.id
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-324e7894"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/order/confirm.js.map
