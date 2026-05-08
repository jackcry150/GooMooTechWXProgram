<template>
	<view class="login-page">
		<image class="login-bg" mode="aspectFill" src="/static/image/login-bg-concept.png"></image>
		<view class="login-panel">
			<button class="login-btn" open-type="getPhoneNumber" @getphonenumber="getPhoneNumber" :disabled="!protocol">
				<view class="login-btn-inner">
					<image class="login-btn-icon" mode="aspectFit" src="/static/image/wechat-login-icon.svg"></image>
					<text>微信手机号一键登录</text>
				</view>
			</button>
			<view class="login-no" @click="goToBack()">
				<text>不登录啦，我先看看</text>
			</view>
		</view>

		<view class="login-foot">
			<checkbox-group @change="ChangeIsDefault">
				<label class="protocol-row">
					<checkbox :checked="protocol ? true : false" />
					<text class="protocol-text">已阅读并同意橘猫</text>
					<text class="main-color" @click.stop="agraeement()">用户服务协议</text>
					<text class="protocol-text">、</text>
					<text class="main-color" @click.stop="privacy()">隐私政策</text>
				</label>
			</checkbox-group>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'

	export default {
		data() {
			return {
				protocol: false,
				showPassword: false,
				rememberMe: false,
				loading: false,
				loginCode: '',
				sessionKey: '',
			}
		},
		onLoad() {
			// this.wxLogin();
		},
		methods: {
			ChangeIsDefault(e) {
				this.$set(this, 'protocol', !this.protocol);
			},
			goToBack() {
				uni.navigateBack()
			},
			agraeement() {
				uni.navigateTo({
					url: '/pages/my/agraeement'
				});
			},
			privacy() {
				uni.navigateTo({
					url: '/pages/my/privacy'
				});
			},
			// 获取用户手机号
			getPhoneNumber(e) {
				if (e.detail.errMsg == 'getPhoneNumber:ok') {
					uni.login({
						provider: 'weixin',
						success: (res) => {
							if (res.errMsg === 'login:ok') {
								this.phoneLogin(res, e)
								return
							} else {
								uni.showToast({
									title: '登录失败',
									icon: 'none'
								})
							}
						},
						fail: (err) => {
							uni.showToast({
								title: '登录失败',
								icon: 'none'
							});
							return
						}
					});

				} else {
					uni.showToast({
						title: '用户拒绝授权',
						icon: 'none'
					});
				}
			},

			async phoneLogin(l, e) {
				try {
					const params = {
						encryptedData: e.detail.encryptedData,
						iv: e.detail.iv,
						code: l.code
					}
					const response = await api.auth.phone(params)
					uni.setStorageSync('token', response.data.token)
					uni.showToast({
						title: '登录成功',
						icon: 'success'
					})
					uni.switchTab({
						url: '/pages/my/index'
					})
				} catch (error) {
					uni.showToast({
						title: '登录失败',
						icon: 'none'
					})
					return
				}
			}
		},
	}
</script>

<style lang="scss" scoped>
	.login-page {
		height: 100vh;
		position: relative;
		overflow: hidden;
	}

	.login-bg {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
	}

	.login-panel {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 280rpx;
		padding: 0 60rpx;
		box-sizing: border-box;
		z-index: 2;
	}

	.login-foot {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 100rpx;
		padding: 0 44rpx;
		box-sizing: border-box;
		z-index: 2;
		color: rgba(255, 255, 255, 0.78);
		font-size: 24rpx;
		text-align: center;
	}

	.protocol-row {
		display: flex;
		align-items: center;
		justify-content: center;
		flex-wrap: wrap;
		line-height: 1.8;
	}

	.login-btn {
		width: 100%;
		height: 110rpx;
		margin-top: 22rpx;
		padding: 0;
		background: linear-gradient(180deg, #ffa625 0%, #ff8c05 100%);
		text-align: center;
		color: #ffffff;
		border-radius: 24rpx;
		font-weight: 800;
		font-size: 32rpx;
		box-shadow: 0 12rpx 36rpx rgba(255, 144, 20, 0.26);
		display: flex;
		align-items: center;
		justify-content: center;
		border: 2rpx solid rgba(255, 220, 160, 0.24);
	}

	.login-btn::after {
		border: none;
	}

	.login-btn[disabled] {
		opacity: 0.6;
		background: linear-gradient(180deg, #ffa625 0%, #ff8c05 100%);
		color: #ffffff;
	}

	.login-btn-inner {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 20rpx;
	}

	.login-btn-icon {
		width: 48rpx;
		height: 48rpx;
		display: block;
		filter: brightness(0) invert(1);
	}

	.login-no {
		width: 100%;
		margin-top: 160rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #ffffff;
		font-size: 28rpx;
		text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.35);
	}

	.protocol-text {
		color: rgba(255, 255, 255, 0.7);
	}

	::v-deep .uni-checkbox-input {
		width: 28rpx;
		height: 28rpx;
		border-radius: 8rpx;
		margin-right: 12rpx;
		border: 2rpx solid rgba(255, 255, 255, 0.4);
		background-color: transparent;
	}

	::v-deep .uni-checkbox-input.uni-checkbox-input-checked {
		background-color: #ff920b;
		border-color: #ff920b;
	}

	::v-deep .uni-checkbox-input.uni-checkbox-input-checked::before {
		font-size: 22rpx;
		color: #ffffff;
	}

	.main-color {
		color: #ff4a1a;
	}
</style>
