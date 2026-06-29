<?php

require __DIR__ . '/../app/common/service/XhsOrderSyncService.php';

use app\common\service\XhsOrderSyncService;

function assertXhsSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$service = new XhsOrderSyncService();

assertXhsSameValue('13800138000', $service->normalizePhone(' 138 0013 8000 '), 'normalizes phone input to digits');
assertXhsSameValue(true, $service->phoneMatches('13800138000', '138 0013 8000'), 'matches the same phone after normalization');
assertXhsSameValue(true, $service->phoneMatches('+86 13800138000', '13800138000'), 'matches phone with China country code prefix');
assertXhsSameValue(true, $service->phoneMatches('0086-13800138000', '13800138000'), 'matches phone with 0086 country code prefix');
assertXhsSameValue(false, $service->phoneMatches('13800138000', '13900139000'), 'rejects different phones');
assertXhsSameValue(true, $service->receiverPhoneMatches(['receiverPhone' => '13800138000'], '138 0013 8000'), 'matches receiver phone when matched flag is absent');
assertXhsSameValue(false, $service->receiverPhoneMatches(['matched' => false, 'receiverPhone' => '13800138000'], '13800138000'), 'rejects receiver phone when matched flag is explicitly false');
assertXhsSameValue(false, $service->receiverPhoneMatches(['matched' => true, 'receiverPhone' => '#abc=#def=#3##'], '13800138000'), 'does not treat encrypted receiver phone as comparable without decrypting it');
assertXhsSameValue(false, $service->receiverPhoneMatches(['receiverPhone' => '#abc=#def=#3##'], '13800138000'), 'rejects encrypted receiver phone when matched flag is absent');

$waitingShipment = [
    'orderStatus' => 4,
    'orderAfterSalesStatus' => 1,
    'cancelStatus' => 0,
    'openAddressId' => 'addr-1',
];
assertXhsSameValue(true, $service->canBindOrder($waitingShipment), 'allows binding only for waiting-shipment orders with address id');

$finished = [
    'orderStatus' => 7,
    'orderAfterSalesStatus' => 1,
    'cancelStatus' => 0,
];
assertXhsSameValue(true, $service->canRewardOrder($finished), 'allows reward for finished orders without aftersale or cancellation');
assertXhsSameValue(false, $service->canBindOrder($finished), 'does not allow first binding after shipment window');

$afterSale = [
    'orderStatus' => 7,
    'orderAfterSalesStatus' => 2,
    'cancelStatus' => 0,
];
assertXhsSameValue(false, $service->canRewardOrder($afterSale), 'does not reward aftersale orders');

assertXhsSameValue(100, $service->calculateEarnedShells(10000), 'converts paid cents to one shell per paid yuan');
assertXhsSameValue(99, $service->calculateEarnedShells(9999), 'rounds paid cents down to full yuan');

fwrite(STDOUT, "XhsOrderSyncService tests passed\n");
