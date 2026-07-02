<?php

namespace app\command;

use app\common\service\AiSafetyService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class AiSafetyClean extends Command
{
    protected function configure()
    {
        $this->setName('ai:safety-clean')
            ->setDescription('Clean expired AI customer service safety logs and conversations')
            ->addOption('days', null, Option::VALUE_OPTIONAL, 'Retention days', 90);
    }

    protected function execute(Input $input, Output $output)
    {
        $days = intval($input->getOption('days'));
        $result = (new AiSafetyService())->cleanExpired($days > 0 ? $days : 90);
        $output->writeln('days: ' . intval($result['days'] ?? 0));
        $output->writeln('cutoff: ' . (string) ($result['cutoff'] ?? ''));
        $output->writeln('ai_safety_log deleted: ' . intval($result['ai_safety_log'] ?? 0));
        $output->writeln('ai_service_message deleted: ' . intval($result['ai_service_message'] ?? 0));
        $output->writeln('ai_service_session deleted: ' . intval($result['ai_service_session'] ?? 0));
        if (!empty($result['skipped'])) {
            $output->writeln('skipped: ' . implode(', ', $result['skipped']));
        }
        return !empty($result['ok']) ? 0 : 1;
    }
}