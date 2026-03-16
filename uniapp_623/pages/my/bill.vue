<template>
	<view>
		<!-- 空状态 -->
		<view class="empty-state" v-if="billist.length === 0">
			<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
			<text class="empty-text">什么都没有呢~</text>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'Bill',
		data() {
			return {
				billist: [],
			}
		},
		onLoad() {
			// this.getBillist()
		},
		// onShow() {
		// 	this.getCartList()
		// },
		// onPullDownRefresh() {
		// 	this.getCartList().finally(() => {
		// 		uni.stopPullDownRefresh()
		// 	})
		// },

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

			async getCartList() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					try {
						const response = await api.cart.list()
						this.cartList = response.data
					} catch (error) {

					}
				}
			},

			toggleSelect(index) {
				this.cartList[index].selected = !this.cartList[index].selected
			},
			toggleSelectAll() {
				const shouldSelectAll = !this.allSelected
				this.cartList.forEach(item => {
					item.selected = shouldSelectAll
				})
			},
			increaseQuantity(index) {
				this.cartList[index].quantity++
			},
			decreaseQuantity(index) {
				if (this.cartList[index].quantity > 1) {
					this.cartList[index].quantity--
				}
			},
			checkout() {
				if (this.selectedCount === 0) {
					uni.showToast({
						title: '请选择商品',
						icon: 'none'
					})
					return
				}

				const selectedItems = this.cartList.filter(item => item.selected)
				uni.navigateTo({
					url: `/pages/order/create?items=${JSON.stringify(selectedItems)}`
				})
			}
		}
	}
</script>


<style scoped>
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
</style>