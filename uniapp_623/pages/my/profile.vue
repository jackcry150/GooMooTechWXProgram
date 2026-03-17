<template>
	<view class="profile">
		<view class="avatar">
			<button class="avatar-btn" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
				<image :src="(userInfo && userInfo.avatar) ? userInfo.avatar : '/static/image/default_avatar.jpg'" class="a-img" mode="widthFix"></image>
			</button>
		</view>
		<view class="nickname">
			<view class="input">
				<text>昵称</text>
				<input id="uni-input-type-text" class="uni-input" @blur="handleBlur" type="nickname" :value="(userInfo && userInfo.nickName) ? userInfo.nickName : ''" />
				<text class="right-arrow"></text>
			</view>
			<view class="input" @click="goToAddress">
				<text>收货地址</text>
				<text class="right-arrow"></text>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		data() {
			return {
				userInfo: {
					nickName: '',
					id: '',
					avatar: '/static/image/default_avatar.jpg',
				},
				uploadResult: false,
			}
		},
		onLoad() {
			this.getProfileInfo()
		},
		methods: {
			async getProfileInfo() {
				const token = uni.getStorageSync('token')
				if (token) {
					try {
						const response = await api.user.profile()
						const profile = response && response.data ? response.data : null
						if (!profile || !profile.id) {
							throw new Error('invalid profile')
						}
						this.userInfo = {
							nickName: profile.nickName || '',
							id: profile.id || '',
							avatar: profile.avatar || '/static/image/default_avatar.jpg',
							...profile
						}
					} catch (error) {
						uni.removeStorageSync('token')
						uni.showToast({
							title: '登录已失效',
							icon: 'none'
						})
						setTimeout(() => {
							uni.reLaunch({
								url: '/pages/login/login'
							})
						}, 500)
					}
				} else {
					uni.reLaunch({
						url: '/pages/login/login'
					})
				}
			},

			// 上传头像并更新到后台
			async onChooseAvatar(event) {
				uni.showLoading({
					title: '上传中...'
				})
				let that = this
				const tmpFilePath = event.detail.avatarUrl
				try {
					// 读取临时文件并转为 base64
					const fileManager = wx.getFileSystemManager()
					const base64Data = fileManager.readFileSync(tmpFilePath, 'base64')
					that.avatar = 'data:image/jpeg;base64,' + base64Data
					this.uploadResult = true
				} catch (error) {
					console.error('Base64 转换失败:', error)
					uni.hideLoading()
					uni.showToast({
						title: '头像处理失败',
						icon: 'none'
					})
				}

				if (this.userInfo.avatar !== that.avatar && this.uploadResult) {
					try {
						const params = {
							avatar: that.avatar,
						}
						const response = await api.user.avatar(params)
						uni.hideLoading()
						uni.showToast({
							title: response.msg,
							icon: 'success'
						})
						this.userInfo.avatar = that.avatar
					} catch (error) {
						uni.hideLoading()
						uni.showToast({
							title: '头像更新失败',
							icon: 'none'
						})
					}
				}
			},

			async handleBlur(event) {
				try {
					const params = {
						nickName: event.detail.value,
					}
					const response = await api.user.nickName(params)
					this.userInfo.nickName = event.detail.value
					uni.showToast({
						title: response.msg,
						icon: 'success'
					})
				} catch (error) {
					uni.showToast({
						title: '昵称更新失败',
						icon: 'none'
					})
				}
			},

			goToAddress() {
				uni.navigateTo({
					url: '/pages/address/list'
				})
			},
		}
	}
</script>

<style scoped>
	.profile {
		background-color: #f5f5f5;
		min-height: 100vh;
	}

	.avatar {
		padding: 50px 0;
		text-align: center;
	}

	.avatar-btn {
		width: 200rpx;
		height: 200rpx;
		padding: 0;
		border-radius: 50%;
	}

	.avatar .a-img {
		width: 200rpx;
		height: 200rpx;
	}

	.nickname {
		padding: 0 60rpx;
	}

	.nickname .input {
		margin: 40rpx 0;
		padding: 30rpx 30rpx;
		background-color: #ffffff;
		border-radius: 20rpx;
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: center;
		font-size: 28rpx;
	}

	.nickname .uni-input {
		text-align: right;
	}

	.right-arrow {
		display: inline;
		width: 40rpx;
		height: 40rpx;
		background-image: url('/static/image/right-arrow.png');
		background-size: 100%;
	}
</style>
