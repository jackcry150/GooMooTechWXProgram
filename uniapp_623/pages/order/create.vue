<template>
	<view class="create-order">
		<!-- 地址提示 -->
		<view class="address-notice">
			<view class="address-info">
				<view class="address-data" v-if="address">
					<view class="address-top" @click="selectAddress">
						<view class="address-top-l">
							<image class="address-icon-img" src="/static/image/location.png" mode="widthFix"></image>
							<text class="address-top-l-t">收货地址</text>
						</view>
						<view class="address-top-r">
							<text class="address-top-r-t">换一个</text>
							<text class="right-arrow"></text>
						</view>
					</view>
					<view class="address-de">
						<view class="address-header">
							<text class="contact-name">{{ address.name }}</text>
							<text class="contact-phone">{{ address.phone }}</text>
						</view>
						<view class="address-detail">{{ address.province }} {{ address.city }} {{ address.area }} {{ address.detail }}</view>
					</view>
				</view>
				<view class="address-no" @click="selectAddress" v-else>
					<text class="address-icon">
						<image class="address-icon-img" src="/static/image/location.png" mode="widthFix"></image>
					</text>
					<text class="address-no-text">添加收货地址</text>
				</view>
			</view>
		</view>

		<!-- 商品信息 -->
		<view class="product-section">
			<view class="section-title">商品信息</view>
			<view class="product-item" v-for="(item, index) in product" :key="index">
				<view class="item-image-l">
					<image class="item-image" :src="item.image" mode="aspectFill" />
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
							<view class="quantity-text">×{{ item.quantity }}</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view class="product-other">
			<!-- 预售定金信息 -->
			<view class="other-item" v-if="hasPresale && totalDepositAmount > 0">
				<view class="method-item">
					<text class="method-label">定金</text>
					<view class="method-value">
						<text class="deposit-amount">¥{{ totalDepositAmount.toFixed(2) }}</text>
						<text class="deposit-tip">（预售商品需先支付定金）</text>
					</view>
				</view>
			</view>
			<view class="other-item" v-if="hasPresale && totalBalanceAmount > 0">
				<view class="method-item">
					<text class="method-label">尾款</text>
					<view class="method-value">
						<text class="balance-amount">¥{{ totalBalanceAmount.toFixed(2) }}</text>
						<text class="balance-tip">（预售结束后支付）</text>
					</view>
				</view>
			</view>

			<view class="other-item">
				<view class="method-item">
					<text class="method-label">运费</text>
					<view class="method-value">
						<text class="shipping-fee-text">{{ shippingFeeText }}</text>
					</view>
				</view>
				<!-- 运费模板详情 -->
				<view class="shipping-template-info" v-if="shippingTemplates.length > 0 && shippingFee > 0">
					<view class="template-item" v-for="(template, index) in shippingTemplates" :key="index">
						<view class="template-name">{{ template.name }}</view>
						<view class="template-detail">
							<text class="template-type">{{ template.type == 1 ? '按件计费' : '按重量计费' }}</text>
							<text class="template-fee">
								{{ template.type == 1 
									? `首${template.firstPiece}件¥${parseFloat(template.firstFee).toFixed(2)}，续${template.continuePiece}件¥${parseFloat(template.continueFee).toFixed(2)}`
									: `首${template.firstWeight}kg¥${parseFloat(template.firstFee).toFixed(2)}，续${template.continueWeight}kg¥${parseFloat(template.continueFee).toFixed(2)}`
								}}
							</text>
						</view>
					</view>
				</view>
			</view>
			<!-- 支付方式 -->
			<view class="other-item">
				<view class="method-item">
					<text class="method-label">支付方式</text>
					<view class="method-value">
						<text class="method-text">微信支付</text>
						<text class="right-arrow"></text>
					</view>
				</view>
			</view>

			<!-- 备注 -->
			<view class="other-item">
				<view class="method-item">
					<text class="method-label">备注</text>
					<view class="method-value">
						<input id="uni-input-type-text" class="uni-input" v-model="remarks" placeholder="选填，可与客服联系确认" type="text" />
					</view>
				</view>
			</view>
		</view>

		<!-- 底部提交栏 -->
		<view class="bottom-bar">
			<view class="total-info">
				<text class="total-text" v-if="hasPresale && totalDepositAmount > 0">
					需支付定金：<text class="total-text-val">¥{{ totalPrice }}</text>
					<text class="presale-note">（尾款¥{{ totalBalanceAmount.toFixed(2) }}预售结束后支付）</text>
				</text>
				<text class="total-text" v-else>
					合计：<text class="total-text-val">¥{{ totalPrice }}</text>
				</text>
			</view>
			<button class="submit-btn" @click="submitOrder">提交订单</button>
		</view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		name: 'CreateOrder',
		data() {
			return {
				product: [],
				address: [],
				remarks: '',
				shippingFee: 0,
				shippingTemplates: [] // 运费模板列表
			}
		},
		onLoad(options) {
			if (options.cartIds) {
				// 来自购物车：传购物车ID和商品ID，通过接口获取商品详情
				const cartIds = decodeURIComponent(options.cartIds || '').split(',').filter(Boolean)
				const productIds = (options.productIds ? decodeURIComponent(options.productIds || '') : '').split(',').filter(Boolean)
				this.loadFromCart(cartIds, productIds)
			} else if (options.productId) {
				// 来自立即购买：仅传产品ID，通过接口获取商品详情
				this.loadBuyNow(options.productId, decodeURIComponent(options.version || ''), parseInt(options.quantity || 1))
			}
		},
		onShow() {
			const selectedAddress = uni.getStorageSync('selectedAddress')
			if (selectedAddress) {
				this.address = selectedAddress
			} else {
				this.getAddressdeDault()
			}
		},
		computed: {
			hasPresale() {
				return this.product.some(item => item.type == 2)
			},
			totalDepositAmount() {
				let total = 0
				this.product.forEach(item => {
					if (item.type == 2 && item.deposit) {
						total += parseFloat(item.deposit) * (item.quantity || 1)
					}
				})
				return total
			},
			totalBalanceAmount() {
				let total = 0
				this.product.forEach(item => {
					if (item.type == 2 && item.price && item.deposit) {
						const balance = parseFloat(item.price) - parseFloat(item.deposit)
						total += balance * (item.quantity || 1)
					}
				})
				return total
			},
			totalPrice() {
				// 如果是预售订单，显示定金+运费，否则显示总价+运费
				if (this.hasPresale && this.totalDepositAmount > 0) {
					return (parseFloat(this.totalDepositAmount) + parseFloat(this.shippingFee)).toFixed(2)
				}
				const productTotal = this.product
					.reduce((total, item) => total + (item.price * item.quantity), 0)
				return (parseFloat(productTotal) + parseFloat(this.shippingFee)).toFixed(2)
			},
			shippingFeeText() {
				if (this.shippingFee <= 0) {
					return '包邮'
				}
				return '¥' + parseFloat(this.shippingFee).toFixed(2)
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

			loadCartItems(items) {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					this.product = items
					this.calculateShippingFee()
				}
			},
			async loadFromCart(cartIds, productIds) {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
					return
				}
				if (!cartIds || cartIds.length === 0) {
					uni.showToast({ title: '请选择商品', icon: 'none' })
					setTimeout(() => uni.navigateBack(), 1500)
					return
				}
				uni.showLoading({ title: '加载中...' })
				try {
					const res = await api.cart.list()
					uni.hideLoading()
					if (res.code !== 200 || !res.data) {
						uni.showToast({ title: res.msg || '加载失败', icon: 'none' })
						setTimeout(() => uni.navigateBack(), 1500)
						return
					}
					const ids = cartIds.map(id => parseInt(id)).filter(n => n > 0)
					const items = (res.data || []).filter(item => ids.includes(parseInt(item.id)))
					if (items.length === 0) {
						uni.showToast({ title: '购物车商品已失效，请重新选择', icon: 'none' })
						setTimeout(() => uni.navigateBack(), 1500)
						return
					}
					this.product = items
					this.calculateShippingFee()
				} catch (e) {
					uni.hideLoading()
					uni.showToast({ title: '加载失败', icon: 'none' })
					setTimeout(() => uni.navigateBack(), 1500)
				}
			},
			async loadBuyNow(productId, version, quantity) {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
					return
				}
				uni.showLoading({ title: '加载中...' })
				try {
					const res = await api.product.detail({ id: productId })
					uni.hideLoading()
					if (res.code !== 200 || !res.data) {
						uni.showToast({ title: res.msg || '商品不存在', icon: 'none' })
						setTimeout(() => uni.navigateBack(), 1500)
						return
					}
					const p = res.data
					const versionStr = version || (p.version && p.version[0]) || ''
					const item = {
						id: '',
						productId: p.id,
						productCode: p.productId || '',
						title: p.title,
						subtitle: p.subtitle || '',
						image: Array.isArray(p.image) ? p.image[0] : p.image,
						price: p.type == 2 ? parseFloat(p.deposit || 0) : parseFloat(p.price || 0),
						version: versionStr,
						quantity: quantity,
						selected: true,
						type: p.type,
						stock: p.stock,
						limitStock: p.limitStock || 0,
						deposit: p.deposit,
						shippingTemplateId: p.shippingTemplateId,
						shippingTemplate: p.shippingTemplate
					}
					this.product = [item]
					this.calculateShippingFee()
				} catch (e) {
					uni.hideLoading()
					uni.showToast({ title: '加载失败', icon: 'none' })
					setTimeout(() => uni.navigateBack(), 1500)
				}
			},

			// 计算运费
			calculateShippingFee() {
				let totalFee = 0
				let totalQuantity = 0
				let totalWeight = 0 // 假设每个商品重量为0.1kg，实际应该从商品信息获取

				// 按运费模板分组计算
				const templateGroups = {}
				const templateMap = {} // 用于去重运费模板

				this.product.forEach(item => {
					if (item.shippingTemplate && item.shippingTemplate.id) {
						const templateId = item.shippingTemplate.id
						if (!templateGroups[templateId]) {
							templateGroups[templateId] = {
								template: item.shippingTemplate,
								quantity: 0,
								weight: 0
							}
							// 保存模板信息用于显示
							templateMap[templateId] = item.shippingTemplate
						}
						templateGroups[templateId].quantity += item.quantity || 1
						templateGroups[templateId].weight += (item.quantity || 1) * 0.1 // 假设每个商品0.1kg
					}
				})

				// 计算每个模板的运费
				Object.values(templateGroups).forEach(group => {
					const template = group.template
					let fee = 0

					if (template.type == 1) {
						// 按件计费
						const firstPiece = parseInt(template.firstPiece) || 1
						const firstFee = parseFloat(template.firstFee) || 0
						const continuePiece = parseInt(template.continuePiece) || 1
						const continueFee = parseFloat(template.continueFee) || 0

						if (group.quantity <= firstPiece) {
							fee = firstFee
						} else {
							const continueCount = Math.ceil((group.quantity - firstPiece) / continuePiece)
							fee = firstFee + (continueCount * continueFee)
						}
					} else if (template.type == 2) {
						// 按重量计费
						const firstWeight = parseFloat(template.firstWeight) || 1
						const firstFee = parseFloat(template.firstFee) || 0
						const continueWeight = parseFloat(template.continueWeight) || 1
						const continueFee = parseFloat(template.continueFee) || 0

						if (group.weight <= firstWeight) {
							fee = firstFee
						} else {
							const continueCount = Math.ceil((group.weight - firstWeight) / continueWeight)
							fee = firstFee + (continueCount * continueFee)
						}
					}

					totalFee += fee
				})

				// 设置运费和模板列表
				this.shippingFee = totalFee
				this.shippingTemplates = Object.values(templateMap)
			},

			async getAddressdeDault() {
				if (!this.address) {
					try {
						const response = await api.address.default()
						this.address = response.data
					} catch (error) {
						// 静默失败，用户可以选择地址
					}
				}
			},

			selectAddress() {
				const token = uni.getStorageSync('token')
				if (!token) {
					this.goLogin()
				} else {
					uni.navigateTo({
						url: '/pages/address/list?t=1'
					})
				}
			},

			async submitOrder() {
				if (!this.address) {
					uni.showToast({
						title: '请选择收货地址',
						icon: 'none'
					})
					return
				}
				uni.showLoading({
					title: '提交中...'
				})

				try {
					// 只提交地址ID和产品ID（含版本、数量），后端支付成功后再存产品详情
					const productMinimal = this.product.map(p => ({
						productId: p.productId,
						version: p.version || '',
						quantity: p.quantity || 1,
						id: p.id
					}))
					const params = {
						product: productMinimal,
						address: { id: this.address.id },
						remarks: this.remarks,
						shippingFee: this.shippingFee,
					}
					const response = await api.order.create(params)
					uni.hideLoading()

					if (response.code === 200 && response.data) {
						const orderId = response.data.orderId
						const orderNo = response.data.orderNo
						if (!orderId || !orderNo) {
							uni.showToast({ title: '订单创建成功', icon: 'success' })
							setTimeout(() => uni.redirectTo({ url: '/pages/order/list' }), 1500)
							return
						}
						// 创建成功后请求支付 API，获取 payinfo 再调起支付
						uni.showLoading({ title: '获取支付参数...' })
						const payRes = await api.order.pay({ id: orderId, payType: 'full' })
						uni.hideLoading()
						if (payRes.code === 200 && payRes.data && payRes.data.payment) {
							const payment = { ...payRes.data.payment, orderId: payRes.data.orderId || orderId }
							this.requestPayment(payment, orderNo)
						} else {
							uni.showToast({ title: payRes.msg || '获取支付参数失败', icon: 'none' })
							setTimeout(() => {
								uni.redirectTo({ url: '/pages/order/detail?id=' + orderId })
							}, 1500)
						}
					} else {
						uni.showToast({ title: response.msg || '提交失败', icon: 'none' })
					}
				} catch (error) {
					uni.hideLoading()
					uni.showToast({ title: '提交失败', icon: 'none' })
				}
			},

			// 调用小程序支付
			requestPayment(paymentData, orderNo) {
				// #ifdef MP-WEIXIN
				uni.requestPayment({
					provider: 'wxpay',
					timeStamp: paymentData.timeStamp || '',
					nonceStr: paymentData.nonceStr || '',
					package: paymentData.package || '',
					signType: paymentData.signType || 'RSA',
					paySign: paymentData.paySign || '',
					success: (res) => {
						// 支付成功
						uni.showToast({
							title: '支付成功',
							icon: 'success'
						})
						setTimeout(() => {
							// 跳转到订单详情页
							uni.redirectTo({
								url: '/pages/order/detail?id=' + (paymentData.orderId || '')
							})
						}, 1500)
					},
					fail: (err) => {
						// 支付失败或取消
						if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) {
							// 用户取消支付
							uni.showToast({
								title: '支付已取消',
								icon: 'none'
							})
							// 跳转到订单列表，用户可以稍后支付
							setTimeout(() => {
								uni.redirectTo({
									url: '/pages/order/list'
								})
							}, 1500)
						} else {
							// 支付失败
							uni.showToast({
								title: '支付失败，请稍后重试',
								icon: 'none'
							})
							// 跳转到订单列表，用户可以稍后支付
							setTimeout(() => {
								uni.redirectTo({
									url: '/pages/order/list'
								})
							}, 1500)
						}
					}
				})
				// #endif

				// #ifndef MP-WEIXIN
				// 非微信小程序环境，提示跳转到订单列表
				uni.showToast({
					title: '订单创建成功',
					icon: 'success'
				})
				setTimeout(() => {
					uni.redirectTo({
						url: '/pages/order/list'
					})
				}, 1500)
				// #endif
			}
		}
	}
</script>

<style scoped>
	.create-order {
		background-color: #f5f5f5;
		min-height: 100vh;
	}

	.address-notice {
		padding-top: 10rpx;
		margin: auto;
	}

	.address-info,
	.product-section,
	.product-other {
		background-color: #ffffff;
		padding: 30rpx;
		width: 86%;
		margin: 20rpx auto;
		border-radius: 20rpx;
	}

	.address-no {
		padding: 50rpx 0;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.address-top {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
	}

	.address-top-l {
		display: flex;
		align-items: center;
	}

	.address-top-l-t {
		font-size: 26rpx;
		margin-left: 10rpx;
	}

	.address-top-r {
		display: flex;
		align-items: center;
	}

	.address-top-r-t {
		color: #cccccc;
		font-size: 24rpx;
		margin-right: 10rpx;
	}

	.address-de {
		padding: 20rpx 0;
	}

	.address-header {
		display: flex;
		align-items: center;
		padding: 16rpx 0;
	}

	.contact-name {
		font-size: 28rpx;
		color: #000000;
		font-weight: bold;
		margin-right: 20rpx;
	}

	.contact-phone {
		font-size: 26rpx;
		color: #000000;
		font-weight: 800;
	}

	.address-detail {
		font-size: 24rpx;
		color: #545454;
		padding: 10rpx 0 10rpx 0;
	}

	.address-icon {
		width: 30rpx;
		height: 30rpx;
	}

	.address-icon-img {
		width: 30rpx;
		height: 30rpx;
	}

	.address-no-text {
		margin-left: 30rpx;
		font-weight: bold;
		font-size: 28rpx;
	}

	.section-title {
		font-size: 24rpx;
		color: #000000;
		font-weight: bold;
		margin-bottom: 20rpx;
	}

	.product-item {
		display: flex;
		align-items: center;
		padding: 15rpx 0;
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
	}

	.item-price {
		font-size: 32rpx;
		color: #dc0000;
		font-weight: bold;
	}

	.quantity-text {
		font-size: 20rpx;
		font-weight: 800;
	}

	.other-item {
		padding: 20rpx 0;
	}

	.method-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.method-label {
		font-size: 28rpx;
		font-weight: bold;
	}

	.method-value {
		display: flex;
		align-items: center;
	}

	.method-text {
		font-size: 28rpx;
		margin-right: 16rpx;
		line-height: 100%;
	}

	.deposit-amount,
	.balance-amount {
		font-size: 30rpx;
		color: #e74c3c;
		font-weight: bold;
		margin-right: 10rpx;
	}

	.deposit-tip,
	.balance-tip {
		font-size: 24rpx;
		color: #999999;
	}

	.presale-note {
		display: block;
		font-size: 22rpx;
		color: #999999;
		font-weight: normal;
		margin-top: 5rpx;
	}

	.shipping-template-info {
		margin-top: 15rpx;
		padding-top: 15rpx;
		border-top: 1px solid #f0f0f0;
	}

	.template-item {
		margin-bottom: 15rpx;
	}

	.template-item:last-child {
		margin-bottom: 0;
	}

	.template-name {
		font-size: 26rpx;
		color: #333333;
		font-weight: 500;
		margin-bottom: 8rpx;
	}

	.template-detail {
		display: flex;
		flex-direction: column;
		gap: 5rpx;
	}

	.template-type {
		font-size: 24rpx;
		color: #666666;
	}

	.template-fee {
		font-size: 24rpx;
		color: #999999;
		line-height: 1.5;
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
	}

	.total-info {
		flex: 1;
	}

	.total-text {
		font-size: 24rpx;
		font-weight: 800;
	}

	.total-text-val {
		font-size: 40rpx;
		color: #dc0000;
		font-weight: bold;
	}

	.submit-btn {
		background-color: #dc0000;
		color: #ffffff;
		border: none;
		border-radius: 50rpx;
		padding: 8rpx 150rpx;
		font-size: 28rpx;
		font-weight: bold;
	}
</style>