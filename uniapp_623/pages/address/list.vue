<template>
	<view class="address-list">
		<!-- 地址列表 -->
		<view class="address-list-content">
			<view class="address-item" v-for="(address, index) in addressList" :key="index">
				<view class="address-info" @tap="selesctAddress(address)">
					<view class="address-header">
						<text class="contact-name">{{ address.name }}</text>
						<text class="contact-phone">{{ address.phone }}</text>
					</view>
					<view class="address-detail">{{ address.province }} {{ address.city }} {{ address.region }} {{ address.detail }}</view>
					<view class="address-footer">

						<view class="default-tag" @click="defaultSelect(index, address.id, address.isDefault)">
							<view class="checkbox" :class="{ active: address.isDefault }">
								<text class="checkmark" v-if="address.isDefault">✓</text>
							</view>
							<text class="default-text">默认地址</text>
						</view>
						<view class="address-actions">
							<button class="edit-btn" @click="editAddress(address)">编辑</button>
							<button class="delete-btn" @click="deleteAddress(address)">删除</button>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 空状态 -->
		<view class="empty-state" v-if="addressList.length === 0">
			<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
			<text class="empty-text">什么都没有呢~</text>
		</view>

		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<button class="add-btn" @click="addAddress">新增地址</button>
			<button class="import-btn" @click="importWechatAddress">获取微信地址</button>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'AddressList',
		data() {
			return {
				addressList: [],
				t: ''
			}
		},
		onLoad(options) {
			if (options.t) {
				this.t = options.t
			}
			this.getAddressList()
		},
		onShow() {
			this.getAddressList()
		},
		onPullDownRefresh() {
			this.getAddressList().finally(() => {
				uni.stopPullDownRefresh()
			})
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

			async getAddressList() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					try {
						const response = await api.address.list()
						this.addressList = response.data
					} catch (error) {

					}
				}
			},

			selesctAddress(address) {
				if (this.t == 1) {
					uni.setStorageSync('selectedAddress', address)
					uni.navigateBack()
				}
			},

			async defaultSelect(index, id, isDefault) {
				if (isDefault) {
					console.log('当前选中，不需要设置')
					return
				}
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					try {
						const params = {
							addressId: id,
							isDefault: true,
							type: 1
						}
						const response = await api.address.edit(params)
						const shouldSelectAll = false
						this.addressList.forEach(item => {
							item.isDefault = shouldSelectAll
						})
						this.addressList[index].isDefault = true
					} catch (error) {

					}
				}
			},

			editAddress(address) {
				uni.navigateTo({
					url: `/pages/address/edit?id=${address.id}`
				})
			},
			deleteAddress(address) {
				uni.showModal({
					title: '确认删除',
					content: '确定要删除这个地址吗？',
					success: (res) => {
						if (res.confirm) {
							this.delAddress(address)
						}
					}
				})
			},

			async delAddress(address) {
				const params = {
					addressId: address.id,
					isDefault: address.isDefault,
				}
				const response = await api.address.del(params)
				this.addressList = response.data
				uni.showToast({
					title: '删除成功',
					icon: 'success'
				})
			},

			addAddress() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: '/pages/address/edit'
					})
				}
			},

			importWechatAddress() {
				uni.chooseAddress({
					success: (res) => {
						uni.showToast({
							title: '地址选择成功',
							icon: 'success'
						})
						uni.navigateTo({
							url: `/pages/address/edit?address=${JSON.stringify(res)}`
						})
					},
					fail: (err) => {
						console.log('获取地址失败:', err)
						uni.showModal({
							title: '提示',
							content: '获取地址失败，请检查权限设置',
						})
					}
				})
			}
		}
	}
</script>

<style scoped>
	.address-list {
		background-color: #f5f5f5;
		min-height: 100vh;
	}

	.address-list-content {
		padding: 20rpx;
	}

	.address-item {
		background-color: #ffffff;
		border-radius: 20rpx;
		margin-bottom: 20rpx;
		padding: 20rpx 30rpx;
	}

	.address-info {
		width: 100%;
	}

	.address-header {
		display: flex;
		align-items: center;
		padding: 16rpx 0;
	}

	.contact-name {
		font-size: 28rpx;
		color: #000000;
		font-weight: bold;
		margin-right: 20rpx;
	}

	.contact-phone {
		font-size: 26rpx;
		color: #000000;
		font-weight: 800;
	}

	.address-detail {
		font-size: 24rpx;
		color: #545454;
		padding: 10rpx 0 60rpx 0;
		border-bottom: 1px dashed #d2d2d2;
	}

	.address-footer {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 20rpx 0;
	}

	.default-tag {
		display: flex;
		align-items: center;
	}

	.checkbox {
		width: 30rpx;
		height: 30rpx;
		border: 2rpx solid #cccccc;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.checkbox.active {
		background-color: #dc0000;
		border-color: #dc0000;
	}

	.checkmark {
		color: #ffffff;
		font-size: 24rpx;
		font-weight: bold;
	}

	.default-text {
		font-size: 24rpx;
		color: #000000;
		font-weight: bold;
		text-indent: 10rpx;
	}

	.address-actions {
		display: flex;
		gap: 20rpx;
	}

	.edit-btn {
		background-color: #ffffff;
		color: #dc0000;
		border: 1rpx solid #dc0000;
		border-radius: 20rpx;
		width: 120rpx;
		height: 40rpx;
		padding: 0 4rpx;
		line-height: 34rpx;
		font-size: 24rpx;
	}

	.delete-btn {
		background-color: #dc0000;
		color: #ffffff;
		border: none;
		border-radius: 20rpx;
		width: 120rpx;
		height: 40rpx;
		padding: 0 4rpx;
		line-height: 34rpx;
		font-size: 24rpx;
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

	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #ffffff;
		height: 80rpx;
		padding: 20rpx 30rpx;
		display: flex;
		gap: 20rpx;
	}

	.add-btn {
		flex: 1;
		background-color: #dc0000;
		color: #ffffff;
		border: none;
		border-radius: 50rpx;
		padding: 10rpx 0;
		font-size: 28rpx;
		font-weight: bold;
	}

	.import-btn {
		flex: 1;
		background-color: #1ccf00;
		color: #ffffff;
		border: none;
		border-radius: 50rpx;
		padding: 10rpx 0;
		font-size: 28rpx;
		font-weight: bold;
	}
</style>