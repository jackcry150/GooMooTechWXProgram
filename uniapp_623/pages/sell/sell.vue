<template>
	<view class="sell">
		<view class="sell-header">
			<view class="sell-header-back">
				<image class="sell-header-back-img" src="/static/image/600_694.png" mode="widthFix"></image>
			</view>
			<view class="order-btn" @click="goToOrders(0)">订单</view>
		</view>

		<!-- 宣传图 -->
		<view class="sell-image" v-if="sellInfo && sellInfo.promoImages && sellInfo.promoImages.length > 0">
			<view class="sell-image-i" v-for="(img, index) in sellInfo.promoImages" :key="index">
				<image class="sell-image-img" :src="img" mode="widthFix"></image>
			</view>
			<view class="quantity-control">
				<view class="buy-now-btn" @click="handleBuyClick">{{ sellStateText }}</view>
				<view class="order-da" @click="handleBuyClick">
					<image class="order-da-img" src="/static/image/right-arrow-red.png" mode="widthFix"></image>
				</view>
			</view>
		</view>

		<!-- 预定须知图 -->
		<view class="booking-notice" v-if="sellInfo && sellInfo.reservationNotice && sellInfo.reservationNotice.length > 0">
			<image class="booking-notice-img" src="/static/image/booking-notice.png" mode="widthFix"></image>
			<view class="booking-notice-con">
				<image class="notice-image" v-for="(img, index) in sellInfo.reservationNotice" :key="index" :src="img" mode="widthFix" @click="previewImage(sellInfo.reservationNotice, index)"></image>
			</view>
		</view>

		<view class="submit-btn">
			<view class="submit-btn-sta" :class="{ 'submit-btn-active': canBuy }" @click="handleSubmit">
				{{ sellStateText }}
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'Sell',
		data() {
			return {
				sellInfo: null,
				sellState: 'ended' // canBuy | notStarted | ended
			}
		},
		computed: {
			sellStateText() {
				if (this.sellState === 'canBuy') return '立即购买'
				if (this.sellState === 'notStarted') return '贩售未开始'
				return '贩售已结束'
			},
			canBuy() {
				return this.sellState === 'canBuy'
			}
		},
		onLoad() {
			this.fetchLatestSell()
		},
		methods: {
			// 获取最新贩售数据
			async fetchLatestSell() {
				try {
					const res = await api.sell.latest()
					if (res.code === 1 && res.data) {
						this.sellInfo = res.data
						this.checkSellStatus()
					} else {
						// 没有符合条件的贩售数据
						uni.showModal({
							title: '提示',
							content: '暂无贩售活动',
							showCancel: false,
							success: () => {
								uni.navigateBack({
									fail: () => {
										uni.switchTab({
											url: '/pages/index/index'
										})
									}
								})
							}
						})
					}
				} catch (e) {
					console.error('获取贩售数据失败', e)
					uni.showModal({
						title: '提示',
						content: '获取数据失败',
						showCancel: false,
						success: () => {
							uni.navigateBack({
								fail: () => {
									uni.switchTab({
										url: '/pages/index/index'
									})
								}
							})
						}
					})
				}
			},

			// 根据开始时间、结束时间、库存、是否上架 判断贩售状态
			checkSellStatus() {
				if (!this.sellInfo) {
					this.sellState = 'ended'
					return
				}

				const now = new Date().getTime()
				const { stock, sellStatus, productStatus, startTime, endTime } = this.sellInfo
				const statusOk = (sellStatus == 1 || sellStatus == null) && (productStatus == 1 || productStatus == null)

				// 贩售未开始：有开始时间且当前时间早于开始时间
				if (startTime) {
					const start = new Date(startTime.replace(/-/g, '/')).getTime()
					if (now < start) {
						this.sellState = 'notStarted'
						return
					}
				}

				// 贩售已结束：库存<=0、未上架、或已过结束时间
				if (stock <= 0 || !statusOk) {
					this.sellState = 'ended'
					return
				}
				if (endTime) {
					const end = new Date(endTime.replace(/-/g, '/')).getTime()
					if (now > end) {
						this.sellState = 'ended'
						return
					}
				}

				this.sellState = 'canBuy'
			},

			goLogin() {
				uni.showModal({
					content: '使用当前功能需要您进行登录，是否去登录?',
					success: function(res) {
						if (res.confirm) {
							uni.navigateTo({
								url: '/pages/login/login'
							})
							return
						} else if (res.cancel) {
							console.log('用户点击取消')
							return
						}
					}
				})
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

			// 跳转到商品详情页（仅可购买时）
			goToProductDetail() {
				if (!this.sellInfo || !this.sellInfo.productId) {
					uni.showToast({ title: '商品信息不存在', icon: 'none' })
					return
				}
				uni.navigateTo({
					url: `/pages/product/detail?id=${this.sellInfo.productId}`
				})
			},

			// 购买按钮点击：仅可购买时跳转商品详情
			handleBuyClick() {
				if (this.sellState !== 'canBuy') {
					uni.showToast({
						title: this.sellState === 'notStarted' ? '贩售未开始' : '贩售已结束',
						icon: 'none'
					})
					return
				}
				this.goToProductDetail()
			},

			// 底部按钮点击
			handleSubmit() {
				if (this.sellState !== 'canBuy') {
					uni.showToast({
						title: this.sellState === 'notStarted' ? '贩售未开始' : '贩售已结束',
						icon: 'none'
					})
					return
				}
				this.goToProductDetail()
			},

			// 预览图片
			previewImage(images, current) {
				uni.previewImage({
					urls: images,
					current: current
				})
			}
		}
	}
</script>

<style>
	.sell {
		background-color: #000000;
		min-height: 100vh;
		width: 100%;
	}

	.sell-header {
		position: relative;
	}

	.sell-header-back {
		z-index: 1;
	}

	.sell-header-back-img {
		width: 100%;
		height: auto;
		z-index: 1;
	}

	.sell-image {
		position: relative;
	}

	.sell-image-i {
		width: 100%;
	}

	.sell-image-img {
		width: 100%;
		height: auto;
		z-index: 1;
		display: block;
	}

	.order-btn {
		z-index: 2;
		position: absolute;
		top: 240rpx;
		right: 50px;
		color: #ffffff;
		background: #950000;
		padding: 10rpx 24rpx;
		border-radius: 20rpx;
	}

	.quantity-control {
		display: flex;
		align-items: center;
		z-index: 2;
		position: absolute;
		bottom: 60rpx;
		right: 80rpx;
	}

	.buy-now-btn {
		padding: 16rpx 40rpx;
		background-color: rgba(149, 0, 0, 0.9);
		color: #ffffff;
		font-size: 28rpx;
		font-weight: bold;
		border-radius: 30rpx;
		text-align: center;
	}

	.order-da {
		width: 80rpx;
		height: 80rpx;
		background-color: rgba(0, 0, 0, 0.8);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-left: 30rpx;
	}

	.order-da-img {
		width: 60rpx;
		height: 60rpx;
	}

	.booking-notice {
		width: 100%;
		margin: auto;
		text-align: center;
	}

	.booking-notice-img {
		width: 290rpx;
		height: auto;
		margin: 80rpx 0 40rpx 0;
	}

	.booking-notice-con {
		width: 100%;
		margin: auto;
	}

	.notice-image {
		width: 100%;
		height: auto;
		display: block;
		margin-bottom: 20rpx;
		border-radius: 8rpx;
	}

	.submit-btn {
		width: 100%;
		text-align: center;
		padding: 50px 0 100rpx 0;
	}

	.submit-btn-sta {
		margin: auto;
		width: 90%;
		height: 120rpx;
		border-radius: 60rpx;
		text-align: center;
		line-height: 120rpx;
		background-color: #5a595f;
		color: #ffffff;
		font-weight: bold;
		font-size: 38rpx;
	}

	.submit-btn-active {
		background-color: #950000;
	}
</style>