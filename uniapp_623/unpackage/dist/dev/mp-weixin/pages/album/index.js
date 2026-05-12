"use strict";
const common_vendor = require("../../common/vendor.js");
const utils_request = require("../../utils/request.js");
const common_assets = require("../../common/assets.js");
const FILTERS = [
  { key: "all", label: "全部" },
  { key: "series", label: "系列" },
  { key: "character", label: "角色" },
  { key: "styling", label: "穿搭" }
];
const _sfc_main = {
  data() {
    return {
      albumList: [],
      activeFilter: "all",
      filters: FILTERS
    };
  },
  computed: {
    displayAlbumList() {
      const list = Array.isArray(this.albumList) ? this.albumList : [];
      if (this.activeFilter === "all") {
        return list;
      }
      return list.filter((item) => {
        const haystack = [
          item.title,
          item.subtitle,
          item.categoryName,
          item.series,
          ...Array.isArray(item.labels) ? item.labels : []
        ].filter(Boolean).join(" ");
        if (this.activeFilter === "series") {
          return /系列/.test(haystack);
        }
        if (this.activeFilter === "character") {
          return /角色|猫|酱|女孩|男孩|娃/.test(haystack);
        }
        if (this.activeFilter === "styling") {
          return /穿搭|套装|日常|服饰|搭配/.test(haystack);
        }
        return true;
      });
    },
    bannerImage() {
      var _a, _b;
      return ((_a = this.displayAlbumList[0]) == null ? void 0 : _a.image) || ((_b = this.albumList[0]) == null ? void 0 : _b.image) || "/static/image/no-data.png";
    }
  },
  onLoad() {
    this.loadInfo();
  },
  onShow() {
    this.loadInfo();
  },
  onPullDownRefresh() {
    this.loadInfo().finally(() => {
      common_vendor.index.stopPullDownRefresh();
    });
  },
  methods: {
    async loadInfo() {
      await this.getAlbumList();
    },
    setFilter(key) {
      this.activeFilter = key;
    },
    async getAlbumList() {
      try {
        const response = await utils_request.api.album.list();
        this.albumList = Array.isArray(response.data) ? response.data : [];
      } catch (error) {
        this.albumList = [];
        common_vendor.index.showToast({
          title: "图册加载失败",
          icon: "none"
        });
      }
    },
    getDisplayLabels(item) {
      const labels = Array.isArray(item == null ? void 0 : item.labels) ? item.labels.filter(Boolean) : [];
      if (labels.length) {
        return labels.slice(0, 2);
      }
      const fallback = [item == null ? void 0 : item.categoryName, item == null ? void 0 : item.series, item == null ? void 0 : item.subtitle].filter(Boolean).slice(0, 2);
      return fallback;
    },
    getSubtitle(item) {
      const labels = this.getDisplayLabels(item);
      if (item == null ? void 0 : item.subtitle) {
        return item.subtitle;
      }
      if (labels.length) {
        return labels.join(" · ");
      }
      return "探索本期图册灵感";
    },
    getBadgeText(item, index) {
      if (item == null ? void 0 : item.badge) {
        return item.badge;
      }
      if (index === 0) {
        return "NEW";
      }
      if (index === 1) {
        return "热门";
      }
      if (index === 2) {
        return "推荐";
      }
      return "";
    },
    getCountText(item, index) {
      const rawCount = (item == null ? void 0 : item.count) || (item == null ? void 0 : item.imageCount) || (item == null ? void 0 : item.total) || (Array.isArray(item == null ? void 0 : item.images) ? item.images.length : 0);
      if (rawCount) {
        return `${rawCount} 款`;
      }
      return `${Math.max(6, 12 - index)} 款`;
    },
    goToAlbumDetail(item) {
      common_vendor.index.navigateTo({
        url: `/pages/album/details?id=${item.id}`
      });
    },
    goToExplore() {
      if (!this.displayAlbumList.length) {
        return;
      }
      this.goToAlbumDetail(this.displayAlbumList[0]);
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return common_vendor.e({
    a: common_vendor.f($data.filters, (filter, k0, i0) => {
      return common_vendor.e({
        a: common_vendor.t(filter.label),
        b: $data.activeFilter === filter.key
      }, $data.activeFilter === filter.key ? {} : {}, {
        c: filter.key,
        d: $data.activeFilter === filter.key ? 1 : "",
        e: common_vendor.o(($event) => $options.setFilter(filter.key), filter.key)
      });
    }),
    b: $options.displayAlbumList.length
  }, $options.displayAlbumList.length ? {
    c: common_vendor.f($options.displayAlbumList, (item, index, i0) => {
      return common_vendor.e({
        a: item.image,
        b: $options.getBadgeText(item, index)
      }, $options.getBadgeText(item, index) ? {
        c: common_vendor.t($options.getBadgeText(item, index))
      } : {}, {
        d: common_vendor.t(item.title || "未命名图册"),
        e: common_vendor.t($options.getSubtitle(item)),
        f: common_vendor.f($options.getDisplayLabels(item), (tag, tagIndex, i1) => {
          return {
            a: common_vendor.t(tag),
            b: `${item.id || index}-${tagIndex}`
          };
        }),
        g: common_vendor.t($options.getCountText(item, index)),
        h: item.id || index,
        i: common_vendor.o(($event) => $options.goToAlbumDetail(item), item.id || index)
      });
    })
  } : {
    d: common_assets._imports_0
  }, {
    e: $options.displayAlbumList.length
  }, $options.displayAlbumList.length ? {
    f: $options.bannerImage,
    g: common_vendor.o((...args) => $options.goToExplore && $options.goToExplore(...args), "5a")
  } : {});
}
const MiniProgramPage = /* @__PURE__ */ common_vendor._export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-2956ad5a"]]);
wx.createPage(MiniProgramPage);
//# sourceMappingURL=../../../.sourcemap/mp-weixin/pages/album/index.js.map
