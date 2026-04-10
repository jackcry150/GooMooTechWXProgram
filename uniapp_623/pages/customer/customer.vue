<template>
	<view class="customer">
		<view
			class="list"
			v-for="(item, index) in customerServiceList"
			:key="index"
			@click="goToCustomer(item)"
		>
			<image class="list-image" :src="item.image" mode="widthFix"></image>
		</view>
	</view>
</template>

<script>
import { api } from '@/utils/request.js'

export default {
	data() {
		return {
			customerServiceList: []
		}
	},
	onLoad() {
		this.getCustomerService()
	},
	methods: {
		goToCustomer(item) {
			// #ifdef MP-WEIXIN
			wx.openCustomerServiceChat({
				extInfo: {
					url: item.linkUrl
				},
				corpId: item.corpId,
				success: (res) => {
					console.log('openCustomerServiceChat success:', res)
				},
				fail: (err) => {
					console.log('openCustomerServiceChat fail:', err)
				}
			})
			// #endif
		},
		async getCustomerService() {
			try {
				const res = await api.server.list({ type: 3 })
				if (res.code === 200 && Array.isArray(res.data)) {
					this.customerServiceList = res.data.map((item) => ({
						id: item.id,
						title: item.title,
						corpId: item.corpId || '',
						image: Array.isArray(item.image) && item.image.length ? item.image[0] : '',
						linkUrl: item.link || ''
					}))
				}
			} catch (e) {
				uni.showToast({ title: '加载客服失败', icon: 'none' })
			}
		}
	}
}
</script>

<style scoped>
.customer {
	min-height: 100vh;
	display: flex;
	flex-direction: column;
	flex-wrap: nowrap;
	align-items: center;
}

.list {
	width: 86%;
	padding: 40rpx 0;
}

.list-image {
	width: 100%;
	height: auto;
}
</style>
