"use strict";
const common_vendor = require("../../common/vendor.js");
const _sfc_main = {
  data() {
    return {
      styleTags: ["甜美系", "软萌系", "粉色系", "蝴蝶结", "奶油感", "礼物推荐"],
      styleBlocks: [
        { title: "轻甜展示", desc: "更适合拍照、礼盒展示和首页专题陈列。" },
        { title: "软萌居家", desc: "适合偏治愈、奶呼呼、云朵感的搭配风格。" },
        { title: "少女送礼", desc: "适合做节日、生日和可爱风送礼推荐。" }
      ],
      goodsList: [
        {
          key: "demo-1",
          id: 1,
          tag: "Pink Sweet",
          title: "奶油草莓小裙装",
          subtitle: "适合可爱风陈列和软萌拍摄",
          priceText: "测试商品位 #1",
          cover: "linear-gradient(135deg, #ffd6e7 0%, #ffc2d1 48%, #fff1c9 100%)"
        },
        {
          key: "demo-2",
          id: 1,
          tag: "Ribbon Mood",
          title: "蝴蝶结花边娃衣",
          subtitle: "更偏礼物感和展示感的一套",
          priceText: "测试商品位 #2",
          cover: "linear-gradient(135deg, #ffe4f3 0%, #f8c7ff 52%, #ffd9b5 100%)"
        },
        {
          key: "demo-3",
          id: 1,
          tag: "Soft Cute",
          title: "糖果云朵居家款",
          subtitle: "适合温柔、蓬松、少女感风格",
          priceText: "测试商品位 #3",
          cover: "linear-gradient(135deg, #fff1f7 0%, #ffd4e8 50%, #ffe7a3 100%)"
        },
        {
          key: "demo-4",
          id: 1,
          tag: "Gift Pick",
          title: "软糖礼盒主题款",
          subtitle: "适合专题页做送礼推荐和精选入口",
          priceText: "测试商品位 #4",
          cover: "linear-gradient(135deg, #ffd6c7 0%, #ffe4ef 56%, #fff1d9 100%)"
        }
      ],
      lookbookTips: [
        "可以把粉色、奶油白、蝴蝶结元素集中到同一专题里，页面风格会更统一。",
        "如果后面要做二维码推广，建议保持专题页标题、头图、商品风格一致，识别度会更高。",
        "下一步最适合直接换成真实商品 ID，这样你就能把这个专题页真正投放出去。"
      ]
    };
  },
  methods: {
    goToProduct(item) {
      common_vendor.index.navigateTo({
        url: "/pages/product/detail?id=" + item.id
      });
    },
    goToHome() {
      common_vendor.index.switchTab({
        url: "/pages/index/index"
      });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return {
    a: common_vendor.f($data.styleTags, (item, index, i0) => {
      return {
        a: common_vendor.t(item),
        b: index
      };
    }),
    b: common_vendor.f($data.styleBlocks, (item, index, i0) => {
      return {
        a: common_vendor.t(item.title),
        b: common_vendor.t(item.desc),
        c: index
      };
    }),
    c: common_vendor.f($data.goodsList, (item, k0, i0) => {
      return {
        a: common_vendor.t(item.tag),
        b: item.cover,
        c: common_vendor.t(item.title),
        d: common_vendor.t(item.subtitle),
        e: common_vendor.t(item.priceText),
        f: item.key,
        g: common_vendor.o(($event) => $options.goToProduct(item), item.key)
      };
    }),
    d: common_vendor.f($data.lookbookTips, (item, index, i0) => {
      return {
        a: common_vendor.t(index + 1),
        b: common_vendor.t(item),
        c: index
      };
    }),
    e: common_vendor.o(($event) => $options.goToProduct($data.goodsList[0]), "c6"),
    f: common_vendor.o((...args) => $options.goToHome && $options.goToHome(...args), "9f")
  };
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-317945ec"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/doll/female.js.map
