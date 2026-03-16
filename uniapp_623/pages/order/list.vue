<template>
	<view class="order-list">
		<!-- 订单状态标签 -->
		<view class="order-tabs">
			<view class="tab-item" :class="{ active: currentTab == tab.key }" v-for="tab in tabs" :key="tab.key" @click="switchTab(tab.key)">
				{{ tab.label }}
			</view>
		</view>

		<!-- 订单列表 -->
		<view class="order-list-content">
			<view class="order-item" v-for="(order, index) in orderList" :key="index">
				<view @click="goToOrderDetail(order)">
					<view class="order-header">
						<text class="order-number">订单编号: {{ order.orderNo }}</text>
						<text class="order-status" :style="order.statusClass">{{ order.statusVal }}</text>
					</view>
					
					<view class="order-product" v-for="(product, index2) in order.product" :key="index2">
						<view class="product-image-l">
							<image class="product-image" :src="product.image" mode="aspectFill" />
						</view>
						<view class="product-info">
							<view class="product-title">{{ product.title }} {{ product.subtitle }}</view>
							<view class="product-tag">{{ product.version }}</view>
							<view class="product-price-quantity">
								<view class="product-price">¥{{ product.price }}</view>
								<view class="product-quantity">x{{ product.quantity }}</view>
							</view>
						</view>
					</view>
				</view>
				<view class="order-bottom" v-if="order.status == 1 || order.status == 8">
					<view class="order-prire">
						<text class="order-prire-t">应付</text>
						<text class="order-prire-val">{{ order.totalPrice }}</text>
					</view>
					<view class="order-btn">
						<view class="order-cancel-btn" @tap="orderCancel(order)">取消订单</view>
						<view class="order-pay-btn" @click="orderPay(order)">立即支付</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 空状态 -->
		<view class="empty-state" v-if="orderList.length === 0">
			<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
			<text class="empty-text">什么都没有呢~</text>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'OrderList',
		data() {
			return {
				currentTab: 0,
				tabs: [
					{ key: 0, label: '全部' },
					{ key: 1, label: '待支付' },
					{ key: 8, label: '已预定' },
					{ key: 2, label: '待发货' },
					{ key: 6, label: '待收货' },
					{ key: 7, label: '已完成' }
				],
				orderList: []
			}
		},
		onLoad(options) {
			if (options.status !== undefined) {
				this.currentTab = parseInt(options.status)
			}
			this.loadOrders(this.currentTab)
		},
		methods: {
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
							uni.navigateBack()
							return
						}
					}
				})
			},

			switchTab(tabKey) {
				this.currentTab = tabKey
				this.loadOrders(tabKey)
			},
			async loadOrders(status) {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					try {
						const params = {
							status: status
						}
						const response = await api.order.list(params)
						this.orderList = response.data
					} catch (error) {
						uni.showToast({
							title: '加载失败',
							icon: 'none'
						})
					}
				}
			},
			async orderCancel(o) {
				try {
					const params = {
						id: o.id
					}
					const response = await api.order.cancel(params)
					uni.showToast({
						title: response.msg,
						icon: 'success'
					})
					this.orderList.forEach(item => {
						if (item.id == o.id) {
							item.status = response.data.status
							item.statusVal = response.data.statusVal
							item.statusClass = response.data.statusClass
						}
					})
				} catch (error) {
					uni.showToast({
						title: '操作失败',
						icon: 'none'
					})
				}
			},
			getAppId() {
				let appid = ''
				// #ifdef MP-WEIXIN
				try { appid = uni.getAccountInfoSync().miniProgram.appId } catch (e) {}
				// #endif
				return appid
			},
			requestPayment(paymentData, orderId, payType = 'full') {
				// #ifdef MP-WEIXIN
				uni.requestPayment({
					provider: 'wxpay',
					timeStamp: paymentData.timeStamp || '',
					nonceStr: paymentData.nonceStr || '',
					package: paymentData.package || '',
					signType: paymentData.signType || 'RSA',
					paySign: paymentData.paySign || '',
					success: () => {
						const msg = payType === 'deposit' ? '定金支付成功'
							: payType === 'balance' ? '尾款支付成功' : '支付成功'
						uni.showToast({ title: msg, icon: 'success' })
						setTimeout(() => {
							this.loadOrders(this.currentTab)
							uni.redirectTo({ url: `/pages/order/detail?id=${orderId}` })
						}, 1500)
					},
					fail: (err) => {
						const msg = err.errMsg && err.errMsg.indexOf('cancel') !== -1 ? '支付已取消' : '支付失败，请重试'
						uni.showToast({ title: msg, icon: 'none' })
					}
				})
				// #endif
				// #ifndef MP-WEIXIN
				uni.showToast({ title: '请在微信小程序中完成支付', icon: 'none' })
				// #endif
			},
			async orderPay(o) {
				try {
					const payType = o.status == 8 ? 'deposit' : 'full'
					uni.showLoading({ title: '获取支付参数...' })
					const params = { id: o.id, payType, appid: this.getAppId() }
					const response = await api.order.pay(params)
					uni.hideLoading()
					if (response.code === 200 && response.data && response.data.payment) {
						const payment = { ...response.data.payment, orderId: response.data.orderId || o.id }
						this.requestPayment(payment, o.id, response.data.payType || payType)
					} else {
						uni.showToast({ title: response.msg || '获取支付参数失败', icon: 'none' })
					}
				} catch (error) {
					uni.hideLoading()
					uni.showToast({ title: '支付失败', icon: 'none' })
				}
			},
			goToOrderDetail(order) {
				uni.navigateTo({
					url: `/pages/order/detail?id=${order.id}`
				})
			}
		}
	}
</script>

<style scoped>
	.order-list {
		background-color: #f5f5f5;
		min-height: 100vh;
	}

	.order-tabs {
		background-color: #ffffff;
		display: flex;
		padding: 0 30rpx;
		border-bottom: 1rpx solid #eeeeee;
	}

	.tab-item {
		flex: 1;
		text-align: center;
		padding: 30rpx 0;
		font-size: 28rpx;
		color: #666666;
		position: relative;
	}

	.tab-item.active {
		color: #dc0000;
	}

	.tab-item.active::after {
		content: '';
		position: absolute;
		bottom: 0;
		left: 50%;
		transform: translateX(-50%);
		width: 60rpx;
		height: 4rpx;
		background-color: #dc0000;
	}

	.order-list-content {
		padding: 20rpx;
	}

	.order-item {
		background-color: #ffffff;
		border-radius: 20rpx;
		margin-bottom: 20rpx;
		padding: 30rpx;
	}

	.order-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
	}

	.order-number {
		font-size: 24rpx;
		color: #333333;
	}

	.order-status {
		font-size: 24rpx;
		color: #cccccc;
		font-weight: bold;
	}


	.order-product {
		display: flex;
		align-items: center;
		padding: 8rpx 0;
	}

	.product-image-l {
		width: 220rpx;
		height: 220rpx;
		margin-right: 20rpx;
	}

	.product-image {
		width: 200rpx;
		height: 200rpx;
		border-radius: 10rpx;
	}

	.product-info {
		flex: 1;
	}

	.product-title {
		font-size: 24rpx;
		color: #000000;
		font-weight: 600;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.product-tag {
		font-size: 18rpx;
		background-color: #f5f5f5;
		padding: 6rpx 20rpx;
		border-radius: 20rpx;
		display: inline-block;
		margin: 10rpx 0;
	}

	.product-price-quantity {
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: nowrap;
		flex-direction: row;
		padding: 10rpx 0;
	}

	.product-price {
		font-size: 26rpx;
		color: #000000;
	}

	.order-bottom {
		padding: 40rpx 0 0 0;
		margin-top: 20rpx;
		border-top: 1px dashed #cccccc;
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: center;
	}

	.order-prire-t {
		font-size: 22rpx;
		color: #000000;
		font-weight: 800;
	}

	.order-prire-val {
		font-size: 36rpx;
		color: #dc0000;
		font-weight: 800;
	}

	.order-btn {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: center;
	}

	.order-cancel-btn {
		padding: 8rpx 30rpx;
		border: 1px solid #cccccc;
		color: #cccccc;
		font-weight: 800;
		border-radius: 30rpx;
		margin: 0 30rpx;
	}

	.order-pay-btn {
		padding: 8rpx 30rpx;
		border: 1px solid #dc0000;
		background-color: #dc0000;
		color: #ffffff;
		font-weight: 800;
		border-radius: 30rpx;
	}

	.product-quantity {
		font-size: 26rpx;
		font-weight: 800;
	}

	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 280rpx 0;
	}

	.empty-icon {
		width: 400rpx;
		height: auto;
		margin-bottom: 30rpx;
	}

	.empty-text {
		font-size: 24rpx;
		color: #999999;
	}
</style>