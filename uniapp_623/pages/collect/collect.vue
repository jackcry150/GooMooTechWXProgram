<template>
	<view class="collect">
		<!-- 购物车商品列表 -->
		<view class="collect-list">
			<uni-swipe-action>
				<uni-swipe-action-item v-for="(item, index) in collectList" :key="index" :right-options="swipeOptions" @click="onSwipeClick($event, item, index)">
					<view class="collect-item">
						<view class="item-content" @click="goToProductDetail(item.productId)">
							<view class="item-image-l">
								<image class="item-image" :src="item.image" mode="widthFix"></image>
							</view>
							<view class="item-info">
								<view class="item-title">{{ item.title }} {{ item.subtitle }}</view>
								<view class="item-tag">{{ item.type }}</view>
								<view class="item-price-quantity">
									<view class="item-price">¥{{ item.price }}</view>
								</view>
							</view>
						</view>
					</view>
				</uni-swipe-action-item>
			</uni-swipe-action>
		</view>

		<!-- 空状态 -->
		<view class="empty-state" v-if="collectList.length === 0">
			<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
			<text class="empty-text">什么都没有呢~</text>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'Collect',
		data() {
			return {
				collectList: [],
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
			// this.getCollectList()
		},

		onShow() {
			this.getCollectList()
		},
		onPullDownRefresh() {
			this.getCollectList().finally(() => {
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

			async getCollectList() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.showLoading({
						title: '加载中'
					});
					try {
						const response = await api.collect.list()
						this.collectList = response.data
						uni.hideLoading()
					} catch (error) {

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
								this.deleteCollect(v, i)
							}
						}
					});
				}
			},
			
			async deleteCollect(v, i){
				uni.showLoading({
					title: '加载中'
				});
				try {
					const params ={
						id: v.id
					}
					const response = await api.collect.cancel(params)
					uni.hideLoading()
					uni.showToast({
						title: response.msg,
						icon: 'success'
					});
					this.collectList.splice(i, 1);
				} catch (error) {
				
				}
			},

			goToProductDetail(id) {
				uni.navigateTo({
					url: `/pages/product/detail?id=${id}`
				})
			},

			async cancelCollect() {
				if (this.selectedCount === 0) {
					uni.showToast({
						title: '请选择商品',
						icon: 'none'
					})
					return
				}

				const selectedItems = this.collectList.filter(item => item.selected)
				try {
					const response = await api.collect.cancel(selectedItems)
					uni.showToast({
						title: response.msg,
						icon: 'success'
					});
					this.collectList = response.data
				} catch (error) {

				}
			}
		}
	}
</script>

<style scoped>
	.collect {
		background-color: #f5f5f5;
		min-height: calc(100vh - 80rpx);
	}

	.collect-list {
		padding: 20rpx;
	}

	.uni-swipe {
		padding: 16rpx 0;
	}

	.collect-item {
		background-color: #ffffff;
		border-radius: 20rpx;
		padding: 20rpx;
		display: flex;
		align-items: center;
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
		width: 100%;
		height: auto;
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
	}

	.item-price {
		font-size: 32rpx;
		color: #dc0000;
		font-weight: bold;
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