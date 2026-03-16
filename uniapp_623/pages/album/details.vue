<template>
	<view class="album">
		<view class="album-image" v-if="groupItem != 4">
			<image :src="albumInfo.image" class="album-image-v" mode="widthFix"></image>
		</view>
		<view class="album-image-list" v-if="groupItem == 4">
			<view class="album-image-li" v-for="(list, index) in albumInfo.images" :key="index" :class="{ one: index === 0 }" @click="viewImage(index)">
				<image class="a-image" :src="list" mode="aspectFill" />
			</view>
		</view>

		<view v-if="imageMask" class="album-image-mask" @touchmove.stop.prevent>
			<view class="mask-btn mask-close" @click="closeMask"></view>
			<view class="mask-btn mask-left" @click="leftImg"></view>
			<view class="mask-btn mask-right" @click="rightImg"></view>
			<view class="mask-image">
				<image :src="viewImageUrl" class="m-img" mode="widthFix"></image>
			</view>
		</view>

		<view class="album-info">
			<view v-if="groupItem == 1">
				<view class="type" v-if="albumInfo.type == 2">
					<view class="buy-type-val1">预售</view>
				</view>
				<view class="name">{{ albumInfo.title }}</view>
				<view class="album-base" v-if="groupItem == 1">
					<text class="album-label" v-for="(item, index) in albumInfo.labels" :key="index">{{ item }}</text>
				</view>
			</view>
			<view v-if="groupItem == 2">
				<view class="name">{{ albumInfo.title }}</view>
				<view class="label-content">
					<view class="album-label">
						<view class="album-label-t">比例</view>
						<view class="album-label-v">{{ albumInfo.proportion }}</view>
					</view>
					<view class="album-label">
						<text class="album-label-t">尺寸</text>
						<text class="album-label-v">{{ albumInfo.size }}</text>
					</view>
					<view class="album-label">
						<text class="album-label-t">材质</text>
						<text class="album-label-v">{{ albumInfo.material }}</text>
					</view>
					<view class="album-label">
						<text class="album-label-t">版权所属</text>
						<text class="album-label-v">{{ albumInfo.copyright }}</text>
					</view>
					<view class="album-label">
						<text class="album-label-t">售价</text>
						<text class="album-label-v">{{ albumInfo.price }}</text>
					</view>
				</view>
			</view>
			<view v-if="groupItem == 3">
				<view class="name">{{ albumInfo.title }}</view>
				<view class="label-other">
					<text class="album-label" v-for="(item, index) in albumInfo.content" :key="index">{{ item }}</text>
				</view>
			</view>
			<view class="group">
				<view class="group-item" :class="{ active: groupItem === 1 }" @click="switchGroup(1)">
					<view class="group-item-top"></view>
					<text>{{ albumInfo.title }}</text>
				</view>
				<view class="group-item" :class="{ active: groupItem === 2 }" @click="switchGroup(2)">
					<view class="group-item-top"></view>
					<text>产品信息</text>
				</view>
				<view class="group-item" :class="{ active: groupItem === 3 }" @click="switchGroup(3)">
					<view class="group-item-top"></view>
					<text>内容物及配件</text>
				</view>
				<view class="group-item" :class="{ active: groupItem === 4 }" @click="switchGroup(4)">
					<view class="group-item-top"></view>
					<text>产品图册</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'

	export default {
		data() {
			return {
				albumInfo: [],
				groupItem: 1,
				imageMask: false,
				viewImageUrl: '',
				curViewImage: 0,
			}
		},

		onLoad(options) {
			if (options.id) {
				this.getAlbumDetails(options.id)
			} else {
				uni.redirectTo({
					url: '/pages/index/index'
				})
			}
		},
		methods: {

			async getAlbumDetails(id) {
				try {
					const params = {
						id: id,
					}
					const response = await api.album.detail(params)
					this.albumInfo = response.data
				} catch (error) {

				}
			},

			switchGroup(item) {
				this.groupItem = item
			},

			viewImage(k) {
				this.curViewImage = k
				this.viewImageUrl = this.albumInfo.images[this.curViewImage]
				this.imageMask = true
			},

			closeMask() {
				this.imageMask = false
			},

			leftImg() {
				let num = this.albumInfo.images.length - 1
				if (this.curViewImage <= 0) {
					this.curViewImage = num
				} else {
					this.curViewImage--
				}
				this.viewImageUrl = this.albumInfo.images[this.curViewImage]
			},

			rightImg() {
				let num = this.albumInfo.images.length - 1
				if (this.curViewImage >= num) {
					this.curViewImage = 0
				} else {
					this.curViewImage++
				}
				this.viewImageUrl = this.albumInfo.images[this.curViewImage]
			},
		}
	}
</script>

<style scoped>
	.album {
		background-color: #000000;
		position: relative;
	}

	.album-image {
		width: 100%;
		height: auto;
	}

	.album-image-v {
		width: 100%;
		height: auto;
	}


	.album-image-list {
		min-height: calc(100vh - 22rpx);
		display: flex;
		flex-wrap: wrap;
		align-content: flex-start;
		z-index: 1;
	}

	.album-image-li {
		width: 33.33%;
		height: calc(100vw / 3);
	}

	.album-image-li .a-image {
		width: 100%;
		height: 100%;
	}

	.one {
		width: 100%;
		height: 100vw;
	}


	.icon-colse:before {
		content: "\e619";
		color: #ffffff;
	}

	.icon-left:before {
		content: "\ec2f";
		color: #ffffff;
	}

	.icon-right:before {
		content: "\e775";
		color: #ffffff;
	}

	.album-image-mask {
		z-index: 100;
		position: absolute;
		top: -100rpx;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: rgba(0, 0, 0, 0.5);
	}

	.album-image-mask .mask-btn {
		position: absolute;
		z-index: 1;
		display: -webkit-box;
		display: -ms-flexbox;
		display: flex;
		-webkit-box-align: center;
		-ms-flex-align: center;
		align-items: center;
		-webkit-box-pack: center;
		-ms-flex-pack: center;
		justify-content: center;
		border-radius: 50%;
		opacity: .8;
		cursor: pointer;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		user-select: none;
	}

	.album-image-mask .mask-close {
		top: 160rpx;
		right: 60rpx;
		width: 60rpx;
		height: 60rpx;
		background-color: #606266;
		background-image: url('/static/image/close.png');
		background-size: 100%;
	}

	.album-image-mask .mask-left {
		top: 50%;
		left: 60rpx;
		width: 60rpx;
		height: 60rpx;
		background-color: #606266;
		background-image: url('/static/image/left-arrow.png');
		background-size: 100%;
	}

	.album-image-mask .mask-right {
		top: 50%;
		right: 60rpx;
		width: 60rpx;
		height: 60rpx;
		background-color: #606266;
		background-image: url('/static/image/right-arrow.png');
		background-size: 100%;
	}

	.album-image-mask .mask-image {
		width: 100%;
		height: 100%;
		display: -webkit-box;
		display: -ms-flexbox;
		display: flex;
		-webkit-box-pack: center;
		-ms-flex-pack: center;
		justify-content: center;
		-webkit-box-align: center;
		-ms-flex-align: center;
		align-items: center;
		z-index: 90;
	}

	.album-image-mask .mask-image .m-img {
		width: 100%;
		height: auto;
	}


	.album-info {
		width: 100%;
		position: absolute;
		bottom: 40rpx;
	}

	.album-info .name {
		width: 100%;
		text-align: center;
		color: #ffffff;
		font-weight: bold;
		font-size: 44rpx;
	}

	.album-info .type {
		width: 100%;
		display: flex;
		flex-direction: row;
		flex-wrap: nowrap;
		justify-content: center;
		padding: 30rpx 0;
	}

	.album-info .type .buy-type-val1 {
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

	.album-info .album-base {
		display: flex;
		flex-direction: row;
		justify-content: center;
		align-items: center;
		padding: 50rpx 0;
	}

	.album-info .album-base .album-label {
		background-color: rgb(255 255 255 / 30%);
		color: #ffffff;
		font-size: 20rpx;
		font-weight: 500;
		border-radius: 24rpx;
		padding: 8rpx 20rpx;
		margin: 0 26rpx 26rpx 0;
	}

	.album-info .label-content {
		display: flex;
		flex-direction: column;
		flex-wrap: nowrap;
		align-items: flex-start;
		padding: 20rpx 0;
	}

	.album-info .label-content .album-label {
		color: #ffffff;
		font-size: 24rpx;
		padding: 20rpx 40rpx;
		width: calc(100% - 80rpx);
		display: flex;

	}

	.album-info .label-content .album-label .album-label-t {
		width: 20%;
	}

	.album-info .label-content .album-label .album-label-v {
		color: #999999;
	}

	.album-info .label-other {
		display: flex;
		flex-direction: row;
		justify-content: flex-start;
		flex-wrap: wrap;
		padding: 50rpx 20rpx;
	}

	.album-info .label-other .album-label {
		background-color: rgb(255 255 255 / 30%);
		color: #ffffff;
		font-size: 28rpx;
		font-weight: 500;
		border-radius: 24rpx;
		padding: 8rpx 20rpx;
		margin: 0 26rpx 26rpx 0;
	}

	.album-info .group {
		box-sizing: border-box;
		border-top: 1px solid #fff;
		display: -webkit-flex;
		-webkit-box-pack: center;
		-ms-flex-pack: center;
		justify-content: center;
		-webkit-justify-content: center;
	}

	.album-info .group .group-item {
		position: relative;
		font-size: 12px;
		color: hsla(0, 0%, 100%, .5);
		font-weight: 800;
		width: 80px;
		padding: 14px 0 0;
		-webkit-transition-property: color, fontSize;
		transition-property: color, fontSize;
		-webkit-transition: .5s;
		transition: .5s;
		cursor: pointer;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
		text-align: center;
	}

	.album-info .group .group-item .group-item-top {
		position: absolute;
		top: -7rpx;
		left: 50%;
		-webkit-transform: translateX(-50%);
		transform: translateX(-50%);
		border-radius: 50%;
		-webkit-transition-property: height, width;
		transition-property: height, width;
		-webkit-transition: all .5s;
		transition: all .5s;
		background-color: #ffffff;
		height: 12rpx;
		width: 12rpx;
	}

	.album-info .group .active {
		color: #ffffff;
	}

	.album-info .group .active .group-item-top {
		height: 20rpx;
		width: 20rpx;
		top: -11rpx;
	}
</style>