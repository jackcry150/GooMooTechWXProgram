<?php

namespace app\api\controller;

use Exception;
use think\facade\Db;
use think\facade\Request;

class Lottery
{
    private function getUserToken()
    {
        $headers = Request::header();
        if (!$headers || !isset($headers['authorization'])) {
            return null;
        }
        $token = $headers['authorization'];
        $userToken = json_decode(decrypt(base64_decode($token)), true);
        if (!$userToken || !isset($userToken['userId'])) {
            return null;
        }
        return $userToken;
    }

    private function getSetting()
    {
        return find_brand_setting();
    }

    private function formatPrizeImage($image)
    {
        if (!$image) {
            return '';
        }
        if (strpos($image, 'http') === 0 || strpos($image, 'data:image') === 0) {
            return $image;
        }
        return Request::domain() . $image;
    }

    public function info()
    {
        $data = ['code' => 100, 'msg' => '操作失败'];
        try {
            if (!Request::isGet()) {
                return json($data);
            }

            $userToken = $this->getUserToken();
            if (!$userToken) {
                $data['code'] = 401;
                $data['msg'] = '请先登录';
                return json($data);
            }

            $setting = $this->getSetting();
            $userInfo = Db::name('user')->field('id, nickName, avatar, snailShells')->where('id', $userToken['userId'])->find();
            if (!$userInfo) {
                $data['code'] = 401;
                $data['msg'] = '用户不存在';
                return json($data);
            }

            $prizes = Db::name('lottery_prize')
                ->field('id, name, image, rewardType, rewardValue, weight, stock, description')
                ->where('status', 1)
                ->order('sort desc, id asc')
                ->select()
                ->toArray();

            foreach ($prizes as &$prize) {
                $prize['image'] = $this->formatPrizeImage($prize['image']);
                $prize['rewardTypeText'] = lotteryRewardType($prize['rewardType']);
                $prize['stockText'] = lotteryStockText($prize['stock']);
            }
            unset($prize);

            $records = Db::name('lottery_record')
                ->alias('r')
                ->leftJoin('user u', 'u.id = r.userId')
                ->field('r.id, r.prizeName, r.prizeImage, r.rewardType, r.rewardValue, r.description, r.createDate, u.nickName')
                ->where('r.userId', $userInfo['id'])
                ->order('r.id desc')
                ->limit(20)
                ->select()
                ->toArray();

            foreach ($records as &$record) {
                $record['prizeImage'] = $this->formatPrizeImage($record['prizeImage']);
                $record['rewardTypeText'] = lotteryRewardType($record['rewardType']);
            }
            unset($record);

            $userInfo['avatar'] = $this->formatPrizeImage($userInfo['avatar']);

            $data['code'] = 200;
            $data['msg'] = '成功！';
            $data['data'] = [
                'cost' => intval($setting['lotteryCost'] ?? 10),
                'rule' => $setting['lotteryRule'] ?? '',
                'userInfo' => $userInfo,
                'prizes' => $prizes,
                'records' => $records,
            ];
            return json($data);
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }

    public function draw()
    {
        $data = ['code' => 100, 'msg' => '操作失败'];
        try {
            if (!Request::isPost()) {
                return json($data);
            }

            $userToken = $this->getUserToken();
            if (!$userToken) {
                $data['code'] = 401;
                $data['msg'] = '请先登录';
                return json($data);
            }

            $setting = $this->getSetting();
            $cost = intval($setting['lotteryCost'] ?? 10);
            if ($cost <= 0) {
                $data['msg'] = '抽奖配置无效';
                return json($data);
            }

            Db::startTrans();
            try {
                $userInfo = Db::name('user')
                    ->where('id', $userToken['userId'])
                    ->lock(true)
                    ->find();
                if (!$userInfo) {
                    Db::rollback();
                    $data['code'] = 401;
                    $data['msg'] = '用户不存在';
                    return json($data);
                }
                if (intval($userInfo['snailShells']) < $cost) {
                    Db::rollback();
                    $data['msg'] = '猫饼不足，无法抽奖';
                    return json($data);
                }

                $prizes = Db::name('lottery_prize')
                    ->where('status', 1)
                    ->where(function ($query) {
                        $query->where('stock', '>', 0)->whereOr('stock', -1);
                    })
                    ->order('sort desc, id asc')
                    ->lock(true)
                    ->select()
                    ->toArray();

                if (empty($prizes)) {
                    Db::rollback();
                    $data['msg'] = '暂无可抽取奖品';
                    return json($data);
                }

                $totalWeight = 0;
                foreach ($prizes as $prize) {
                    $weight = max(0, intval($prize['weight']));
                    $totalWeight += $weight;
                }
                if ($totalWeight <= 0) {
                    Db::rollback();
                    $data['msg'] = '奖品权重配置无效';
                    return json($data);
                }

                $rand = mt_rand(1, $totalWeight);
                $current = 0;
                $selectedPrize = null;
                foreach ($prizes as $prize) {
                    $current += max(0, intval($prize['weight']));
                    if ($rand <= $current) {
                        $selectedPrize = $prize;
                        break;
                    }
                }
                if (!$selectedPrize) {
                    $selectedPrize = $prizes[count($prizes) - 1];
                }

                $beforeShells = intval($userInfo['snailShells']);
                $rewardType = intval($selectedPrize['rewardType']);
                $rewardValue = intval($selectedPrize['rewardValue']);
                $afterShells = $beforeShells - $cost;

                if ($rewardType === 2 && $rewardValue > 0) {
                    $afterShells += $rewardValue;
                }

                Db::name('user')->where('id', $userInfo['id'])->dec('snailShells', $cost)->update();
                if ($rewardType === 2 && $rewardValue > 0) {
                    Db::name('user')->where('id', $userInfo['id'])->inc('snailShells', $rewardValue)->update();
                } elseif ($rewardType === 3 && $rewardValue > 0) {
                    Db::name('user')->where('id', $userInfo['id'])->inc('collectionCards', $rewardValue)->update();
                }

                if (intval($selectedPrize['stock']) > 0) {
                    Db::name('lottery_prize')->where('id', $selectedPrize['id'])->dec('stock', 1)->update();
                }

                Db::name('lottery_record')->insert([
                    'userId' => $userInfo['id'],
                    'prizeId' => $selectedPrize['id'],
                    'prizeName' => $selectedPrize['name'],
                    'prizeImage' => $selectedPrize['image'],
                    'rewardType' => $rewardType,
                    'rewardValue' => $rewardValue,
                    'costShells' => $cost,
                    'snailShellsBefore' => $beforeShells,
                    'snailShellsAfter' => $afterShells,
                    'description' => $selectedPrize['description'] ?? '',
                    'createDate' => date('Y-m-d H:i:s'),
                    'createTime' => time(),
                ]);

                Db::commit();

                $data['code'] = 200;
                $data['msg'] = '抽奖成功！';
                $data['data'] = [
                    'cost' => $cost,
                    'snailShells' => $afterShells,
                    'prize' => [
                        'id' => $selectedPrize['id'],
                        'name' => $selectedPrize['name'],
                        'image' => $this->formatPrizeImage($selectedPrize['image']),
                        'rewardType' => $rewardType,
                        'rewardTypeText' => lotteryRewardType($rewardType),
                        'rewardValue' => $rewardValue,
                        'description' => $selectedPrize['description'] ?? '',
                    ],
                ];
                return json($data);
            } catch (Exception $e) {
                Db::rollback();
                $data['msg'] = '抽奖失败：' . $e->getMessage();
                return json($data);
            }
        } catch (Exception $e) {
            $data['msg'] = $e->getMessage();
            return json($data);
        }
    }
}
