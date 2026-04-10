<template>
	<view class="ai-customer-page">
		<view class="page-top">
			<view class="top-bar">
				<view class="back-btn" @click="goBack">
					<image class="back-icon" src="/static/image/left-arrow.png" mode="aspectFit"></image>
				</view>
				<view class="top-title">AI客服</view>
				<view class="top-spacer"></view>
			</view>
		</view>

		<scroll-view class="chat-scroll" scroll-y :scroll-into-view="scrollIntoView">
			<view class="chat-inner">
				<view
					v-for="(item, index) in messages"
					:id="'msg-' + index"
					:key="index"
					:class="['bubble-row', item.role === 'user' ? 'bubble-row-user' : 'bubble-row-ai']"
				>
					<view :class="['bubble', item.role === 'user' ? 'bubble-user' : 'bubble-ai']">
						<text class="bubble-text">{{ item.content }}</text>
					</view>
				</view>
			</view>
		</scroll-view>

		<view class="shortcut-panel">
			<view class="switch-row">
				<view :class="['switch-pill', activeTab === 'faq' ? 'switch-pill-active' : '']" @click="activeTab = 'faq'">常见问题</view>
				<view :class="['switch-pill', activeTab === 'service' ? 'switch-pill-active' : '']" @click="activeTab = 'service'">服务分类</view>
			</view>

			<view v-if="activeTab === 'faq'" class="chip-list">
				<view v-for="(item, index) in quickQuestions" :key="'faq-' + index" class="chip-item" @click="fillQuestion(item)">
					{{ item }}
				</view>
			</view>

			<view v-else class="chip-list">
				<view class="chip-item" @click="fillQuestion(scene === 'aftersale' ? '我想咨询售后问题。' : '我想咨询预售商品。')">
					{{ scene === 'aftersale' ? '售后咨询' : '售前咨询' }}
				</view>
				<view class="chip-item" v-if="orderId" @click="fillQuestion('请帮我查看订单状态。')">订单状态</view>
				<view class="chip-item" v-if="productId" @click="fillQuestion('请帮我介绍这个商品。')">商品咨询</view>
				<view class="chip-item chip-item-outline" @click="goToManualCustomer">转人工客服</view>
			</view>
		</view>

		<view class="composer">
			<input
				v-model="draftQuestion"
				class="composer-input"
				maxlength="300"
				placeholder="请输入您的问题"
				confirm-type="send"
				@confirm="sendMessage"
			/>
			<view :class="['send-btn', sending ? 'send-btn-disabled' : '']" @click="sendMessage">
				<text class="send-icon">➤</text>
			</view>
		</view>
	</view>
</template>

<script>
import { api } from '@/utils/request.js'

const QUICK_QUESTIONS = {
	presale: [
		'这个商品什么时候发货？',
		'是现货还是预售？',
		'定金和尾款怎么支付？',
		'可以用猫币抵扣吗？',
		'支持开发票吗？'
	],
	aftersale: [
		'我的订单现在是什么状态？',
		'怎么申请退款？',
		'退款进度在哪里看？',
		'签收后发现问题怎么办？',
		'请帮我转人工客服。'
	]
}

export default {
	data() {
		return {
			scene: 'presale',
			productId: '',
			orderId: '',
			sourcePage: '',
			draftQuestion: '',
			sending: false,
			messages: [],
			activeTab: 'faq',
			scrollIntoView: ''
		}
	},
	computed: {
		welcomeText() {
			if (this.scene === 'aftersale') {
				return '请输入您的问题，订单、退款、物流等问题我会先帮您梳理。'
			}
			return '请输入您的问题，商品、预售、尾款、发货等问题我都可以先帮您解答。'
		},
		quickQuestions() {
			return QUICK_QUESTIONS[this.scene] || QUICK_QUESTIONS.presale
		}
	},
	onLoad(options) {
		this.scene = options.scene === 'aftersale' ? 'aftersale' : 'presale'
		this.productId = options.productId || ''
		this.orderId = options.orderId || ''
		this.sourcePage = options.sourcePage || ''
		this.messages = [
			{ role: 'user', content: this.welcomeText },
			{
				role: 'ai',
				content: this.scene === 'aftersale'
					? '我可以先帮您处理订单、物流、退款和售后相关问题，复杂情况也可以转人工客服。'
					: '我可以先帮您解答商品、预售、发货、尾款和支付相关问题，必要时也可以转人工客服。'
			}
		]
		this.scrollToBottom()
	},
	methods: {
		goBack() {
			uni.navigateBack({
				fail: () => {
					uni.switchTab({ url: '/pages/index/index' })
				}
			})
		},
		scrollToBottom() {
			this.$nextTick(() => {
				if (!this.messages.length) return
				this.scrollIntoView = 'msg-' + (this.messages.length - 1)
			})
		},
		fillQuestion(question) {
			this.draftQuestion = question
		},
		async sendMessage() {
			if (this.sending) return
			if (!this.draftQuestion.trim()) {
				uni.showToast({ title: '请先输入问题', icon: 'none' })
				return
			}

			const content = this.draftQuestion.trim()
			this.messages.push({ role: 'user', content })
			this.draftQuestion = ''
			this.sending = true
			this.scrollToBottom()

			try {
				const res = await api.aiService.sendMessage({
					scene: this.scene,
					productId: this.productId,
					orderId: this.orderId,
					sourcePage: this.sourcePage,
					content
				})
				let replyText = res && res.data && res.data.reply ? res.data.reply : '暂未获取到回复，请稍后重试。'
				if (res && res.data && res.data.needTransfer) {
					replyText += '\n\n建议：当前问题建议直接转人工客服处理。'
				}
				this.messages.push({ role: 'ai', content: replyText })
			} catch (error) {
				this.messages.push({ role: 'ai', content: '请求失败了，您可以换个说法继续提问，或者直接转人工客服。' })
			} finally {
				this.sending = false
				this.scrollToBottom()
			}
		},
		goToManualCustomer() {
			uni.navigateTo({ url: '/pages/customer/customer' })
		}
	}
}
</script>

<style scoped>
	.ai-customer-page {
		min-height: 100vh;
		background: #fffdf8;
		padding-bottom: calc(210rpx + env(safe-area-inset-bottom));
		box-sizing: border-box;
	}
	.page-top {
		background: linear-gradient(180deg, #ff9d00 0%, #ff9a00 100%);
		padding-top: calc(var(--status-bar-height) + 10rpx);
		padding-bottom: 26rpx;
		box-shadow: 0 12rpx 30rpx rgba(255, 160, 0, 0.22);
	}
	.top-bar {
		height: 88rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 26rpx;
	}
	.back-btn,
	.top-spacer {
		width: 72rpx;
		height: 72rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.back-icon {
		width: 34rpx;
		height: 34rpx;
	}
	.top-title {
		font-size: 54rpx;
		font-weight: 700;
		color: #ffffff;
		text-shadow: 0 4rpx 8rpx rgba(126, 77, 0, 0.25);
	}
	.chat-scroll {
		height: calc(100vh - 430rpx - env(safe-area-inset-bottom));
	}
	.chat-inner {
		padding: 32rpx 26rpx 24rpx;
	}
	.bubble-row {
		display: flex;
		margin-bottom: 28rpx;
	}
	.bubble-row-user {
		justify-content: flex-end;
	}
	.bubble-row-ai {
		justify-content: flex-start;
	}
	.bubble {
		position: relative;
		max-width: 82%;
		padding: 28rpx 30rpx;
		border-radius: 32rpx;
		box-shadow: 0 14rpx 34rpx rgba(46, 29, 3, 0.08);
	}
	.bubble-text {
		font-size: 28rpx;
		line-height: 1.75;
		color: #2d2418;
		word-break: break-word;
		white-space: pre-wrap;
	}
	.bubble-user {
		background: linear-gradient(180deg, #ffe89c 0%, #fff0c6 100%);
		border-bottom-right-radius: 10rpx;
	}
	.bubble-user::after {
		content: '';
		position: absolute;
		right: -2rpx;
		bottom: -2rpx;
		width: 34rpx;
		height: 34rpx;
		background: linear-gradient(180deg, #ffe8a0 0%, #fff1cc 100%);
		clip-path: polygon(100% 0, 0 100%, 100% 100%);
	}
	.bubble-ai {
		background: #ffffff;
		border: 1rpx solid rgba(236, 227, 212, 0.95);
		border-bottom-left-radius: 10rpx;
	}
	.bubble-ai::after {
		content: '';
		position: absolute;
		left: -2rpx;
		bottom: -2rpx;
		width: 34rpx;
		height: 34rpx;
		background: #ffffff;
		border-left: 1rpx solid rgba(236, 227, 212, 0.95);
		border-bottom: 1rpx solid rgba(236, 227, 212, 0.95);
		clip-path: polygon(0 0, 0 100%, 100% 100%);
	}
	.shortcut-panel {
		position: fixed;
		left: 0;
		right: 0;
		bottom: calc(126rpx + env(safe-area-inset-bottom));
		padding: 0 26rpx;
	}
	.switch-row {
		width: 420rpx;
		margin: 0 auto 22rpx;
		padding: 8rpx;
		border-radius: 999rpx;
		background: #ffffff;
		border: 4rpx solid #f18c00;
		display: flex;
	}
	.switch-pill {
		flex: 1;
		height: 70rpx;
		line-height: 70rpx;
		text-align: center;
		font-size: 30rpx;
		font-weight: 600;
		color: #b16500;
		border-radius: 999rpx;
	}
	.switch-pill-active {
		background: linear-gradient(180deg, #ffb321 0%, #ff9400 100%);
		color: #ffffff;
		box-shadow: 0 8rpx 16rpx rgba(255, 157, 0, 0.24);
	}
	.chip-list {
		display: flex;
		flex-wrap: wrap;
		gap: 14rpx;
	}
	.chip-item {
		padding: 14rpx 24rpx;
		border-radius: 999rpx;
		background: rgba(255, 248, 233, 0.98);
		border: 2rpx solid rgba(255, 170, 27, 0.42);
		color: #9b5d00;
		font-size: 24rpx;
		box-shadow: 0 8rpx 18rpx rgba(255, 170, 27, 0.08);
	}
	.chip-item-outline {
		background: #fff;
		border-color: #f18c00;
		color: #eb7d00;
		font-weight: 600;
	}
	.composer {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		align-items: center;
		gap: 18rpx;
		padding: 18rpx 24rpx calc(18rpx + env(safe-area-inset-bottom));
		background: rgba(255, 255, 255, 0.98);
		border-top: 1rpx solid #f0e6d8;
	}
	.composer-input {
		flex: 1;
		height: 92rpx;
		padding: 0 28rpx;
		border-radius: 999rpx;
		border: 2rpx solid rgba(241, 140, 0, 0.4);
		background: #fffdf8;
		font-size: 30rpx;
		color: #2d2418;
	}
	.send-btn {
		width: 110rpx;
		height: 92rpx;
		border-radius: 999rpx;
		background: linear-gradient(180deg, #ffb11a 0%, #ff9400 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 14rpx 26rpx rgba(255, 157, 0, 0.26);
	}
	.send-btn-disabled {
		opacity: 0.68;
	}
	.send-icon {
		font-size: 38rpx;
		color: #ffffff;
		transform: translateX(2rpx);
	}
</style>
