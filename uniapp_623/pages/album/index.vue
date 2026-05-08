<template>
	<view class="album-page">
		<view class="album-page__glow album-page__glow--left"></view>
		<view class="album-page__glow album-page__glow--right"></view>

		<view class="album-page__inner">
			<view class="album-hero">
				<scroll-view class="album-filter-scroll" scroll-x enable-flex show-scrollbar="false">
					<view class="album-filter-row">
						<view
							v-for="filter in filters"
							:key="filter.key"
							class="album-filter-chip"
							:class="{ 'is-active': activeFilter === filter.key }"
							@click="setFilter(filter.key)"
						>
							<text>{{ filter.label }}</text>
							<view v-if="activeFilter === filter.key" class="album-filter-chip__paw">
								<view class="paw-dot paw-dot--main"></view>
								<view class="paw-dot paw-dot--top"></view>
								<view class="paw-dot paw-dot--left"></view>
								<view class="paw-dot paw-dot--right"></view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>

			<view v-if="displayAlbumList.length" class="album-grid">
				<view
					v-for="(item, index) in displayAlbumList"
					:key="item.id || index"
					class="album-card"
					@click="goToAlbumDetail(item)"
				>
					<view class="album-card__media">
						<image :src="item.image" class="album-card__image" mode="aspectFill"></image>
						<view v-if="getBadgeText(item, index)" class="album-card__badge">
							{{ getBadgeText(item, index) }}
						</view>
					</view>
					<view class="album-card__body">
						<view class="album-card__title">{{ item.title || '未命名图册' }}</view>
						<view class="album-card__subtitle">
							{{ getSubtitle(item) }}
						</view>
						<view class="album-card__meta">
							<view class="album-card__tags">
								<text
									v-for="(tag, tagIndex) in getDisplayLabels(item)"
									:key="`${item.id || index}-${tagIndex}`"
									class="album-card__tag"
								>
									{{ tag }}
								</text>
							</view>
							<text class="album-card__count">{{ getCountText(item, index) }}</text>
						</view>
					</view>
				</view>
			</view>

			<view v-else class="empty-state">
				<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
				<text class="empty-title">这里还没有内容哦</text>
				<text class="empty-text">换个分类试试，或者稍后再来看看。</text>
			</view>

			<view v-if="displayAlbumList.length" class="album-banner" @click="goToExplore">
				<view class="album-banner__content">
					<view class="album-banner__title">发现更多精彩</view>
					<view class="album-banner__desc">探索更多系列与穿搭灵感</view>
					<view class="album-banner__button">去探索更多</view>
				</view>
				<view class="album-banner__visual">
					<image
						:src="bannerImage"
						class="album-banner__image"
						mode="aspectFill"
					></image>
				</view>
				<view class="album-banner__paw album-banner__paw--one"></view>
				<view class="album-banner__paw album-banner__paw--two"></view>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'

	const FILTERS = [
		{ key: 'all', label: '全部' },
		{ key: 'series', label: '系列' },
		{ key: 'character', label: '角色' },
		{ key: 'styling', label: '穿搭' },
	]

	export default {
		data() {
			return {
				albumList: [],
				activeFilter: 'all',
				filters: FILTERS,
			}
		},

		computed: {
			displayAlbumList() {
				const list = Array.isArray(this.albumList) ? this.albumList : []
				if (this.activeFilter === 'all') {
					return list
				}

				return list.filter((item) => {
					const haystack = [
						item.title,
						item.subtitle,
						item.categoryName,
						item.series,
						...(Array.isArray(item.labels) ? item.labels : []),
					]
						.filter(Boolean)
						.join(' ')

					if (this.activeFilter === 'series') {
						return /系列/.test(haystack)
					}

					if (this.activeFilter === 'character') {
						return /角色|猫|酱|女孩|男孩|娃/.test(haystack)
					}

					if (this.activeFilter === 'styling') {
						return /穿搭|套装|日常|服饰|搭配/.test(haystack)
					}

					return true
				})
			},

			bannerImage() {
				return this.displayAlbumList[0]?.image || this.albumList[0]?.image || '/static/image/no-data.png'
			},
		},

		onLoad() {
			this.loadInfo()
		},

		onShow() {
			this.loadInfo()
		},

		onPullDownRefresh() {
			this.loadInfo().finally(() => {
				uni.stopPullDownRefresh()
			})
		},

		methods: {
			async loadInfo() {
				await this.getAlbumList()
			},

			setFilter(key) {
				this.activeFilter = key
			},

			async getAlbumList() {
				try {
					const response = await api.album.list()
					this.albumList = Array.isArray(response.data) ? response.data : []
				} catch (error) {
					this.albumList = []
					uni.showToast({
						title: '图册加载失败',
						icon: 'none',
					})
				}
			},

			getDisplayLabels(item) {
				const labels = Array.isArray(item?.labels) ? item.labels.filter(Boolean) : []
				if (labels.length) {
					return labels.slice(0, 2)
				}

				const fallback = [item?.categoryName, item?.series, item?.subtitle]
					.filter(Boolean)
					.slice(0, 2)

				return fallback
			},

			getSubtitle(item) {
				const labels = this.getDisplayLabels(item)
				if (item?.subtitle) {
					return item.subtitle
				}
				if (labels.length) {
					return labels.join(' · ')
				}
				return '探索本期图册灵感'
			},

			getBadgeText(item, index) {
				if (item?.badge) {
					return item.badge
				}
				if (index === 0) {
					return 'NEW'
				}
				if (index === 1) {
					return '热门'
				}
				if (index === 2) {
					return '推荐'
				}
				return ''
			},

			getCountText(item, index) {
				const rawCount =
					item?.count ||
					item?.imageCount ||
					item?.total ||
					(Array.isArray(item?.images) ? item.images.length : 0)

				if (rawCount) {
					return `${rawCount} 款`
				}

				return `${Math.max(6, 12 - index)} 款`
			},

			goToAlbumDetail(item) {
				uni.navigateTo({
					url: `/pages/album/details?id=${item.id}`,
				})
			},

			goToExplore() {
				if (!this.displayAlbumList.length) {
					return
				}
				this.goToAlbumDetail(this.displayAlbumList[0])
			},
		},
	}
</script>

<style scoped>
	.album-page {
		position: relative;
		min-height: 100vh;
		background:
			radial-gradient(circle at top left, rgba(255, 184, 77, 0.18), transparent 30%),
			radial-gradient(circle at top right, rgba(255, 208, 145, 0.18), transparent 26%),
			linear-gradient(180deg, #fffaf3 0%, #fffdfa 44%, #fff7ef 100%);
		overflow: hidden;
	}

	.album-page__glow {
		position: absolute;
		border-radius: 999rpx;
		filter: blur(18rpx);
		opacity: 0.6;
		pointer-events: none;
	}

	.album-page__glow--left {
		top: 220rpx;
		left: -120rpx;
		width: 260rpx;
		height: 260rpx;
		background: rgba(255, 196, 121, 0.22);
	}

	.album-page__glow--right {
		top: 540rpx;
		right: -140rpx;
		width: 320rpx;
		height: 320rpx;
		background: rgba(255, 214, 168, 0.28);
	}

	.album-page__inner {
		position: relative;
		z-index: 1;
		padding: 34rpx 26rpx 170rpx;
	}

	.album-hero {
		padding: 8rpx 6rpx 18rpx;
	}

	.album-filter-scroll {
		width: 100%;
		white-space: nowrap;
	}

	.album-filter-row {
		display: inline-flex;
		gap: 16rpx;
		padding: 4rpx 2rpx;
	}

	.album-filter-chip {
		position: relative;
		display: inline-flex;
		flex-shrink: 0;
		align-items: center;
		justify-content: center;
		width: 105rpx;
		height: 72rpx;
		padding: 0 26rpx;
		border-radius: 999rpx;
		background: rgba(255, 255, 255, 0.88);
		border: 2rpx solid rgba(245, 228, 209, 0.92);
		color: #3f2a1e;
		font-size: 26rpx;
		font-weight: 600;
		box-shadow: 0 10rpx 24rpx rgba(232, 197, 151, 0.18);
	}

	.album-filter-chip.is-active {
		background: linear-gradient(135deg, #ff981f 0%, #ffb14f 100%);
		color: #ffffff;
		box-shadow: 0 18rpx 34rpx rgba(255, 152, 31, 0.28);
	}

	.album-filter-chip__paw {
		position: absolute;
		right: 18rpx;
		bottom: 12rpx;
		width: 22rpx;
		height: 18rpx;
	}

	.paw-dot {
		position: absolute;
		background: rgba(255, 234, 209, 0.92);
		border-radius: 50%;
	}

	.paw-dot--main {
		left: 5rpx;
		bottom: 0;
		width: 12rpx;
		height: 10rpx;
	}

	.paw-dot--top {
		left: 8rpx;
		top: 0;
		width: 6rpx;
		height: 6rpx;
	}

	.paw-dot--left {
		left: 0;
		top: 6rpx;
		width: 6rpx;
		height: 6rpx;
	}

	.paw-dot--right {
		right: 1rpx;
		top: 6rpx;
		width: 6rpx;
		height: 6rpx;
	}

	.album-grid {
		display: flex;
		flex-wrap: wrap;
		align-items: flex-start;
		gap: 22rpx;
	}

	.album-card {
		width: calc((100% - 22rpx) / 2);
		border-radius: 34rpx;
		overflow: hidden;
		background: rgba(255, 255, 255, 0.9);
		box-shadow: 0 22rpx 54rpx rgba(209, 182, 145, 0.2);
	}

	.album-card__media {
		position: relative;
		height: 430rpx;
		background: linear-gradient(180deg, #ffe7cb 0%, #fff5ea 100%);
	}

	.album-card__image {
		width: 100%;
		height: 100%;
	}

	.album-card__badge {
		position: absolute;
		top: 18rpx;
		left: 18rpx;
		padding: 8rpx 18rpx;
		border-radius: 18rpx;
		background: linear-gradient(135deg, #ff8f1f 0%, #ffab3d 100%);
		color: #ffffff;
		font-size: 24rpx;
		font-weight: 700;
	}

	.album-card__fav {
		position: absolute;
		top: 18rpx;
		right: 18rpx;
		width: 58rpx;
		height: 58rpx;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.22);
		backdrop-filter: blur(10rpx);
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.album-card__fav-heart {
		position: relative;
		width: 24rpx;
		height: 24rpx;
		transform: rotate(-45deg);
		background: rgba(255, 255, 255, 0.95);
	}

	.album-card__fav-heart::before,
	.album-card__fav-heart::after {
		content: '';
		position: absolute;
		width: 24rpx;
		height: 24rpx;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.95);
	}

	.album-card__fav-heart::before {
		top: -12rpx;
		left: 0;
	}

	.album-card__fav-heart::after {
		top: 0;
		left: 12rpx;
	}

	.album-card__body {
		padding: 26rpx 22rpx 24rpx;
	}

	.album-card__title {
		color: #23150d;
		font-size: 28rpx;
		line-height: 1.35;
		font-weight: 700;
	}

	.album-card__subtitle {
		margin-top: 10rpx;
		color: #78604f;
		font-size: 22rpx;
		line-height: 1.5;
		min-height: 66rpx;
	}

	.album-card__meta {
		margin-top: 18rpx;
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		gap: 12rpx;
	}

	.album-card__tags {
		display: flex;
		flex-wrap: wrap;
		gap: 10rpx;
		flex: 1;
		min-width: 0;
	}

	.album-card__tag {
		max-width: 100%;
		padding: 8rpx 14rpx;
		border-radius: 999rpx;
		background: #fff4e6;
		color: #b5752a;
		font-size: 20rpx;
		line-height: 1;
	}

	.album-card__count {
		flex-shrink: 0;
		color: #ff8e1b;
		font-size: 24rpx;
		font-weight: 700;
	}

	.album-banner {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-top: 34rpx;
		padding: 42rpx 36rpx;
		border-radius: 40rpx;
		background:
			radial-gradient(circle at top left, rgba(255, 194, 115, 0.24), transparent 34%),
			linear-gradient(135deg, #fff4df 0%, #ffeacc 100%);
		box-shadow: 0 24rpx 56rpx rgba(225, 190, 142, 0.24);
		overflow: hidden;
	}

	.album-banner__content {
		position: relative;
		z-index: 1;
		flex: 1;
		padding-right: 18rpx;
	}

	.album-banner__title {
		color: #2b1c12;
		font-size: 52rpx;
		font-weight: 700;
		line-height: 1.15;
	}

	.album-banner__desc {
		margin-top: 18rpx;
		color: #8d715d;
		font-size: 28rpx;
		line-height: 1.5;
	}

	.album-banner__button {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		margin-top: 30rpx;
		min-width: 236rpx;
		height: 78rpx;
		padding: 0 28rpx;
		border-radius: 999rpx;
		background: linear-gradient(135deg, #ff981f 0%, #ffb342 100%);
		color: #ffffff;
		font-size: 30rpx;
		font-weight: 700;
		box-shadow: 0 16rpx 28rpx rgba(255, 152, 31, 0.24);
	}

	.album-banner__visual {
		position: relative;
		z-index: 1;
		width: 240rpx;
		height: 240rpx;
		border-radius: 34rpx;
		overflow: hidden;
		box-shadow: 0 20rpx 36rpx rgba(214, 175, 121, 0.18);
	}

	.album-banner__image {
		width: 100%;
		height: 100%;
	}

	.album-banner__paw {
		position: absolute;
		width: 60rpx;
		height: 60rpx;
		background: radial-gradient(circle, rgba(255, 190, 99, 0.42) 30%, transparent 32%);
		opacity: 0.55;
	}

	.album-banner__paw--one {
		top: 26rpx;
		right: 182rpx;
	}

	.album-banner__paw--two {
		bottom: 34rpx;
		left: 52%;
	}

	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 220rpx 48rpx 160rpx;
		text-align: center;
	}

	.empty-icon {
		width: 360rpx;
		height: auto;
		margin-bottom: 26rpx;
		opacity: 0.9;
	}

	.empty-title {
		font-size: 34rpx;
		font-weight: 600;
		color: #3a2618;
	}

	.empty-text {
		margin-top: 12rpx;
		font-size: 24rpx;
		line-height: 1.6;
		color: #a28d79;
	}
</style>
