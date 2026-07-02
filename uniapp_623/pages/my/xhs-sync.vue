<template>
	<view class="page">
		<view class="panel">
			<view class="header">
				<text class="title">同步小红书积分</text>
				<text class="subtitle">首次提交后需人工审核，审核通过后订单完成自动到账</text>
			</view>

			<view class="status" v-if="bound || pending || reviewStatus === 'rejected'">
				<text class="status-title">{{ statusTitle }}</text>
				<text class="status-text">首次订单：{{ firstOrderId || '-' }}</text>
				<text class="status-text" v-if="bound || pending">{{ bound ? '绑定时间' : '提交时间' }}：{{ bindTime || updateTime || '-' }}</text>
				<text class="status-text" v-if="reviewStatus === 'rejected'">审核未通过，请核对订单信息后重新提交</text>
			</view>

			<view class="form" v-if="canSubmit">
				<view class="field">
					<text class="label">小程序手机号</text>
					<input class="input" v-model="phone" type="number" placeholder="请输入当前小程序手机号" maxlength="11" />
				</view>
				<view class="field">
					<text class="label">小红书订单号</text>
					<input class="input" v-model="orderId" placeholder="请输入待发货小红书订单号" />
				</view>
				<button class="submit" :loading="submitting" :disabled="submitting" @click="submitBind">提交审核</button>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'

	export default {
		computed: {
			canSubmit() {
				return !this.bound && !this.pending
			},
			statusTitle() {
				if (this.bound) {
					return '已绑定小红书账号'
				}
				if (this.pending) {
					return '绑定审核中'
				}
				if (this.reviewStatus === 'rejected') {
					return '绑定审核未通过'
				}
				return ''
			}
		},
		data() {
			return {
				orderId: '',
				phone: '',
				bound: false,
				pending: false,
				reviewStatus: 'none',
				firstOrderId: '',
				bindTime: '',
				updateTime: '',
				submitting: false
			}
		},
		onShow() {
			this.loadStatus()
		},
		methods: {
			async loadStatus() {
				const token = uni.getStorageSync('token')
				if (!token) {
					uni.navigateTo({ url: '/pages/login/login' })
					return
				}
				try {
					const res = await api.xhs.status()
					if (res.code !== 200 || !res.data) {
						return
					}
					this.bound = !!res.data.bound
					this.pending = !!res.data.pending
					this.reviewStatus = res.data.reviewStatus || 'none'
					this.firstOrderId = res.data.firstOrderId || ''
					this.bindTime = res.data.bindTime || ''
					this.updateTime = res.data.updateTime || ''
					if (!this.phone && res.data.phone) {
						this.phone = res.data.phone
					}
				} catch (error) {
					const message = error && error.message ? error.message : '读取绑定状态失败'
					if (message.indexOf('登录') !== -1) {
						uni.navigateTo({ url: '/pages/login/login' })
						return
					}
					uni.showToast({ title: message, icon: 'none' })
				}
			},
			async submitBind() {
				if (!this.canSubmit) {
					uni.showToast({ title: this.bound ? '已绑定，无需重复提交' : '审核中，请勿重复提交', icon: 'none' })
					return
				}
				const orderId = this.orderId.trim()
				const phone = this.phone.trim()
				if (!phone || phone.length < 7) {
					uni.showToast({ title: '请填写小程序手机号', icon: 'none' })
					return
				}
				if (!orderId) {
					uni.showToast({ title: '请填写小红书订单号', icon: 'none' })
					return
				}

				this.submitting = true
				try {
					const res = await api.xhs.bind({ orderId, phone })
					uni.showToast({ title: res.msg || '已提交审核', icon: res.code === 200 ? 'success' : 'none' })
					if (res.code === 200) {
						this.orderId = ''
						this.loadStatus()
					}
				} catch (error) {
					const message = error && error.message ? error.message : '提交失败，请稍后重试'
					if (message.indexOf('登录') !== -1) {
						uni.navigateTo({ url: '/pages/login/login' })
						return
					}
					uni.showToast({ title: message, icon: 'none' })
				} finally {
					this.submitting = false
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #f8f8f8;
		padding: 28rpx;
		box-sizing: border-box;
	}

	.panel {
		background: #ffffff;
		border-radius: 24rpx;
		padding: 34rpx 28rpx;
		box-sizing: border-box;
	}

	.header {
		margin-bottom: 34rpx;
	}

	.title {
		display: block;
		font-size: 38rpx;
		font-weight: 800;
		color: #111111;
	}

	.subtitle {
		display: block;
		margin-top: 14rpx;
		font-size: 26rpx;
		line-height: 1.5;
		color: #666666;
	}

	.status {
		margin-bottom: 30rpx;
		padding: 24rpx;
		border-radius: 18rpx;
		background: #fff7ec;
	}

	.status-title,
	.status-text {
		display: block;
	}

	.status-title {
		font-size: 28rpx;
		font-weight: 700;
		color: #c86f00;
		margin-bottom: 10rpx;
	}

	.status-text {
		font-size: 24rpx;
		color: #7a6042;
		line-height: 1.6;
	}

	.field {
		margin-bottom: 26rpx;
	}

	.label {
		display: block;
		font-size: 26rpx;
		color: #333333;
		margin-bottom: 12rpx;
	}

	.input {
		height: 88rpx;
		border-radius: 16rpx;
		background: #f5f5f5;
		padding: 0 22rpx;
		box-sizing: border-box;
		font-size: 28rpx;
		color: #111111;
	}

	.submit {
		margin-top: 16rpx;
		height: 88rpx;
		line-height: 88rpx;
		border-radius: 44rpx;
		background: #111111;
		color: #ffffff;
		font-size: 30rpx;
		font-weight: 700;
	}
</style>
