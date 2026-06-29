<?php

namespace app\common\service;

class XhsOrderSyncService
{
    public function normalizePhone($phone)
    {
        return preg_replace('/\D+/', '', strval($phone));
    }

    public function phoneMatches($left, $right)
    {
        $leftCandidates = $this->phoneCandidates($left);
        $rightCandidates = $this->phoneCandidates($right);

        return !empty(array_intersect($leftCandidates, $rightCandidates));
    }

    private function phoneCandidates($phone)
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return [];
        }

        $candidates = [$normalized];
        if (strlen($normalized) > 11) {
            $last11 = substr($normalized, -11);
            if (preg_match('/^1\d{10}$/', $last11)) {
                $candidates[] = $last11;
            }
        }

        return array_values(array_unique($candidates));
    }

    public function receiverPhoneMatches(array $receiver, $phone)
    {
        if (array_key_exists('matched', $receiver) && empty($receiver['matched'])) {
            return false;
        }

        return $this->phoneMatches($receiver['receiverPhone'] ?? '', $phone);
    }

    public function canBindOrder(array $order)
    {
        return intval($order['orderStatus'] ?? 0) === 4
            && intval($order['orderAfterSalesStatus'] ?? 1) === 1
            && intval($order['cancelStatus'] ?? 0) === 0
            && strval($order['openAddressId'] ?? '') !== '';
    }

    public function canRewardOrder(array $order)
    {
        return intval($order['orderStatus'] ?? 0) === 7
            && intval($order['orderAfterSalesStatus'] ?? 1) === 1
            && intval($order['cancelStatus'] ?? 0) === 0;
    }

    public function calculateEarnedShells($totalPayAmount)
    {
        return max(0, intval(floor(intval($totalPayAmount) / 100)));
    }
}

