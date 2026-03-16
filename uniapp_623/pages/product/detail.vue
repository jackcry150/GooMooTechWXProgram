<template>
	<view class="product-detail">
		<!-- 商品图片轮播 -->
		<view class="product-images">
			<swiper class="swiper" indicator-dots="true" autoplay="true" interval="3000" duration="500">
				<swiper-item v-for="(image, index) in product.image" :key="index">
					<image :src="image" mode="widthFix" class="product-image" />
				</swiper-item>
			</swiper>
			<view class="preorder-badge" v-if="product.type == 2">预定开启!</view>
		</view>

		<!-- 价格信息 -->
		<view class="price-section" v-if="product.type == 2">
			<view class="price-info">
				<text class="deposit-price">定金¥<text class="deposit-price-val">{{ product.deposit }}</text></text>
				<text class="total-price">商品总价¥{{ product.price }}</text>
			</view>
			<view class="countdown" v-if="product.endTimeStamp">
				<text class="countdown-text">预定时间剩余</text>
				<text class="countdown-time">{{ countdown }}</text>
			</view>
		</view>

		<!-- 商品信息 -->
		<view class="product-info">
			<view class="discount-info" v-if="product.deduct > 0" @tap="showDeductPopup">
				<text class="discount-text">可使用蜗壳抵扣，抵后到手价¥{{ product.price - product.deduct }}</text>
				<text class="right-arrow"></text>
			</view>

			<view class="product-price" v-if="product.type == 1">
				<view class="price-deposit">￥<text class="fontBold">{{ product.price }}</text></view>
			</view>

			<view class="product-title">
				<view class="title">
					<text class="buy-type-val1" v-if="product.type == 1">现货</text>
					<text class="buy-type-val2" v-else-if="product.type == 2">预售</text>
					{{ product.subtitle }} {{ product.title }}
				</view>
			</view>
		</view>

		<!-- 产品规格信息 -->
		<view class="product-specs" v-if="hasProductSpecs">
			<view class="spec-item" v-if="product.proportion">
				<text class="spec-label">比例：</text>
				<view class="spec-value-wrapper" @tap="showSpecDetailPopup('proportion', product.proportion)">
					<text class="spec-value" :class="{ 'spec-truncate': product.proportion.length > 20 }">{{ product.proportion }}</text>
					<text class="spec-more" v-if="product.proportion.length > 20">查看详情</text>
				</view>
			</view>
			<view class="spec-item" v-if="product.dimensions">
				<text class="spec-label">尺寸：</text>
				<view class="spec-value-wrapper" @tap="showSpecDetailPopup('dimensions', product.dimensions)">
					<text class="spec-value" :class="{ 'spec-truncate': product.dimensions.length > 20 }">{{ product.dimensions }}</text>
					<text class="spec-more" v-if="product.dimensions.length > 20">查看详情</text>
				</view>
			</view>
			<view class="spec-item" v-if="product.material">
				<text class="spec-label">材质：</text>
				<view class="spec-value-wrapper" @tap="showSpecDetailPopup('material', product.material)">
					<text class="spec-value" :class="{ 'spec-truncate': product.material.length > 20 }">{{ product.material }}</text>
					<text class="spec-more" v-if="product.material.length > 20">查看详情</text>
				</view>
			</view>
			<view class="spec-item" v-if="product.copyright">
				<text class="spec-label">版权所属：</text>
				<view class="spec-value-wrapper" @tap="showSpecDetailPopup('copyright', product.copyright)">
					<text class="spec-value" :class="{ 'spec-truncate': product.copyright.length > 20 }">{{ product.copyright }}</text>
					<text class="spec-more" v-if="product.copyright.length > 20">查看详情</text>
				</view>
			</view>
		</view>

		<!-- 规格详情弹窗 -->
		<view class="cart-popup" v-if="showSpecDetail">
			<view class="mask" @tap="hideSpecDetail"></view>
			<view class="popup-content-spec">
				<view class="spec-detail-title">{{ getSpecLabel(currentSpecType) }}</view>
				<view class="spec-detail-content">{{ currentSpecValue }}</view>
				<view class="spec-detail-btn" @tap="hideSpecDetail">关闭</view>
			</view>
		</view>

		<!-- 预售时间信息 -->
		<view class="presale-timeline" v-if="product.type == 2 && product.startTime && product.endTime">
			<!-- 头部横幅 -->
			<view class="presale-header">
				<view class="presale-header-icon">✓</view>
				<text class="presale-header-text">官方预定·优先发货·售后无忧</text>
			</view>
			<!-- 主体内容 -->
			<view class="presale-body">
				<view class="presale-time-left">
					<text class="presale-start-text">{{ formatPresaleDate(product.startTime) }} 开始预定</text>
					<text class="presale-end-text">{{ formatPresaleEndDate(product.endTime) }}前 补款发货</text>
				</view>
				<!-- 时间轴 -->
				<view class="presale-timeline-line">
					<view class="timeline-dot-start"></view>
					<view class="timeline-progress" :style="{ width: timelineProgress + '%' }"></view>
					<view class="timeline-dot-end"></view>
				</view>
			</view>
		</view>

		<!-- 标签页 -->
		<view class="tabs">
			<view class="tab-item" :class="contentTab == 1 ? 'active' : '' " @click="switchTab(1)">商品详情</view>
			<view class="tab-item" :class="contentTab == 2 ? 'active' : '' " @click="switchTab(2)">购买须知</view>
		</view>

		<view class="content">
			<view class="contentDe" v-if="contentTab == 1">
				<image v-for="(image, index) in product.content" :key="index" :src="image" class="c-img" mode="widthFix"></image>
			</view>
			<view class="contentDe" v-if="contentTab == 2">
				<image v-for="(image, index) in product.purchaseNotice" :key="index" :src="image" class="c-img" mode="widthFix"></image>
			</view>
		</view>

		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<view class="action-buttons">
				<view class="action-btn">
					<button class="action-btn-shar" open-type="share">
						<view class="action-icon">
							<image class="action-icon-image" src="/static/image/icon_share.png" mode="widthFix"></image>
						</view>
						<text class="action-text">分享</text>
					</button>
				</view>

				<view class="action-btn" @click="goToCustomer">
					<view class="action-icon">
						<image class="action-icon-image" src="/static/image/icon_service2.png" mode="widthFix"></image>
					</view>
					<text class="action-text">客服</text>
				</view>
				<view class="action-btn" @click="goToCollect">
					<view class="action-icon">
						<image class="action-icon-image" src="/static/image/icon_collect_btn_select.png" mode="widthFix" v-if="productCollect === 1"></image>
						<image class="action-icon-image" src="/static/image/icon_collect_btn.png" mode="widthFix" v-else></image>
					</view>
					<text class="action-text">收藏</text>
				</view>
				<view class="action-btn add-cart" @click="goToCart">
					<view class="action-icon">
						<image class="action-icon-image" src="/static/image/icon_shop.png" mode="widthFix"></image>
					</view>
					<text class="action-text">购物车</text>
					<text class="cart-number" v-if="cartNumbers > 0">{{ cartNumbers }}</text>
				</view>
			</view>
			<!-- 普通商品：加入购物车 + 立即购买 -->
			<view class="order-type-btn" v-if="product.type != 2">
				<view class="order-btn" style="margin-right: 10rpx;" @click="showCartPopup(1)">
					<text>加入购物车</text>
				</view>
				<view class="order-btn" @click="showCartPopup(2)">
					<text>立即购买</text>
				</view>
			</view>
			<!-- 预售商品：立即预约 -->
			<view class="order-type-btn" v-if="product.type == 2">
				<view class="order-btn presale-btn" @click="handlePresaleOrder">
					<text>立即预约</text>
				</view>
			</view>
		</view>

		<view class="cart-popup" v-if="showDeduct">
			<!-- 遮罩层 -->
			<view class="mask"></view>
			<!-- 弹窗内容 -->
			<view class="popup-content-deduct">
				<view class="deduct-title">最多可使用蜗壳抵扣￥{{ product.deduct }}</view>
				<view class="deduct-desc">*仅限在购买现货或支付尾款时使用</view>
				<view class="deduct-btn" @tap="hideDeductPopup">知道了</view>
			</view>
		</view>

		<!-- 加入购物车弹窗 -->
		<view v-if="showPopup" class="cart-popup">
			<!-- 遮罩层 -->
			<view class="mask" @tap="hideCartPopup"></view>

			<!-- 弹窗内容 -->
			<view class="popup-content">
				<!-- 商品信息 -->
				<view class="goods-info">
					<image :src="product.image[0]" class="goods-img" mode="aspectFit"></image>
					<view class="goods-text">
						<view class="goods-name">{{ product.subtitle }} {{ product.title }}</view>
						<view class="goods-price" v-if="product.type == 2">定金：¥{{ product.deposit }}</view>
						<view class="goods-price" v-else>¥{{ product.price }}</view>
					</view>
				</view>

				<!-- 规格选择 -->
				<view class="specification">
					<view class="spec-title">版本</view>
					<view class="spec-list">
						<view v-for="(spec, index) in product.version" :key="index" :class="['spec-item', currentVersion === index ? 'active' : '']" @tap="selectSpec(index)">
							{{ spec }}
						</view>
					</view>
				</view>

				<!-- 数量选择 -->
				<view class="quantity">
					<view class="quantity-title">数量</view>
					<view class="quantity-control">
						<view class="minus" @tap="decreaseQuantity" :class="quantity <= 1 ? 'disabled' : ''">-</view>
						<view class="num">{{ quantity }}</view>
						<view class="plus" @tap="increaseQuantity">+</view>
					</view>
				</view>

				<view class="add-cart-btn" @tap="addToOrder">
					<text v-if="orderType == 1">加入购物车</text>
					<text v-else-if="orderType == 2 && product.type == 2">立即预约</text>
					<text v-else-if="orderType == 2">立即购买</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'ProductDetail',
		data() {
			return {
				product: [],
				config: [],
				countdown: '--',
				countdownTimer: null,
				contentTab: 1,
				showDeduct: false,
				showPopup: false,
				currentVersion: 0,
				quantity: 1,
				orderType: 1,
				cartNumbers: 0,
				productCollect: 0,
				specExpanded: {
					proportion: false,
					dimensions: false,
					material: false,
					copyright: false
				},
				showSpecDetail: false,
				currentSpecType: '',
				currentSpecValue: ''
			}
		},
		computed: {
			hasProductSpecs() {
				return !!(this.product.proportion || this.product.dimensions || this.product.material || this.product.copyright)
			},
			// 计算时间轴进度百分比
			timelineProgress() {
				if (!this.product.startTimeStamp || !this.product.endTimeStamp) {
					return 0
				}
				const now = Math.floor(Date.now() / 1000)
				const start = this.product.startTimeStamp
				const end = this.product.endTimeStamp

				if (now < start) {
					return 0
				}
				if (now >= end) {
					return 100
				}

				const total = end - start
				const passed = now - start
				const percentage = (passed / total) * 100
				return Math.min(100, Math.max(0, percentage))
			}
		},
		onLoad(options) {
			if (options.id) {
				this.getProductDetail(options.id)
				this.getCartCount(options.id)
				this.getConfig()
			} else {
				uni.redirectTo({
					url: '/pages/index/index'
				})
			}
		},
		watch: {
			// 监听数量变化，确保不超过库存和限购
			quantity(newVal) {
				if (this.product.id) {
					const maxAllowed = this.getMaxAllowedQuantity()
					if (newVal > maxAllowed) {
						this.$nextTick(() => {
							this.quantity = maxAllowed
							uni.showToast({
								title: `最多可购买${maxAllowed}件`,
								icon: 'none'
							})
						})
					}
				}
			}
		},
		onUnload() {
			// 页面卸载时清除倒计时
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer)
				this.countdownTimer = null
			}
		},
		methods: {
			async getProductDetail(id) {
				try {
					const params = {
						id: id
					}
					const response = await api.product.detail(params)
					this.product = response.data
					// 如果是预售商品，启动倒计时
					if (this.product.type == 2 && this.product.endTimeStamp) {
						this.startCountdown()
					}
				} catch (error) {
					uni.showToast({
						title: '加载失败',
						icon: 'none'
					})
				}
			},

			// 开始倒计时
			startCountdown() {
				if (this.countdownTimer) {
					clearInterval(this.countdownTimer)
				}

				const updateCountdown = () => {
					if (!this.product.endTimeStamp) {
						this.countdown = '--'
						return
					}

					// 结束时间戳（秒）
					const endTime = this.product.endTimeStamp
					// 当前时间戳（秒）
					const now = Math.floor(Date.now() / 1000)
					// 剩余时间（秒）
					const remaining = endTime - now

					if (remaining <= 0) {
						this.countdown = '已结束'
						clearInterval(this.countdownTimer)
						this.countdownTimer = null
						return
					}

					// 计算天、时、分、秒
					const days = Math.floor(remaining / 86400)
					const hours = Math.floor((remaining % 86400) / 3600)
					const minutes = Math.floor((remaining % 3600) / 60)
					const seconds = remaining % 60

					// 补零函数（兼容性处理）
					const pad = (num) => {
						return num < 10 ? '0' + num : String(num)
					}

					// 格式化显示
					if (days > 0) {
						this.countdown = `${days}天 ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`
					} else {
						this.countdown = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`
					}
				}

				// 立即执行一次
				updateCountdown()
				// 每秒更新一次
				this.countdownTimer = setInterval(updateCountdown, 1000)
			},

			async getConfig() {
				try {
					const response = await api.setting.info()
					this.config = response.data
				} catch (error) {

				}
			},

			onShareAppMessage() {
				return {
					title: this.product.title + ' ' + this.product.subtitle,
					path: '/pages/product/detail?id=' + this.product.id,
					imageUrl: this.product.image[0]
				}
			},

			onShareTimeline() {
				return {
					title: this.product.title + ' ' + this.product.subtitle,
					path: 'id=' + this.product.id,
					imageUrl: this.product.image[0]
				}
			},

			async getCartCount(id) {
				const token = uni.getStorageSync('token')
				if (token) {
					try {
						const params = { id: id }
						const response = await api.cart.count(params)
						this.cartNumbers = response.data.count
						this.productCollect = response.data.collect
					} catch (error) {
						// 静默失败，不影响主要功能
					}
				}
			},

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
							return
						}
					}
				})
			},

			goToCustomer() {
				// #ifdef MP-WEIXIN
				if (this.config) {
					wx.openCustomerServiceChat({
						extInfo: {
							url: this.config.customerLink,
						},
						corpId: this.config.corpId,
						success: (res) => {
							console.log(" openCustomerServiceChat success:" + JSON.stringify(res));
						},
						fail: (err) => {
							console.log(" openCustomerServiceChat fail:" + JSON.stringify(err));
						}
					})
				}
				// #endif
			},

			async goToCollect() {
				try {
					const params = {
						id: this.product.id,
						collect: this.productCollect
					}
					const response = await api.collect.edit(params)
					uni.showToast({
						title: response.msg,
						icon: 'success'
					})
					this.productCollect = response.data
				} catch (error) {
					uni.showToast({
						title: '操作失败',
						icon: 'none'
					})
				}
			},

			goToCart() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: '/pages/cart/cart'
					})
				}
			},

			showDeductPopup() {
				this.showDeduct = true
			},

			hideDeductPopup() {
				this.showDeduct = false
			},

			showSpecDetailPopup(type, value) {
				// 如果内容超过20个字符，才显示弹窗，否则直接显示完整内容
				if (value && value.length > 20) {
					this.currentSpecType = type
					this.currentSpecValue = value
					this.showSpecDetail = true
				}
			},

			hideSpecDetail() {
				this.showSpecDetail = false
				this.currentSpecType = ''
				this.currentSpecValue = ''
			},

			getSpecLabel(type) {
				const labels = {
					proportion: '比例',
					dimensions: '尺寸',
					material: '材质',
					copyright: '版权所属'
				}
				return labels[type] || ''
			},

			// 格式化预售开始日期：2026.01.15
			formatPresaleDate(dateStr) {
				if (!dateStr) return ''
				const date = new Date(dateStr.replace(/-/g, '/'))
				const year = date.getFullYear()
				const month = String(date.getMonth() + 1).padStart(2, '0')
				const day = String(date.getDate()).padStart(2, '0')
				return `${year}.${month}.${day}`
			},

			// 格式化预售结束日期：2026年9月30日
			formatPresaleEndDate(dateStr) {
				if (!dateStr) return ''
				const date = new Date(dateStr.replace(/-/g, '/'))
				const year = date.getFullYear()
				const month = date.getMonth() + 1
				const day = date.getDate()
				return `${year}年${month}月${day}日`
			},

			hideDeductPopup() {
				this.showDeduct = false
			},

			//详情切换
			switchTab(tabKey) {
				this.contentTab = tabKey
			},

			// 显示弹窗
			showCartPopup(type) {
				this.showPopup = true;
				this.orderType = type
			},

			// 处理预售订单（立即预约）
			handlePresaleOrder() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
					return
				}
				// 预售商品也需要选择规格和数量，所以也显示弹窗
				this.showCartPopup(2) // 使用立即购买的逻辑（type=2）
			},

			// 隐藏弹窗
			hideCartPopup() {
				this.showPopup = false;
				// document.body.classList.remove('modal-open');
				// 恢复背景滚动
				// uni.setPageStyle({
				// 	overflow: 'auto'
				// });
			},

			// 选择规格
			selectSpec(index) {
				this.currentVersion = index;
			},

			// 减少数量
			decreaseQuantity() {
				if (this.quantity > 1) {
					this.quantity--;
				}
			},

			// 增加数量
			increaseQuantity() {
				// 检查库存
				if (this.product.type != 2 && this.product.stock > 0) {
					// 非预售商品检查库存
					if (this.quantity >= this.product.stock) {
						uni.showToast({
							title: '库存不足',
							icon: 'none'
						})
						return
					}
				}
				// 检查限购
				if (this.product.limitStock > 0) {
					const maxAllowed = this.getMaxAllowedQuantity()
					if (this.quantity >= maxAllowed) {
						uni.showToast({
							title: `限购${this.product.limitStock}件`,
							icon: 'none'
						})
						return
					}
				}
				this.quantity++
			},

			// 获取最大允许购买数量
			getMaxAllowedQuantity() {
				if (this.product.limitStock <= 0) {
					// 不限购，返回库存或999
					return this.product.type == 2 ? 999 : (this.product.stock || 999)
				}
				// 有限购，返回限购数量
				return this.product.limitStock
			},

			// 检查库存和限购
			checkStockAndLimit() {
				// 预售商品不检查库存
				if (this.product.type != 2) {
					if (this.product.stock <= 0) {
						uni.showToast({
							title: '商品已售罄',
							icon: 'none'
						})
						return false
					}
					if (this.quantity > this.product.stock) {
						uni.showToast({
							title: `库存不足，最多可购买${this.product.stock}件`,
							icon: 'none'
						})
						return false
					}
				}

				// 检查限购
				if (this.product.limitStock > 0) {
					if (this.quantity > this.product.limitStock) {
						uni.showToast({
							title: `限购${this.product.limitStock}件，您已超过限购数量`,
							icon: 'none'
						})
						return false
					}
				}

				return true
			},

			async addToOrder() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
					return
				}

				// 检查库存和限购
				if (!this.checkStockAndLimit()) {
					return
				}

				try {
					if (this.orderType === 1) {
						// 加入购物车：只传产品ID、版本、数量，商品详情由购物车接口从数据库读取
						const params = {
							productId: this.product.id,
							version: this.product.version[this.currentVersion],
							quantity: this.quantity
						}
						const response = await api.cart.create(params)
						if (response.code !== 200) {
							uni.showToast({
								title: response.msg || '加入购物车失败',
								icon: 'none'
							})
							return
						}
						this.cartNumbers = response.data.count
						uni.showToast({
							title: response.msg,
							icon: 'success'
						})
					} else if (this.orderType === 2) {
						// 立即购买：只传产品ID、版本、数量，订单创建页通过接口获取商品详情
						const productId = this.product.id
						const version = encodeURIComponent(this.product.version[this.currentVersion] || '')
						const quantity = this.quantity
						uni.navigateTo({
							url: `/pages/order/create?productId=${productId}&version=${version}&quantity=${quantity}`
						})
					}
					this.hideCartPopup()
				} catch (error) {
					uni.showToast({
						title: '操作失败',
						icon: 'none'
					})
				}
			}

		}
	}
</script>

<style scoped>
	.product-detail {
		background-color: #ffffff;
		height: 100vh;
	}

	.product-images {
		position: relative;
		height: 600rpx;
	}

	.swiper {
		height: 100%;
	}

	.product-image {
		width: 100%;
		height: auto;
	}

	.preorder-badge {
		position: absolute;
		top: 20rpx;
		left: 20rpx;
		background-color: #ffd700;
		color: #000000;
		padding: 8rpx 16rpx;
		border-radius: 20rpx;
		font-size: 24rpx;
		font-weight: bold;
	}

	.price-section {
		background-color: #cd0000;
		padding: 20rpx 30rpx;
		margin: 24rpx;
		border-radius: 20rpx;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.price-info {
		display: flex;
		flex-direction: column;
	}

	.deposit-price {
		font-size: 36rpx;
		color: #ffffff;
		font-weight: bold;
	}

	.deposit-price .deposit-price-val {
		font-size: 60rpx;
	}

	.product-price {
		color: #dc0000;
		font-size: 26rpx;
		font-weight: 500;
		padding: 28rpx 0;
	}

	.product-price .fontBold {
		font-size: 52rpx;
	}

	.total-price {
		font-size: 24rpx;
		color: #ffffff;
		margin-top: 8rpx;
	}

	.countdown {
		text-align: right;
	}

	.countdown-text {
		font-size: 24rpx;
		color: #ffffff;
		display: block;
	}

	.countdown-time {
		font-size: 28rpx;
		color: #ffffff;
		font-weight: bold;
		margin-top: 8rpx;
	}

	.product-info {
		padding: 30rpx;
	}

	.product-specs {
		padding: 0 30rpx 30rpx;
		background-color: #ffffff;
	}

	/* 预售时间信息 */
	.presale-timeline {
		background-color: #ffffff;
		margin: 20rpx 30rpx;
		border-radius: 20rpx;
		overflow: hidden;
	}

	.presale-header {
		background-color: #dc0000;
		padding: 20rpx 30rpx;
		display: flex;
		align-items: center;
		border-radius: 20rpx 20rpx 0 0;
	}

	.presale-header-icon {
		width: 32rpx;
		height: 32rpx;
		border-radius: 50%;
		background-color: #ffffff;
		color: #dc0000;
		font-size: 20rpx;
		font-weight: bold;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 12rpx;
		flex-shrink: 0;
	}

	.presale-header-text {
		color: #ffffff;
		font-size: 24rpx;
		font-weight: 500;
	}

	.presale-body {
		background-color: #fef2f2;
		padding: 30rpx;
		position: relative;
	}

	.presale-time-left {
		margin-bottom: 40rpx;
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
	}

	.presale-start-text {
		color: #dc0000;
		font-size: 24rpx;
		font-weight: 600;
	}

	.presale-time-right {
		margin-bottom: 30rpx;
		text-align: right;
	}

	.presale-end-text {
		color: #333333;
		font-size: 24rpx;
		font-weight: 500;
	}

	.presale-timeline-line {
		position: relative;
		height: 4rpx;
		background-color: #ffe5e5;
		border-radius: 2rpx;
		margin-top: 20rpx;
	}

	.timeline-dot-start {
		position: absolute;
		left: 0;
		top: 50%;
		transform: translateY(-50%);
		width: 16rpx;
		height: 16rpx;
		background-color: #dc0000;
		border-radius: 50%;
		border: 3rpx solid #ffffff;
		box-shadow: 0 0 0 2rpx #dc0000;
	}

	.timeline-dot-end {
		position: absolute;
		right: 0;
		top: 50%;
		transform: translateY(-50%);
		width: 16rpx;
		height: 16rpx;
		background-color: #ffffff;
		border: 3rpx solid #dc0000;
		border-radius: 50%;
	}

	.timeline-progress {
		position: absolute;
		left: 0;
		top: 0;
		height: 100%;
		background-color: #dc0000;
		width: 100%;
		border-radius: 2rpx;
	}

	.spec-item {
		display: flex;
		align-items: flex-start;
		padding: 20rpx 0;
		border-bottom: 1px solid #f5f5f5;
	}

	.spec-item:last-child {
		border-bottom: none;
	}

	.spec-label {
		font-size: 28rpx;
		color: #666666;
		min-width: 140rpx;
		flex-shrink: 0;
	}

	.spec-value-wrapper {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.spec-value {
		font-size: 28rpx;
		color: #333333;
		flex: 1;
		word-break: break-all;
	}

	.spec-value.spec-truncate {
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 1;
		-webkit-box-orient: vertical;
	}

	.spec-more {
		font-size: 24rpx;
		color: #dc0000;
		margin-left: 20rpx;
		flex-shrink: 0;
	}

	.popup-content-spec {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #ffffff;
		border-radius: 30rpx 30rpx 0 0;
		padding: 40rpx 30rpx;
		z-index: 1001;
		max-height: 70vh;
		display: flex;
		flex-direction: column;
	}

	.spec-detail-title {
		font-size: 36rpx;
		font-weight: bold;
		color: #333333;
		margin-bottom: 30rpx;
		text-align: center;
	}

	.spec-detail-content {
		font-size: 28rpx;
		color: #666666;
		line-height: 1.8;
		padding: 20rpx;
		background-color: #f5f5f5;
		border-radius: 10rpx;
		margin-bottom: 30rpx;
		max-height: 50vh;
		overflow-y: auto;
		word-break: break-all;
	}

	.spec-detail-btn {
		width: 100%;
		height: 88rpx;
		background-color: #dc0000;
		color: #ffffff;
		font-size: 32rpx;
		text-align: center;
		line-height: 88rpx;
		border-radius: 44rpx;
	}

	.product-title {
		margin-bottom: 20rpx;
	}

	.buy-type-val1 {
		display: inline-block;
		width: 70rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #1ccf00;
		color: #ffffff;
		font-size: 18rpx;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
		margin-left: 12rpx;
	}

	.buy-type-val2 {
		display: inline-block;
		width: 70rpx;
		text-align: center;
		padding: 6rpx 0;
		background-color: #dc0000;
		color: #ffffff;
		font-size: 18rpx;
		transform: skew(-20deg);
		transform-origin: top left;
		border-radius: 8rpx;
		margin-left: 12rpx;
	}

	.title {
		font-size: 32rpx;
		color: #000000;
		font-weight: bold;
	}

	.discount-info {
		display: flex;
		align-items: center;
		justify-content: space-between;
		background-color: #fbe5e6;
		padding: 14rpx 20rpx;
		border-radius: 14rpx;
	}

	.discount-text {
		font-size: 22rpx;
		font-weight: 600;
		color: #cd0000;
	}

	.right-arrow {
		display: inline-block;
		width: 40rpx;
		height: 40rpx;
		background-image: url('/static/image/right-arrow.png');
		background-size: 100%;
	}


	.tabs {
		display: flex;
	}

	.tab-item {
		flex: 1;
		text-align: center;
		padding: 16rpx 0;
		font-size: 28rpx;
		color: #666666;
		position: relative;
	}

	.tab-item.active {
		color: #000000;
		font-weight: bold;
	}

	.tab-item.active::after {
		content: '';
		position: absolute;
		bottom: 0;
		left: 50%;
		transform: translateX(-50%);
		width: 40rpx;
		height: 8rpx;
		background-color: #dc0000;
		border-radius: 4rpx;
	}

	.content .contentDe {
		display: flex;
		flex-direction: column;
		padding: 20rpx 0 120rpx 0;
	}

	.content .c-img {
		width: 100%;
		height: auto;
	}

	.bottom-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #ffffff;
		padding: 20rpx 30rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		border-top: 1rpx solid #eeeeee;
	}

	.action-buttons {
		display: flex;
		align-items: center;
		justify-content: space-between;
		flex-direction: row;
		flex-wrap: nowrap;
	}

	.action-btn {
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 70rpx;
	}

	.action-btn-shar {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 0;
		background-color: #ffffff;
		line-height: normal;
		margin: 0;
		border: none;
	}

	.action-btn-shar::after {
		border: none;
	}

	.add-cart {
		position: relative;
	}

	.cart-number {
		position: absolute;
		display: inline;
		background-color: #dc0000;
		width: 30rpx;
		height: 30rpx;
		text-align: center;
		line-height: 30rpx;
		border-radius: 50%;
		color: #ffffff;
		font-size: 22rpx;
		top: -20rpx;
		right: -20rpx;
	}

	.action-icon {
		width: 50rpx;
		height: 50rpx;
		margin-bottom: 8rpx;
	}

	.action-icon-image {
		width: 100%;
		height: auto;
	}

	.action-text {
		font-size: 20rpx;
		color: #666666;
	}

	.order-type-btn {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		flex-wrap: nowrap;
		align-items: center;
	}

	.order-btn {
		background-color: #cd0000;
		color: #ffffff;
		border-radius: 50rpx;
		font-size: 26rpx;
		font-weight: bold;
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 180rpx;
		text-align: center;
		padding: 18rpx 0;
	}

	.order-btn.presale-btn {
		width: 300rpx;
	}

	.cart-popup {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 999;
	}

	.cart-popup .mask {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.5);
	}

	.cart-popup .popup-content-deduct {
		position: absolute;
		left: 10%;
		right: 10%;
		top: 36%;
		background-color: #ffffff;
		border-radius: 20rpx;
		padding: 80rpx 40rpx;
		max-height: 80vh;
		text-align: center;
	}

	.deduct-title {
		font-size: 34rpx;
		font-weight: bold;
	}

	.deduct-desc {
		font-size: 24rpx;
		color: #cccccc;
		padding: 20rpx 0;
	}

	.deduct-btn {
		width: 100%;
		padding: 16rpx 0;
		background-color: #dc0000;
		color: #ffffff;
		font-size: 26rpx;
		border-radius: 20rpx;
		margin-top: 40rpx;
	}

	.cart-popup .popup-content {
		position: absolute;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #fff;
		border-top-left-radius: 30rpx;
		border-top-right-radius: 30rpx;
		padding: 30rpx;
		max-height: 80vh;
		overflow-y: auto;
	}

	.cart-popup .goods-info {
		display: flex;
	}

	.cart-popup .goods-info .goods-img {
		width: 200rpx;
		height: 200rpx;
		border-radius: 10rpx;
		margin-right: 20rpx;
	}

	.cart-popup .goods-info .goods-text {
		flex: 1;
	}

	.cart-popup .goods-info .goods-text .goods-name {
		font-size: 30rpx;
		margin-bottom: 10rpx;
		line-height: 1.4;
	}

	.cart-popup .goods-info .goods-text .goods-price {
		font-size: 32rpx;
		color: #dc0000;
		font-weight: bold;
	}

	.cart-popup .specification {
		padding: 30rpx 0;
	}

	.cart-popup .specification .spec-title {
		font-size: 28rpx;
		padding: 10rpx 0;
		color: #333333;
	}

	.cart-popup .specification .spec-list {
		display: flex;
		flex-wrap: wrap;
	}

	.cart-popup .specification .spec-list .spec-item {
		padding: 12rpx 30rpx;
		border: 1px solid #ddd;
		border-radius: 50rpx;
		margin-right: 20rpx;
		font-size: 26rpx;
	}

	.cart-popup .specification .spec-list .active {
		border-color: #dc0000;
		color: #ffffff;
		background-color: #dc0000;
		font-weight: bold;
	}


	.cart-popup .quantity {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 50rpx;
	}

	.cart-popup .quantity .quantity-title {
		font-size: 28rpx;
		color: #333;
	}

	.cart-popup .quantity .quantity-control {
		display: flex;
		align-items: center;
	}

	.cart-popup .quantity .quantity-control .minus,
	.cart-popup .quantity .quantity-control .plus {
		width: 60rpx;
		height: 60rpx;
		font-size: 60rpx;
		text-align: center;
		line-height: 60rpx;
	}

	.cart-popup .quantity .quantity-control .minus {
		line-height: 46rpx;
	}

	.cart-popup .quantity .quantity-control .minus.disabled {
		color: #ccc;
	}

	.cart-popup .quantity .quantity-control .num {
		width: 140rpx;
		text-align: center;
		font-size: 28rpx;
		height: 60rpx;
		line-height: 60rpx;
		margin: 0 20rpx;
		background-color: #eeeeee;
		border-radius: 10rpx;
	}

	.cart-popup .add-cart-btn {
		height: 90rpx;
		background-color: #dc0000;
		color: #fff;
		border-radius: 45rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 32rpx;
		font-weight: bold;
	}
</style>