<template>
	<view class="cart">
		<!-- 购物车商品列表 -->
		<view class="cart-list">
			<uni-swipe-action>
				<uni-swipe-action-item v-for="(item, index) in cartList" :key="index" :right-options="swipeOptions" @click="onSwipeClick($event, item, index)">
					<view class="cart-item">
						<view class="item-checkbox" @click="toggleSelect(index)">
							<view class="checkbox" :class="{ active: item.selected }">
								<text class="checkmark" v-if="item.selected">✓</text>
							</view>
						</view>
						<view class="item-content">
							<view class="item-image-l">
								<image class="item-image" :src="item.image" mode="aspectFill"></image>
							</view>
							<view class="item-info">
								<view class="item-title">
									<text class="type-tag presale" v-if="item.type == 2">预售</text>
									<text class="type-tag instock" v-else-if="item.type == 1">现货</text>
									{{ item.title }} {{ item.subtitle }}
								</view>
								<view class="item-tag">{{ item.version }}</view>
								<view class="item-price-quantity">
									<view class="item-price">¥{{ item.price }}</view>
									<view class="quantity-control">
										<view class="quantity-btn minus" @click="decreaseQuantity(index)">-</view>
										<view class="quantity-text">{{ item.quantity }}</view>
										<view class="quantity-btn" @click="increaseQuantity(index)">+</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</uni-swipe-action-item>
			</uni-swipe-action>
		</view>

		<!-- 空状态 -->
		<view class="empty-state" v-if="cartList.length === 0">
			<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
			<text class="empty-text">什么都没有呢~</text>
		</view>

		<!-- 底部结算栏 -->
		<view class="bottom-bar">
			<view class="select-all" @click="toggleSelectAll">
				<view class="checkbox" :class="{ active: allSelected }">
					<text class="checkmark" v-if="allSelected">✓</text>
				</view>
				<text class="select-text">全选</text>
			</view>

			<view class="total-info">
				<text class="total-text">合计<text class="total-text-val">¥{{ totalPrice }}</text></text>
			</view>

			<view class="checkout-btn" :class="{ disabled: selectedCount === 0 }" @click="checkout">
				结算<text v-if="selectedCount > 0">（{{ selectedCount }}）</text>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'Cart',
		data() {
			return {
				cartList: [],
				swipeOptions: [{
					text: '删除',
					style: {
						backgroundColor: '#dc0000',
						color: '#ffffff'
					}
				}]
			}
		},
		onLoad() {
		},
		onShow() {
			this.getCartList()
		},
		onPullDownRefresh() {
			this.getCartList().finally(() => {
				uni.stopPullDownRefresh()
			})
		},

		computed: {
			allSelected() {
				return this.cartList.length > 0 && this.cartList.every(item => item.selected)
			},
			selectedCount() {
				return this.cartList.filter(item => item.selected).length
			},
			totalPrice() {
				return this.cartList
					.filter(item => item.selected)
					.reduce((total, item) => total + (item.price * item.quantity), 0)
					.toFixed(2)
			}
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

			async getCartList() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.showLoading({
						title: '加载中'
					});
					try {
						const response = await api.cart.list()
						this.cartList = response.data
						uni.hideLoading()
					} catch (error) {
						uni.hideLoading()
					}
				}
			},

			async onSwipeClick(e, v, i) {
				if (e.position === 'right' && e.content.text === '删除') {
					uni.showModal({
						title: '提示',
						content: '确定要删除这款商品吗？',
						success: (res) => {
							if (res.confirm) {
								this.deleteCart(v, i)
							}
						}
					});
				}
			},
			
			async deleteCart(v, i){
				uni.showLoading({
					title: '加载中'
				});
				try {
					const params ={
						id: v.id
					}
					const response = await api.cart.cancel(params)
					uni.hideLoading()
					uni.showToast({
						title: response.msg,
						icon: 'success'
					});
					this.cartList.splice(i, 1);
				} catch (error) {
					uni.hideLoading()
					uni.showToast({
						title: '删除失败',
						icon: 'none'
					});
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
				this.quantity(index)
			},
			decreaseQuantity(index) {
				if (this.cartList[index].quantity > 1) {
					this.cartList[index].quantity--
					this.quantity(index)
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
				const cartIds = selectedItems.map(item => item.id).join(',')
				const productIds = selectedItems.map(item => item.productId).join(',')
				uni.navigateTo({
					url: `/pages/order/create?cartIds=${encodeURIComponent(cartIds)}&productIds=${encodeURIComponent(productIds)}`
				})
			},
			async quantity(index) {
				try {
					const params = {
						id: this.cartList[index].id,
						quantity: this.cartList[index].quantity
					}
					const res = await api.cart.quantity(params)
					if (res.code !== 200) {
						uni.showToast({ 
							title: res.msg || '操作失败', 
							icon: 'none',
						})
						setTimeout(() => {
							this.getCartList()
						}, 1500)
					}
				} catch (error) {
					uni.showToast({ title: '操作失败', icon: 'none' })
					this.getCartList()
				}
			}
		}
	}
</script>

<style scoped>
	.cart {
		background-color: #f5f5f5;
		min-height: 100vh;
	}

	.cart-list {
		padding: 20rpx;
	}


	.cart-item {
		background-color: #ffffff;
		border-radius: 20rpx;
		padding: 20rpx;
		display: flex;
		align-items: center;
	}

	.item-checkbox {
		margin-right: 20rpx;
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

	.item-content {
		flex: 1;
		display: flex;
		align-items: center;
	}

	.item-image-l {
		width: 200rpx;
		height: 200rpx;
		margin-right: 20rpx;
	}

	.item-image {
		width: 200rpx;
		height: 200rpx;
		border-radius: 10rpx;
	}

	.item-info {
		flex: 1;
		height: 200rpx;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		align-items: flex-start;
	}

	.item-title {
		font-size: 24rpx;
		color: #000000;
		font-weight: 600;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	
	.type-tag {
		display: inline-block;
		padding: 2rpx 12rpx;
		font-size: 20rpx;
		border-radius: 6rpx;
		margin-right: 8rpx;
		font-weight: bold;
	}
	
	.type-tag.presale {
		background-color: #dc0000;
		color: #ffffff;
	}
	
	.type-tag.instock {
		background-color: #1ccf00;
		color: #ffffff;
	}

	.item-tag {
		font-size: 18rpx;
		background-color: #f5f5f5;
		padding: 6rpx 20rpx;
		border-radius: 20rpx;
		display: inline-block;
		margin: 10rpx 0;
	}

	.item-price-quantity {
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: nowrap;
		flex-direction: row;
		width: 100%;
	}

	.item-price {
		font-size: 32rpx;
		color: #dc0000;
		font-weight: bold;
	}


	.quantity-control {
		display: flex;
		align-items: center;
	}

	.quantity-btn {
		width: 50rpx;
		height: 50rpx;
		background-color: #ffffff;
		border: none;
		font-size: 40rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.minus {
		line-height: 40rpx;
	}

	.quantity-text {
		width: 80rpx;
		height: 50rpx;
		line-height: 50rpx;
		text-align: center;
		font-size: 24rpx;
		background-color: #eeeeee;
		border-radius: 10rpx;
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
		align-items: center;
		border-top: 1rpx solid #eeeeee;
	}

	.select-all {
		display: flex;
		align-items: center;
	}

	.select-text {
		font-size: 24rpx;
		color: #000000;
		margin-left: 16rpx;
		font-weight: 800;
	}

	.total-info {
		flex: 1;
		text-align: right;
		margin-right: 30rpx;
	}

	.total-text {
		font-size: 24rpx;
		color: #000000;
		font-weight: 800;
	}

	.total-text-val {
		font-size: 36rpx;
		color: #dc0000;
		font-weight: bold;
	}

	.checkout-btn {
		background-color: #dc0000;
		color: #ffffff;
		border: none;
		border-radius: 40rpx;
		padding: 20rpx 0;
		font-size: 28rpx;
		font-weight: bold;
		width: 260rpx;
		text-align: center;
	}

	.checkout-btn.disabled {
		background-color: #cccccc;
		color: #ffffff;
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
</style>