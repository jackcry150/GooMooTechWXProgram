<template>
	<view class="login-page">
		<view class="login-top">
			<view class="logo">
				<image class="logo-img" mode="widthFix" src="/static/image/logo.png"></image>
			</view>

			<button class="login-btn" open-type="getPhoneNumber" @getphonenumber="getPhoneNumber" :disabled="!protocol">
				<text>手机号快捷登录</text>
			</button>
			<view class="login-no" @click="goToBack()">
				<text>不登录啦，我先看看</text>
			</view>
		</view>
		<view class="login-foot">
			<checkbox-group @change="ChangeIsDefault">
				<checkbox :checked="protocol ? true : false" />
				已阅读并同意蜗之壳
				<text class="main-color" @click="agraeement()">用户服务协议</text>
				、
				<text class="main-color" @click="privacy()">隐私条款</text>
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
		background: #000000;

	}

	.login-top {
		width: 100%;
		height: 86vh;
		display: flex;
		flex-direction: column;
		flex-wrap: nowrap;
		align-items: center;
		justify-content: center;
	}

	.logo {
		width: 20%;
		padding-bottom: 30rpx;

		.logo-img {
			width: 100%;
			height: auto;
		}
	}

	.login-btn {
		width: 80%;
		padding: 20rpx 0;
		margin: 20rpx auto;
		background-color: #dc0000;
		text-align: center;
		color: #ffffff;
		border-radius: 10rpx;
		font-weight: 800;
	}

	.login-no {
		width: 80%;
		padding: 20rpx 0;
		margin: 20rpx auto;
		text-align: center;
		color: #ffffff;
		font-size: 26rpx;
	}

	.login-foot {
		margin-top: 40rpx;
		color: #ffffff;
		font-size: 24rpx;
		text-align: center;
		bottom: 20rpx;

		::v-deep .uni-checkbox-input {
			width: 20rpx;
			height: 20rpx;
			border-radius: 50%;
			margin-right: 0;
			border: 2px solid #ffffff;
			background-color: #000000;
		}

		.main-color {
			color: #dc0000;
		}
	}
</style>