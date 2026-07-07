<?php

namespace app\command;

use app\common\service\AiIntentMatcher;
use app\common\service\KnowledgeIndexer;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class RagSync extends Command
{
    protected function configure()
    {
        $this->setName('rag:sync')
            ->setDescription('Sync RAG knowledge sources and embedding jobs')
            ->addOption('products', null, Option::VALUE_NONE, 'Sync product sources')
            ->addOption('news', null, Option::VALUE_NONE, 'Sync news sources')
            ->addOption('all', null, Option::VALUE_NONE, 'Sync all business sources')
            ->addOption('jobs', null, Option::VALUE_OPTIONAL, 'Run pending embedding jobs', 0)
            ->addOption('intents', null, Option::VALUE_OPTIONAL, 'Run pending semantic intent embeddings', 0);
    }

    protected function execute(Input $input, Output $output)
    {
        $indexer = new KnowledgeIndexer();
        $syncAll = (bool) $input->getOption('all');
        $syncProducts = $syncAll || (bool) $input->getOption('products');
        $syncNews = $syncAll || (bool) $input->getOption('news');
        $jobLimit = intval($input->getOption('jobs'));
        $intentLimit = intval($input->getOption('intents'));

        if ($syncProducts) {
            $count = 0;
            $products = Db::name('product')->field('id')->select()->toArray();
            foreach ($products as $product) {
                $result = $indexer->syncProduct(intval($product['id']));
                if (!empty($result['ok'])) {
                    $count++;
                }
            }
            $output->writeln('products synced: ' . $count);
        }

        if ($syncNews) {
            $count = 0;
            $newsList = Db::name('news')->field('id')->select()->toArray();
            foreach ($newsList as $news) {
                $result = $indexer->syncNews(intval($news['id']));
                if (!empty($result['ok'])) {
                    $count++;
                }
            }
            $output->writeln('news synced: ' . $count);
        }

        if ($jobLimit > 0) {
            $result = $indexer->runPendingJobs($jobLimit);
            $output->writeln('jobs processed: ' . intval($result['processed'] ?? 0) . ', failed: ' . intval($result['failed'] ?? 0));
            if (empty($result['ok']) && !empty($result['error'])) {
                $output->writeln('jobs error: ' . $result['error']);
            }
        }

        if ($intentLimit > 0) {
            $result = (new AiIntentMatcher())->runPendingEmbeddings($intentLimit);
            $output->writeln('intents processed: ' . intval($result['processed'] ?? 0) . ', failed: ' . intval($result['failed'] ?? 0));
            if (empty($result['ok']) && !empty($result['error'])) {
                $output->writeln('intents error: ' . $result['error']);
            }
        }

        if (!$syncProducts && !$syncNews && $jobLimit <= 0 && $intentLimit <= 0) {
            $output->writeln('Nothing to do. Use --all, --products, --news, --jobs=50, or --intents=50.');
        }

        return 0;
    }
}
