<template>
	<view class="home">
		<!-- 轮播图 -->
		<view class="banner-section">
			<swiper class="banner-swiper" :indicator-dots="true" :autoplay="true" :interval="4000" :duration="500">
				<swiper-item v-for="(banner, index) in bannerList" :key="index">
					<view class="banner-item">
						<image :src="banner.image" class="banner-image" mode="center" @click="onBannerClick(banner)"></image>
					</view>
				</swiper-item>
			</swiper>
		</view>

		<!-- 顶部导航栏 -->
		<view class="nav-section">
			<view class="nav-bar">
				<view class="nav-item" @click="goToAbout()">
					<view class="nav-item-img">
						<image class="nav-item-image" src="/static/image/icon_brand.png" mode="widthFix"></image>
					</view>
					<text class="nav-item-title">品牌介绍</text>
				</view>
				<view class="nav-item" @click="goToCollect()">
					<view class="nav-item-img">
						<image class="nav-item-image" src="/static/image/icon_collect.png" mode="widthFix"></image>
					</view>
					<view class="iconfont nav-item-collect"></view>
					<text class="nav-item-title">线上收藏卡</text>
				</view>
				<view class="nav-item" @click="goToCustomer()">
					<view class="nav-item-img">
						<image class="nav-item-image" src="/static/image/icon_customer.png" mode="widthFix"></image>
					</view>
					<text class="nav-item-title">联系客服</text>
				</view>
				<view class="nav-item" @click="goToSell()">
					<view class="nav-item-img">
						<image class="nav-item-image" src="/static/image/icon_brand.png" mode="widthFix"></image>
					</view>
					<view class="iconfont nav-item-sell"></view>
					<text class="nav-item-title">特别贩售</text>
				</view>
			</view>
		</view>

		<!-- 商品列表 -->
		<view class="product-list">
			<view class="section-title">
				<text class="title-text">全部商品</text>
			</view>

			<view class="product-hot">
				<view class="product-hot-item" v-for="(productHot, index) in productListHot" :key="index" @click="goToProductDetail(productHot.id)">
					<view class="product-hot-top">
						<view class="product-hot-top-l">
							<view>www.goomooplay.com</view>
							<view class="fontBold">SNAIL SHELL</view>
						</view>
						<view class="product-hot-top-c">
							<image src="/static/image/t1.png" class="c-img" mode="aspectFit"></image>
						</view>
						<view class="product-hot-top-r">
							<view class="product-hot-top-t">
								<view>established in</view>
								<view class="fontBold">2019</view>
							</view>
							<view class="product-hot-top-logo">
								<image src="/static/image/logo1.png" class="l-img" mode="aspectFit"></image>
							</view>
							<view class="product-hot-top-logo">
								<image src="/static/image/logo2.png" class="l-img" mode="aspectFit"></image>
							</view>
						</view>
					</view>
					<view class="product-hot-center">
						<view class="product-hot-img">
							<image :src="productHot.image[0]" class="p-img" mode="widthFix"></image>
						</view>
						<view class="product-hot-time" v-if="productHot.type == 2 && productHot.countdown">
							预定时间剩余: {{ productHot.countdown }}
						</view>
					</view>
					<view class="product-hot-bottom">
						<view class="product-hot-buy-type">
							<view class="product-hot-sub">
								<text class="buy-type-val1" v-if="productHot.type == 2">预售</text>
								<text class="buy-type-val2" v-if="productHot.type == 1">现货</text>
								{{ productHot.subtitle }}
							</view>
						</view>
						<view class="product-hot-name">{{ productHot.title }}</view>
						<view class="product-hot-price">
							<view class="price">
								<view v-if="productHot.type == 2">
									<view class="price-deposit">定金￥<text class="fontBold">{{ productHot.deposit }}</text></view>
									<view class="price-total">商品总价￥{{ productHot.price }}</view>
								</view>
								<view v-if="productHot.type == 1">
									<view class="price-deposit">￥<text class="fontBold">{{ productHot.price }}</text></view>
									<view class="pay-price" v-if="productHot.deduct > 0">
										<text class="pay-price-val">抵后到手价￥{{ productHot.price - productHot.deduct }}</text>
									</view>
								</view>

							</view>
							<view class="buy">
								立即购买
							</view>
						</view>
					</view>
					<view class="product-hot-center">
						<view class="product-hot-center-k"></view>
					</view>
				</view>

			</view>

			<view class="product-recom">
				<view class="product-recom-item" v-for="(productRecom, index) in productListRecom" :key="index" @click="goToProductDetail(productRecom.id)">
					<view class="product-recom-h">
						<view class="product-recom-top">
							<view class="product-recom-top-l">
								<view>www.goomooplay.com</view>
								<view class="fontBold">SNAIL SHELL</view>
							</view>
							<view class="product-recom-top-c">
								<image src="/static/image/t1.png" class="c-img" mode="aspectFit"></image>
							</view>
							<view class="product-recom-top-r">
								<view class="product-recom-top-t">
									<view>established in</view>
									<view class="fontBold">2019</view>
								</view>
								<view class="product-recom-top-logo">
									<image src="/static/image/logo1.png" class="l-img" mode="aspectFit"></image>
								</view>
								<view class="product-recom-top-logo">
									<image src="/static/image/logo2.png" class="l-img" mode="aspectFit"></image>
								</view>
							</view>
						</view>
						<view class="product-recom-center">
							<view class="product-recom-img">
								<image :src="productRecom.image[0]" class="p-img" mode="widthFix"></image>
							</view>
							<view class="product-recom-time" v-if="productRecom.type == 2 && productRecom.countdown">
								预定时间剩余: {{ productRecom.countdown }}
							</view>
						</view>
					</view>

					<view class="product-recom-bottom">
						<view class="product-recom-buy-type">

							<view class="product-recom-name">
								<text class="buy-type-val1" v-if="productRecom.type == 2">预售</text>
								<text class="buy-type-val2" v-if="productRecom.type == 1">现货</text>
								{{ productRecom.subtitle }} {{ productRecom.title }}
							</view>
						</view>

						<view class="product-recom-price">
							<view class="price">
								<view v-if="productRecom.type == 2">
									<view class="price-deposit">定金￥<text class="fontBold">{{ productRecom.deposit }}</text></view>
									<view class="price-total">商品总价￥{{ productRecom.price }}</view>
								</view>
								<view v-if="productRecom.type == 1">
									<view class="price-deposit">￥<text class="fontBold">{{ productRecom.price }}</text></view>
								</view>
							</view>
							<view class="pay-price" v-if="productRecom.deduct > 0">
								<text class="pay-price-val">抵后到手价￥{{ productRecom.price - productRecom.deduct }}</text>
							</view>
						</view>
					</view>
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
				bannerList: [],
				productListHot: [],
				productListRecom: [],
				countdownTimers: []
			}
		},

		onLoad() {
			this.loadInfo()
			uni.setStorageSync('token', 'U013YUN6UDZFdWlhd1UyRzE4QkRvQkdrS2tzRjZuWVRlcUtuZ1V6TnJWNXVKQS9hZnpxNXI4V29pYkNyNkl5dmhOZ05zcml0MCtMQnd4Vmc2SGNjcjViQWRiUm51aFNub09icEg3NDhTVmc9')
		},

		onUnload() {
			// 清除所有倒计时定时器
			this.countdownTimers.forEach(timer => {
				if (timer) {
					clearInterval(timer)
				}
			})
			this.countdownTimers = []
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
				this.getBannerList()
				this.getProductList()
			},

			async getBannerList() {
				try {
					const response = await api.banner.list()
					this.bannerList = response.data
				} catch (error) {
					console.error('getBannerList error', error)
  					uni.showToast({ title: 'banner加载失败', icon: 'none' })

				}

			},
			async getProductList() {
				try {
					const response = await api.product.list()
					this.productListHot = response.data.hot || []
					this.productListRecom = response.data.recom || []
					
					// 清除之前的倒计时定时器
					this.countdownTimers.forEach(timer => {
						if (timer) {
							clearInterval(timer)
						}
					})
					this.countdownTimers = []
					
					// 为所有预售商品启动倒计时
					this.startCountdowns()
				} catch (error) {

				}
			},

			// 启动所有预售商品的倒计时
			startCountdowns() {
				// 热销商品倒计时
				this.productListHot.forEach((product, index) => {
					if (product.type == 2 && product.endTimeStamp) {
						this.startCountdown(product, 'hot', index)
					}
				})
				
				// 推荐商品倒计时
				this.productListRecom.forEach((product, index) => {
					if (product.type == 2 && product.endTimeStamp) {
						this.startCountdown(product, 'recom', index)
					}
				})
			},

			// 为单个商品启动倒计时
			startCountdown(product, listType, index) {
				// 使用Vue.set或者直接赋值来触发响应式更新
				if (!product.endTimeStamp) {
					this.$set(product, 'countdown', '')
					return
				}
				
				const updateCountdown = () => {
					const now = Math.floor(Date.now() / 1000)
					const remaining = product.endTimeStamp - now
					
					if (remaining <= 0) {
						this.$set(product, 'countdown', '已结束')
						return
					}
					
					const days = Math.floor(remaining / 86400)
					const hours = Math.floor((remaining % 86400) / 3600)
					const minutes = Math.floor((remaining % 3600) / 60)
					const seconds = remaining % 60
					
					const pad = (n) => n < 10 ? '0' + n : n
					
					let countdownText = ''
					if (days > 0) {
						countdownText = `${days} 天 ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`
					} else {
						countdownText = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`
					}
					
					this.$set(product, 'countdown', countdownText)
				}
				
				// 立即更新一次
				updateCountdown()
				
				// 设置定时器，每秒更新
				const timer = setInterval(updateCountdown, 1000)
				this.countdownTimers.push(timer)
			},

			onBannerClick(e) {
				if (e.link) {
					uni.navigateTo({
						url: e.link
					})
				}
			},

			goToAbout() {
				uni.navigateTo({
					url: '/pages/about/about'
				})
			},

			goToCollect() {
				uni.navigateTo({
					url: '/pages/collect/collect'
				})
			},

			goToCustomer() {
				uni.navigateTo({
					url: '/pages/customer/customer'
				})
			},

			goToSell() {
				uni.navigateTo({
					url: '/pages/sell/sell'
				})
			},

			goToProductDetail(id) {
				uni.navigateTo({
					url: `/pages/product/detail?id=${id}`
				})
			}
		}
	}
</script>

<style scoped>
	.home {
		background-color: #000000;
		min-height: 100vh;
		padding-bottom: 150rpx;
	}

	/* 轮播图 */
	.banner-section {
		height: 1240rpx;
		position: relative;
		background-color: #f5f5f5;
	}

	.banner-swiper {
		height: 100%;
	}

	.banner-item {
		position: relative;
		height: 100%;
	}

	.banner-image {
		width: 100%;
		height: 100%;
	}

	.nav-section {
		padding: 0 20rpx;
	}

	.nav-bar {
		background-color: #ffffff;
		border-radius: 10rpx;
		display: flex;
		justify-content: space-around;
		padding: 30rpx 0;
	}

	.nav-item {
		width: 40%;
		display: flex;
		flex-direction: column;
		flex-wrap: nowrap;
		align-items: center;
		color: #000000;
	}

	.nav-item-img {
		width: 70rpx;
		height: 70rpx;
	}

	.nav-item-image {
		width: 100%;
		height: auto;
	}

	.nav-item-title {
		font-size: 24rpx;
		font-weight: 600;
		padding: 10rpx 0;
	}

	.section-title {
		padding: 60rpx 0 20rpx 0;
	}

	.title-text {
		font-size: 36rpx;
		color: #ffffff;
		font-weight: bold;
	}

	.product-list {
		padding: 0 30rpx;
	}

	.product-hot {
		/* background-color: #f5f5f5; */
	}

	.product-hot-item {
		padding: 15rpx 0;
	}

	.product-hot-top {
		margin: auto;
		background-color: #ffffff;
		border-radius: 20rpx 20rpx 0 0;
		display: flex;
		flex-direction: row;
		flex-wrap: nowrap;
		padding: 10rpx 2% 30rpx 2%;
		margin: 0 46rpx;
	}

	.fontBold {
		font-weight: bold;
	}

	.product-hot-top-l {
		width: 40%;
		font-size: 16rpx;
	}

	.product-hot-top-c {
		width: 20%;
		height: 30rpx;
	}

	.product-hot-top-c .c-img {
		width: 100%;
		height: 30rpx;

	}

	.product-hot-top-r {
		width: 40%;
		font-size: 16rpx;
		text-align: right;
		display: flex;
		flex-direction: row;
		flex-wrap: nowrap;
		justify-content: flex-end;
	}

	.product-hot-top-t {
		padding-right: 10rpx;
	}

	.product-hot-top-logo {
		width: 40rpx;
		height: 40rpx;
		padding: 0 2rpx;
	}

	.product-hot-top-logo .l-img {
		width: 40rpx;
		height: 40rpx;
	}


	.product-hot-center {
		border: 1px solid #e5e5e5;
		background-color: #808080;
		padding: 0 30rpx;
		position: relative;
		margin: 0 12rpx;
	}

	.product-hot-img {
		background-color: #FFFFFF;
		padding: 0 10rpx;
	}

	.p-img {
		width: 100%;
		height: auto;
	}

	.product-hot-time {
		font-size: 30rpx;
		color: #ffffff;
		font-weight: bold;
		text-align: center;
		height: 60rpx;
		width: calc(100% - 60rpx);
		line-height: 60rpx;
		background-color: rgba(255, 0, 0, 0.5);
		position: absolute;
		bottom: 0;
	}

	.product-hot-bottom {
		width: calc(100% - 80rpx);
		background-color: #ffffff;
		border-radius: 12rpx;
		padding: 16rpx 40rpx;
	}

	.product-hot-buy-type {
		padding: 0 20rpx;
	}

	.product-hot-buy-type .buy-type-val1 {
		display: inline-block;
		width: 70rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #dc0000;
		color: #ffffff;
		font-size: 20rpx;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
	}

	.product-hot-buy-type .buy-type-val2 {
		display: inline-block;
		width: 70rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #1ccf00;
		color: #ffffff;
		font-size: 20rpx;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
	}

	.product-hot-buy-type .product-hot-sub {
		margin-left: 10rpx;
	}

	.product-hot-name {
		color: #000000;
		font-weight: bold;
		font-size: 38rpx;
		padding: 6rpx 0;
	}

	.product-hot-price {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		flex-direction: row;
		flex-wrap: nowrap;
	}

	.product-hot-price .price {
		color: #dc0000;
		font-size: 26rpx;
		font-weight: 500;
	}

	.product-hot-price .price .fontBold {
		font-size: 52rpx;
	}


	.product-hot-price .pay-price {
		width: 100%;
	}

	.product-hot-price .pay-price .pay-price-val {
		background-color: #dc0000;
		font-size: 18rpx;
		font-weight: 600;
		color: #ffffff;
		border-radius: 16rpx;
		padding: 4rpx 16rpx;
	}

	.product-hot-price .buy {
		background-color: #dc0000;
		padding: 10rpx 30rpx;
		color: #ffffff;
		font-weight: bold;
		border-radius: 40rpx;
	}


	.product-hot-center-k {
		height: 20rpx;
	}

	.product-recom {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.product-recom-item {
		width: 49%;
	}

	.product-recom-h {
		background-color: #ffffff;
		border-radius: 10rpx;
	}

	.product-recom-top {
		margin: auto;
		display: flex;
		justify-content: space-between;
		flex-direction: row;
		flex-wrap: nowrap;
		align-items: flex-start;
		padding: 5rpx 10rpx 15rpx 10rpx;
	}

	.product-recom-top-l {
		font-size: 8rpx;
	}

	.product-recom-top-c {
		width: 20%;
		height: 15rpx;
	}

	.product-recom-top-c .c-img {
		width: 100%;
		height: 15rpx;
	}

	.product-recom-top-r {
		font-size: 8rpx;
		text-align: right;
		display: flex;
		flex-direction: row;
		flex-wrap: nowrap;
		justify-content: flex-end;
	}

	.product-recom-top-t {
		padding-right: 5rpx;
	}

	.product-recom-top-logo {
		width: 20rpx;
		height: 20rpx;
		padding: 0 1rpx;
	}

	.product-recom-top-logo .l-img {
		width: 20rpx;
		height: 20rpx;
	}

	.product-recom-center {
		position: relative;
	}

	.product-recom-img {
		padding: 0 10rpx;
	}

	.product-recom-img .p-img {
		border-radius: 10rpx;
	}

	.product-recom-time {
		font-size: 15rpx;
		color: #ffffff;
		font-weight: bold;
		text-align: center;
		height: 30rpx;
		width: 100%;
		line-height: 30rpx;
		background-color: rgba(255, 0, 0, 0.5);
		position: absolute;
		bottom: 0;
	}

	.product-recom-bottom {
		width: 100%;
		padding: 20rpx 0;
	}

	.product-recom-buy-type {
		padding: 0 10rpx;
	}

	.product-recom-buy-type .buy-type-val1 {
		display: inline-block;
		width: 60rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #dc0000;
		color: #ffffff;
		font-size: 16rpx;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
	}

	.product-recom-buy-type .buy-type-val2 {
		display: inline-block;
		width: 60rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #1ccf00;
		color: #ffffff;
		font-size: 16rpx;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
	}

	.product-recom-name {
		color: #ffffff;
		font-weight: bold;
		font-size: 26rpx;
		padding: 6rpx 0;
	}

	.product-recom-price .price {
		width: 100%;
		color: #dc0000;
		font-size: 26rpx;
		font-weight: 500;
	}

	.product-recom-price .price .fontBold {
		font-size: 46rpx;
	}

	.product-recom-price .pay-price {
		width: 100%;
	}

	.product-recom-price .pay-price .pay-price-val {
		background-color: #dc0000;
		font-size: 18rpx;
		font-weight: 600;
		color: #ffffff;
		border-radius: 16rpx;
		padding: 4rpx 16rpx;
	}
</style>