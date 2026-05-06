// API请求工具类
class Request {
	constructor() {
		// #ifdef  MP-WEIXIN
		this.baseURL = 'http://127.0.0.1:8080/api'
		// #endif
		
		// #ifdef H5
		this.baseURL = 'http://127.0.0.1:8080/api'
		// #endif
		this.timeout = 10000
		this.header = {
			'Content-Type': 'application/json'
		}
	}

	// 设置请求头
	setHeader(key, value) {
		this.header[key] = value
	}

	// 获取token
	getToken() {
		return uni.getStorageSync('token')
	}

	// 请求拦截器
	requestInterceptor(config) {
		// 添加token
		const token = this.getToken()
		if (token) {
			config.header.Authorization = `${token}`
		}

		// 添加时间戳防止缓存
		if (config.method === 'GET') {
			config.url += (config.url.includes('?') ? '&' : '?') + '_t=' + Date.now()
		}

		return config
	}

	// 响应拦截器
	responseInterceptor(response) {
		const { statusCode, data } = response

		// HTTP状态码检查
		if (statusCode !== 200) {
			uni.showToast({
				title: '网络请求失败',
				icon: 'none'
			})
			return Promise.reject(new Error('网络请求失败'))
		}

		// 业务状态码检查
		if (data.code === 401) {
			// token过期，清除本地存储并跳转到登录页
			uni.removeStorageSync('token')
			uni.showToast({
				title: '登录已过期，请重新登录',
				icon: 'none'
			})
			setTimeout(() => {
				uni.reLaunch({
					url: '/pages/login/login'
				})
			}, 1500)
			return Promise.reject(new Error('登录已过期'))
		}

		// if (data.code !== 200) {
		// 	uni.showToast({
		// 		title: data.msg || '请求失败',
		// 		icon: 'none'
		// 	})
		// 	return Promise.reject(new Error(data.msg || '请求失败'))
		// }

		return data
	}

	// 通用请求方法
	request(options) {
		return new Promise((resolve, reject) => {
			// 请求拦截
			const config = this.requestInterceptor({
				url: this.baseURL + options.url,
				method: options.method || 'GET',
				data: options.data || {},
				header: { ...this.header, ...options.header },
				timeout: options.timeout || this.timeout
			})

			uni.request({
				...config,
				success: (response) => {
					try {
						const result = this.responseInterceptor(response)
						resolve(result)
					} catch (error) {
						reject(error)
					}
				},
				fail: (error) => {
					uni.showToast({
						title: '网络连接失败',
						icon: 'none'
					})
					reject(error)
				}
			})
		})
	}

	// GET请求
	get(url, data = {}, options = {}) {
		return this.request({
			url,
			method: 'GET',
			data,
			...options
		})
	}

	// POST请求
	post(url, data = {}, options = {}) {
		return this.request({
			url,
			method: 'POST',
			data,
			...options
		})
	}

	// PUT请求
	put(url, data = {}, options = {}) {
		return this.request({
			url,
			method: 'PUT',
			data,
			...options
		})
	}
}

// 创建实例
const request = new Request()

// 导出API方法
export const api = {
	// 网站设置
	setting: {
		//网站设置
		info: (params) => request.get('/setting/info', params),
	},
	// 资讯相关
	news: {
		// 获取资讯详情（code: about / after_sale / service_agreement）
		detail: (params) => request.get('/news/detail', params),
	},
	//广告相关
	banner: {
		list: (params) => request.get('/banner/list', params),
	},
	// 商品相关
	product: {
		// 获取商品列表
		list: (params) => request.get('/product/list', params),
		// 获取商品详情
		detail: (params) => request.get('/product/detail', params),
	},
	// 收藏相关
	collect: {
		// 修改收藏
		edit: (params) => request.post('/collect/edit', params),
		// 批量取消收藏
		cancel: (params) => request.post('/collect/cancel', params),
		// 获取商品列表
		list: (params) => request.get('/collect/list', params),
		// 获取收藏数量
		count: (params) => request.get('/collect/count', params),
	},
	// 画册相关
	album: {
		// 获取画册列表
		list: (params) => request.get('/album/list', params),
		// 获取画册详情
		detail: (params) => request.get('/album/detail', params),
	},
	// 客服相关
	server: {
		//获取客服列表
		list: (params) => request.get('/server/list', params),
	},
	aiService: {
		sendMessage: (params) => request.post('/aiService/sendMessage', params),
	},
	// 用户认证相关
	auth: {
		// 手机号登录
		phone: (data) => request.post('/auth/phoneLogin', data),
		// 小程序 code 换 openid 登录
		onLogin: (data) => request.post('/auth/onLogin', data),
	},
	// 用户相关
	user: {
		// 获取用户信息
		profile: () => request.get('/user/profile'),
		// 更新昵称
		nickName: (data) => request.post('/user/nickName', data),
		// 更新头像
		avatar: (data) => request.post('/user/avatar', data),
		// 退出登录
		logout: () => request.post('/user/logout'),
	},
	// 订单相关
	order: {
		// 创建订单
		create: (params) => request.post('/order/create', params),
		// 获取订单列表
		list: (params) => request.get('/order/list', params),
		// 获取订单详情
		detail: (params) => request.get('/order/detail', params),
		// 取消订单
		cancel: (params) => request.post('/order/cancel', params),
		// 删除订单（仅已取消）
		delete: (params) => request.post('/order/delete', params),
		// 确认收货
		confirmReceipt: (params) => request.post('/order/confirmReceipt', params),
		// 支付订单
		pay: (params) => request.post('/order/pay', params),
	},
	// 购物车相关
	cart: {
		// 加入购物车
		create: (params) => request.post('/cart/create', params),
		// 获取购物车数量
		count: (params) => request.get('/cart/count', params),
		// 获取购物车列表
		list: (params) => request.get('/cart/list', params),
		// 批量删除购物车
		cancel: (params) => request.post('/cart/cancel', params),
		// 修改购物车数量
		quantity: (params) => request.post('/cart/quantity', params),
	},
	// 地址相关
	address: {
		// 添加地址
		create: (params) => request.post('/address/create', params),
		// 获取地址列表
		list: () => request.get('/address/list'),
		// 修改地址
		edit: (params) => request.post('/address/edit', params),
		// 地址信息
		detail: (params) => request.get('/address/detail', params),
		// 默认地址
		default: () => request.get('/address/default'),
		// 删除地址
		del: (params) => request.post('/address/del', params),
	},
	// 贩售相关
	sell: {
		// 获取最新贩售
		latest: () => request.get('/sell/latest'),
	},
}

export default request
