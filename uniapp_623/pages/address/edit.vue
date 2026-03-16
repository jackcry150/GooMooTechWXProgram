<template>
	<view class="address-edit">
		<view class="address-section">
			<view class="default-section">
				<view class="form-item-l" @click="getWxAddress">
					<text class="form-label">获取微信收货地址</text>
					<view class="form-value">
						<text class="right-arrow"></text>
					</view>
				</view>
			</view>

			<view class="form-section">
				<view class="form-item">
					<text class="form-label">收货人</text>
					<view class="form-value">
						<input class="form-input" v-model="form.name" @blur="getFromValue" placeholder="请输入收货人姓名" />
					</view>
				</view>
				<view class="form-item">
					<text class="form-label">手机号</text>
					<view class="form-value">
						<input class="form-input" v-model="form.phone" @blur="getFromValue" placeholder="请输入收货人手机号" type="number" />
					</view>
				</view>
				<view class="form-item">
					<text class="form-label">所在地区</text>
					<view class="form-value" @tap="selectAddress">
						<input class="form-input" v-model="region" placeholder="请选择收货人所在地区" />
						<text class="right-arrow"></text>
					</view>
				</view>
				<view class="form-item">
					<text class="form-label">详细地址</text>
					<view class="form-value">
						<input class="form-input" v-model="form.detail" @blur="getFromValue" placeholder="请输入详细地址" />
					</view>
				</view>
			</view>

			<view class="default-section">
				<view class="form-item-l">
					<text class="form-label">设为默认地址</text>
					<view class="form-value">
						<switch :checked="form.isDefault" @change="toggleDefault" color="#dc0000" />
					</view>
				</view>
			</view>
		</view>
		<!-- 底部保存按钮 -->
		<view class="bottom-bar">
			<button class="save-btn" @click="saveAddress" :class="{ disabled: !isSave }">保存</button>
		</view>

		<!-- 地区选择器 -->
		<cityPicker :column="column" :default-value="defaultValue" :mask-close-able="maskCloseAble" @confirm="confirm" @cancel="cancel" :visible="visible" />
	</view>
</template>

<script>
	import cityPicker from '@/uni_modules/piaoyi-cityPicker/components/piaoyi-cityPicker/piaoyi-cityPicker'
	import { api } from '@/utils/request.js'
	export default {
		name: 'AddressEdit',
		data() {
			return {
				form: {
					name: '',
					phone: '',
					province: '',
					city: '',
					region: '',
					detail: '',
					isDefault: false
				},
				region: '',
				visible: false,
				maskCloseAble: true,
				defaultValue: ['北京市', '北京市', '东城区'],
				column: 3,
				isEdit: false,
				addressId: null,
				isSave: false,
			}
		},

		onLoad(options) {
			this.loadInfo()
			if (options.id) {
				this.isEdit = true
				this.addressId = options.id
				this.loadAddress(options.id)
			}

			if (options.address) {
				this.loadWxAddress(JSON.parse(options.address))
			}
		},
		components: {
			cityPicker
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

			loadInfo() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				}
			},

			async loadAddress(id) {
				try {
					const params = {
						id: id
					}
					const response = await api.address.detail(params)
					this.form = response.data
					this.form.isDefault = this.form.isDefault == 1 ? true : false
					this.region = this.form.province + ' ' + this.form.city + ' ' + this.form.region
				} catch (error) {

				}
			},

			getWxAddress() {
				uni.chooseAddress({
					success: (res) => {
						this.form = {
							name: res.userName,
							phone: res.telNumber,
							province: res.provinceName,
							city: res.cityName,
							region: res.countyName,
							detail: res.detailInfo
						}
						this.region = this.form.province + ' ' + this.form.city + ' ' + this.form.region
						this.getFromValue()
						uni.showToast({
							title: '地址选择成功',
							icon: 'success'
						})
					},
					fail: (err) => {
						uni.showModal({
							title: '提示',
							content: '获取地址失败，请检查权限设置',
						})
					}
				})
			},
			
			loadWxAddress(e){
				this.form = {
					name: e.userName,
					phone: e.telNumber,
					province: e.provinceName,
					city: e.cityName,
					region: e.countyName,
					detail: e.detailInfo
				}
				this.region = this.form.province + ' ' + this.form.city + ' ' + this.form.region
				this.getFromValue()
			},

			selectAddress() {
				this.visible = true
			},
			confirm(e) {
				this.form.province = e.provinceName
				this.form.city = e.cityName
				this.form.region = e.areaName
				this.region = this.form.province + ' ' + this.form.city + ' ' + this.form.region
				this.getFromValue()
				this.visible = false
			},
			cancel() {
				this.visible = false
				this.getFromValue()
			},

			toggleDefault(e) {
				this.form.isDefault = e.detail.value
				this.getFromValue()
			},

			getFromValue() {
				if (this.form.name && this.form.phone && this.form.province && this.form.city && this.form.region && this.form.detail) {
					this.isSave = true
				} else {
					this.isSave = false
				}
			},
			
			// 表单验证
			validateForm() {
				if (!this.form.name || this.form.name.trim() === '') {
					uni.showToast({
						title: '请输入收货人姓名',
						icon: 'none'
					})
					return false
				}

				if (!this.form.phone || this.form.phone.trim() === '') {
					uni.showToast({
						title: '请输入收货人手机号',
						icon: 'none'
					})
					return false
				}

				const phoneReg = /^1[3-9]\d{9}$/
				if (!phoneReg.test(this.form.phone)) {
					uni.showToast({
						title: '请正确输入收货人手机号',
						icon: 'none'
					})
					return false
				}

				if (!this.form.province || !this.form.city || !this.form.region) {
					uni.showToast({
						title: '请选择收货人所在地区',
						icon: 'none'
					})
					return false
				}

				if (!this.form.detail || this.form.detail.trim() === '') {
					uni.showToast({
						title: '请输入详细地址',
						icon: 'none'
					})
					return false
				}
				
				return true
			},
			
			async saveAddress() {
				if (!this.validateForm()) {
					return
				}
				// 保存地址
				uni.showLoading({
					title: '保存中...'
				})
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					try {
						if (this.isEdit) {
							// 编辑地址
							this.form.type = 2
							this.form.id = this.addressId
							const response = await api.address.edit(this.form)
							uni.hideLoading()
							if (response.code === 200) {
								uni.showToast({
									title: response.msg || '保存成功',
									icon: 'success'
								})
								setTimeout(() => {
									uni.navigateBack()
								}, 1500)
							} else {
								uni.showToast({
									title: response.msg || '保存失败',
									icon: 'none'
								})
							}
						} else {
							// 新增地址
							const response = await api.address.create(this.form)
							uni.hideLoading()
							if (response.code === 200) {
								uni.showToast({
									title: response.msg || '添加成功',
									icon: 'success'
								})
								setTimeout(() => {
									uni.navigateBack()
								}, 1500)
							} else {
								uni.showToast({
									title: response.msg || '添加失败',
									icon: 'none'
								})
							}
						}
					} catch (error) {
						uni.hideLoading()
						uni.showToast({
							title: '操作失败，请重试',
							icon: 'none'
						})
					}
				}
			},
		}
	}
</script>

<style scoped>
	.address-edit {
		background-color: #f5f5f5;
		min-height: 100vh;
	}

	.address-section {
		padding: 20rpx 0;
	}

	.form-section {
		background-color: #ffffff;
		padding: 30rpx;
		width: 86%;
		margin: auto;
		border-radius: 20rpx;
	}

	.form-item {
		padding: 20rpx 0;
		display: flex;
		align-items: center;
	}

	.form-label {
		font-size: 28rpx;
		font-weight: 800;
		display: inline-block;
		width: 160rpx;
	}

	.form-value {
		display: inline-block;
		width: calc(100% - 160rpx);
		display: flex;
	}

	.right-arrow {
		display: inline-block;
		width: 30rpx;
		height: 30rpx;
		background-image: url('/static/image/right-arrow.png');
		background-size: 100%;
		background-position: center;
		margin-top: 4rpx;
	}

	.form-input {
		width: 100%;
		font-size: 28rpx;
		color: #333333;
		border: none;
		outline: none;
	}

	.form-item-l {
		padding: 20rpx 0;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.form-item-l .form-label {
		font-size: 28rpx;
		font-weight: bold;
		display: inline-block;
		width: 80%;
	}

	.form-item-l .form-value {
		display: inline-block;
		width: 20%;
		display: flex;
		justify-content: flex-end;
	}

	.region-selector {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.region-text {
		font-size: 28rpx;
		color: #333333;
	}

	.region-placeholder {
		font-size: 28rpx;
		color: #999999;
	}

	.default-section {
		background-color: #ffffff;
		padding: 0 30rpx;
		width: 86%;
		margin: 20rpx auto;
		border-radius: 20rpx;
	}

	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #ffffff;
		height: 80rpx;
		padding: 20rpx 30rpx;
	}

	.save-btn {
		flex: 1;
		background-color: #dc0000;
		color: #ffffff;
		border: none;
		border-radius: 50rpx;
		padding: 8rpx 0;
		font-size: 28rpx;
		font-weight: bold;
	}

	.save-btn.disabled {
		background-color: #cccccc;
		color: #ffffff;
	}
</style>