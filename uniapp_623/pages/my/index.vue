<template>
	<view class="profile">
		<view class="profile-shell">
			<view class="brand-head">
				<view class="brand-lockup">
					<view class="brand-icon-wrap">
						<image class="brand-icon" src="/static/image/logo1.png" mode="aspectFit" />
					</view>
					<view class="brand-copy">
						<text class="brand-en">GOOMOO PLAY</text>
						<text class="brand-cn">橘猫智酷</text>
					</view>
				</view>
			</view>
	
			<view class="profile-card" @click="goToProfile()">
				<image class="card-bg-image" src="/static/image/my-profile-card-bg.png" mode="scaleToFill" />
				<view class="profile-top">
					<image class="avatar" :src="userInfo.avatar || '/static/image/default_avatar.jpg'" mode="aspectFill" />
					<view class="profile-info">
						<view class="user-headline">
							<view class="user-copy">
								<view class="user-mainline">
									<text class="user-name">{{ displayName }}</text>
								</view>
								<view class="user-id">橘猫智酷玩家</view>
							</view>
							<view class="profile-arrow"></view>
						</view>
						<view class="stats-grid">
							<view class="stat-item" @click.stop="goToCollect">
								<text class="stat-number">{{ collectCount }}</text>
								<text class="stat-label">线上收藏</text>
							</view>
							<view class="stat-item stat-divider">
								<text class="stat-number">{{ userInfo.snailShells || 0 }}</text>
								<text class="stat-label">我的猫饼</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view class="welfare-banner" @click="goToGroup">
				<image class="banner-bg-image" src="/static/image/my-welfare-banner-bg.png" mode="scaleToFill" />
				<view class="banner-copy">
					<text class="banner-title">社群福利·惊喜不断</text>
					<text class="banner-desc">新品资讯·专属福利</text>
				</view>
				<view class="banner-btn">
					<text>加入群聊</text>
					<text class="banner-btn-arrow">›</text>
				</view>
			</view>

			<view class="order-section card-block">
				<view class="section-header">
					<text class="section-title">我的订单</text>
					<view class="view-all" @click="goToOrders(0)">
						<text>全部订单</text>
						<text class="chevron">›</text>
					</view>
				</view>
				<view class="order-status">
					<view class="status-item" @click="goToOrders(1)">
						<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_pending.png" mode="aspectFit"></image></view>
						<text class="status-text">待支付</text>
					</view>
					<view class="status-item" @click="goToOrders(8)">
						<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_booked.png" mode="aspectFit"></image></view>
						<text class="status-text">已预定</text>
					</view>
					<view class="status-item" @click="goToOrders(2)">
						<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_shipped.png" mode="aspectFit"></image></view>
						<text class="status-text">待发货</text>
					</view>
					<view class="status-item" @click="goToOrders(6)">
						<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_received.png" mode="aspectFit"></image></view>
						<text class="status-text">待收货</text>
					</view>
					<view class="status-item" @click="goToOrders(7)">
						<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_completed.png" mode="aspectFit"></image></view>
						<text class="status-text">已完成</text>
					</view>
				</view>
			</view>

			<view class="functions-section card-block">
				<view class="section-header">
					<text class="section-title">常用功能</text>
				</view>
				<view class="function-list">
					<view class="function-item" @click="goToCart()">
						<view class="function-main">
							<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_cart.png" mode="aspectFit"></image></view>
							<text class="function-text">购物车</text>
						</view>
						<view class="function-side">
							<view class="cart-badge" v-if="cartCount > 0">{{ cartCount }}</view>
							<text class="chevron">›</text>
						</view>
					</view>
					<view v-if="showLotteryEntry" class="function-item" @click="goToBill()">
						<view class="function-main">
							<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_document.png" mode="aspectFit"></image></view>
							<text class="function-text">猫饼抽奖</text>
						</view>
						<text class="chevron">›</text>
					</view>
					<view class="function-item" @click="goToProfile()">
						<view class="function-main">
							<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_profile.png" mode="aspectFit"></image></view>
							<text class="function-text">个人资料</text>
						</view>
						<text class="chevron">›</text>
					</view>
					<view class="function-item" @click="goToCustomer()">
						<view class="function-main">
							<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_service.png" mode="aspectFit"></image></view>
							<text class="function-text">联系客服</text>
						</view>
						<text class="chevron">›</text>
					</view>
					<view class="function-item" @click="goToAfterSales()">
						<view class="function-main">
							<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_question.png" mode="aspectFit"></image></view>
							<text class="function-text">售后说明</text>
						</view>
						<text class="chevron">›</text>
					</view>
					<view class="function-item" @click="goToAgreement()">
						<view class="function-main">
							<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_document.png" mode="aspectFit"></image></view>
							<text class="function-text">服务协议</text>
						</view>
						<text class="chevron">›</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	const ARRIVAL_SUBSCRIBE_TEMPLATE_ID = 'PSTyqbj2wf1P74dSDb1qfh0ErUGegNQ8DFS6-SKM4_M'
	const ARRIVAL_SUBSCRIBE_ASKED_KEY = 'arrival_subscribe_asked_v1'
	export default {
		name: 'Profile',
	data() {
		return {
			showLotteryEntry: false,
			userInfo: {
					nickName: '',
					id: '',
					avatar: '/static/image/default_avatar.jpg',
					snailShells: 0
			},
			cartCount: 0,
			collectCount: 0,
		}
	},
	computed: {
		displayName() {
			return this.userInfo.nickName || '小橘酱'
		}
	},

		onLoad() {

		},
		onShow() {
			const token = uni.getStorageSync('token')
			if (token) {
				this.getProfileInfo()
				this.getCartCount()
				this.getCollectCount()
			}
		},

		methods: {
			tryAskArrivalSubscribe() {
				// #ifdef MP-WEIXIN
				const token = uni.getStorageSync('token')
				if (!token) {
					return
				}
				if (!ARRIVAL_SUBSCRIBE_TEMPLATE_ID || ARRIVAL_SUBSCRIBE_TEMPLATE_ID === 'REPLACE_WITH_TEMPLATE_ID') {
					return
				}
				const asked = uni.getStorageSync(ARRIVAL_SUBSCRIBE_ASKED_KEY)
				if (asked) {
					return
				}
				uni.showModal({
					title: '到货通知',
					content: '是否开启到货后通知？',
					confirmText: '开启',
					cancelText: '稍后',
					success: (res) => {
						if (!res.confirm) {
							uni.showToast({
							title: '未开启通知，到货后请在我的订单手动查看',
								icon: 'none'
							})
							return
						}
						wx.requestSubscribeMessage({
							tmplIds: [ARRIVAL_SUBSCRIBE_TEMPLATE_ID],
							success: (ret) => {
								const accepted = ret[ARRIVAL_SUBSCRIBE_TEMPLATE_ID] === 'accept'
								if (accepted) {
									uni.showToast({ title: '已开启通知', icon: 'none' })
								} else {
									uni.showToast({ title: '未开启通知，到货后请在我的订单手动查看', icon: 'none' })
								}
								uni.setStorageSync(ARRIVAL_SUBSCRIBE_ASKED_KEY, 1)
							},
							fail: () => {
								uni.showToast({ title: '订阅调用失败', icon: 'none' })
							}
						})
					}
				})
				// #endif
			},

			goLogin() {
				uni.showModal({
					content: '使用当前功能需要先登录，是否去登录？',
					success: function(res) {
						if (res.confirm) {
							uni.navigateTo({ url: '/pages/login/login' })
							return
						} else if (res.cancel) {
							console.log('user cancel login')
							return
						}
					}
				})
			},
			async getProfileInfo() {
				try {
					const response = await api.user.profile()
					const profile = response && response.data ? response.data : null
					if (!profile || !profile.id) {
						throw new Error('invalid profile')
					}
					this.userInfo = profile
					this.tryAskArrivalSubscribe()
				} catch (error) {
					uni.removeStorageSync('token')
				}
			},

			async getCartCount() {
				try {
					const response = await api.cart.count()
					this.cartCount = response.data.count
				} catch (error) {

				}
			},

			async getCollectCount() {
				try {
					const response = await api.collect.count()
					this.collectCount = response.data.count
				} catch (error) {

				}
			},

			goToCollect() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: '/pages/collect/collect'
					})
				}
			},

			goToBill() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: '/pages/my/bill'
					})
				}
			},

			goToOrders(status = '') {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: `/pages/order/list?status=${status}`
					})
				}
			},

			goToCart() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: '/pages/cart/cart'
					})
				}
			},

			async goToProfile() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
					return
				}
				try {
					const profileRes = await api.user.profile()
					if (!profileRes || !profileRes.data || !profileRes.data.id) {
						throw new Error('invalid profile')
					}
					uni.navigateTo({
						url: '/pages/my/profile'
					})
				} catch (error) {
					uni.removeStorageSync('token')
					this.goLogin()
				}
			},

			goToCustomer() {
				uni.navigateTo({
					url: '/pages/customer/customer'
				})
			},

			goToAfterSales() {
				uni.navigateTo({
					url: '/pages/my/sales'
				})
			},

			goToAgreement() {
				uni.navigateTo({
					url: '/pages/my/agraeement'
				})
			},

			goToGroup() {
				uni.navigateTo({
					url: '/pages/my/group'
				})
			}
		}
	}
</script>

<style scoped>
	.profile {
		padding-top: var(--status-bar-height);
		min-height: 100vh;
		background:
			radial-gradient(circle at 12% 0%, rgba(255, 192, 105, 0.16), transparent 24%),
			radial-gradient(circle at 100% 8%, rgba(255, 214, 154, 0.22), transparent 26%),
			linear-gradient(180deg, #fffdf8 0%, #fff8ef 100%);
		padding-bottom: 150rpx;
		box-sizing: border-box;
	}

	.profile-shell {
		padding: 28rpx 24rpx 0;
	}

	.brand-head {
		padding: 14rpx 10rpx 28rpx;
	}

	.brand-lockup {
		display: inline-flex;
		align-items: center;
	}

	.brand-icon-wrap {
		width: 74rpx;
		height: 74rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 16rpx;
	}

	.brand-icon {
		width: 74rpx;
		height: 74rpx;
	}

	.brand-copy {
		display: flex;
		flex-direction: column;
	}

	.brand-en {
		font-size: 22rpx;
		line-height: 1.1;
		font-weight: 800;
		color: #151515;
		letter-spacing: 1rpx;
	}

	.brand-cn {
		margin-top: 4rpx;
		font-size: 40rpx;
		line-height: 1.05;
		font-weight: 900;
		color: #141414;
	}

	.profile-card {
		position: relative;
		width: 690rpx;
		height: 270rpx;
		margin: 0 auto;
		padding: 28rpx 30rpx 24rpx;
		box-sizing: border-box;
		border-radius: 30rpx;
		box-shadow: 0 16rpx 40rpx rgba(243, 211, 167, 0.26);
		overflow: hidden;
	}

	.card-bg-image {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		z-index: 0;
	}

	.avatar {
		flex: none;
		width: 146rpx;
		height: 146rpx;
		border-radius: 50%;
		border: 4rpx solid rgba(255, 255, 255, 0.9);
		box-shadow: 0 10rpx 22rpx rgba(255, 174, 66, 0.16);
	}

	.profile-info {
		flex: 1;
		min-width: 0;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 8rpx 0 4rpx;
		position: relative;
		z-index: 1;
	}

	.user-headline {
		display: flex;
		align-items: center;
		justify-content: space-between;
		column-gap: 16rpx;
	}

	.user-copy {
		flex: 1;
		min-width: 0;
	}

	.user-mainline {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 8rpx;
		justify-content: flex-start;
	}

	.user-name {
		font-size: 40rpx;
		color: #111111;
		font-weight: 800;
		line-height: 1.1;
	}

	.level-pill {
		height: 34rpx;
		padding: 0 12rpx;
		border-radius: 18rpx;
		background: rgba(255, 177, 64, 0.18);
		border: 2rpx solid rgba(255, 177, 64, 0.46);
		display: inline-flex;
		align-items: center;
		font-size: 18rpx;
		font-weight: 700;
		color: #ff8e0c;
	}

	.level-paw {
		font-size: 14rpx;
		margin-right: 2rpx;
	}

	.user-id {
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #6d655e;
	}

	.profile-arrow {
		position: relative;
		flex: none;
		width: 52rpx;
		height: 52rpx;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.68);
	}

	.profile-arrow::before {
		content: '';
		position: absolute;
		left: 50%;
		top: 50%;
		width: 12rpx;
		height: 12rpx;
		border-top: 3rpx solid #111111;
		border-right: 3rpx solid #111111;
		transform: translate(-60%, -50%) rotate(45deg);
	}

	.stats-grid {
		margin-top: 10px;
		padding-top: 0;
		display: flex;
		justify-content: flex-start;
		position: relative;
		width: 100%;
	}

	.profile-top {
		display: flex;
		align-items: center;
		column-gap: 28rpx;
		height: 100%;
		position: relative;
		z-index: 1;
	}

	.stat-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		position: relative;
	}

	.stat-number {
		font-size: 42rpx;
		line-height: 1;
		color: #121212;
		font-weight: 700;
		margin-bottom: 8rpx;
	}

	.stat-label {
		font-size: 22rpx;
		color: #66605b;
	}

	.stat-divider::before,
	.stat-divider::after {
		content: '';
		position: absolute;
		top: 4rpx;
		width: 2rpx;
		height: 56rpx;
		background: rgba(188, 170, 146, 0.36);
	}

	.stat-divider::before {
		left: -86rpx;
	}

	.stat-divider::after {
		display: none;
	}

	.welfare-banner {
		margin-top: 26rpx;
		width: 690rpx;
		height: 244rpx;
		margin-left: auto;
		margin-right: auto;
		padding: 34rpx 32rpx;
		box-sizing: border-box;
		border-radius: 34rpx;
		box-shadow: 0 18rpx 48rpx rgba(243, 212, 171, 0.24);
		overflow: hidden;
		position: relative;
	}

	.banner-bg-image {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		z-index: 0;
	}

	.banner-copy {
		 position: absolute;
		    left: 32rpx;
		    top: 80rpx;
		    z-index: 1;
		    max-width: 330rpx;
	}

	.banner-title {
		display: block;
		font-size: 32rpx;
		line-height: 1.24;
		font-weight: 900;
		color: #1a1715;
	}

	.banner-desc {
		display: block;
		margin-top: 14rpx;
		font-size: 24rpx;
		line-height: 1.5;
		color: #7c736b;
	}

	.banner-btn {
		position: absolute;
		right: 34rpx;
		bottom: 15rpx;
		z-index: 1;
		height: 74rpx;
		padding: 0 28rpx 0 30rpx;
		border-radius: 40rpx;
		background: linear-gradient(180deg, #ffa625 0%, #ff8c05 100%);
		display: inline-flex;
		align-items: center;
		font-size: 28rpx;
		font-weight: 800;
		color: #ffffff;
		box-shadow: 0 10rpx 26rpx rgba(255, 145, 19, 0.28);
	}

	.banner-btn-arrow {
		font-size: 42rpx;
		line-height: 1;
		margin-left: 6rpx;
	}

	.card-block {
		margin-top: 28rpx;
		padding: 32rpx 26rpx;
		background: rgba(255, 255, 255, 0.9);
		border-radius: 34rpx;
		box-shadow: 0 16rpx 46rpx rgba(234, 222, 209, 0.32);
	}

	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 26rpx;
	}

	.section-title {
		font-size: 40rpx;
		color: #141414;
		font-weight: 900;
	}

	.view-all {
		display: inline-flex;
		align-items: center;
		font-size: 28rpx;
		color: #8c847e;
	}

	.chevron {
		font-size: 44rpx;
		line-height: 1;
		margin-left: 4rpx;
		color: #9b938c;
	}

	.order-status {
		display: flex;
		justify-content: space-between;
		padding: 10rpx 0 0;
		gap: 8rpx;
	}

	.status-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.status-icon {
		width: 70rpx;
		height: 70rpx;
		margin-bottom: 14rpx;
	}

	.status-text {
		font-size: 28rpx;
		color: #222222;
		font-weight: 500;
	}

	.function-list {
		overflow: hidden;
	}

	.function-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 28rpx 10rpx;
		border-bottom: 2rpx solid rgba(239, 232, 224, 0.9);
	}

	.function-item:last-child {
		border-bottom: none;
	}

	.function-main,
	.function-side {
		display: flex;
		align-items: center;
	}

	.function-icon {
		width: 74rpx;
		height: 74rpx;
		margin-right: 18rpx;
		flex-shrink: 0;
	}

	.nav-item-image {
		width: 100%;
		height: 100%;
	}

	.function-text {
		font-size: 28rpx;
		color: #141414;
		font-weight: 600;
	}

	.cart-badge {
		background-color: #ff8e10;
		color: #ffffff;
		font-size: 20rpx;
		padding: 4rpx 12rpx;
		border-radius: 20rpx;
		margin-right: 12rpx;
		font-weight: 800;
	}
</style>
