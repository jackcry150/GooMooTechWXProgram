"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const _sfc_main = {
  name: "Bill",
  data() {
    return {
      cost: 10,
      ruleText: "每次抽奖消耗固定数量猫饼，奖品和概率以后台配置为准。",
      userInfo: {
        snailShells: 0
      },
      prizes: [],
      records: [],
      drawing: false
    };
  },
  computed: {
    drawButtonText() {
      return this.drawing ? "抽奖中..." : `立即抽奖 -${this.cost} 猫饼`;
    }
  },
  onShow() {
    this.getLotteryInfo();
  },
  methods: {
    goLogin() {
      common_vendor.index.showModal({
        content: "使用当前功能需要先登录，是否去登录？",
        success: function(res) {
          if (res.confirm) {
            common_vendor.index.navigateTo({
              url: "/pages/login/login"
            });
          } else if (res.cancel) {
            common_vendor.index.navigateBack();
          }
        }
      });
    },
    formatPrizeDesc(item) {
      if (item.rewardType == 2 && item.rewardValue > 0) {
        return `获得 ${item.rewardValue} 猫饼`;
      }
      if (item.rewardType == 3 && item.rewardValue > 0) {
        return `获得 ${item.rewardValue} 张收藏卡`;
      }
      if (item.rewardType == 4) {
        return item.description || "实物奖品";
      }
      return item.description || item.rewardTypeText || "谢谢参与";
    },
    async getLotteryInfo() {
      const token = common_vendor.index.getStorageSync("token");
      if (!token) {
        this.goLogin();
        return;
      }
      try {
        const response = await utils_request.api.lottery.info();
        if (response.code !== 200) {
          throw new Error(response.msg || "获取抽奖信息失败");
        }
        const data = response.data || {};
        this.cost = data.cost || 10;
        this.ruleText = data.rule || this.ruleText;
        this.userInfo = data.userInfo || { snailShells: 0 };
        this.prizes = data.prizes || [];
        this.records = data.records || [];
      } catch (error) {
        common_vendor.index.showToast({
          title: error.message || "加载失败",
          icon: "none"
        });
      }
    },
    async startDraw() {
      if (this.drawing) {
        return;
      }
      if ((this.userInfo.snailShells || 0) < this.cost) {
        common_vendor.index.showToast({
          title: "猫饼不足",
          icon: "none"
        });
        return;
      }
      this.drawing = true;
      try {
        const response = await utils_request.api.lottery.draw();
        if (response.code !== 200) {
          throw new Error(response.msg || "抽奖失败");
        }
        const result = response.data || {};
        const prize = result.prize || {};
        this.userInfo.snailShells = result.snailShells || 0;
        common_vendor.index.showModal({
          title: "抽奖结果",
          content: `${prize.name || "未知奖品"}
${this.formatPrizeDesc(prize)}`,
          showCancel: false
        });
        this.getLotteryInfo();
      } catch (error) {
        common_vendor.index.showToast({
          title: error.message || "抽奖失败",
          icon: "none"
        });
      } finally {
        this.drawing = false;
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.t($data.userInfo.snailShells || 0),
    b: common_vendor.t($data.cost),
    c: common_vendor.t($data.ruleText),
    d: common_vendor.t($options.drawButtonText),
    e: $data.drawing,
    f: common_vendor.o((...args) => $options.startDraw && $options.startDraw(...args), "3d"),
    g: ($data.userInfo.snailShells || 0) < $data.cost
  }, ($data.userInfo.snailShells || 0) < $data.cost ? {} : {}, {
    h: common_vendor.t($data.prizes.length),
    i: $data.prizes.length
  }, $data.prizes.length ? {
    j: common_vendor.f($data.prizes, (item, k0, i0) => {
      return common_vendor.e({
        a: item.image
      }, item.image ? {
        b: item.image
      } : {
        c: common_vendor.t(item.name.slice(0, 2))
      }, {
        d: common_vendor.t(item.name),
        e: common_vendor.t($options.formatPrizeDesc(item)),
        f: common_vendor.t(item.stockText),
        g: item.id
      });
    })
  } : {}, {
    k: common_vendor.t($data.records.length),
    l: $data.records.length
  }, $data.records.length ? {
    m: common_vendor.f($data.records, (item, k0, i0) => {
      return {
        a: common_vendor.t(item.prizeName),
        b: common_vendor.t($options.formatPrizeDesc(item)),
        c: common_vendor.t(item.createDate),
        d: item.id
      };
    })
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-8ba69474"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/my/bill.js.map
