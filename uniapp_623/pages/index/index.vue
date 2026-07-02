<template>
	<view class="home">
		<view class="home-glow glow-left"></view>
		<view class="home-glow glow-right"></view>

		<view class="page-shell" :style="{ paddingTop: `${statusBarHeight + 12}px` }">
			<view class="topbar">
				<view class="brand-block">
					<image class="brand-icon" src="/static/image/logo1.png" mode="aspectFit"></image>
					<view class="brand-copy">
						<text class="brand-en">GOOMOO PLAY</text>
						<text class="brand-cn">橘猫智酷</text>
					</view>
				</view>
			</view>

			<view class="hero-card">
				<image class="hero-background" :src="heroBannerImage" mode="widthFix"></image>
			</view>

			<view class="quick-grid">
				<view
					v-for="item in quickActions"
					:key="item.title"
					class="quick-card"
					@click="goToPage(item.url)"
				>
					<image class="quick-icon" :src="item.icon" mode="aspectFit"></image>
					<text class="quick-title">{{ item.title }}</text>
					<text class="quick-desc">{{ item.desc }}</text>
				</view>
			</view>

			<view class="section-head">
				<view class="section-copy">
					<text class="section-title">精选推荐</text>
					<text class="section-subtitle">人气娃衣精选</text>
				</view>
				<view class="section-link" @click="goToSell">
					<text>查看全部</text>
				</view>
			</view>

			<view class="product-grid">
				<view
					v-for="(product, index) in displayProducts"
					:key="product.id || index"
					class="product-card"
					@click="goToProductDetail(product.id)"
				>
					<view class="product-image-wrap">
						<image class="product-image" :src="getProductImage(product)" mode="aspectFill"></image>
						<text :class="['product-tag', index % 2 === 0 ? 'product-tag-dark' : 'product-tag-light']">
							{{ getProductTag(product, index) }}
						</text>
						<view class="product-countdown" v-if="product.type == 2 && product.countdown">
							剩余 {{ product.countdown }}
						</view>
					</view>
					<view class="product-body">
						<text class="product-name">{{ product.title || '新品系列' }}</text>
						<text class="product-desc">{{ getProductDescription(product) }}</text>
						<view class="product-meta">
							<text class="product-price">{{ getProductPrice(product) }}</text>
							<view class="product-heat">
								<text class="product-heat-icon">♨</text>
								<text>{{ getProductHeat(index) }}</text>
							</view>
							<view
								class="product-cart"
								:class="{ 'product-cart--active': isProductInCart(product) }"
								@click.stop="handleAddToCart(product)"
							>
								<image class="product-cart-icon" src="/static/image/icon_shop.png" mode="aspectFit"></image>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view class="empty-state" v-if="!displayProducts.length">
				<image class="empty-image" src="/static/image/no-data.png" mode="aspectFit"></image>
				<text class="empty-title">首页素材准备中</text>
				<text class="empty-desc">商品上架后会在这里展示</text>
			</view>
		</view>
	</view>
</template>

<script>
import { api } from '@/utils/request.js'

export default {
	data() {
		return {
			statusBarHeight: 44,
			bannerList: [],
			productListHot: [],
			productListRecom: [],
			cartProductMap: {},
			countdownTimers: [],
			placeholderImage: '/static/image/600_694.png',
			quickActions: [
				{
					title: '品牌介绍',
					desc: '了解我们',
					icon: '/static/image/icon_brand.png',
					url: '/pages/about/about'
				},
				{
					title: '线上收藏卡',
					desc: '收集专属回忆',
					icon: '/static/image/icon_collect.png',
					url: '/pages/collect/collect'
				},
				{
					title: '联系客服',
					desc: '贴心为您服务',
					icon: '/static/image/icon_customer.png',
					url: '/pages/customer/customer'
				},
				{
					title: '特别贩售',
					desc: '限时发售中',
					icon: '/static/image/icon_shop.png',
					url: '/pages/sell/sell'
				}
			]
		}
	},

	computed: {
		featuredProduct() {
			return this.productListHot[0] || this.productListRecom[0] || null
		},
		heroProductImage() {
			return this.getProductImage(this.featuredProduct)
		},
		heroBannerImage() {
			return (this.bannerList[0] && this.bannerList[0].image) || this.heroProductImage
		},
		heroSubtitle() {
			if (this.featuredProduct && this.featuredProduct.subtitle) {
				return this.featuredProduct.subtitle
			}
			return '萌力全开 · 猫系穿搭系列'
		},
		heroDotCount() {
			const count = this.bannerList.length || 3
			return Math.min(count, 3)
		},
		displayProducts() {
			const merged = [...this.productListHot, ...this.productListRecom]
			const seen = new Set()
			return merged.filter((item) => {
				const key = item.id || `${item.title}-${item.price}`
				if (seen.has(key)) {
					return false
				}
				seen.add(key)
				return true
			}).slice(0, 4)
		}
	},

	onLoad() {
		const systemInfo = uni.getSystemInfoSync ? uni.getSystemInfoSync() : {}
		this.statusBarHeight = systemInfo.statusBarHeight || 22
		this.loadInfo()
	},

	onUnload() {
		this.countdownTimers.forEach((timer) => {
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
			return Promise.allSettled([
				this.getBannerList(),
				this.getProductList(),
				this.getHomeCartState()
			])
		},

		async getBannerList() {
			try {
				const response = await api.banner.list()
				this.bannerList = response.data || []
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

				this.countdownTimers.forEach((timer) => {
					if (timer) {
						clearInterval(timer)
					}
				})
				this.countdownTimers = []
				this.startCountdowns()
			} catch (error) {
				console.error('getProductList error', error)
			}
		},

		async getHomeCartState() {
			const token = uni.getStorageSync('token')
			if (!token) {
				this.cartProductMap = {}
				return
			}

			try {
				const response = await api.cart.list()
				const nextMap = {}
				const list = Array.isArray(response.data) ? response.data : []
				list.forEach((item) => {
					const key = this.getCartKey(item.productId, item.version)
					nextMap[key] = {
						id: item.id,
						productId: item.productId,
						version: item.version,
						quantity: item.quantity
					}
				})
				this.cartProductMap = nextMap
			} catch (error) {
				this.cartProductMap = {}
			}
		},

		startCountdowns() {
			this.productListHot.forEach((product, index) => {
				if (product.type == 2 && product.endTimeStamp) {
					this.startCountdown(product, index)
				}
			})

			this.productListRecom.forEach((product, index) => {
				if (product.type == 2 && product.endTimeStamp) {
					this.startCountdown(product, index + this.productListHot.length)
				}
			})
		},

		startCountdown(product) {
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
				const pad = (value) => (value < 10 ? `0${value}` : value)

				const countdownText = days > 0
					? `${days}天 ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`
					: `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`

				this.$set(product, 'countdown', countdownText)
			}

			updateCountdown()
			const timer = setInterval(updateCountdown, 1000)
			this.countdownTimers.push(timer)
		},

		getProductImage(product) {
			if (product && product.image && product.image.length && product.image[0]) {
				return product.image[0]
			}
			return this.placeholderImage
		},

		getProductTag(product, index) {
			if (product && product.type == 2) {
				return '预售'
			}
			const tags = ['新品', '热门', '推荐', '精选']
			return tags[index % tags.length]
		},

		getProductDescription(product) {
			if (product && product.subtitle) {
				return product.subtitle
			}
			if (product && product.type == 2) {
				return '限量预定款'
			}
			return '软萌可爱系列'
		},

		getProductPrice(product) {
			if (!product) {
				return '¥0.00'
			}
			if (product.type == 2 && product.deposit) {
				return `定金 ¥${product.deposit}`
			}
			return `¥${Number(product.price || 0).toFixed(2)}`
		},

		getProductHeat(index) {
			return `${(2.4 + index * 0.7).toFixed(1)}k`
		},

		getDefaultVersion(product) {
			if (!product) {
				return '默认规格'
			}
			if (Array.isArray(product.version) && product.version.length) {
				return product.version[0] || '默认规格'
			}
			if (typeof product.version === 'string' && product.version.trim()) {
				return product.version.trim()
			}
			return '默认规格'
		},

		getCartKey(productId, version) {
			return `${productId || ''}::${String(version || '默认规格').trim() || '默认规格'}`
		},

		isProductInCart(product) {
			if (!product || !product.id) {
				return false
			}
			return Object.values(this.cartProductMap).some((entry) => {
				return String(entry.productId) === String(product.id)
			})
		},

		getCartEntry(product) {
			if (!product || !product.id) {
				return null
			}
			const defaultKey = this.getCartKey(product.id, this.getDefaultVersion(product))
			if (this.cartProductMap[defaultKey]) {
				return this.cartProductMap[defaultKey]
			}

			return (
				Object.values(this.cartProductMap).find((entry) => {
					return String(entry.productId) === String(product.id)
				}) || null
			)
		},

		handleHeroClick() {
			if (this.featuredProduct && this.featuredProduct.id) {
				this.goToProductDetail(this.featuredProduct.id)
				return
			}
			this.goToSell()
		},

		goLogin() {
			uni.showModal({
				content: '使用当前功能需要您进行登录，是否去登录?',
				success: function(res) {
					if (res.confirm) {
						uni.navigateTo({
							url: '/pages/login/login'
						})
					}
				}
			})
		},

		async handleAddToCart(product) {
			const token = uni.getStorageSync('token')
			if (!token) {
				this.goLogin()
				return
			}

			if (!product || !product.id) {
				uni.showToast({
					title: '商品信息缺失',
					icon: 'none'
				})
				return
			}

			const version = this.getDefaultVersion(product)
			try {
				const cartEntry = this.getCartEntry(product)
				if (cartEntry?.id) {
					const cancelResponse = await api.cart.cancel({ id: cartEntry.id })
					if (cancelResponse.code !== 200) {
						uni.showToast({
							title: cancelResponse.msg || '取消加入失败',
							icon: 'none'
						})
						return
					}

					const cartKey = this.getCartKey(product.id, version)
					const nextMap = { ...this.cartProductMap }
					delete nextMap[cartKey]
					this.cartProductMap = nextMap

					uni.showToast({
						title: cancelResponse.msg || '已移出购物车',
						icon: 'success'
					})
					return
				}

				const response = await api.cart.create({
					productId: product.id,
					version,
					quantity: 1
				})

				if (response.code !== 200) {
					uni.showToast({
						title: response.msg || '加入购物车失败',
						icon: 'none'
					})
					return
				}

				const cartKey = this.getCartKey(product.id, version)
				this.cartProductMap = {
					...this.cartProductMap,
					[cartKey]: {
						id: null,
						productId: product.id,
						version,
						quantity: 1
					}
				}

				await this.getHomeCartState()

				uni.showToast({
					title: response.msg || '加入购物车成功',
					icon: 'success'
				})
			} catch (error) {
				uni.showToast({
					title: '加入购物车失败',
					icon: 'none'
				})
			}
		},

		goToPage(url) {
			uni.navigateTo({ url })
		},

		goToSell() {
			uni.navigateTo({
				url: '/pages/sell/sell'
			})
		},

		goToProductDetail(id) {
			if (!id) {
				return
			}
			uni.navigateTo({
				url: `/pages/product/detail?id=${id}`
			})
		}
	}
}
</script>

<style scoped>
.home {
	min-height: 100vh;
	background:
		radial-gradient(circle at top left, rgba(255, 192, 108, 0.26), transparent 28%),
		radial-gradient(circle at top right, rgba(255, 227, 181, 0.32), transparent 24%),
		linear-gradient(180deg, #fffaf2 0%, #fffefb 35%, #ffffff 100%);
	position: relative;
	overflow: hidden;
}

.home-glow {
	position: absolute;
	border-radius: 999rpx;
	filter: blur(50rpx);
	opacity: 0.45;
	pointer-events: none;
}

.glow-left {
	width: 240rpx;
	height: 240rpx;
	background: rgba(255, 186, 85, 0.35);
	top: 100rpx;
	left: -60rpx;
}

.glow-right {
	width: 220rpx;
	height: 220rpx;
	background: rgba(255, 221, 169, 0.45);
	top: 260rpx;
	right: -50rpx;
}

.page-shell {
	position: relative;
	z-index: 1;
	padding: 0 24rpx 44rpx;
}

.topbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 26rpx;
}

.brand-block {
	display: flex;
	align-items: center;
}

.brand-icon {
	width: 76rpx;
	height: 76rpx;
	margin-right: 16rpx;
}

.brand-copy {
	display: flex;
	flex-direction: column;
}

.brand-en {
	font-size: 22rpx;
	line-height: 1.1;
	font-weight: 700;
	color: #141414;
	letter-spacing: 2rpx;
}

.brand-cn {
	font-size: 50rpx;
	line-height: 1;
	font-weight: 900;
	color: #161616;
}

.hero-card {
	position: relative;
	width: 100%;
	border-radius: 34rpx;
	overflow: hidden;
	box-shadow: 0 26rpx 60rpx rgba(232, 176, 93, 0.22);
	margin-bottom: 26rpx;
}

.hero-background {
	display: block;
	width: 100%;
	height: auto;
}

.hero-action {
	position: absolute;
	left: 46rpx;
	bottom: 34rpx;
	z-index: 2;
}

.hero-cta {
	min-width: 220rpx;
	height: 68rpx;
	padding: 0 24rpx 0 30rpx;
	border-radius: 999rpx;
	background: linear-gradient(135deg, #ff9f1f 0%, #ff8400 100%);
	box-shadow: 0 18rpx 30rpx rgba(255, 145, 35, 0.28);
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.hero-cta-text,
.hero-cta-arrow {
	color: #ffffff;
	font-weight: 800;
}

.hero-cta-text {
	font-size: 34rpx;
}

.hero-cta-arrow {
	width: 34rpx;
	height: 34rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.18);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24rpx;
}

.quick-grid {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 16rpx;
	margin-bottom: 34rpx;
}

.quick-card {
	background: rgba(255, 255, 255, 0.82);
	border-radius: 28rpx;
	padding: 26rpx 12rpx 22rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	box-shadow: 0 18rpx 36rpx rgba(228, 208, 175, 0.18);
}

.quick-icon {
	width: 62rpx;
	height: 62rpx;
	margin-bottom: 18rpx;
}

.quick-title {
	font-size: 28rpx;
	line-height: 1.2;
	color: #161616;
	font-weight: 800;
	text-align: center;
	margin-bottom: 8rpx;
}

.quick-desc {
	font-size: 22rpx;
	line-height: 1.3;
	color: #9b9b9b;
	text-align: center;
}

.section-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 22rpx;
}

.section-copy {
	display: flex;
	align-items: baseline;
}

.section-title {
	font-size: 42rpx;
	line-height: 1;
	font-weight: 900;
	color: #101010;
	margin-right: 14rpx;
}

.section-subtitle {
	font-size: 22rpx;
	color: #bababa;
}

.section-link {
	display: flex;
	align-items: center;
	font-size: 28rpx;
	font-weight: 700;
	color: #ff931a;
}

.section-link-arrow {
	margin-left: 8rpx;
	font-size: 24rpx;
}

.product-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 20rpx;
}

.product-card {
	overflow: hidden;
	border-radius: 30rpx;
	background: rgba(255, 255, 255, 0.92);
	box-shadow: 0 18rpx 46rpx rgba(228, 212, 190, 0.24);
}

.product-image-wrap {
	position: relative;
	height: 340rpx;
	background: linear-gradient(180deg, #fff4e2 0%, #f7efe6 100%);
}

.product-image {
	width: 100%;
	height: 100%;
}

.product-tag {
	position: absolute;
	left: 16rpx;
	top: 16rpx;
	padding: 8rpx 16rpx;
	border-radius: 14rpx;
	font-size: 24rpx;
	line-height: 1;
	color: #ffffff;
	font-weight: 800;
}

.product-tag-dark {
	background: rgba(0, 0, 0, 0.78);
}

.product-tag-light {
	background: #ff8f17;
}

.product-heart {
	position: absolute;
	right: 18rpx;
	top: 16rpx;
	width: 44rpx;
	height: 44rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #ffffff;
	font-size: 34rpx;
	text-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.2);
}

.product-countdown {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 12rpx 16rpx;
	font-size: 22rpx;
	color: #ffffff;
	text-align: center;
	background: linear-gradient(180deg, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.55));
}

.product-body {
	padding: 22rpx 20rpx 22rpx;
}

.product-name {
	display: block;
	font-size: 36rpx;
	line-height: 1.25;
	color: #171717;
	font-weight: 800;
	margin-bottom: 10rpx;
}

.product-desc {
	display: block;
	font-size: 26rpx;
	line-height: 1.3;
	color: #8f8f8f;
	margin-bottom: 20rpx;
	min-height: 68rpx;
}

.product-meta {
	display: flex;
	align-items: center;
}

.product-price {
	font-size: 28rpx;
	line-height: 1;
	color: #ff8b08;
	font-weight: 900;
}

.product-heat {
	display: flex;
	align-items: center;
	margin-left: 18rpx;
	font-size: 24rpx;
	color: #afafaf;
}

.product-heat-icon {
	font-size: 24rpx;
	margin-right: 6rpx;
}

.product-cart {
	margin-left: auto;
	width: 58rpx;
	height: 58rpx;
	border-radius: 50%;
	background: #fff7ed;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2rpx solid rgba(255, 166, 37, 0.12);
	box-sizing: border-box;
	transition: background 180ms ease, box-shadow 180ms ease, transform 180ms ease;
}

.product-cart--active {
	background: linear-gradient(135deg, #ff9b25 0%, #ff8308 100%);
	box-shadow: 0 10rpx 22rpx rgba(255, 145, 35, 0.22);
	border-color: transparent;
	transform: translateY(-1rpx);
}

.product-cart-icon {
	width: 30rpx;
	height: 30rpx;
	transition: filter 180ms ease, opacity 180ms ease;
}

.product-cart--active .product-cart-icon {
	filter: brightness(0) invert(1);
}

.empty-state {
	margin-top: 28rpx;
	padding: 48rpx 24rpx 56rpx;
	border-radius: 30rpx;
	background: rgba(255, 255, 255, 0.88);
	display: flex;
	flex-direction: column;
	align-items: center;
	box-shadow: 0 18rpx 46rpx rgba(228, 212, 190, 0.2);
}

.empty-image {
	width: 200rpx;
	height: 200rpx;
	margin-bottom: 18rpx;
}

.empty-title {
	font-size: 30rpx;
	font-weight: 800;
	color: #222222;
	margin-bottom: 8rpx;
}

.empty-desc {
	font-size: 24rpx;
	color: #9c9c9c;
}
</style>
