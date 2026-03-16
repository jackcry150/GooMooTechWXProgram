<template>
	<view class="group">
		<image class="group-img" v-for="(ad, index) in adList" :key="index" :src="ad.image" mode="widthFix"></image>
	</view>
</template>

<script>
	import { api } from '@/utils/request.js'
	export default {
		data() {
			return {
				adList: [],
			};
		},
		onLoad() {
			this.loadAdList()
		},

		methods: {
			async loadAdList() {
				try {
					const params = {
						type: 2
					}
					const response = await api.banner.list(params)
					this.adList = response.data
				} catch (error) {

				}
			}
		}
	}
</script>

<style lang="scss">
	.group {
		background-color: #000000;
	}

	.group-img {
		width: 100%;
		height: auto;
	}
</style>