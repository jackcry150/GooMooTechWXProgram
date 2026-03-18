<template>
	<view class="order-detail">
		<!-- 加载中 -->
		<view v-if="loading && !order.id" class="loading-wrap">
			<view class="loading-spin"></view>
			<text class="loading-text">加载中...</text>
		</view>

		<template v-else>
			<!-- 订单状态卡片 -->
			<view class="status-card" :style="{ background: statusBgColor }">
				<view class="status-main">
					<text class="status-text">{{ order.statusText || '未知状态' }}</text>
					<text class="status-desc" v-if="statusMessage">{{ statusMessage }}</text>
				</view>
			</view>

			<!-- 收货地址 -->
			<view class="card address-card" v-if="order.addressInfo && order.addressInfo.name">
				<view class="address-body">
					<view class="address-row">
						<text class="address-name">{{ order.addressInfo.name }}</text>
						<text class="address-phone">{{ order.addressInfo.phone }}</text>
					</view>
					<view class="address-detail">
						{{ order.addressInfo.province }}{{ order.addressInfo.city }}{{ order.addressInfo.region || order.addressInfo.area }}{{ order.addressInfo.detail }}
					</view>
				</view>
			</view>
			<view class="card address-card empty" v-else>
				<text class="empty-tip">暂无收货地址</text>
			</view>

			<!-- 商品列表 -->
			<view class="card product-card">
				<view class="card-title">商品信息</view>
				<view v-if="productList.length" class="product-list">
					<view class="product-item" v-for="(item, index) in productList" :key="index" @click="goProduct(item.productId)">
						<image class="product-img" :src="item.image" mode="aspectFill" />
						<view class="product-body">
							<view class="product-name">{{ item.subtitle || '' }}{{ item.title || item.name }}</view>
							<view class="product-meta">
								<text class="product-version" v-if="item.version">{{ item.version }}</text>
								<text class="product-qty">×{{ item.quantity }}</text>
							</view>
							<view class="product-price">¥{{ item.price }}</view>
						</view>
					</view>
				</view>
				<view v-else class="empty-tip">暂无商品信息</view>
				<view class="product-total" v-if="productList.length">
					<text class="total-label">合计</text>
					<text class="total-price">¥{{ order.totalPrice != null ? order.totalPrice : '0.00' }}</text>
				</view>
			</view>

			<!-- 预售信息 -->
			<view class="card presale-card" v-if="order.isPresale">
				<view class="card-title">预售订单</view>
				<view class="presale-item" v-if="order.depositAmount > 0">
					<view class="presale-row">
						<text class="presale-label">定金</text>
						<text class="presale-amount">¥{{ order.depositAmount }}</text>
					</view>
					<view class="presale-meta">
						<text :class="['tag', order.depositPaid == 1 ? 'tag-success' : 'tag-warn']">
							{{ order.depositPaid == 1 ? '已支付' : '未支付' }}
						</text>
						<text class="presale-time" v-if="order.depositPayTime">{{ order.depositPayTime }}</text>
					</view>
				</view>
				<view class="presale-item" v-if="order.balanceAmount > 0">
					<view class="presale-row">
						<text class="presale-label">尾款</text>
						<text class="presale-amount">¥{{ order.balanceAmount }}</text>
					</view>
					<view class="presale-meta">
						<text :class="['tag', order.balancePaid == 1 ? 'tag-success' : 'tag-warn']">
							{{ order.balancePaid == 1 ? '已支付' : '未支付' }}
						</text>
						<text class="presale-time" v-if="order.balancePayTime">{{ order.balancePayTime }}</text>
						<text class="presale-time" v-if="order.balanceDueTime && !order.balancePaid">尾款截止 {{ order.balanceDueTime }}</text>
						<text class="countdown" v-if="order.balanceDueTimeStamp > 0 && !order.balancePaid && balanceCountdown">
							{{ balanceCountdown }}
						</text>
					</view>
				</view>
				<view class="presale-total">
					<text class="total-label">订单总额</text>
					<text class="total-amount">¥{{ order.totalPrice != null ? order.totalPrice : '0.00' }}</text>
				</view>
			</view>

			<!-- 订单信息 -->
			<view class="card info-card">
				<view class="card-title">订单信息</view>
				<view class="info-row">
					<text class="info-label">订单编号</text>
					<view class="info-value-wrap">
						<text class="info-value">{{ order.orderNo }}</text>
						<text class="copy-link" @click="copyOrderNumber">复制</text>
					</view>
				</view>
				<view class="info-row">
					<text class="info-label">创建时间</text>
					<text class="info-value">{{ order.createDate }}</text>
				</view>
				<view class="info-row" v-if="order.payDate">
					<text class="info-label">支付时间</text>
					<text class="info-value">{{ order.payDate }}</text>
				</view>
				<view class="info-row">
					<text class="info-label">支付方式</text>
					<text class="info-value">{{ order.paymentMethod || '微信支付' }}</text>
				</view>
				<view class="info-row" v-if="remarkText">
					<text class="info-label">备注</text>
					<text class="info-value">{{ remarkText }}</text>
				</view>
			</view>

			<!-- 退款信息 -->
			<view class="card refund-card" v-if="order.refundStatus > 0">
				<view class="card-title">退款信息</view>
				<view class="info-row">
					<text class="info-label">退款状态</text>
					<text class="info-value highlight">{{ order.refundStatusText }}</text>
				</view>
				<view class="info-row" v-if="order.refundReason">
					<text class="info-label">退款原因</text>
					<text class="info-value">{{ order.refundReason }}</text>
				</view>
				<view class="info-row" v-if="order.refundAmount > 0">
					<text class="info-label">退款金额</text>
					<text class="info-value price">¥{{ order.refundAmount }}</text>
				</view>
				<view class="info-row" v-if="order.refundRemark">
					<text class="info-label">退款备注</text>
					<text class="info-value">{{ order.refundRemark }}</text>
				</view>
				<view class="info-row" v-if="order.refundApplyTime">
					<text class="info-label">申请时间</text>
					<text class="info-value">{{ order.refundApplyTime }}</text>
				</view>
				<view class="info-row" v-if="order.refundTime">
					<text class="info-label">处理时间</text>
					<text class="info-value">{{ order.refundTime }}</text>
				</view>
			</view>

			<!-- 物流信息 -->
			<view class="card logistics-card" v-if="order.freightName">
				<view class="card-title">物流信息</view>
				<view class="info-row">
					<text class="info-label">物流公司</text>
					<text class="info-value">{{ order.freightName }}</text>
				</view>
				<view class="info-row">
					<text class="info-label">物流单号</text>
					<view class="info-value-wrap">
						<text class="info-value">{{ order.freightNo }}</text>
						<text class="copy-link" @click="copyFreightNo">复制</text>
					</view>
				</view>
				<view class="info-row" v-if="order.freightTime">
					<text class="info-label">发货时间</text>
					<text class="info-value">{{ order.freightTime }}</text>
				</view>
			</view>

			<view class="bottom-placeholder"></view>
		</template>

		<!-- 底部操作栏 -->
		<view class="bottom-bar" v-if="!loading && order.id">
			<button class="btn btn-secondary" @click="goToAiCustomer">售后咨询</button>
			<button class="btn btn-secondary" v-if="showCancelBtn" @click="handleCancel">取消订单</button>
			<button class="btn btn-secondary" v-if="showDeleteBtn" @click="handleDelete">删除订单</button>
			<button class="btn btn-primary" v-if="order.isPresale && order.canPayDeposit" @click="handlePayDeposit">
				支付定金 ¥{{ order.depositAmount != null ? order.depositAmount : '0' }}
			</button>
			<button class="btn btn-primary" v-if="order.isPresale && order.canPayBalance" @click="handlePayBalance">
				支付尾款 ¥{{ order.balanceAmount != null ? order.balanceAmount : '0' }}
			</button>
			<button class="btn btn-primary" v-if="showPayBtn" @click="handlePay">立即支付</button>
			<button class="btn btn-primary" v-if="showConfirmReceiptBtn" @click="handleConfirmReceipt">确认收货</button>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request'

	const STATUS_COLORS = {
		'待支付': 'linear-gradient(135deg, #e53935, #ef5350)',
		'支付失败': 'linear-gradient(135deg, #78909c, #90a4ae)',
		'待发货': 'linear-gradient(135deg, #00897b, #26a69a)',
		'待收货': 'linear-gradient(135deg, #00897b, #26a69a)',
		'订单完成': 'linear-gradient(135deg, #00897b, #26a69a)',
		'申请退款': 'linear-gradient(135deg, #f57c00, #ffb74d)',
		'同意退款': 'linear-gradient(135deg, #00897b, #26a69a)',
		'拒绝退款': 'linear-gradient(135deg, #c62828, #e57373)',
		'已付定金待付尾款': 'linear-gradient(135deg, #f57c00, #ffb74d)',
		'已预定': 'linear-gradient(135deg, #f57c00, #ffb74d)'
	}

	const STATUS_MESSAGES = {
		'待支付': '请尽快完成支付',
		'待发货': '商家正在准备商品',
		'待收货': '商品已发货，请注意查收',
		'订单完成': '感谢您的购买',
		'申请退款': '退款申请已提交，等待处理',
		'已付定金待付尾款': '请及时支付尾款',
		'已预定': '请先支付定金'
	}

	export default {
		name: 'OrderDetail',
		data() {
			return {
				orderId: '',
				loading: true,
				order: {},
				balanceCountdown: '',
				countdownTimer: null
			}
		},
		computed: {
			productList() {
				return this.order.productList || []
			},
			statusBgColor() {
				return STATUS_COLORS[this.order.statusText] || 'linear-gradient(135deg, #546e7a, #78909c)'
			},
			statusMessage() {
				return STATUS_MESSAGES[this.order.statusText] || ''
			},
			remarkText() {
				const r = this.order.remarks
				if (!r || (typeof r === 'string' && !r.trim())) return ''
				return r.trim() === '无' ? '' : r
			},
			showPayBtn() {
				const s = this.order.status
				return !this.order.isPresale && (s === 1 || s === 8)
			},
			showCancelBtn() {
				const s = this.order.status
				return (s === 1 || s === 8) || (this.order.isPresale && this.order.canPayDeposit)
			},
			showRefundBtn() {
				return false // 支付成功后不显示申请退款
			},
			showConfirmReceiptBtn() {
				return this.order.status === 6
			},
			showDeleteBtn() {
				return this.order.status === 4
			}
		},
		onLoad(options) {
			if (options.id) {
				this.orderId = options.id
				this.loadOrderDetail(options.id)
			}
		},
		onPullDownRefresh() {
			this.loadOrderDetail(this.orderId).finally(() => {
				uni.stopPullDownRefresh()
			})
		},
		onUnload() {
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer)
				this.countdownTimer = null
			}
		},
		methods: {
			async loadOrderDetail(orderId) {
				if (!orderId) return Promise.resolve()
				this.loading = true
				try {
					const res = await api.order.detail({ id: orderId })
					if (res.code === 200 && res.data) {
						this.order = res.data
						if (this.order.balanceDueTimeStamp > 0 && this.order.balancePaid == 0) {
							this.startBalanceCountdown()
						}
					} else {
						uni.showToast({ title: res.msg || '加载失败', icon: 'none' })
					}
				} catch (e) {
					uni.showToast({ title: '加载失败', icon: 'none' })
				} finally {
					this.loading = false
				}
			},
			startBalanceCountdown() {
				if (this.countdownTimer) clearInterval(this.countdownTimer)
				const tick = () => {
					if (!this.order.balanceDueTimeStamp || this.order.balancePaid == 1) {
						this.balanceCountdown = ''
						if (this.countdownTimer) {
							clearInterval(this.countdownTimer)
							this.countdownTimer = null
						}
						return
					}
					const now = Math.floor(Date.now() / 1000)
					const rest = this.order.balanceDueTimeStamp - now
					if (rest <= 0) {
						this.balanceCountdown = '已过期'
						if (this.countdownTimer) {
							clearInterval(this.countdownTimer)
							this.countdownTimer = null
						}
						return
					}
					const d = Math.floor(rest / 86400)
					const h = Math.floor((rest % 86400) / 3600)
					const m = Math.floor((rest % 3600) / 60)
					const s = rest % 60
					const pad = n => (n < 10 ? '0' + n : n)
					this.balanceCountdown = d > 0 ?
						`${d}天 ${pad(h)}:${pad(m)}:${pad(s)}` :
						`${pad(h)}:${pad(m)}:${pad(s)}`
				}
				tick()
				this.countdownTimer = setInterval(tick, 1000)
			},
			copyOrderNumber() {
				uni.setClipboardData({
					data: this.order.orderNo,
					success: () => uni.showToast({ title: '订单号已复制', icon: 'success' })
				})
			},
			copyFreightNo() {
				if (!this.order.freightNo) return
				uni.setClipboardData({
					data: this.order.freightNo,
					success: () => uni.showToast({ title: '物流单号已复制', icon: 'success' })
				})
			},
			goToAiCustomer() {
				uni.navigateTo({
					url: `/pages/ai/customer?scene=aftersale&sourcePage=order&orderId=${this.order.id || this.orderId || ''}`
				})
			},
			goProduct(id) {
				if (!id) return
				uni.navigateTo({ url: `/pages/product/detail?id=${id}` })
			},
			getAppId() {
				let appid = ''
				// #ifdef MP-WEIXIN
				try { appid = uni.getAccountInfoSync().miniProgram.appId } catch (e) {}
				// #endif
				return appid
			},
			requestPayment(paymentData, payType) {
				// #ifdef MP-WEIXIN
				uni.requestPayment({
					provider: 'wxpay',
					timeStamp: paymentData.timeStamp || '',
					nonceStr: paymentData.nonceStr || '',
					package: paymentData.package || '',
					signType: paymentData.signType || 'RSA',
					paySign: paymentData.paySign || '',
					success: () => {
						const msg = payType === 'deposit' ? '定金支付成功' : payType === 'balance' ? '尾款支付成功' : '支付成功'
						uni.showToast({ title: msg, icon: 'success' })
						setTimeout(() => this.loadOrderDetail(this.orderId), 1500)
					},
					fail: (err) => {
						const msg = err.errMsg && err.errMsg.indexOf('cancel') !== -1 ? '支付已取消' : '支付失败，请重试'
						uni.showToast({ title: msg, icon: 'none' })
					}
				})
				// #endif
				// #ifndef MP-WEIXIN
				uni.showToast({ title: '请在微信小程序中支付', icon: 'none' })
				// #endif
			},
			async handlePay() {
				uni.showLoading({ title: '获取支付参数...' })
				try {
					const res = await api.order.pay({ id: this.order.id, payType: 'full', appid: this.getAppId() })
					uni.hideLoading()
					if (res.code === 200 && res.data && res.data.payment) {
						this.requestPayment(res.data.payment, res.data.payType || 'full')
					} else {
						uni.showToast({ title: res.msg || '获取支付参数失败', icon: 'none' })
					}
				} catch (e) {
					uni.hideLoading()
					uni.showToast({ title: '支付失败', icon: 'none' })
				}
			},
			async handlePayDeposit() {
				uni.showModal({
					title: '支付定金',
					content: `确认支付定金 ¥${this.order.depositAmount} 吗？`,
					success: async (res) => {
						if (!res.confirm) return
						uni.showLoading({ title: '获取支付参数...' })
						try {
							const result = await api.order.pay({ id: this.order.id, payType: 'deposit', appid: this.getAppId() })
							uni.hideLoading()
							if (result.code === 200 && result.data && result.data.payment) {
								this.requestPayment(result.data.payment, 'deposit')
							} else {
								uni.showToast({ title: result.msg || '获取支付参数失败', icon: 'none' })
							}
						} catch (e) {
							uni.hideLoading()
							uni.showToast({ title: '支付失败', icon: 'none' })
						}
					}
				})
			},
			async handlePayBalance() {
				uni.showModal({
					title: '支付尾款',
					content: `确认支付尾款 ¥${this.order.balanceAmount} 吗？`,
					success: async (res) => {
						if (!res.confirm) return
						uni.showLoading({ title: '获取支付参数...' })
						try {
							const result = await api.order.pay({ id: this.order.id, payType: 'balance', appid: this.getAppId() })
							uni.hideLoading()
							if (result.code === 200 && result.data && result.data.payment) {
								this.requestPayment(result.data.payment, 'balance')
							} else {
								uni.showToast({ title: result.msg || '获取支付参数失败', icon: 'none' })
							}
						} catch (e) {
							uni.hideLoading()
							uni.showToast({ title: '支付失败', icon: 'none' })
						}
					}
				})
			},
			async handleCancel() {
				uni.showModal({
					title: '提示',
					content: '确定要取消该订单吗？',
					success: async (res) => {
						if (!res.confirm) return
						uni.showLoading({ title: '处理中...' })
						try {
							const result = await api.order.cancel({ id: this.order.id })
							uni.hideLoading()
							if (result.code === 200) {
								uni.showToast({ title: result.msg || '取消成功', icon: 'success' })
								setTimeout(() => this.loadOrderDetail(this.orderId), 1500)
							} else {
								uni.showToast({ title: result.msg || '取消失败', icon: 'none' })
							}
						} catch (e) {
							uni.hideLoading()
							uni.showToast({ title: '取消失败', icon: 'none' })
						}
					}
				})
			},
			handleRefund() {
				uni.navigateTo({ url: `/pages/order/refund?id=${this.order.id}` })
			},
			async handleDelete() {
				uni.showModal({
					title: '提示',
					content: '确定要删除该订单吗？删除后将无法恢复',
					success: async (res) => {
						if (!res.confirm) return
						uni.showLoading({ title: '删除中...' })
						try {
							const result = await api.order.delete({ id: this.order.id })
							uni.hideLoading()
							if (result.code === 200) {
								uni.showToast({ title: result.msg || '删除成功', icon: 'success' })
								setTimeout(() => uni.navigateBack(), 1500)
							} else {
								uni.showToast({ title: result.msg || '删除失败', icon: 'none' })
							}
						} catch (e) {
							uni.hideLoading()
							uni.showToast({ title: '删除失败', icon: 'none' })
						}
					}
				})
			},
			async handleConfirmReceipt() {
				uni.showModal({
					title: '确认收货',
					content: '确认已收到商品？确认后将获得蜗壳奖励',
					success: async (res) => {
						if (!res.confirm) return
						uni.showLoading({ title: '处理中...' })
						try {
							const result = await api.order.confirmReceipt({ id: this.order.id })
							uni.hideLoading()
							if (result.code === 200) {
								uni.showToast({ title: result.msg || '确认收货成功', icon: 'success' })
								setTimeout(() => this.loadOrderDetail(this.orderId), 1500)
							} else {
								uni.showToast({ title: result.msg || '操作失败', icon: 'none' })
							}
						} catch (e) {
							uni.hideLoading()
							uni.showToast({ title: '操作失败', icon: 'none' })
						}
					}
				})
			}
		}
	}
</script>

<style scoped>
	.order-detail {
		background: #f2f2f2;
		padding-bottom: 180rpx;
	}

	.loading-wrap {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 120rpx 0;
	}

	.loading-spin {
		width: 64rpx;
		height: 64rpx;
		border: 4rpx solid #eee;
		border-top-color: #e53935;
		border-radius: 50%;
		animation: spin 0.8s linear infinite;
	}

	@keyframes spin {
		to {
			transform: rotate(360deg);
		}
	}

	.loading-text {
		margin-top: 24rpx;
		font-size: 28rpx;
		color: #999;
	}

	.status-card {
		margin: 24rpx;
		padding: 36rpx 32rpx;
		border-radius: 16rpx;
		color: #fff;
		box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
	}

	.status-main {
		display: flex;
		flex-direction: column;
	}

	.status-text {
		font-size: 40rpx;
		font-weight: 700;
		margin-bottom: 8rpx;
	}

	.status-desc {
		font-size: 26rpx;
		opacity: 0.9;
	}

	.card {
		margin: 0 24rpx 24rpx;
		padding: 28rpx 32rpx;
		background: #fff;
		border-radius: 16rpx;
		box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
	}

	.card-title {
		font-size: 30rpx;
		font-weight: 700;
		color: #333;
		margin-bottom: 24rpx;
		padding-bottom: 16rpx;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.address-card {
		display: flex;
		flex-direction: column;
	}

	.address-card.empty {
		align-items: center;
		padding: 48rpx;
	}

	.empty-tip {
		font-size: 28rpx;
		color: #999;
	}

	.address-body {
		flex: 1;
	}

	.address-row {
		display: flex;
		align-items: center;
		margin-bottom: 12rpx;
	}

	.address-name {
		font-size: 32rpx;
		font-weight: 700;
		color: #333;
		margin-right: 20rpx;
	}

	.address-phone {
		font-size: 28rpx;
		color: #666;
	}

	.address-detail {
		font-size: 28rpx;
		color: #666;
		line-height: 1.5;
	}

	.product-card .product-list {
		margin: 0 -32rpx;
	}

	.product-item {
		display: flex;
		align-items: center;
		padding: 24rpx 32rpx;
		border-bottom: 1rpx solid #f5f5f5;
	}

	.product-item:last-of-type {
		border-bottom: none;
	}

	.product-img {
		width: 140rpx;
		height: 140rpx;
		border-radius: 12rpx;
		background: #f5f5f5;
		margin-right: 24rpx;
		flex-shrink: 0;
	}

	.product-body {
		flex: 1;
		min-width: 0;
	}

	.product-name {
		font-size: 28rpx;
		font-weight: 600;
		color: #333;
		margin-bottom: 8rpx;
		line-height: 1.4;
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
	}

	.product-meta {
		display: flex;
		align-items: center;
		gap: 16rpx;
		margin-bottom: 8rpx;
	}

	.product-version {
		font-size: 24rpx;
		color: #999;
	}

	.product-qty {
		font-size: 26rpx;
		color: #666;
	}

	.product-price {
		font-size: 30rpx;
		font-weight: 700;
		color: #e53935;
	}
	
	.product-total {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		margin-top: 20rpx;
		padding-top: 20rpx;
		border-top: 1rpx solid #f0f0f0;
	}

	.product-total .total-label {
		font-size: 28rpx;
		color: #666;
		margin-right: 12rpx;
	}

	.product-total .total-price {
		font-size: 34rpx;
		font-weight: 700;
		color: #e53935;
	}

	.presale-item {
		margin-bottom: 24rpx;
		padding-bottom: 24rpx;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.presale-item:last-of-type {
		margin-bottom: 0;
		padding-bottom: 0;
		border-bottom: none;
	}

	.presale-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 12rpx;
	}

	.presale-label {
		font-size: 28rpx;
		color: #333;
	}

	.presale-amount {
		font-size: 30rpx;
		font-weight: 700;
		color: #e53935;
	}

	.presale-meta {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 12rpx;
	}

	.tag {
		font-size: 22rpx;
		padding: 4rpx 12rpx;
		border-radius: 6rpx;
	}

	.tag-success {
		background: #e8f5e9;
		color: #2e7d32;
	}

	.tag-warn {
		background: #fff3e0;
		color: #e65100;
	}

	.presale-time {
		font-size: 24rpx;
		color: #999;
	}

	.countdown {
		font-size: 26rpx;
		font-weight: 600;
		color: #e53935;
	}

	.presale-total {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 20rpx;
		padding-top: 20rpx;
		border-top: 1rpx solid #f0f0f0;
	}

	.presale-total .total-label {
		font-size: 28rpx;
		color: #333;
	}

	.presale-total .total-amount {
		font-size: 32rpx;
		font-weight: 700;
		color: #e53935;
	}

	.info-row {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		margin-bottom: 20rpx;
		font-size: 28rpx;
	}

	.info-row:last-child {
		margin-bottom: 0;
	}

	.info-label {
		color: #666;
		width: 160rpx;
		flex-shrink: 0;
	}

	.info-value {
		color: #333;
		flex: 1;
		text-align: right;
		word-break: break-all;
	}

	.info-value-wrap {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		flex: 1;
	}

	.copy-link {
		color: #e53935;
		margin-left: 16rpx;
		flex-shrink: 0;
	}

	.info-value.highlight {
		color: #f57c00;
		font-weight: 600;
	}

	.info-value.price {
		color: #e53935;
		font-weight: 600;
	}

	.bottom-placeholder {
		height: 140rpx;
	}

	.bottom-bar {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 20rpx;
		padding: 20rpx 24rpx;
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
		background: #fff;
		border-top: 1rpx solid #eee;
		box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.06);
	}

	.btn {
		padding: 0 50rpx;
		border-radius: 50rpx;
		font-size: 26rpx;
		font-weight: bold;
		border: none;
	}

	.btn-primary {
		background: linear-gradient(135deg, #e53935, #ef5350);
		color: #fff;
	}

	.btn-secondary {
		background: #fff;
		color: #666;
		border: 1rpx solid #ddd;
	}
</style>
