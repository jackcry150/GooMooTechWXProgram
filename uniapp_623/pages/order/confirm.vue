<template>
	<view class="confirm-page">
		<view v-if="loading" class="loading-wrap">
			<view class="loading-dot"></view>
			<text class="loading-text">加载中...</text>
		</view>

		<view v-else-if="order.id" class="page-body">
			<view class="hero-card">
				<text class="hero-title">商品已到货</text>
				<text class="hero-desc">请先核对收货信息，确认无误后我们再继续下一步。</text>
			</view>

			<view class="card">
				<view class="card-title">订单信息</view>
				<view class="info-row">
					<text class="label">订单号</text>
					<text class="value">{{ order.orderNo || '-' }}</text>
				</view>
				<view class="info-row">
					<text class="label">订单状态</text>
					<text class="value">{{ order.statusText || '-' }}</text>
				</view>
			</view>

			<view class="card">
				<view class="card-title">收货信息</view>
				<view class="field-item">
					<text class="field-label">收货人</text>
					<input class="field-input" v-model="addressForm.name" placeholder="请输入收货人" />
				</view>
				<view class="field-item">
					<text class="field-label">手机号</text>
					<input class="field-input" v-model="addressForm.phone" type="number" maxlength="11" placeholder="请输入手机号" />
				</view>
				<view class="field-item">
					<text class="field-label">省份</text>
					<input class="field-input" v-model="addressForm.province" placeholder="请输入省份" />
				</view>
				<view class="field-item">
					<text class="field-label">城市</text>
					<input class="field-input" v-model="addressForm.city" placeholder="请输入城市" />
				</view>
				<view class="field-item">
					<text class="field-label">区县</text>
					<input class="field-input" v-model="addressForm.area" placeholder="请输入区县" />
				</view>
				<view class="field-item">
					<text class="field-label">详细地址</text>
					<textarea class="field-textarea" v-model="addressForm.detail" placeholder="请输入详细地址" />
				</view>
				<view class="field-item">
					<text class="field-label">备注</text>
					<textarea class="field-textarea remark-textarea" v-model="remark" placeholder="选填，给商家补充说明"></textarea>
				</view>
			</view>

			<view class="card">
				<view class="card-title">当前阶段</view>
				<text class="stage-tip">请确认收货信息是否正确。你可以不修改直接提交，也可以修改后再确认。</text>
			</view>

			<button class="submit-btn" :disabled="submitting" @click="submitConfirm">
				{{ submitting ? '提交中...' : '确认收货信息' }}
			</button>
		</view>

		<view v-else class="empty-wrap">
			<text class="empty-text">未找到订单信息</text>
		</view>
	</view>
</template>

<script>
import { api } from '@/utils/request'

export default {
	data() {
		return {
			loading: true,
			submitting: false,
			orderId: '',
			order: {},
			remark: '',
			addressForm: {
				name: '',
				phone: '',
				province: '',
				city: '',
				area: '',
				region: '',
				detail: ''
			}
		}
	},
	computed: {
		regionText() {
			return [
				this.addressForm.province,
				this.addressForm.city,
				this.addressForm.region || this.addressForm.area
			].filter(Boolean).join(' ')
		}
	},
	onLoad(options) {
		this.orderId = options.id || ''
		this.loadOrderDetail()
	},
	onPullDownRefresh() {
		this.loadOrderDetail().finally(() => {
			uni.stopPullDownRefresh()
		})
	},
		methods: {
		validateForm() {
			if (!this.addressForm.name.trim()) {
				uni.showToast({ title: '请填写收货人', icon: 'none' })
				return false
			}
			if (!/^1\d{10}$/.test((this.addressForm.phone || '').trim())) {
				uni.showToast({ title: '请填写正确手机号', icon: 'none' })
				return false
			}
			if (!this.addressForm.detail.trim()) {
				uni.showToast({ title: '请填写详细地址', icon: 'none' })
				return false
			}
			return true
		},
		async loadOrderDetail() {
			if (!this.orderId) {
				this.loading = false
				return Promise.resolve()
			}

			this.loading = true
			try {
				const res = await api.order.detail({ id: this.orderId })
				if (res.code === 200 && res.data) {
					this.order = res.data
					const address = res.data.addressInfo || {}
					this.addressForm = {
						name: address.name || '',
						phone: address.phone || '',
						province: address.province || '',
						city: address.city || '',
						area: address.area || '',
						region: address.region || '',
						detail: address.detail || ''
					}
				} else {
					uni.showToast({ title: res.msg || '加载订单失败', icon: 'none' })
				}
			} catch (e) {
				uni.showToast({ title: '加载订单失败', icon: 'none' })
			} finally {
				this.loading = false
			}
		},
		async submitConfirm() {
			if (this.submitting || !this.validateForm()) return
			this.submitting = true
			try {
				const res = await api.order.confirmArrivalInfo({
					id: this.orderId,
					name: this.addressForm.name,
					phone: this.addressForm.phone,
					province: this.addressForm.province,
					city: this.addressForm.city,
					area: this.addressForm.area,
					detail: this.addressForm.detail,
					remark: this.remark
				})
				if (res.code === 200) {
					uni.showToast({ title: res.msg || '确认成功', icon: 'success' })
					setTimeout(() => {
						this.loadOrderDetail()
					}, 900)
				} else {
					uni.showToast({ title: res.msg || '提交失败', icon: 'none' })
				}
			} catch (e) {
				uni.showToast({ title: '提交失败', icon: 'none' })
			} finally {
				this.submitting = false
			}
		}
	}
}
</script>

<style scoped>
.confirm-page {
	min-height: 100vh;
	background: linear-gradient(180deg, #fffaf2 0%, #f7f7f7 280rpx);
}

.loading-wrap,
.empty-wrap {
	min-height: 100vh;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

.loading-dot {
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	border: 6rpx solid #f5d7a3;
	border-top-color: #f29a16;
	animation: spin 0.8s linear infinite;
}

.loading-text,
.empty-text {
	margin-top: 24rpx;
	font-size: 28rpx;
	color: #8d8d8d;
}

.page-body {
	padding: 24rpx;
}

.hero-card {
	padding: 34rpx 30rpx;
	border-radius: 24rpx;
	background: linear-gradient(135deg, #ffb227, #ff8f00);
	color: #fff;
	box-shadow: 0 16rpx 36rpx rgba(255, 153, 0, 0.22);
}

.hero-title {
	display: block;
	font-size: 40rpx;
	font-weight: 700;
	margin-bottom: 10rpx;
}

.hero-desc {
	display: block;
	font-size: 26rpx;
	line-height: 1.7;
	opacity: 0.96;
}

.card {
	margin-top: 24rpx;
	padding: 28rpx;
	border-radius: 22rpx;
	background: #fff;
	box-shadow: 0 8rpx 22rpx rgba(30, 30, 30, 0.05);
}

.card-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #222;
	margin-bottom: 22rpx;
}

.info-row {
	display: flex;
	justify-content: space-between;
	gap: 24rpx;
	padding: 14rpx 0;
	font-size: 28rpx;
}

.label {
	color: #777;
}

.value {
	color: #222;
	text-align: right;
	word-break: break-all;
}

.field-item {
	margin-bottom: 20rpx;
}

.field-item:last-child {
	margin-bottom: 0;
}

.field-label {
	display: block;
	margin-bottom: 12rpx;
	font-size: 26rpx;
	color: #666;
}

.field-input,
.field-textarea {
	width: 100%;
	box-sizing: border-box;
	padding: 0 22rpx;
	border-radius: 16rpx;
	background: #f8f8f8;
	border: 2rpx solid #efefef;
	font-size: 28rpx;
	color: #333;
}

.field-input {
	height: 84rpx;
	line-height: 84rpx;
}

.field-textarea {
	height: 160rpx;
	padding-top: 20rpx;
}

.stage-tip {
	font-size: 27rpx;
	line-height: 1.8;
	color: #8a5b11;
}

.remark-textarea {
	height: 120rpx;
}

.submit-btn {
	margin-top: 28rpx;
	height: 92rpx;
	line-height: 92rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #ffb324, #ff9100);
	color: #fff;
	font-size: 30rpx;
	font-weight: 700;
	box-shadow: 0 14rpx 28rpx rgba(255, 145, 0, 0.22);
}

@keyframes spin {
	to {
		transform: rotate(360deg);
	}
}
</style>
