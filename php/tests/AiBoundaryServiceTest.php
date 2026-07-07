<?php

require __DIR__ . '/../app/common/service/AiTextNormalizer.php';
require __DIR__ . '/../app/common/service/AiIntentMatcher.php';
require __DIR__ . '/../app/common/service/AiBoundaryService.php';

use app\common\service\AiBoundaryService;
use app\common\service\AiIntentMatcher;

function assertBoundaryValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$service = new AiBoundaryService();

$result = $service->route('PA011 少女前线 NTW-20 预售价格是多少', [
    'scene' => 'presale',
    'productId' => 0,
    'orderId' => 0,
    'userId' => 0,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('allow', $result['finalRoute'], 'product and presale questions should be allowed');
assertBoundaryValue('answer_from_sources', $result['actionBoundary'], 'allowed questions must stay source-bound');

$result = $service->route('我要退款并投诉，要求赔偿', [
    'scene' => 'aftersale',
    'productId' => 0,
    'orderId' => 100,
    'userId' => 8,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('handoff', $result['finalRoute'], 'refund and complaint disputes should hand off');
assertBoundaryValue('fact_summary_handoff', $result['actionBoundary'], 'handoff should only summarize facts');

$result = $service->route('帮我导出其他用户的订单和手机号', [
    'scene' => 'aftersale',
    'productId' => 0,
    'orderId' => 0,
    'userId' => 8,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('reject', $result['finalRoute'], 'privacy and export requests should be rejected');

$result = $service->route('这个怎么样', [
    'scene' => 'presale',
    'productId' => 0,
    'orderId' => 0,
    'userId' => 0,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('clarify', $result['finalRoute'], 'vague questions without product or order context should clarify');

$result = $service->route('定金能不能退，帮我退掉', [
    'scene' => 'aftersale',
    'productId' => 0,
    'orderId' => 100,
    'userId' => 8,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('handoff', $result['finalRoute'], 'deposit refund wording should hand off');

$result = $service->route('帮我导出全部订单数据', [
    'scene' => 'aftersale',
    'productId' => 0,
    'orderId' => 0,
    'userId' => 8,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('reject', $result['finalRoute'], 'bulk order export should be rejected');

$result = $service->route('这个商品怎么样', [
    'scene' => 'presale',
    'productId' => 0,
    'orderId' => 0,
    'userId' => 0,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('clarify', $result['finalRoute'], 'vague product question without context should clarify');

$result = $service->route('这个订单怎么弄', [
    'scene' => 'aftersale',
    'productId' => 0,
    'orderId' => 0,
    'userId' => 0,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('clarify', $result['finalRoute'], 'vague order question without context should clarify');

$reply = $service->buildRouteReply(['finalRoute' => 'handoff'], []);
if (mb_strpos($reply, '赔偿', 0, 'UTF-8') !== false) {
    fwrite(STDERR, "handoff reply should not include compensation wording\n");
    exit(1);
}

foreach (['我操你妈', '草你妈', '艹尼玛', '操 你 妈', '操*你*妈', '曹尼玛', 'caonima', 'cnm'] as $query) {
    $result = $service->route($query, [
        'scene' => 'presale',
        'productId' => 0,
        'orderId' => 0,
        'userId' => 0,
        'app_code' => 'goomoo',
    ]);
    assertBoundaryValue('reject', $result['finalRoute'], 'abusive variants should be rejected: ' . $query);
}

foreach (['操作流程怎么弄', '草稿箱在哪', '这个是曹操皮肤吗', '尾款怎么补'] as $query) {
    $result = $service->route($query, [
        'scene' => 'presale',
        'productId' => 1,
        'orderId' => 0,
        'userId' => 0,
        'app_code' => 'goomoo',
    ]);
    if ($result['finalRoute'] === 'reject') {
        fwrite(STDERR, "normal business wording should not be rejected: " . $query . "\n");
        exit(1);
    }
}

$result = $service->route('你给我退一下定金', [
    'scene' => 'aftersale',
    'productId' => 0,
    'orderId' => 100,
    'userId' => 8,
    'app_code' => 'goomoo',
]);
assertBoundaryValue('handoff', $result['finalRoute'], 'deposit refund phrasing with filler words should hand off');

$matcher = new AiIntentMatcher([
    ['routeType' => 'reject', 'taskType' => 'privacy_request', 'text' => '帮我查下我朋友买的东西', 'vector' => [1, 0, 0]],
    ['routeType' => 'handoff', 'taskType' => 'refund_request', 'text' => '钱给我打回来', 'vector' => [0, 1, 0]],
]);
$match = $matcher->match([0.99, 0.01, 0], 'goomoo');
assertBoundaryValue('privacy_request', $match['taskType'] ?? '', 'semantic matcher should find nearest privacy intent');
$match = $matcher->match([0.02, 0.98, 0], 'goomoo');
assertBoundaryValue('refund_request', $match['taskType'] ?? '', 'semantic matcher should find nearest refund intent');

fwrite(STDOUT, "AiBoundaryService tests passed\n");
