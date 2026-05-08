<template>
	<view class="verify-page">
		<view class="verify-shell" :style="{ paddingTop: `${statusBarHeight + 12}px` }">
			<view class="brand-head">
				<view class="brand-lockup">
					<view class="brand-icon-wrap">
						<image class="brand-icon" src="/static/image/logo1.png" mode="aspectFit"></image>
					</view>
					<view class="brand-copy">
						<text class="brand-en">GOOMOO PLAY</text>
						<text class="brand-cn">橘猫智酷</text>
					</view>
				</view>
			</view>

			<view class="verify-card">
				<image class="card-bg" src="/static/image/verify-main-card-bg.png" mode="scaleToFill"></image>
				<button class="verify-action" @click="showVerifyFrom">
					<text>开始校验</text>
					<text class="verify-action-arrow">›</text>
				</button>
			</view>

			<view class="group-card" @click="goToGroup">
				<image class="card-bg" src="/static/image/verify-group-card-bg.png" mode="scaleToFill"></image>
			</view>
		</view>

		<view class="cart-popup" v-if="showFrom">
			<view class="mask" @click="closeVerifyFrom"></view>
			<view class="popup-content-deduct">
				<view class="popup-title">填写校验信息</view>
				<view class="verify-from-input">
					<input class="uni-input" v-model="from.name" type="text" placeholder="请输入您的姓名" />
				</view>
				<view class="verify-from-input">
					<input class="uni-input" v-model="from.phone" type="text" placeholder="请输入您的手机号" />
				</view>
				<view class="popup-actions">
					<button class="popup-cancel" @click="closeVerifyFrom">取消</button>
					<button class="verify-from-but" @click="verifyFrom">提交</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			statusBarHeight: 44,
			showFrom: false,
			from: {
				name: '',
				phone: '',
				address: ''
			}
		}
	},

	onLoad() {
		const systemInfo = uni.getSystemInfoSync ? uni.getSystemInfoSync() : {}
		this.statusBarHeight = systemInfo.statusBarHeight || 22
	},

	methods: {
		showVerifyFrom() {
			this.showFrom = true
		},
		closeVerifyFrom() {
			this.showFrom = false
		},
		verifyFrom() {
			uni.showToast({
				title: '提交成功',
				icon: 'success'
			})
			this.showFrom = false
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
	.verify-page {
		min-height: 100vh;
		background:
			radial-gradient(circle at 14% 0%, rgba(255, 203, 125, 0.14), transparent 22%),
			radial-gradient(circle at 100% 10%, rgba(255, 223, 177, 0.22), transparent 24%),
			linear-gradient(180deg, #fffdfa 0%, #fff9f2 100%);
	}

	.verify-shell {
		padding: 0 24rpx 72rpx;
		box-sizing: border-box;
	}

	.brand-head {
		padding: 12rpx 10rpx 28rpx;
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

	.verify-card,
	.group-card {
		position: relative;
		overflow: hidden;
		background: #ffffff;
		box-shadow: 0 20rpx 54rpx rgba(237, 227, 214, 0.42);
	}

	.verify-card {
		min-height: 624rpx;
		border-radius: 34rpx;
		padding: 58rpx 40rpx 40rpx;
		box-sizing: border-box;
	}

	.group-card {
		margin-top: 34rpx;
		min-height: 214rpx;
		border-radius: 32rpx;
		padding: 44rpx 40rpx;
		box-sizing: border-box;
	}

	.card-bg {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
	}

		.verify-action {
			position: relative;
			z-index: 1;
		}

		.verify-action {
			position: absolute;
			left: 40rpx;
			right: 40rpx;
			bottom: 40rpx;
			height: 108rpx;
			border-radius: 30rpx;
		background: linear-gradient(90deg, #ff9410 0%, #ff8510 100%);
		color: #ffffff;
		font-size: 34rpx;
		font-weight: 800;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 20rpx;
		box-shadow: 0 16rpx 34rpx rgba(255, 143, 16, 0.24);
		border: none;
	}

	.verify-action::after {
		border: none;
	}

		.verify-action-arrow {
			width: 48rpx;
			height: 48rpx;
			border-radius: 50%;
		border: 4rpx solid rgba(255, 255, 255, 0.9);
		display: inline-flex;
		align-items: center;
		justify-content: center;
			font-size: 34rpx;
			line-height: 1;
		}

	.cart-popup {
		position: fixed;
		inset: 0;
		z-index: 999;
	}

	.mask {
		position: absolute;
		inset: 0;
		background-color: rgba(0, 0, 0, 0.45);
	}

	.popup-content-deduct {
		position: absolute;
		left: 48rpx;
		right: 48rpx;
		top: 30%;
		background-color: #ffffff;
		border-radius: 28rpx;
		padding: 48rpx 34rpx 34rpx;
		text-align: center;
		box-shadow: 0 24rpx 54rpx rgba(0, 0, 0, 0.16);
	}

	.popup-title {
		font-size: 34rpx;
		font-weight: 800;
		color: #181818;
		margin-bottom: 14rpx;
	}

	.verify-from-input {
		margin-top: 20rpx;
		padding: 22rpx 24rpx;
		border: 2rpx solid #ece2d6;
		border-radius: 18rpx;
		background: #fffdf9;
	}

	.uni-input {
		font-size: 28rpx;
		color: #333333;
	}

	.popup-actions {
		display: flex;
		gap: 18rpx;
		margin-top: 28rpx;
	}

	.popup-cancel,
	.verify-from-but {
		flex: 1;
		height: 88rpx;
		border-radius: 20rpx;
		font-size: 28rpx;
		font-weight: 700;
		border: none;
	}

	.popup-cancel {
		background: #f4efe8;
		color: #746b61;
	}

	.verify-from-but {
		background: linear-gradient(90deg, #ff9512 0%, #ff8810 100%);
		color: #ffffff;
	}

	.popup-cancel::after,
	.verify-from-but::after {
		border: none;
	}
</style>
