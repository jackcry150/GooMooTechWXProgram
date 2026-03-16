<template>
	<view class="album">
		<view class="album-item" v-for="(item, index) in albumList" :key="index" @click="goToAlbumDetail(item)">
			<view class="album-item-val">
				<view class="album-item-img">
					<image :src="item.image" class="p-img" mode="aspectFill"></image>
				</view>
				<view class="album-item-info">
					<view class="album-item-info-name">{{ item.title }}</view>
					<view class="album-item-info-label">
						<text class="album-label" v-for="(item2, index2) in item.labels" :key="index2">{{ item2 }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 空状态 -->
		<view class="empty-state" v-if="albumList.length === 0">
			<image class="empty-icon" src="/static/image/no-data.png" mode="widthFix" />
			<text class="empty-text">什么都没有呢~</text>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'

	export default {
		data() {
			return {
				albumList: [],
			}
		},

		onLoad() {
			this.loadInfo()
		},

		onShow() {
			this.loadInfo()
		},

		onPullDownRefresh() {
			this.loadInfo().finally(() => {
				uni.stopPullDownRefresh()
			})
		},

		methods: {
			loadInfo() {
				this.getAlbumList()
			},

			async getAlbumList() {
				try {
				    const response = await api.album.list()
					this.albumList = response.data
				} catch (error) {
				   
				}
			},
			goToAlbumDetail(a) {
				uni.navigateTo({
					url: `/pages/album/details?id=${a.id}`
				})
			}
		}
	}
</script>

<style scoped>
	.album {
		min-height: 100vh;
		padding-bottom: 150rpx;
	}

	.album-item {
		padding: 20rpx 20rpx;
	}

	.album-item-val {
		position: relative;
	}
	
	.album-item-img{
		width: 100%;
		height: 700rpx;
	}

	.album-item-img .p-img {
		width: 100%;
		height: 700rpx;
	}

	.album-item-info {
		position: absolute;
		bottom: 10rpx;
		padding: 0 30rpx;
	}

	.album-item-info .album-item-info-name {
		color: #ffffff;
		font-weight: bold;
		font-size: 44rpx;
	}

	.album-item-info .album-item-info-type {
		padding: 20rpx 20rpx;
	}

	.album-item-info .album-item-info-type .buy-type-val1 {
		width: 100rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #dc0000;
		color: #ffffff;
		font-size: 24rpx;
		font-weight: 600;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
	}

	.album-item-info .album-item-info-label {
		display: flex;
		padding: 20rpx 0;
	}

	.album-item-info .album-item-info-label .album-label {
		background-color: rgb(255 255 255 / 30%);
		color: #ffffff;
		font-size: 20rpx;
		font-weight: 500;
		border-radius: 24rpx;
		padding: 8rpx 20rpx;
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