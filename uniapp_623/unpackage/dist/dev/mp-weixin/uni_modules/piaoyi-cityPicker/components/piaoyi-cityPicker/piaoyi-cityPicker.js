"use strict";
const common_vendor = require("../../../../common/vendor.js");
const uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData = require("./cityData.js");
const _sfc_main = {
  data() {
    return {
      value: [],
      addressList: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList,
      provinceList: [],
      cityList: [],
      areaList: [],
      indicatorStyle: `height: 50px;`,
      provinceIndex: 0,
      cityIndex: 0,
      areaIndex: 0,
      animationData: {}
    };
  },
  props: {
    column: {
      type: Number,
      default: 3
    },
    defaultValue: {
      default: () => ""
    },
    visible: {
      type: Boolean,
      default: () => false
    },
    maskCloseAble: {
      type: Boolean,
      default: () => true
    }
  },
  watch: {
    visible(val) {
      this.changeActive();
    },
    defaultValue() {
      this.init();
    }
  },
  created() {
    this.init();
  },
  methods: {
    init() {
      var provinceList = [];
      uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList.filter((item) => {
        provinceList.push({
          code: item.code,
          name: item.name
        });
      });
      this.provinceList = [...provinceList];
      if (!this.defaultValue) {
        this.cityList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[0].children;
        this.areaList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[0].children[0].children;
      } else {
        var city = { 11: "北京", 12: "天津", 13: "河北", 14: "山西", 15: "内蒙古", 21: "辽宁", 22: "吉林", 23: "黑龙江 ", 31: "上海", 32: "江苏", 33: "浙江", 34: "安徽", 35: "福建", 36: "江西", 37: "山东", 41: "河南", 42: "湖北 ", 43: "湖南", 44: "广东", 45: "广西", 46: "海南", 50: "重庆", 51: "四川", 52: "贵州", 53: "云南", 54: "西藏 ", 61: "陕西", 62: "甘肃", 63: "青海", 64: "宁夏", 65: "新疆", 71: "台湾", 81: "香港", 82: "澳门", 91: "国外 " };
        if (Array.isArray(this.defaultValue) && this.defaultValue.length >= 2) {
          var province = this.defaultValue[0];
          var city = this.defaultValue[1];
          this.provinceIndex = 0;
          this.cityIndex = 0;
          this.areaIndex = 0;
          this.provinceIndex = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList.findIndex((obj) => {
            return obj.name == province;
          });
          this.provinceIndex = this.provinceIndex >= 0 ? this.provinceIndex : 0;
          this.cityList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children;
          this.cityIndex = this.cityList.findIndex((obj) => {
            return obj.name == city;
          });
          this.cityIndex = this.cityIndex >= 0 ? this.cityIndex : 0;
          this.areaList = this.cityList[this.cityIndex].children;
          if (this.defaultValue.length > 2) {
            this.areaIndex = this.areaList.findIndex((obj) => {
              return obj.name == this.defaultValue[2];
            });
          }
          this.areaIndex = this.areaIndex >= 0 ? this.areaIndex : 0;
          this.$nextTick(() => {
            if (this.column == 3) {
              this.value = JSON.parse(JSON.stringify([this.provinceIndex, this.cityIndex, this.areaIndex]));
            } else if (this.column == 2) {
              this.value = JSON.parse(JSON.stringify([this.provinceIndex, this.cityIndex]));
            }
          });
        } else if (/^\d{6}$/.test(this.defaultValue)) {
          var province = this.defaultValue.substr(0, 2);
          var city = this.defaultValue.substr(0, 4);
          this.provinceIndex = 0;
          this.cityIndex = 0;
          this.areaIndex = 0;
          this.provinceIndex = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList.findIndex((obj) => {
            return obj.code == province;
          });
          this.provinceIndex = this.provinceIndex >= 0 ? this.provinceIndex : 0;
          this.cityList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children;
          this.cityIndex = this.cityList.findIndex((obj) => {
            return obj.code == city;
          });
          this.cityIndex = this.cityIndex >= 0 ? this.cityIndex : 0;
          this.areaList = this.cityList[this.cityIndex].children;
          this.areaIndex = this.areaList.findIndex((obj) => {
            return obj.code == this.defaultValue;
          });
          this.areaIndex = this.areaIndex >= 0 ? this.areaIndex : 0;
          this.$nextTick(() => {
            if (this.column == 3) {
              this.value = JSON.parse(JSON.stringify([this.provinceIndex, this.cityIndex, this.areaIndex]));
            } else if (this.column == 2) {
              this.value = JSON.parse(JSON.stringify([this.provinceIndex, this.cityIndex]));
            }
          });
        } else {
          common_vendor.index.showToast({
            title: "地区编码格式不正确",
            icon: "none"
          });
          common_vendor.index.__f__("log", "at uni_modules/piaoyi-cityPicker/components/piaoyi-cityPicker/piaoyi-cityPicker.vue:148", "地区编码格式不正确");
        }
      }
      this.changeActive();
    },
    confirm() {
      if (this.column == 3) {
        this.$emit("confirm", {
          code: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].children[this.areaIndex].code,
          name: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].name + uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].name + uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].children[this.areaIndex].name,
          provinceName: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].name,
          cityName: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].name,
          areaName: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].children[this.areaIndex].name
        });
      } else if (this.column == 2) {
        this.$emit("confirm", {
          code: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].children[this.areaIndex].code.substring(0, 4) + "00",
          name: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].name + uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].name,
          provinceName: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].name,
          cityName: uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[this.cityIndex].name
        });
      } else {
        common_vendor.index.showToast({
          title: "目前column只能传2和3",
          icon: "none"
        });
      }
    },
    cancel() {
      this.$emit("cancel");
    },
    // 动画
    changeActive() {
      var active = "-415px";
      if (this.visible) {
        active = 0;
      }
      var animation = common_vendor.index.createAnimation({
        duration: 400,
        timingFunction: "linear"
      });
      animation.bottom(active).step();
      this.animationData = animation.export();
    },
    bindChange(e) {
      e.detail.value[0] = e.detail.value[0] || 0;
      e.detail.value[1] = e.detail.value[1] || 0;
      e.detail.value[2] = e.detail.value[2] || 0;
      if (e.detail.value[0] != this.provinceIndex) {
        this.provinceChange(e.detail.value[0]);
      } else if (e.detail.value[1] != this.cityIndex) {
        this.cityChange(e.detail.value[1]);
      } else if (e.detail.value[2] != this.areaIndex) {
        this.areaChange(e.detail.value[2]);
      }
    },
    // 监听第一列变化
    provinceChange(e) {
      this.provinceIndex = e;
      this.cityIndex = 0;
      this.areaIndex = 0;
      this.value = [...[e, 0, 0]];
      this.cityList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[e].children;
      this.areaList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[e].children[0].children;
    },
    // 监听第二列变化
    cityChange(e) {
      this.cityIndex = e;
      this.areaIndex = 0;
      this.value = [...[this.provinceIndex, e, 0]];
      this.cityList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children;
      this.areaList = uni_modules_piaoyiCityPicker_components_piaoyiCityPicker_cityData.addressList[this.provinceIndex].children[e].children;
    },
    // 监听第三列变化
    areaChange(e) {
      this.areaIndex = e;
    },
    // 点击模态框
    close() {
      if (this.maskCloseAble) {
        this.cancel();
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.o((...args) => $options.cancel && $options.cancel(...args)),
    b: common_vendor.o((...args) => $options.confirm && $options.confirm(...args)),
    c: common_vendor.f($data.provinceList, (item, index, i0) => {
      return {
        a: common_vendor.t(item.name),
        b: index
      };
    }),
    d: common_vendor.f($data.cityList, (item, index, i0) => {
      return {
        a: common_vendor.t(item.name),
        b: index
      };
    }),
    e: $props.column == 3
  }, $props.column == 3 ? {
    f: common_vendor.f($data.areaList, (item, index, i0) => {
      return {
        a: common_vendor.t(item.name),
        b: index
      };
    })
  } : {}, {
    g: $data.value,
    h: $data.indicatorStyle,
    i: common_vendor.o((...args) => $options.bindChange && $options.bindChange(...args)),
    j: $data.animationData,
    k: common_vendor.o((...args) => $options.close && $options.close(...args)),
    l: common_vendor.o(() => {
    }),
    m: common_vendor.n($props.visible ? "pupop-model" : "pupop-models")
  });
}
const Component = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-0c5a8a35"]]);
wx.createComponent(Component);
//# sourceMappingURL=../../../../../.sourcemap/mp-weixin/uni_modules/piaoyi-cityPicker/components/piaoyi-cityPicker/piaoyi-cityPicker.js.map
