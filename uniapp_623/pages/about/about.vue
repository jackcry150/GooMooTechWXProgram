<template>
	<view class="brand-intro">
		<view class="contact-us">
			<view class="contact-us-title">微信官方社群</view>

			<view class="contact-us-copy" v-for="(item, index) in wechatGroup">
				<image class="c-img" :src="item.image[0]" mode="widthFix"></image>
				<text class="c-text">{{ item.title }}</text>
			</view>

		</view>
		<view class="follow-section">
			<view class="section-title">关注我们</view>
			<view class="search-bar">
				<image class="search-icon" src="/static/image/chat.png" />
				<text class="search-text">橘猫 GooMoo</text>
				<image class="search-btn" src="/static/image/search.png" />
			</view>

			<view class="social-list">
				<view class="social-item" v-for="(item, index) in followUs">
					<image class="social-icon" :src="item.image[0]"></image>
					<text class="c-text">{{ item.title }}</text>
				</view>
			</view>
		</view>

		<view class="who-section">
			<view class="section-title">我们是谁</view>
			<view class="content-text">
				<text class="paragraph">{{ aboutContent }}</text>
			</view>
		</view>

		<!-- 产品展示区域 -->
		<view class="product-showcase">
			<view class="showcase-item">
				<text class="showcase-text">原创机娘开发</text>
			</view>
			<view class="showcase-item">
				<text class="showcase-text">授权产品开发</text>
			</view>
		</view>

		<!-- 联系我们区域 -->
		<view class="contact-section">
			<view class="section-title">联系我们</view>
			<view class="contact-content">
				<text class="contact-text">{{ webSetting.contactUs }}</text>

				<view class="contact-info">
					<view class="info-item">
						<image class="info-icon" src="/static/image/location.png" />
						<text class="info-text">{{ webSetting.address }}</text>
					</view>
					<view class="info-item">
						<image class="info-icon" src="/static/image/email.png" />
						<text class="info-text">{{ webSetting.email }}</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'BrandIntro',
		data() {
			return {
				wechatGroup: [],
				followUs: [],
				webSetting: [],
				aboutContent: ''
			}
		},
		onLoad() {
			this.loadInfo()
		},
		methods: {
			loadInfo() {
				this.getWechatGroup()
				this.getFollowUs()
				this.getWebSetting()
				this.getAbout()
			},

			async getWechatGroup() {
				try {
					const params = {
						type: 1,
					}
					const response = await api.server.list(params)
					this.wechatGroup = response.data
				} catch (error) {

				}
			},
			async getFollowUs() {
				try {
					const params = {
						type: 2,
					}
					const response = await api.server.list(params)
					this.followUs = response.data
				} catch (error) {

				}
			},
			async getWebSetting() {
				try {
					const response = await api.setting.info()
					this.webSetting = response.data
				} catch (error) {

				}
			},
			async getAbout() {
				try {
					const res = await api.news.detail({ code: 'about' })
					if (res.code === 200 && res.data) {
						this.aboutContent = res.data.content || ''
					}
				} catch (e) {
					// 忽略错误，仅不展示简介
				}
			},
		}
	}
</script>

<style scoped>
	.brand-intro {
		background-color: #000000;
		min-height: 100vh;
		color: #ffffff;
	}

	.contact-us {
		background-color: #ffffff;
		padding: 220rpx 0;
		text-align: center;
	}

	.contact-us-title {
		color: #000000;
		font-size: 68rpx;
		font-weight: 600;
	}

	.contact-us-copy {
		padding: 30rpx 0;
		display: flex;
		flex-direction: column;
		align-items: center;
		flex-wrap: nowrap;
	}

	.contact-us-copy .c-img {
		width: 280rpx;
		height: 280rpx;
	}

	.contact-us-copy .c-text {
		color: #000000;
		font-size: 22rpx;
	}

	.follow-section {
		padding: 200rpx 0;
		background-color: #000000;
	}

	.section-title {
		font-size: 68rpx;
		font-weight: 600;
		color: #ffffff;
		text-align: center;
	}

	.search-bar {
		background-color: #ffffff;
		border-radius: 20rpx;
		padding: 20rpx 30rpx;
		display: flex;
		align-items: center;
		width: 70%;
		margin: 100rpx auto;
	}

	.search-icon {
		width: 40rpx;
		height: 40rpx;
		margin-right: 20rpx;
	}

	.search-text {
		flex: 1;
		font-size: 28rpx;
		color: #333333;
	}

	.search-btn {
		width: 40rpx;
		height: 40rpx;
	}

	.social-list {
		display: flex;
		flex-direction: column;
		gap: 20rpx;
		padding: 20rpx 160rpx;
	}

	.social-item {
		display: flex;
		align-items: center;
		padding: 26rpx 0;
	}

	.social-icon {
		width: 40rpx;
		height: 40rpx;
		border-radius: 50%;
		margin-right: 40rpx;
	}

	.social-text {
		font-size: 28rpx;
		color: #ffffff;
	}

	.who-section {
		background-color: #ffffff;
		padding: 240rpx 50rpx;
		color: #000000;
	}

	.who-section .section-title {
		color: #000000;
	}

	.content-text {
		line-height: 1.8;
		padding: 120rpx 60rpx 0 60rpx;
	}

	.paragraph {
		display: block;
		font-size: 28rpx;
		color: #333333;
		margin-bottom: 20rpx;
	}

	.product-showcase {
		background-color: #000000;
		padding: 40rpx 30rpx;
		display: flex;
		justify-content: space-between;
	}

	.showcase-item {
		flex: 1;
		text-align: center;
	}

	.showcase-text {
		font-size: 24rpx;
		color: #ffffff;
	}

	.contact-section {
		background-color: #ffffff;
		padding: 40rpx 30rpx;
		color: #333333;
	}

	.contact-section .section-title {
		color: #333333;
		text-align: left;
		margin-bottom: 30rpx;
	}

	.contact-content {
		line-height: 1.8;
	}

	.contact-text {
		display: block;
		font-size: 28rpx;
		color: #333333;
		margin-bottom: 20rpx;
	}

	.contact-info {
		margin-top: 30rpx;
	}

	.info-item {
		display: flex;
		align-items: flex-start;
		margin-bottom: 20rpx;
	}

	.info-icon {
		width: 30rpx;
		height: 30rpx;
		margin-right: 16rpx;
		margin-top: 4rpx;
	}

	.info-text {
		flex: 1;
		font-size: 26rpx;
		color: #333333;
		line-height: 1.6;
	}
</style>