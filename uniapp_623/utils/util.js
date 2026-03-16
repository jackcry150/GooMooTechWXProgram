// 工具函数库



/**
 * 格式化手机号
 * @param {string} phone 手机号
 * @returns {string} 格式化后的手机号
 */
export function formatPhone(phone) {
    if (!phone) return ''
    return phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

/**
 * 验证手机号
 * @param {string} phone 手机号
 * @returns {boolean} 是否有效
 */
export function validatePhone(phone) {
    const reg = /^1[3-9]\d{9}$/
    return reg.test(phone)
}

/**
 * 验证邮箱
 * @param {string} email 邮箱
 * @returns {boolean} 是否有效
 */
export function validateEmail(email) {
    const reg = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
    return reg.test(email)
}

/**
 * 验证密码强度
 * @param {string} password 密码
 * @returns {object} 验证结果
 */
export function validatePassword(password) {
    const result = {
        valid: false,
        strength: 0,
        message: ''
    }
    
    if (!password) {
        result.message = '密码不能为空'
        return result
    }
    
    if (password.length < 6) {
        result.message = '密码长度不能少于6位'
        return result
    }
    
    if (password.length > 20) {
        result.message = '密码长度不能超过20位'
        return result
    }
    
    // 计算密码强度
    let strength = 0
    if (/[a-z]/.test(password)) strength++
    if (/[A-Z]/.test(password)) strength++
    if (/[0-9]/.test(password)) strength++
    if (/[^a-zA-Z0-9]/.test(password)) strength++
    
    result.strength = strength
    result.valid = true
    
    if (strength < 2) {
        result.message = '密码强度较弱，建议包含字母和数字'
    } else if (strength < 3) {
        result.message = '密码强度中等'
    } else {
        result.message = '密码强度较强'
    }
    
    return result
}

/**
 * 防抖函数
 * @param {Function} func 要防抖的函数
 * @param {number} delay 延迟时间
 * @returns {Function} 防抖后的函数
 */
export function debounce(func, delay = 300) {
    let timeoutId
    return function (...args) {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => func.apply(this, args), delay)
    }
}

/**
 * 节流函数
 * @param {Function} func 要节流的函数
 * @param {number} delay 延迟时间
 * @returns {Function} 节流后的函数
 */
export function throttle(func, delay = 300) {
    let lastTime = 0
    return function (...args) {
        const now = Date.now()
        if (now - lastTime >= delay) {
            lastTime = now
            func.apply(this, args)
        }
    }
}

/**
 * 深拷贝
 * @param {any} obj 要拷贝的对象
 * @returns {any} 拷贝后的对象
 */
export function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') {
        return obj
    }
    
    if (obj instanceof Date) {
        return new Date(obj.getTime())
    }
    
    if (obj instanceof Array) {
        return obj.map(item => deepClone(item))
    }
    
    if (typeof obj === 'object') {
        const clonedObj = {}
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                clonedObj[key] = deepClone(obj[key])
            }
        }
        return clonedObj
    }
}

/**
 * 获取文件扩展名
 * @param {string} filename 文件名
 * @returns {string} 扩展名
 */
export function getFileExtension(filename) {
    return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2)
}

/**
 * 生成随机字符串
 * @param {number} length 长度
 * @returns {string} 随机字符串
 */
export function randomString(length = 8) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    let result = ''
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
}

/**
 * 获取订单状态文本
 * @param {number} status 状态码
 * @returns {string} 状态文本
 */
export function getOrderStatusText(status) {
    const statusMap = {
        0: '待支付',
        1: '待发货',
        2: '待收货',
        3: '已完成',
        4: '已取消'
    }
    return statusMap[status] || '未知状态'
}

/**
 * 获取订单状态颜色
 * @param {number} status 状态码
 * @returns {string} 状态颜色
 */
export function getOrderStatusColor(status) {
    const colorMap = {
        0: '#f0ad4e',
        1: '#007aff',
        2: '#4cd964',
        3: '#4cd964',
        4: '#999999'
    }
    return colorMap[status] || '#999999'
}

/**
 * 显示加载提示
 * @param {string} title 提示文本
 */
export function showLoading(title = '加载中...') {
    uni.showLoading({
        title,
        mask: true
    })
}

/**
 * 隐藏加载提示
 */
export function hideLoading() {
    uni.hideLoading()
}

/**
 * 显示成功提示
 * @param {string} title 提示文本
 */
export function showSuccess(title = '操作成功') {
    uni.showToast({
        title,
        icon: 'success',
        duration: 2000
    })
}

/**
 * 显示错误提示
 * @param {string} title 提示文本
 */
export function showError(title = '操作失败') {
    uni.showToast({
        title,
        icon: 'none',
        duration: 2000
    })
}

/**
 * 显示确认对话框
 * @param {string} content 内容
 * @param {string} title 标题
 * @returns {Promise<boolean>} 用户选择结果
 */
export function showConfirm(content, title = '提示') {
    return new Promise((resolve) => {
        uni.showModal({
            title,
            content,
            success: (res) => {
                resolve(res.confirm)
            }
        })
    })
}

/**
 * 页面跳转
 * @param {string} url 页面路径
 * @param {object} params 参数
 */
export function navigateTo(url, params = {}) {
    const query = Object.keys(params).map(key => `${key}=${encodeURIComponent(params[key])}`).join('&')
    const fullUrl = query ? `${url}?${query}` : url
    
    uni.navigateTo({
        url: fullUrl
    })
}

/**
 * 页面重定向
 * @param {string} url 页面路径
 * @param {object} params 参数
 */
export function redirectTo(url, params = {}) {
    const query = Object.keys(params).map(key => `${key}=${encodeURIComponent(params[key])}`).join('&')
    const fullUrl = query ? `${url}?${query}` : url
    
    uni.redirectTo({
        url: fullUrl
    })
}

/**
 * 返回上一页
 * @param {number} delta 返回层数
 */
export function navigateBack(delta = 1) {
    uni.navigateBack({
        delta
    })
}
