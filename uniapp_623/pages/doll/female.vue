<template>
	<view class="female-doll-page">
		<view class="hero-section">
			<view class="hero-badge">Cute Doll Outfit Edit</view>
			<view class="hero-title">女性向娃衣专题</view>
			<view class="hero-desc">收集甜美、软萌、奶油感的可爱风娃衣，把适合拍照、送礼、陈列展示的款式集中放在这里。</view>
			<view class="hero-subcopy">当前先用测试商品做 demo，后面我们可以直接替换成真实商品 ID。</view>
		</view>

		<view class="intro-card">
			<view class="section-title">专题推荐</view>
			<view class="intro-grid">
				<view v-for="(item, index) in styleTags" :key="index" class="intro-tag">{{ item }}</view>
			</view>
		</view>

		<view class="style-panel">
			<view class="section-head">
				<view class="section-title">本期风格</view>
				<view class="section-tip">轻甜 · 软萌 · 少女感</view>
			</view>
			<view class="style-grid">
				<view v-for="(item, index) in styleBlocks" :key="index" class="style-card">
					<view class="style-name">{{ item.title }}</view>
					<view class="style-copy">{{ item.desc }}</view>
				</view>
			</view>
		</view>

		<view class="goods-section">
			<view class="section-head">
				<view class="section-title">可爱风精选</view>
				<view class="section-tip">测试商品位</view>
			</view>
			<view class="goods-list">
				<view
					v-for="item in goodsList"
					:key="item.key"
					class="goods-card"
					@click="goToProduct(item)"
				>
					<view class="goods-cover" :style="{ background: item.cover }">
						<view class="goods-cover-tag">{{ item.tag }}</view>
					</view>
					<view class="goods-info">
						<view>
							<view class="goods-name">{{ item.title }}</view>
							<view class="goods-subtitle">{{ item.subtitle }}</view>
						</view>
						<view class="goods-meta">
							<text class="goods-price">{{ item.priceText }}</text>
							<text class="goods-action">查看详情</text>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view class="lookbook-card">
			<view class="section-head">
				<view class="section-title">搭配建议</view>
				<view class="section-tip">给后续专题运营预留</view>
			</view>
			<view class="lookbook-list">
				<view v-for="(item, index) in lookbookTips" :key="index" class="lookbook-item">
					<view class="lookbook-index">0{{ index + 1 }}</view>
					<view class="lookbook-copy">{{ item }}</view>
				</view>
			</view>
		</view>

		<view class="footer-card">
			<view class="section-title">下一步建议</view>
			<view class="footer-copy">如果你觉得这个方向没问题，下一步我们就把这里的测试商品换成真实商品 ID，再生成这个页面专属的小程序码。</view>
			<view class="footer-actions">
				<view class="footer-btn primary" @click="goToProduct(goodsList[0])">先看测试商品</view>
				<view class="footer-btn secondary" @click="goToHome">返回首页</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			styleTags: ['甜美系', '软萌系', '粉色系', '蝴蝶结', '奶油感', '礼物推荐'],
			styleBlocks: [
				{ title: '轻甜展示', desc: '更适合拍照、礼盒展示和首页专题陈列。' },
				{ title: '软萌居家', desc: '适合偏治愈、奶呼呼、云朵感的搭配风格。' },
				{ title: '少女送礼', desc: '适合做节日、生日和可爱风送礼推荐。' }
			],
			goodsList: [
				{
					key: 'demo-1',
					id: 1,
					tag: 'Pink Sweet',
					title: '奶油草莓小裙装',
					subtitle: '适合可爱风陈列和软萌拍摄',
					priceText: '测试商品位 #1',
					cover: 'linear-gradient(135deg, #ffd6e7 0%, #ffc2d1 48%, #fff1c9 100%)'
				},
				{
					key: 'demo-2',
					id: 1,
					tag: 'Ribbon Mood',
					title: '蝴蝶结花边娃衣',
					subtitle: '更偏礼物感和展示感的一套',
					priceText: '测试商品位 #2',
					cover: 'linear-gradient(135deg, #ffe4f3 0%, #f8c7ff 52%, #ffd9b5 100%)'
				},
				{
					key: 'demo-3',
					id: 1,
					tag: 'Soft Cute',
					title: '糖果云朵居家款',
					subtitle: '适合温柔、蓬松、少女感风格',
					priceText: '测试商品位 #3',
					cover: 'linear-gradient(135deg, #fff1f7 0%, #ffd4e8 50%, #ffe7a3 100%)'
				},
				{
					key: 'demo-4',
					id: 1,
					tag: 'Gift Pick',
					title: '软糖礼盒主题款',
					subtitle: '适合专题页做送礼推荐和精选入口',
					priceText: '测试商品位 #4',
					cover: 'linear-gradient(135deg, #ffd6c7 0%, #ffe4ef 56%, #fff1d9 100%)'
				}
			],
			lookbookTips: [
				'可以把粉色、奶油白、蝴蝶结元素集中到同一专题里，页面风格会更统一。',
				'如果后面要做二维码推广，建议保持专题页标题、头图、商品风格一致，识别度会更高。',
				'下一步最适合直接换成真实商品 ID，这样你就能把这个专题页真正投放出去。'
			]
		}
	},
	methods: {
		goToProduct(item) {
			uni.navigateTo({
				url: '/pages/product/detail?id=' + item.id
			})
		},
		goToHome() {
			uni.switchTab({
				url: '/pages/index/index'
			})
		}
	}
}
</script>

<style scoped>
	.female-doll-page {
		min-height: 100vh;
		padding: 28rpx;
		box-sizing: border-box;
		background:
			radial-gradient(circle at top left, rgba(255, 219, 232, 0.95) 0%, rgba(255, 219, 232, 0) 32%),
			linear-gradient(180deg, #fff8fb 0%, #fffdf8 45%, #fff7ef 100%);
	}

	.hero-section,
	.intro-card,
	.style-panel,
	.goods-section,
	.lookbook-card,
	.footer-card {
		background: rgba(255, 255, 255, 0.9);
		backdrop-filter: blur(10rpx);
		border-radius: 30rpx;
		padding: 30rpx;
		margin-bottom: 24rpx;
		box-shadow: 0 16rpx 40rpx rgba(120, 72, 40, 0.08);
	}

	.hero-section {
		background: linear-gradient(135deg, #ffedf4 0%, #fff4d9 100%);
		border: 2rpx solid rgba(255, 194, 209, 0.55);
	}

	.hero-badge {
		display: inline-flex;
		padding: 10rpx 20rpx;
		border-radius: 999rpx;
		background: rgba(255, 255, 255, 0.76);
		font-size: 22rpx;
		color: #a85b6f;
		margin-bottom: 18rpx;
	}

	.hero-title {
		font-size: 46rpx;
		font-weight: 700;
		color: #5b352c;
		margin-bottom: 16rpx;
	}

	.hero-desc,
	.hero-subcopy,
	.footer-copy {
		font-size: 26rpx;
		line-height: 1.8;
		color: #7a6058;
	}

	.hero-subcopy {
		margin-top: 12rpx;
		color: #9a7564;
	}

	.section-title {
		font-size: 32rpx;
		font-weight: 700;
		color: #563228;
		margin-bottom: 20rpx;
	}

	.section-head {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 18rpx;
	}

	.section-tip {
		font-size: 22rpx;
		color: #b78372;
	}

	.intro-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 16rpx;
	}

	.intro-tag {
		padding: 14rpx 22rpx;
		border-radius: 999rpx;
		background: linear-gradient(135deg, #ffe0ec 0%, #fff0c8 100%);
		color: #8c5565;
		font-size: 24rpx;
	}

	.style-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 18rpx;
	}

	.style-card {
		padding: 22rpx;
		border-radius: 22rpx;
		background: #fff8f4;
	}

	.style-name {
		font-size: 28rpx;
		font-weight: 700;
		color: #5b352c;
		margin-bottom: 10rpx;
	}

	.style-copy,
	.lookbook-copy {
		font-size: 24rpx;
		line-height: 1.7;
		color: #82675d;
	}

	.goods-list {
		display: flex;
		flex-direction: column;
		gap: 22rpx;
	}

	.goods-card {
		display: flex;
		gap: 22rpx;
		padding: 18rpx;
		border-radius: 24rpx;
		background: #fffaf7;
	}

	.goods-cover {
		width: 190rpx;
		height: 190rpx;
		border-radius: 22rpx;
		position: relative;
		flex-shrink: 0;
	}

	.goods-cover-tag {
		position: absolute;
		left: 14rpx;
		top: 14rpx;
		padding: 8rpx 14rpx;
		border-radius: 999rpx;
		background: rgba(255, 255, 255, 0.82);
		font-size: 20rpx;
		color: #925f70;
	}

	.goods-info {
		flex: 1;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 4rpx 0;
	}

	.goods-name {
		font-size: 30rpx;
		font-weight: 700;
		color: #55322d;
		margin-bottom: 10rpx;
	}

	.goods-subtitle {
		font-size: 24rpx;
		line-height: 1.7;
		color: #82675d;
	}

	.goods-meta {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 18rpx;
	}

	.goods-price {
		font-size: 24rpx;
		color: #c26f56;
	}

	.goods-action,
	.footer-btn {
		padding: 10rpx 18rpx;
		border-radius: 999rpx;
		font-size: 22rpx;
	}

	.goods-action {
		background: linear-gradient(135deg, #ffbf7d 0%, #ff8ea8 100%);
		color: #ffffff;
	}

	.lookbook-list {
		display: flex;
		flex-direction: column;
		gap: 18rpx;
	}

	.lookbook-item {
		display: flex;
		gap: 18rpx;
		align-items: flex-start;
		padding: 18rpx 0;
		border-bottom: 1rpx solid #f3e7df;
	}

	.lookbook-item:last-child {
		border-bottom: 0;
		padding-bottom: 0;
	}

	.lookbook-index {
		width: 56rpx;
		height: 56rpx;
		line-height: 56rpx;
		text-align: center;
		border-radius: 999rpx;
		background: linear-gradient(135deg, #ffdbe7 0%, #ffeec9 100%);
		color: #9d6271;
		font-size: 22rpx;
		flex-shrink: 0;
	}

	.footer-actions {
		display: flex;
		gap: 16rpx;
		margin-top: 24rpx;
	}

	.footer-btn {
		flex: 1;
		text-align: center;
	}

	.footer-btn.primary {
		background: linear-gradient(135deg, #ffbf7d 0%, #ff8ea8 100%);
		color: #ffffff;
	}

	.footer-btn.secondary {
		background: #fff3e8;
		color: #8a5f4f;
	}
</style>
