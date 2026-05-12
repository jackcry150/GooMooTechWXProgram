"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const QUICK_QUESTIONS = {
  presale: [
    "这个商品什么时候发货？",
    "是现货还是预售？",
    "定金和尾款怎么支付？",
    "可以用猫币抵扣吗？",
    "支持开发票吗？"
  ],
  aftersale: [
    "我的订单现在是什么状态？",
    "怎么申请退款？",
    "退款进度在哪里看？",
    "签收后发现问题怎么办？",
    "请帮我转人工客服。"
  ]
};
const _sfc_main = {
  data() {
    return {
      scene: "presale",
      productId: "",
      orderId: "",
      sourcePage: "",
      draftQuestion: "",
      sending: false,
      messages: [],
      activeTab: "faq",
      scrollIntoView: ""
    };
  },
  computed: {
    welcomeText() {
      if (this.scene === "aftersale") {
        return "请输入您的问题，订单、退款、物流等问题我会先帮您梳理。";
      }
      return "请输入您的问题，商品、预售、尾款、发货等问题我都可以先帮您解答。";
    },
    quickQuestions() {
      return QUICK_QUESTIONS[this.scene] || QUICK_QUESTIONS.presale;
    }
  },
  onLoad(options) {
    this.scene = options.scene === "aftersale" ? "aftersale" : "presale";
    this.productId = options.productId || "";
    this.orderId = options.orderId || "";
    this.sourcePage = options.sourcePage || "";
    this.messages = [
      { role: "user", content: this.welcomeText },
      {
        role: "ai",
        content: this.scene === "aftersale" ? "我可以先帮您处理订单、物流、退款和售后相关问题，复杂情况也可以转人工客服。" : "我可以先帮您解答商品、预售、发货、尾款和支付相关问题，必要时也可以转人工客服。"
      }
    ];
    this.scrollToBottom();
  },
  methods: {
    goBack() {
      common_vendor.index.navigateBack({
        fail: () => {
          common_vendor.index.switchTab({ url: "/pages/index/index" });
        }
      });
    },
    scrollToBottom() {
      this.$nextTick(() => {
        if (!this.messages.length)
          return;
        this.scrollIntoView = "msg-" + (this.messages.length - 1);
      });
    },
    fillQuestion(question) {
      this.draftQuestion = question;
    },
    async sendMessage() {
      if (this.sending)
        return;
      if (!this.draftQuestion.trim()) {
        common_vendor.index.showToast({ title: "请先输入问题", icon: "none" });
        return;
      }
      const content = this.draftQuestion.trim();
      this.messages.push({ role: "user", content });
      this.draftQuestion = "";
      this.sending = true;
      this.scrollToBottom();
      try {
        const res = await utils_request.api.aiService.sendMessage({
          scene: this.scene,
          productId: this.productId,
          orderId: this.orderId,
          sourcePage: this.sourcePage,
          content
        });
        let replyText = res && res.data && res.data.reply ? res.data.reply : "暂未获取到回复，请稍后重试。";
        if (res && res.data && res.data.needTransfer) {
          replyText += "\n\n建议：当前问题建议直接转人工客服处理。";
        }
        this.messages.push({ role: "ai", content: replyText });
      } catch (error) {
        this.messages.push({ role: "ai", content: "请求失败了，您可以换个说法继续提问，或者直接转人工客服。" });
      } finally {
        this.sending = false;
        this.scrollToBottom();
      }
    },
    goToManualCustomer() {
      common_vendor.index.navigateTo({ url: "/pages/customer/customer" });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_assets._imports_0$8,
    b: common_vendor.o((...args) => $options.goBack && $options.goBack(...args), "22"),
    c: common_vendor.f($data.messages, (item, index, i0) => {
      return {
        a: common_vendor.t(item.content),
        b: common_vendor.n(item.role === "user" ? "bubble-user" : "bubble-ai"),
        c: "msg-" + index,
        d: index,
        e: common_vendor.n(item.role === "user" ? "bubble-row-user" : "bubble-row-ai")
      };
    }),
    d: $data.scrollIntoView,
    e: common_vendor.n($data.activeTab === "faq" ? "switch-pill-active" : ""),
    f: common_vendor.o(($event) => $data.activeTab = "faq", "be"),
    g: common_vendor.n($data.activeTab === "service" ? "switch-pill-active" : ""),
    h: common_vendor.o(($event) => $data.activeTab = "service", "82"),
    i: $data.activeTab === "faq"
  }, $data.activeTab === "faq" ? {
    j: common_vendor.f($options.quickQuestions, (item, index, i0) => {
      return {
        a: common_vendor.t(item),
        b: "faq-" + index,
        c: common_vendor.o(($event) => $options.fillQuestion(item), "faq-" + index)
      };
    })
  } : common_vendor.e({
    k: common_vendor.t($data.scene === "aftersale" ? "售后咨询" : "售前咨询"),
    l: common_vendor.o(($event) => $options.fillQuestion($data.scene === "aftersale" ? "我想咨询售后问题。" : "我想咨询预售商品。"), "79"),
    m: $data.orderId
  }, $data.orderId ? {
    n: common_vendor.o(($event) => $options.fillQuestion("请帮我查看订单状态。"), "0a")
  } : {}, {
    o: $data.productId
  }, $data.productId ? {
    p: common_vendor.o(($event) => $options.fillQuestion("请帮我介绍这个商品。"), "6d")
  } : {}, {
    q: common_vendor.o((...args) => $options.goToManualCustomer && $options.goToManualCustomer(...args), "36")
  }), {
    r: common_vendor.o((...args) => $options.sendMessage && $options.sendMessage(...args), "c8"),
    s: $data.draftQuestion,
    t: common_vendor.o(($event) => $data.draftQuestion = $event.detail.value, "4a"),
    v: common_vendor.n($data.sending ? "send-btn-disabled" : ""),
    w: common_vendor.o((...args) => $options.sendMessage && $options.sendMessage(...args), "ea")
  });
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-463d58a5"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/ai/customer.js.map
