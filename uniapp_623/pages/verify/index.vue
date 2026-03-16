<template>
	<view class="verify">
		<view class="verify-top">
			<image src="/static/image/verify_top_cn_m.png" class="v-img" mode="widthFix"></image>
		</view>
		<view class="verify-from">
			<view class="verify-number">
				<button class="uni-button" size="default" type="default" @click="showVerifyFrom">填写表单</button>
			</view>
		</view>
	</view>

	<view class="join_group">
		<image src="/static/image/join_group.png" @click="goToGroup" class="v-img" mode="widthFix"></image>
	</view>

	<view class="cart-popup" v-if="showFrom">
		<!-- 遮罩层 -->
		<view class="mask"></view>

		<!-- 弹窗内容 -->
		<view class="popup-content-deduct">
			<view class="verify-from-input"><input id="uni-input-type-text" class="uni-input" v-model="from.name" type="text" placeholder="请输入您的姓名" /></view>
			<view class="verify-from-input"><input id="uni-input-type-text" class="uni-input" v-model="from.phone" type="text" placeholder="请输入您的手机号" /></view>
			<view><button class="verify-from-but" @click="verifyFrom">提交</button></view>
		</view>
	</view>


</template>

<script>
	import { api } from '@/utils/request.js'

	export default {
		data() {
			return {
				showFrom: false,
				from: {
					name: '',
					phone: '',
					address: ''
				}
			}
		},

		onLoad() {
			this.loadInfo()
		},

		methods: {
			loadInfo() {},

			showVerifyFrom() {
				this.showFrom = true
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
	.verify {
		background-color: #000000;
		min-height: 100vh;
	}

	.verify-top {
		/* margin-top: var(--status-bar-height); */
		width: 100%;
	}

	.verify-top .v-img,
	.join_group .v-img {
		width: 100%;
		height: auto;
	}

	.verify-from {
		padding: 0 80rpx;
	}

	.verify-number .uni-input {
		background-color: #ffffff;
		width: 100%;
		text-align: center;
		padding: 20rpx 0;
		border-radius: 20rpx;
	}

	.verify-number .uni-button {
		background-color: #dc0000;
		text-align: center;
		color: #ffffff;
		border-radius: 20rpx;
		margin-top: 40rpx;
	}


	.cart-popup {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 999;
	}

	.cart-popup .mask {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.5);
	}

	.cart-popup .popup-content-deduct {
		position: absolute;
		left: 10%;
		right: 10%;
		top: 36%;
		background-color: #ffffff;
		border-radius: 20rpx;
		padding: 80rpx 40rpx;
		max-height: 80vh;
		text-align: center;
	}

	.verify-from-input {
		padding: 20rpx 0;
		border: 1px solid #cccccc;
		border-radius: 10rpx;
		margin: 20rpx 0;
	}

	.verify-from-but {
		background-color: #dc0000;
		color: #ffffff;
	}

	.join_group {
		position: fixed;
		z-index: 100;
		right: 0;
		bottom: 10%;
		width: 200rpx;
	}
</style>