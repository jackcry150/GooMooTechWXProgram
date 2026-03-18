<template>
	<view class="ai-customer-page">
		<view class="hero-card">
			<view class="hero-badge">{{ sceneLabel }}</view>
			<view class="hero-title">智能客服</view>
			<view class="hero-desc">{{ welcomeText }}</view>
		</view>

		<view class="context-card">
			<view class="section-title">当前会话</view>
			<view class="context-row">
				<text class="context-label">场景</text>
				<text class="context-value">{{ sceneLabel }}</text>
			</view>
			<view class="context-row" v-if="sourcePage">
				<text class="context-label">来源</text>
				<text class="context-value">{{ sourcePage }}</text>
			</view>
			<view class="context-row" v-if="productId">
				<text class="context-label">商品ID</text>
				<text class="context-value">{{ productId }}</text>
			</view>
			<view class="context-row" v-if="orderId">
				<text class="context-label">订单ID</text>
				<text class="context-value">{{ orderId }}</text>
			</view>
		</view>

		<view class="faq-card">
			<view class="section-title">快捷问题</view>
			<view class="faq-list">
				<view
					v-for="(item, index) in quickQuestions"
					:key="index"
					class="faq-item"
					@click="fillQuestion(item)"
				>
					{{ item }}
				</view>
			</view>
		</view>

		<view class="chat-card">
			<view class="section-title">对话记录</view>
			<view class="message-list">
				<view
					v-for="(item, index) in messages"
					:key="index"
					:class="['message-item', item.role === 'user' ? 'message-user' : 'message-ai']"
				>
					{{ item.content }}
				</view>
			</view>
			<textarea
				v-model="draftQuestion"
				class="question-input"
				maxlength="300"
				placeholder="请输入你的问题。"
			/>
			<view class="action-row">
				<button class="action-btn secondary" @click="goToManualCustomer">转人工</button>
				<button class="action-btn primary" @click="sendMessage">{{ sending ? '发送中...' : '发送' }}</button>
			</view>
		</view>

		<view class="tips-card">
			<view class="section-title">说明</view>
			<view class="tips-text">当前版本会结合商品或订单上下文返回内容，复杂问题会建议你转人工客服。</view>
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
			messages: []
		}
	},
	computed: {
		sceneLabel() {
			return this.scene === 'aftersale' ? '售后咨询' : '售前咨询'
		},
		welcomeText() {
			if (this.scene === 'aftersale') {
				return '可以先描述你的订单、退款或物流问题，复杂情况会建议你转人工客服处理。'
			}
			return '可以先咨询商品、预售、发货、支付和发票等问题。'
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
			{
				role: 'ai',
				content: this.welcomeText
			}
		]
	},
	methods: {
		fillQuestion(question) {
			this.draftQuestion = question
		},
		async sendMessage() {
			if (!this.draftQuestion.trim()) {
				uni.showToast({
					title: '请先输入问题',
					icon: 'none'
				})
				return
			}

			if (this.sending) {
				return
			}

			const content = this.draftQuestion.trim()
			this.messages.push({
				role: 'user',
				content
			})
			this.draftQuestion = ''
			this.sending = true

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
				this.messages.push({
					role: 'ai',
					content: replyText
				})
			} catch (error) {
				this.messages.push({
					role: 'ai',
					content: '请求失败了，你可以先转人工客服处理。'
				})
			} finally {
				this.sending = false
			}
		},
		goToManualCustomer() {
			uni.navigateTo({
				url: '/pages/customer/customer'
			})
		}
	}
}
</script>

<style scoped>
	.ai-customer-page {
		min-height: 100vh;
		padding: 24rpx;
		background: linear-gradient(180deg, #f6f1e8 0%, #f7f7f7 38%, #ffffff 100%);
		box-sizing: border-box;
	}

	.hero-card,
	.context-card,
	.faq-card,
	.chat-card,
	.tips-card {
		background: #ffffff;
		border-radius: 24rpx;
		padding: 28rpx;
		margin-bottom: 24rpx;
		box-shadow: 0 10rpx 30rpx rgba(38, 32, 24, 0.06);
	}

	.hero-card {
		background: linear-gradient(135deg, #1f1a17 0%, #3b312a 100%);
		color: #ffffff;
	}

	.hero-badge {
		display: inline-flex;
		padding: 8rpx 18rpx;
		border-radius: 999rpx;
		background: rgba(255, 255, 255, 0.18);
		font-size: 22rpx;
		margin-bottom: 16rpx;
	}

	.hero-title {
		font-size: 40rpx;
		font-weight: 600;
		margin-bottom: 12rpx;
	}

	.hero-desc {
		font-size: 26rpx;
		line-height: 1.7;
		color: rgba(255, 255, 255, 0.88);
	}

	.section-title {
		font-size: 32rpx;
		font-weight: 600;
		color: #2f2a26;
		margin-bottom: 20rpx;
	}

	.context-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16rpx 0;
		border-bottom: 1rpx solid #f0ebe5;
		font-size: 26rpx;
	}

	.context-row:last-child {
		border-bottom: 0;
	}

	.context-label {
		color: #8a8178;
	}

	.context-value {
		color: #2f2a26;
		font-weight: 500;
	}

	.faq-list {
		display: flex;
		flex-wrap: wrap;
		gap: 16rpx;
	}

	.faq-item {
		padding: 16rpx 22rpx;
		border-radius: 18rpx;
		background: #f7f2ec;
		color: #5f554c;
		font-size: 26rpx;
	}

	.message-list {
		display: flex;
		flex-direction: column;
		gap: 18rpx;
		margin-bottom: 20rpx;
	}

	.message-item {
		max-width: 100%;
		padding: 18rpx 20rpx;
		border-radius: 18rpx;
		font-size: 26rpx;
		line-height: 1.7;
		word-break: break-word;
	}

	.message-ai {
		background: #f7f2ec;
		color: #4d433b;
	}

	.message-user {
		background: #ece3ff;
		color: #4b3d73;
		align-self: flex-end;
	}

	.question-input {
		width: 100%;
		min-height: 180rpx;
		padding: 20rpx;
		border-radius: 18rpx;
		background: #faf7f3;
		font-size: 26rpx;
		box-sizing: border-box;
		margin-bottom: 20rpx;
	}

	.action-row {
		display: flex;
		gap: 20rpx;
	}

	.action-btn {
		flex: 1;
		height: 84rpx;
		line-height: 84rpx;
		border-radius: 18rpx;
		font-size: 28rpx;
	}

	.action-btn.secondary {
		background: #f2ece5;
		color: #5d5148;
	}

	.action-btn.primary {
		background: linear-gradient(135deg, #262018 0%, #4f4138 100%);
		color: #ffffff;
	}

	.tips-text {
		font-size: 24rpx;
		line-height: 1.8;
		color: #746a61;
		margin-bottom: 10rpx;
	}

	.tips-text:last-child {
		margin-bottom: 0;
	}
</style>
