<template>
	<view class="profile">
		<view class="user-info" @click="goToProfile()">
			<image class="avatar" :src="userInfo.avatar" mode="aspectFill" />
			<view class="user-details">
				<view class="user-name">{{ userInfo.nickName }}</view>
				<view class="user-id">小橘ID: {{ userInfo.id }}</view>
			</view>
		</view>

		<view class="user-stats">
			<view class="stat-item" @click="goToCollect">
				<text class="stat-number">{{ collectCount }}</text>
					<text class="stat-label">线上收藏</text>
			</view>
			<view class="stat-item" @click="goToBill">
				<text class="stat-number">{{ userInfo.snailShells }}</text>
					<text class="stat-label">我的猫币</text>
			</view>
		</view>

		<view class="welfare-banner">
			<image class="banner-icon" @click="goToGroup" src="/static/image/ad.png" mode="widthFix" />
		</view>

		<view class="order-section">
			<view class="section-header">
					<text class="section-title">我的订单</text>
					<text class="view-all" @click="goToOrders(0)">全部订单 <text class="right-arrow"></text></text>
			</view>
			<view class="order-status">
				<view class="status-item" @click="goToOrders(1)">
					<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_pending.png" mode="widthFix"></image></view>
						<text class="status-text">待支付</text>
				</view>
				<view class="status-item" @click="goToOrders(8)">
					<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_booked.png" mode="widthFix"></image></view>
						<text class="status-text">已预定</text>
				</view>
				<view class="status-item" @click="goToOrders(2)">
					<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_shipped.png" mode="widthFix"></image></view>
						<text class="status-text">待发货</text>
				</view>
				<view class="status-item" @click="goToOrders(6)">
					<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_received.png" mode="widthFix"></image></view>
						<text class="status-text">待收货</text>
				</view>
				<view class="status-item" @click="goToOrders(7)">
					<view class="status-icon"><image class="nav-item-image" src="/static/image/icon_completed.png" mode="widthFix"></image></view>
						<text class="status-text">已完成</text>
				</view>
			</view>
		</view>

		<view class="functions-section">
				<view class="section-header"><text class="section-title">常用功能</text></view>
			<view class="function-list">
				<view class="function-item" @click="goToCart()">
					<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_cart.png" mode="widthFix"></image></view>
						<text class="function-text">购物车</text>
					<view class="cart-badge" v-if="cartCount > 0">{{ cartCount }}</view>
					<text class="right-arrow"></text>
				</view>
				<view class="function-item" @click="goToProfile()">
					<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_profile.png" mode="widthFix"></image></view>
						<text class="function-text">个人资料</text>
					<text class="right-arrow"></text>
				</view>
				<view class="function-item" @click="goToCustomer()">
					<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_service.png" mode="widthFix"></image></view>
						<text class="function-text">联系客服</text>
					<text class="right-arrow"></text>
				</view>
				<view class="function-item" @click="goToAfterSales()">
					<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_question.png" mode="widthFix"></image></view>
						<text class="function-text">售后说明</text>
					<text class="right-arrow"></text>
				</view>
				<view class="function-item" @click="goToAgreement()">
					<view class="function-icon"><image class="nav-item-image" src="/static/image/icon_document.png" mode="widthFix"></image></view>
						<text class="function-text">服务协议</text>
					<text class="right-arrow"></text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	const ARRIVAL_SUBSCRIBE_TEMPLATE_ID = '064jbSrGui-nwHcDSAxE-laUCzY5cgbqciU3aeyAhig'
	const ARRIVAL_SUBSCRIBE_ASKED_KEY = 'arrival_subscribe_asked_v1'
	export default {
		name: 'Profile',
		data() {
			return {
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

		onLoad() {

		},
		onShow() {
			const token = uni.getStorageSync('token')
			if (token) {
				this.tryAskArrivalSubscribe()
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
					this.userInfo = response.data
				} catch (error) {

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
		background-color: #ffffff;
		padding-bottom: 150rpx;
	}

	.user-info {
		padding: 40rpx 30rpx;
		display: flex;
		align-items: center;
	}

	.avatar {
		width: 200rpx;
		height: 200rpx;
		border-radius: 50%;
		margin-right: 30rpx;
	}

	.user-details {
		flex: 1;
	}

	.user-name {
		font-size: 40rpx;
		color: #000000;
		font-weight: bold;
		margin-bottom: 10rpx;
	}

	.user-id {
		font-size: 24rpx;
	}

	.user-stats {
		padding: 20rpx 50rpx;
		display: flex;
		justify-content: flex-start;
	}

	.stat-item {
		width: 25%;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
	}

	.stat-number {
		font-size: 36rpx;
		color: #000000;
		font-weight: bold;
		margin-bottom: 8rpx;
	}

	.stat-label {
		font-size: 24rpx;
		color: #999999;
	}

	.welfare-banner {
		padding: 20rpx;
	}

	.banner-icon {
		width: 100%;
		height: auto;
	}

	.order-section {
		margin: 40rpx 30rpx 10rpx 30rpx;
	}

	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
	}

	.section-title {
		font-size: 36rpx;
		color: #000000;
		font-weight: bold;
		text-indent: 20rpx
	}

	.view-all {
		font-size: 24rpx;
		color: #999999;
	}

	.order-status {
		display: flex;
		justify-content: space-around;
		background-color: #ffffff;
		padding: 30rpx 0;
		border-radius: 20rpx;
	}

	.status-item {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.status-icon {
		width: 70rpx;
		height: 70rpx;
		margin-bottom: 12rpx;
	}

	.status-text {
		font-size: 24rpx;
		color: #333333;
	}

	.functions-section {
		margin: 0 30rpx;
	}

	.function-list {
		background-color: #ffffff;
		border-radius: 20rpx;
		overflow: hidden;
	}

	.function-item {
		display: flex;
		align-items: center;
		padding: 10rpx 30rpx;
		position: relative;
	}

	.function-item:last-child {
		border-bottom: none;
	}

	.function-icon {
		width: 70rpx;
		height: 70rpx;
		margin-right: 10rpx;
	}

	.nav-item-image {
		width: 100%;
		height: auto;
	}

	.function-text {
		flex: 1;
		font-size: 28rpx;
		color: #000000;
		text-indent: 10rpx;
		font-weight: 500;
	}

	.cart-badge {
		background-color: #dc0000;
		color: #ffffff;
		font-size: 20rpx;
		padding: 4rpx 12rpx;
		border-radius: 20rpx;
		margin-right: 16rpx;
		font-weight: 800;
	}

	.right-arrow {
		display: inline-block;
		width: 40rpx;
		height: 40rpx;
		background-image: url('/static/image/right-arrow.png');
		background-size: 100%;
	}
</style>
