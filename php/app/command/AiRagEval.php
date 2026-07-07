<?php

namespace app\command;

use app\common\service\AiBoundaryService;
use app\common\service\AiIntentMatcher;
use app\common\service\EmbeddingClient;
use app\common\service\RagConfig;
use app\common\service\RagRetriever;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use Throwable;

class AiRagEval extends Command
{
    protected function configure()
    {
        $this->setName('ai:rag-eval')
            ->setDescription('Evaluate AI customer-service boundary routing and RAG retrieval')
            ->addOption('id', null, Option::VALUE_OPTIONAL, 'Run one case id', '')
            ->addOption('skip-llm', null, Option::VALUE_NONE, 'Skip answer generation checks for allow cases');
    }

    protected function execute(Input $input, Output $output)
    {
        $caseId = trim((string) $input->getOption('id'));
        $skipLlm = (bool) $input->getOption('skip-llm');
        $cases = $this->loadCases();
        if ($caseId !== '') {
            $cases = array_values(array_filter($cases, function ($case) use ($caseId) {
                return (string) ($case['id'] ?? '') === $caseId;
            }));
        }
        if (empty($cases)) {
            $output->writeln('No eval cases found' . ($caseId !== '' ? (': ' . $caseId) : ''));
            return 1;
        }

        $summary = [
            'route' => ['PASS' => 0, 'FAIL' => 0, 'SKIP' => 0],
            'retrieval' => ['PASS' => 0, 'FAIL' => 0, 'SKIP' => 0],
            'answer' => ['PASS' => 0, 'FAIL' => 0, 'SKIP' => 0],
            'citation' => ['PASS' => 0, 'FAIL' => 0, 'SKIP' => 0],
        ];
        $boundaryService = new AiBoundaryService();
        $retriever = new RagRetriever();

        foreach ($cases as $case) {
            $query = (string) ($case['query'] ?? '');
            $scene = (string) ($case['scene'] ?? 'presale');
            $appCode = (string) ($case['app_code'] ?? 'goomoo');
            $requiresSemantic = !empty($case['requires_semantic']);
            $questionVector = $requiresSemantic ? $this->buildQuestionVector($query) : [];
            if ($requiresSemantic && (empty($questionVector) || !(new AiIntentMatcher())->hasUsableExamples($appCode))) {
                $this->record($summary, 'route', 'SKIP');
                $this->record($summary, 'retrieval', 'SKIP');
                $this->record($summary, 'answer', 'SKIP');
                $this->record($summary, 'citation', 'SKIP');
                $output->writeln('case=' . (string) ($case['id'] ?? ''));
                $output->writeln('route=SKIP expected=' . (string) ($case['expected_route'] ?? 'allow') . ' actual=semantic_unavailable');
                $output->writeln('retrieval=SKIP missing=- sourceIds=-');
                $output->writeln('answer=SKIP missing=-');
                $output->writeln('citation=SKIP sources=0');
                $output->writeln('');
                continue;
            }

            $route = $boundaryService->route($query, [
                'scene' => $scene,
                'productId' => intval($case['productId'] ?? 0),
                'orderId' => intval($case['orderId'] ?? 0),
                'userId' => intval($case['userId'] ?? 0),
                'app_code' => $appCode,
                'questionVector' => $questionVector,
            ]);
            $actualRoute = (string) ($route['finalRoute'] ?? 'allow');
            $expectedRoute = (string) ($case['expected_route'] ?? 'allow');
            $routePass = $actualRoute === $expectedRoute;
            $this->record($summary, 'route', $routePass ? 'PASS' : 'FAIL');

            $contexts = [];
            if ($actualRoute === 'allow') {
                $retrieval = $retriever->retrieve($query, [
                    'scene' => $scene,
                    'productId' => intval($case['productId'] ?? 0),
                    'orderId' => intval($case['orderId'] ?? 0),
                    'app_code' => $appCode,
                    'questionVector' => $questionVector,
                ]);
                $contexts = is_array($retrieval['contexts'] ?? null) ? $retrieval['contexts'] : [];
            }

            $retrievalCheck = $this->checkTerms($case['expected_source_terms'] ?? [], $this->joinContexts($contexts));
            $retrievalStatus = $retrievalCheck['ok'] ? 'PASS' : 'FAIL';
            $this->record($summary, 'retrieval', $retrievalStatus);

            $answer = '';
            $answerStatus = 'SKIP';
            $answerMissing = [];
            if (!$skipLlm || $actualRoute !== 'allow') {
                $answer = $actualRoute === 'allow'
                    ? $this->buildContextAnswer($contexts)
                    : $boundaryService->buildRouteReply($route, []);
                $answerCheck = $this->checkAnswer($answer, $case['expected_answer_terms'] ?? [], $case['forbidden_terms'] ?? []);
                $answerStatus = $answerCheck['ok'] ? 'PASS' : 'FAIL';
                $answerMissing = $answerCheck['missing'];
            }
            $this->record($summary, 'answer', $answerStatus);

            $sourceIds = $this->extractSourceIds($contexts);
            $citationStatus = 'SKIP';
            if ($actualRoute === 'allow') {
                $citationStatus = (!empty($sourceIds) && $this->chunksExist($contexts)) ? 'PASS' : 'FAIL';
            } elseif (empty($case['expected_source_terms'] ?? [])) {
                $citationStatus = 'PASS';
            }
            $this->record($summary, 'citation', $citationStatus);

            $output->writeln('case=' . (string) ($case['id'] ?? ''));
            $output->writeln('route=' . ($routePass ? 'PASS' : 'FAIL') . ' expected=' . $expectedRoute . ' actual=' . $actualRoute);
            $output->writeln('retrieval=' . $retrievalStatus . ' missing=' . $this->formatMissing($retrievalCheck['missing']) . ' sourceIds=' . $this->formatMissing($sourceIds));
            $output->writeln('answer=' . $answerStatus . ' missing=' . $this->formatMissing($answerMissing));
            $output->writeln('citation=' . $citationStatus . ' sources=' . count($sourceIds));
            $output->writeln('');
        }

        foreach ($summary as $layer => $counts) {
            $output->writeln($layer . ' summary: PASS=' . $counts['PASS'] . ' FAIL=' . $counts['FAIL'] . ' SKIP=' . $counts['SKIP']);
        }

        return $this->hasFailures($summary) ? 1 : 0;
    }


    private function buildQuestionVector(string $query): array
    {
        if (!RagConfig::enabled()) {
            return [];
        }
        try {
            $embed = (new EmbeddingClient(RagConfig::load()))->embed($query);
            return !empty($embed['ok']) && is_array($embed['embedding'] ?? null) ? $embed['embedding'] : [];
        } catch (Throwable $e) {
            return [];
        }
    }
    private function loadCases(): array
    {
        $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'rag_eval_cases.json';
        if (!is_file($file)) {
            return [];
        }
        $cases = json_decode((string) file_get_contents($file), true);
        return is_array($cases) ? $cases : [];
    }

    private function checkTerms($terms, string $haystack): array
    {
        $missing = [];
        foreach ((array) $terms as $term) {
            $term = (string) $term;
            if ($term !== '' && mb_stripos($haystack, $term, 0, 'UTF-8') === false) {
                $missing[] = $term;
            }
        }
        return ['ok' => empty($missing), 'missing' => $missing];
    }

    private function checkAnswer(string $answer, $expectedTerms, $forbiddenTerms): array
    {
        $missing = $this->checkTerms($expectedTerms, $answer)['missing'];
        foreach ((array) $forbiddenTerms as $term) {
            $term = (string) $term;
            if ($term !== '' && mb_stripos($answer, $term, 0, 'UTF-8') !== false) {
                $missing[] = 'forbidden:' . $term;
            }
        }
        return ['ok' => empty($missing), 'missing' => $missing];
    }

    private function buildContextAnswer(array $contexts): string
    {
        $parts = [];
        foreach (array_slice($contexts, 0, 3) as $context) {
            $parts[] = (string) ($context['title'] ?? '') . "\n" . (string) ($context['content'] ?? '');
        }
        return implode("\n", $parts);
    }

    private function joinContexts(array $contexts): string
    {
        return $this->buildContextAnswer($contexts);
    }

    private function extractSourceIds(array $contexts): array
    {
        $ids = [];
        foreach ($contexts as $context) {
            if (isset($context['sourceId'])) {
                $ids[] = intval($context['sourceId']);
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function chunksExist(array $contexts): bool
    {
        $chunkIds = [];
        foreach ($contexts as $context) {
            if (isset($context['chunkId'])) {
                $chunkIds[] = intval($context['chunkId']);
            }
        }
        $chunkIds = array_values(array_unique(array_filter($chunkIds)));
        if (empty($chunkIds)) {
            return false;
        }
        try {
            return Db::name('ai_knowledge_chunk')->whereIn('id', $chunkIds)->count() === count($chunkIds);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function record(array &$summary, string $layer, string $status): void
    {
        $summary[$layer][$status]++;
    }

    private function hasFailures(array $summary): bool
    {
        foreach ($summary as $counts) {
            if (($counts['FAIL'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    private function formatMissing(array $items): string
    {
        return empty($items) ? '-' : implode(',', array_map('strval', $items));
    }
}
