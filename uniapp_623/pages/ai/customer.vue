<template>
	<view class="ai-customer-page">
		<view class="page-shell">
			<view class="page-top">
				<view class="top-bar">
					<view class="back-btn" @click="goBack">
						<image class="back-icon" src="/static/image/left-arrow.png" mode="aspectFit"></image>
					</view>
					<view class="brand-block">
						<image class="brand-logo" src="/static/image/logo.png" mode="aspectFit"></image>
						<view class="brand-copy">
							<text class="brand-en">GooMoo</text>
							<text class="brand-cn">AI 客服</text>
						</view>
					</view>
					<view class="top-spacer"></view>
				</view>
			</view>

			<view class="hero-card">
				<view class="hero-copy">
					<text class="hero-title">AI 客服小沐</text>
					<text class="hero-subtitle">商品、预售、订单问题都可以先问我</text>
					<view class="online-pill">
						<text class="online-dot"></text>
						<text>在线</text>
					</view>
				</view>
				<image class="hero-character" src="/static/image/goomoo-ai-xiaomu.jpg" mode="aspectFill"></image>
			</view>

			<scroll-view class="chat-scroll" scroll-y :scroll-into-view="scrollIntoView">
				<view class="chat-inner">
					<view class="ai-notice">本服务为AI生成内容，结果仅供参考</view>
					<view
						v-for="(item, index) in messages"
						:id="'msg-' + index"
						:key="index"
						:class="['message-row', item.role === 'user' ? 'message-row-user' : 'message-row-ai']"
					>
						<view v-if="item.role !== 'user'" class="ai-avatar-wrap">
							<image class="ai-avatar" src="/static/image/goomoo-ai-avatar.jpg" mode="aspectFill"></image>
						</view>
						<view class="message-main">
							<view :class="['speaker-line', item.role === 'user' ? 'speaker-line-user' : '']">
								<text>{{ item.role === 'user' ? '我' : '小沐' }}</text>
								<text v-if="item.role !== 'user'" class="ai-tag">AI</text>
							</view>
							<view :class="['bubble', item.role === 'user' ? 'bubble-user' : 'bubble-ai']">
								<text class="bubble-text">{{ item.content }}</text>
							</view>
						</view>
					</view>
				</view>
			</scroll-view>

			<view class="question-panel">
				<view class="question-head">
					<text class="question-title">猜你想问</text>
					<view class="refresh-btn" @click="refreshQuestions">
						<text>换一换</text>
						<text class="refresh-icon">↻</text>
					</view>
				</view>
				<view class="question-grid">
					<view
						v-for="(item, index) in quickCards"
						:key="'quick-' + index"
						class="question-card"
						@click="handleQuickCard(item)"
					>
						<image class="question-icon" :src="item.icon" mode="aspectFit"></image>
						<text class="question-label">{{ item.label }}</text>
						<text class="question-arrow">›</text>
					</view>
				</view>
			</view>
		</view>

		<view class="composer">
			<input
				v-model="draftQuestion"
				class="composer-input"
				maxlength="300"
				placeholder="请输入您的问题..."
				placeholder-class="composer-placeholder"
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

const QUICK_CARD_META = [
	{ label: '发货时间', icon: '/static/image/icon_shipped.png' },
	{ label: '预售规则', icon: '/static/image/booking-notice.png' },
	{ label: '支付方式', icon: '/static/image/icon_cart.png' },
	{ label: '转人工', icon: '/static/image/icon_service.png' }
]

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
			questionOffset: 0,
			scrollIntoView: ''
		}
	},
	computed: {
		welcomeText() {
			if (this.scene === 'aftersale') {
				return '你好呀！我是小沐，很高兴为您服务。\n订单、退款、物流问题都可以先告诉我。'
			}
			return '你好呀！我是小沐，很高兴为您服务。\n商品、预售、发货、尾款问题都可以先问我。'
		},
		quickQuestions() {
			return QUICK_QUESTIONS[this.scene] || QUICK_QUESTIONS.presale
		},
		quickCards() {
			return QUICK_CARD_META.map((item, index) => {
				const questionIndex = (this.questionOffset + index) % this.quickQuestions.length
				return {
					...item,
					question: this.quickQuestions[questionIndex]
				}
			})
		}
	},
	onLoad(options) {
		this.scene = options.scene === 'aftersale' ? 'aftersale' : 'presale'
		this.productId = options.productId || ''
		this.orderId = options.orderId || ''
		this.sourcePage = options.sourcePage || ''
		this.messages = [
			{
				role: 'ai',
				content: this.welcomeText
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
		handleQuickCard(item) {
			if (item.question === '请帮我转人工客服。') {
				this.goToManualCustomer()
				return
			}
			this.draftQuestion = item.question
		},
		refreshQuestions() {
			this.questionOffset = (this.questionOffset + 1) % this.quickQuestions.length
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
		background: #fff7ec;
		padding-bottom: calc(150rpx + env(safe-area-inset-bottom));
		box-sizing: border-box;
		color: #25180c;
	}
	.page-shell {
		padding: 0 24rpx 30rpx;
		box-sizing: border-box;
	}
	.page-top {
		margin: 0 -24rpx;
		padding: calc(var(--status-bar-height) + 8rpx) 24rpx 30rpx;
		background: linear-gradient(180deg, #ff9d00 0%, #ffb63e 58%, #fff7ec 100%);
	}
	.top-bar {
		height: 90rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.back-btn,
	.top-spacer {
		width: 70rpx;
		height: 70rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.back-icon {
		width: 34rpx;
		height: 34rpx;
	}
	.brand-block {
		display: flex;
		align-items: center;
		gap: 16rpx;
		color: #ffffff;
	}
	.brand-logo {
		width: 74rpx;
		height: 74rpx;
		border-radius: 22rpx;
		background: #ffffff;
		box-shadow: 0 10rpx 24rpx rgba(128, 74, 0, 0.18);
	}
	.brand-copy {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
	}
	.brand-en {
		font-size: 30rpx;
		font-weight: 800;
		line-height: 1.1;
	}
	.brand-cn {
		margin-top: 6rpx;
		font-size: 24rpx;
		font-weight: 700;
		line-height: 1.1;
		opacity: 0.92;
	}
	.hero-card {
		position: relative;
		height: 224rpx;
		border-radius: 34rpx;
		background: #fff7eb;
		border: 6rpx solid rgba(255, 255, 255, 0.96);
		box-shadow: 0 20rpx 52rpx rgba(169, 98, 0, 0.12);
		overflow: hidden;
		margin-top: -12rpx;
		margin-bottom: 28rpx;
	}
	.hero-copy {
		position: relative;
		z-index: 2;
		padding: 42rpx 300rpx 0 34rpx;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
	}
	.hero-title {
		font-size: 42rpx;
		font-weight: 800;
		line-height: 1.16;
		color: #25180c;
	}
	.hero-subtitle {
		margin-top: 16rpx;
		font-size: 25rpx;
		line-height: 1.45;
		color: #8a5a18;
	}
	.online-pill {
		margin-top: 16rpx;
		height: 42rpx;
		padding: 0 18rpx;
		border-radius: 999rpx;
		background: #fff7ec;
		display: flex;
		align-items: center;
		gap: 10rpx;
		font-size: 22rpx;
		color: #6d440a;
	}
	.online-dot {
		width: 14rpx;
		height: 14rpx;
		border-radius: 50%;
		background: #28c48c;
	}
	.hero-character {
		position: absolute;
		inset: 0;
		z-index: 1;
		width: 100%;
		height: 100%;
	}
	.chat-scroll {
		height: 548rpx;
	}
	.chat-inner {
		padding: 4rpx 4rpx 20rpx;
		box-sizing: border-box;
	}
	.ai-notice {
		margin-bottom: 24rpx;
		padding: 18rpx 22rpx;
		border-radius: 18rpx;
		background: rgba(255, 247, 224, 0.98);
		border: 2rpx solid rgba(241, 140, 0, 0.36);
		color: #8a5200;
		font-size: 24rpx;
		line-height: 1.5;
		text-align: center;
		box-shadow: 0 10rpx 24rpx rgba(255, 157, 0, 0.1);
	}
	.message-row {
		display: flex;
		margin-bottom: 30rpx;
	}
	.message-row-ai {
		justify-content: flex-start;
	}
	.message-row-user {
		justify-content: flex-end;
		align-items: flex-start;
	}
	.ai-avatar-wrap {
		width: 76rpx;
		margin-right: 18rpx;
		padding-top: 10rpx;
	}
	.ai-avatar {
		width: 76rpx;
		height: 76rpx;
		border-radius: 26rpx;
		background: #ffffff;
		border: 4rpx solid #fff4df;
		box-shadow: 0 10rpx 22rpx rgba(143, 86, 0, 0.1);
	}
	.message-main {
		max-width: 610rpx;
		display: flex;
		flex-direction: column;
	}
	.speaker-line {
		display: flex;
		align-items: center;
		gap: 8rpx;
		margin: 0 0 8rpx 4rpx;
		color: #9f6d25;
		font-size: 22rpx;
		font-weight: 600;
	}
	.speaker-line-user {
		justify-content: flex-end;
		margin-right: 4rpx;
	}
	.ai-tag {
		height: 26rpx;
		line-height: 26rpx;
		padding: 0 10rpx;
		border-radius: 999rpx;
		background: #fff0d2;
		color: #d97900;
		font-size: 18rpx;
		font-weight: 700;
	}
	.bubble {
		position: relative;
		padding: 24rpx 28rpx;
		border-radius: 26rpx;
		box-shadow: 0 14rpx 34rpx rgba(46, 29, 3, 0.08);
	}
	.bubble-text {
		font-size: 27rpx;
		line-height: 1.72;
		color: #2d2418;
		word-break: break-word;
		white-space: pre-wrap;
	}
	.bubble-user {
		background: linear-gradient(180deg, #ffe89c 0%, #fff0c6 100%);
		border-bottom-right-radius: 10rpx;
	}
	.bubble-ai {
		background: #ffffff;
		border: 1rpx solid rgba(236, 227, 212, 0.95);
		border-bottom-left-radius: 10rpx;
	}
	.question-panel {
		margin-top: 20rpx;
		padding: 24rpx;
		border-radius: 28rpx;
		background: #ffffff;
		box-shadow: 0 18rpx 42rpx rgba(163, 102, 8, 0.1);
	}
	.question-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 18rpx;
	}
	.question-title {
		font-size: 30rpx;
		font-weight: 800;
		color: #2c1c0d;
	}
	.refresh-btn {
		height: 48rpx;
		padding: 0 18rpx;
		border-radius: 999rpx;
		background: #fff3df;
		color: #c06c00;
		font-size: 22rpx;
		display: flex;
		align-items: center;
		gap: 6rpx;
	}
	.refresh-icon {
		font-size: 24rpx;
	}
	.question-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 16rpx;
	}
	.question-card {
		min-width: 0;
		height: 86rpx;
		padding: 0 18rpx;
		border-radius: 22rpx;
		background: #fffaf3;
		border: 2rpx solid rgba(255, 179, 64, 0.28);
		display: flex;
		align-items: center;
		box-sizing: border-box;
	}
	.question-icon {
		width: 34rpx;
		height: 34rpx;
		margin-right: 12rpx;
	}
	.question-label {
		flex: 1;
		min-width: 0;
		font-size: 24rpx;
		font-weight: 650;
		color: #5b390a;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.question-arrow {
		margin-left: 8rpx;
		font-size: 34rpx;
		color: #d97900;
		line-height: 1;
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
		box-sizing: border-box;
		z-index: 20;
	}
	.composer-input {
		flex: 1;
		height: 88rpx;
		padding: 0 28rpx;
		border-radius: 999rpx;
		border: 2rpx solid rgba(241, 140, 0, 0.35);
		background: #fffdf8;
		font-size: 28rpx;
		color: #2d2418;
		box-shadow: 0 8rpx 22rpx rgba(255, 157, 0, 0.06) inset;
	}
	.composer-placeholder {
		color: #b99a69;
	}
	.send-btn {
		width: 90rpx;
		height: 90rpx;
		border-radius: 50%;
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
		font-size: 36rpx;
		color: #ffffff;
		transform: translateX(2rpx);
	}
</style>
