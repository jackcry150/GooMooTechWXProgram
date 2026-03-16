<template>
	<view class="page">
		<scroll-view scroll-y class="content">
			<text class="text">{{ content }}</text>
		</scroll-view>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		data() {
			return {
				content: ''
			}
		},
		onLoad() {
			this.loadContent()
		},
		methods: {
			async loadContent() {
				try {
					const res = await api.news.detail({ code: 'after_sale' })
					if (res.code === 200 && res.data) {
						this.content = res.data.content || ''
					}
				} catch (e) {
					uni.showToast({ title: '加载失败', icon: 'none' })
				}
			}
		}
	}
</script>

<style scoped>
	.page {
		min-height: 100vh;
		background: #ffffff;
	}
	.content {
		max-height: 100vh;
		padding: 30rpx;
		box-sizing: border-box;
	}
	.text {
		font-size: 28rpx;
		color: #333;
		line-height: 1.8;
		white-space: pre-wrap;
	}
</style>
