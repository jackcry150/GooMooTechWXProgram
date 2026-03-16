# 橘猫智酷商城小程序 API 接口文档

**版本**：1.0  
**BaseURL**：生产 `https://mp.goomooplay.com/api`，开发可配为 `http://127.0.1.23/api`  
**鉴权**：除登录、设置等公开接口外，请求头需带 `Authorization: {token}`（小程序登录后获得）

**统一响应**：
- 成功：`{ "code": 200, "msg": "成功", "data": ... }`
- 失败：`{ "code": 100, "msg": "错误说明" }`
- 未登录/过期：`{ "code": 401, "msg": "..." }`

---

## 1. 认证 Auth

### 1.1 手机号登录（微信解密手机号）

- **URL**：`POST /api/auth/phoneLogin`
- **说明**：小程序传 code + encryptedData + iv，后端解密手机号并创建/更新用户，返回 token。
- **请求体**：`{ "code": "", "encryptedData": "", "iv": "" }`
- **响应**：`{ "code": 200, "msg": "登录成功！", "data": { "token": "..." } }`

### 1.2 小程序 code 登录（openId）

- **URL**：`POST /api/auth/onLogin`
- **说明**：若项目实现为用 code 换 openId 生成 token（不解密手机号），接口形态以实际控制器为准；前端可能调用 `api.auth.onLogin(data)`。

---

## 2. 网站设置 Setting

### 2.1 获取站点设置

- **URL**：`GET /api/setting/info`
- **鉴权**：否
- **响应**：`{ "code": 200, "data": { "name", "link", "aboutUs", "contactUs", "address", "email", "customerLink", "corpId" } }`

---

## 3. 资讯 News

### 3.1 获取资讯详情

- **URL**：`GET /api/news/detail`
- **参数**：`code`（about | after_sale | service_agreement）或 `id`
- **鉴权**：否
- **响应**：`{ "code": 200, "data": { "id", "code", "title", "content" } }`

---

## 4. 广告 Banner

### 4.1 广告列表

- **URL**：`GET /api/banner/list`
- **参数**：`type`（1-banner 轮播，2-会员页）
- **鉴权**：否
- **响应**：`{ "code": 200, "data": [ { "id", "type", "title", "image", "link", "status", "sort" } ] }`

---

## 5. 商品 Product

### 5.1 商品列表

- **URL**：`GET /api/product/list`
- **参数**：可选分页、分类等（以实际控制器为准）
- **鉴权**：否
- **响应**：`{ "code": 200, "data": [ { "id", "productId", "title", "subtitle", "type", "image", "price", "deposit", "stock", "status", ... } ] }`

### 5.2 商品详情

- **URL**：`GET /api/product/detail`
- **参数**：`id` 商品主键
- **鉴权**：否
- **响应**：`{ "code": 200, "data": { "id", "title", "subtitle", "image", "price", "deposit", "stock", "limitStock", "content", "type", ... } }`

---

## 6. 收藏 Collect

- **鉴权**：是

### 6.1 添加/取消收藏

- **URL**：`POST /api/collect/edit`
- **请求体**：`{ "productId": 1, "status": 1 }`（1 收藏 0 取消，以实际为准）

### 6.2 收藏数量

- **URL**：`GET /api/collect/count`
- **响应**：`{ "code": 200, "data": { "count": 0 } }`

### 6.3 收藏列表

- **URL**：`GET /api/collect/list`
- **响应**：`{ "code": 200, "data": [ ... ] }`

### 6.4 批量取消收藏

- **URL**：`POST /api/collect/cancel` 或 `POST /api/collect/cancelAll`
- **请求体**：以实际控制器为准（如 ids 数组）

---

## 7. 画册 Album

### 7.1 画册列表

- **URL**：`GET /api/album/list`
- **鉴权**：否
- **响应**：`{ "code": 200, "data": [ ... ] }`

### 7.2 画册详情

- **URL**：`GET /api/album/detail`
- **参数**：`id`
- **鉴权**：否
- **响应**：`{ "code": 200, "data": { ... } }`

---

## 8. 客服 Server

### 8.1 客服/社群列表

- **URL**：`GET /api/server/list`
- **参数**：`type`（1-微信社区群，2-社交账号，3-在线客服）
- **鉴权**：否
- **响应**：`{ "code": 200, "data": [ { "id", "title", "image": [...], "link", "corpId" } ] }`  
  - 在线客服(type=3) 时，前端用 link 与 corpId 调 wx.openCustomerServiceChat。

---

## 9. 用户 User

- **鉴权**：是

### 9.1 用户信息

- **URL**：`GET /api/user/profile`
- **响应**：`{ "code": 200, "data": { "id", "nickName", "avatar", "collectionCards", "snailShells" } }`

### 9.2 修改昵称

- **URL**：`POST /api/user/nickName`
- **请求体**：`{ "nickName": "" }`

### 9.3 修改头像

- **URL**：`POST /api/user/avatar`
- **请求体**：`{ "avatar": "" }`

### 9.4 退出登录

- **URL**：`POST /api/user/logout`

---

## 10. 订单 Order

- **鉴权**：是

### 10.1 订单列表

- **URL**：`GET /api/order/list`
- **参数**：`status`（0 全部，1 待支付，2 待发货，6 待收货，7 已完成，4 已取消等）
- **响应**：`{ "code": 200, "data": [ { "id", "orderNo", "totalPrice", "product", "status", "statusVal", "statusClass" } ] }`  
  - 全部列表自动排除 status=5（已删除）。

### 10.2 订单详情

- **URL**：`GET /api/order/detail`
- **参数**：`id` 订单ID
- **响应**：`{ "code": 200, "data": { "id", "orderNo", "totalPrice", "productList", "addressInfo", "status", "statusText", "refundStatus", "isPresale", "canPayDeposit", "canPayBalance", "depositAmount", "balanceAmount", ... } }`

### 10.3 创建订单

- **URL**：`POST /api/order/create`
- **请求体**：`{ "product": [ { "productId", "version", "quantity", "id?" } ], "address": 地址ID或对象, "remarks": "", "shippingFee": 0 }`
- **响应**：`{ "code": 200, "data": { "orderId", "orderNo" } }`

### 10.4 取消订单

- **URL**：`POST /api/order/cancel`
- **请求体**：`{ "id": 订单ID }`
- **说明**：仅待支付(1)、已预定(8) 可取消。

### 10.5 删除订单

- **URL**：`POST /api/order/delete`
- **请求体**：`{ "id": 订单ID }`
- **说明**：仅已取消(4) 可删除，软删除为 status=5。

### 10.6 确认收货

- **URL**：`POST /api/order/confirmReceipt`
- **请求体**：`{ "id": 订单ID }`
- **说明**：仅待收货(6) 可确认；成功后按订单实付金额 1 元=1 蜗壳增加用户蜗壳。
- **响应**：`{ "code": 200, "msg": "确认收货成功，获得n蜗壳", "data": { "snailShells": n } }`

### 10.7 发起支付

- **URL**：`POST /api/order/pay`
- **请求体**：`{ "id": 订单ID, "payType": "full|deposit|balance", "appid": "微信小程序appid" }`
- **响应**：`{ "code": 200, "data": { "payment": { "timeStamp", "nonceStr", "package", "signType", "paySign" }, "orderNo", "payType" } }`  
  - 前端用 payment 调 wx.requestPayment。

### 10.8 支付回调（内部）

- **URL**：`POST /api/order/notify` 或项目内支付回调地址
- **说明**：汇付/微信异步通知，更新订单状态、扣库存、同步管家婆；不对外文档化参数。

---

## 11. 购物车 Cart

- **鉴权**：是

### 11.1 加入购物车

- **URL**：`POST /api/cart/create`
- **请求体**：`{ "productId", "version", "quantity" }`

### 11.2 购物车数量

- **URL**：`GET /api/cart/count`
- **参数**：可选 `productId` 查某商品数量
- **响应**：`{ "code": 200, "data": { "count": 0 } }`

### 11.3 购物车列表

- **URL**：`GET /api/cart/list`
- **响应**：`{ "code": 200, "data": [ { "id", "productId", "version", "quantity", "title", "image", "price", ... } ] }`

### 11.4 删除购物车项

- **URL**：`POST /api/cart/cancel`
- **请求体**：`{ "id": 购物车项ID }` 或 `ids` 数组（以实际为准）

### 11.5 修改数量

- **URL**：`POST /api/cart/quantity`
- **请求体**：`{ "id": 购物车项ID, "quantity": 1 }`
- **说明**：后端校验库存、限购，失败返回 code!=200 及 msg。

---

## 12. 地址 Address

- **鉴权**：是

### 12.1 添加地址

- **URL**：`POST /api/address/create`
- **请求体**：`{ "name", "phone", "province", "city", "region", "detail", "isDefault": 0|1 }`

### 12.2 地址列表

- **URL**：`GET /api/address/list`
- **响应**：`{ "code": 200, "data": [ ... ] }`

### 12.3 默认地址

- **URL**：`GET /api/address/default`
- **响应**：`{ "code": 200, "data": { ... } }`

### 12.4 地址详情

- **URL**：`GET /api/address/detail`
- **参数**：`id`

### 12.5 修改地址

- **URL**：`POST /api/address/edit`
- **请求体**：`{ "id", "name", "phone", "province", "city", "region", "detail", "isDefault" }`

### 12.6 删除地址

- **URL**：`POST /api/address/del`
- **请求体**：`{ "id": 地址ID }`

---

## 13. 贩售 Sell

### 13.1 最新贩售商品

- **URL**：`GET /api/sell/latest`
- **鉴权**：否
- **说明**：取 isSpecialSale=1 且上架的最新商品，用于贩售页。
- **响应**：`{ "code": 200, "data": { "productId", "title", "image", "startTime", "endTime", "stock", "status", ... } }`

---

## 14. 上传 Upfile

### 14.1 上传图片

- **URL**：`POST /api/upfile/image`（以实际路由为准）
- **请求**：multipart/form-data，字段名以实际为准
- **鉴权**：一般为后台 token 或接口鉴权
- **响应**：`{ "code": 200, "data": { "url": "..." } }` 或等价结构

---

## 15. 图库 Gallery（后台选择图片用）

- **URL**：`GET /api/gallery/list`（若开放给前端，以实际为准）
- **说明**：多用于后台管理选择图片，前端一般不直接调用。

---