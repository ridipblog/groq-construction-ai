<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RagRetrieverService
{
    protected ?array $recordsCache = null;

    /**
     * Stop words to filter out of search queries
     */
    protected const STOP_WORDS = [
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'in', 'on', 'at', 'by', 'for', 'with',
        'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above',
        'below', 'to', 'from', 'up', 'down', 'in', 'out', 'over', 'under', 'again', 'further',
        'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both',
        'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only',
        'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don',
        'should', 'now', 'please', 'tell', 'me', 'calculate', 'give', 'what', 'need', 'want'
    ];

    /**
     * Load knowledge base records from the generated JSONL file
     *
     * @return array
     */
    public function getRecords(): array
    {
        if ($this->recordsCache !== null) {
            return $this->recordsCache;
        }

        $jsonlPath = storage_path('app/rag/nirmaan_system_knowledge.jsonl');
        if (!File::exists($jsonlPath)) {
            Log::warning("Nirmaan RAG knowledge file not found at: {$jsonlPath}");
            $this->recordsCache = [];
            return $this->recordsCache;
        }

        $lines = file($jsonlPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $records = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded && !empty($decoded['content'])) {
                $records[] = $decoded;
            }
        }

        $this->recordsCache = $records;
        return $this->recordsCache;
    }

    /**
     * Retrieve the top-N relevant knowledge chunks for a query
     *
     * @param string $query User prompt or technical question
     * @param int $limit Maximum chunks to retrieve (default: 3)
     * @return array
     */
    public function retrieve(string $query, int $limit = 3): array
    {
        $records = $this->getRecords();
        if (empty($records) || empty(trim($query))) {
            return [];
        }

        // Tokenize query
        $cleanQuery = strtolower(trim($query));
        preg_match_all('/\b[a-z0-9_:\-]+\b/', $cleanQuery, $matches);
        $rawTokens = $matches[0] ?? [];

        $tokens = [];
        foreach ($rawTokens as $tok) {
            if (strlen($tok) > 1 && !in_array($tok, self::STOP_WORDS, true)) {
                $tokens[] = $tok;
            }
        }

        if (empty($tokens)) {
            return array_slice($records, 0, $limit);
        }

        $scored = [];
        foreach ($records as $record) {
            $score = 0;
            $searchableText = strtolower(
                ($record['title'] ?? '') . ' ' .
                ($record['standard'] ?? '') . ' ' .
                ($record['category'] ?? '') . ' ' .
                ($record['content'] ?? '')
            );

            foreach ($tokens as $token) {
                // Exact word match bonus
                $count = substr_count($searchableText, $token);
                if ($count > 0) {
                    $score += ($count * 2);
                    // Strong boost if token matches title or standard
                    if (str_contains(strtolower($record['title'] ?? ''), $token)) {
                        $score += 5;
                    }
                    if (str_contains(strtolower($record['standard'] ?? ''), $token)) {
                        $score += 4;
                    }
                }
            }

            if ($score > 0) {
                $record['relevance_score'] = $score;
                $scored[] = $record;
            }
        }

        usort($scored, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Build formatted context string ready for injection into system prompt
     *
     * @param string $query
     * @param int $limit
     * @return string|null
     */
    public function getFormattedContext(string $query, int $limit = 3): ?string
    {
        $chunks = $this->retrieve($query, $limit);
        if (empty($chunks)) {
            return null;
        }

        $out = "RELEVANT NIRMAAN TECHNICAL STANDARDS & SPECIFICATIONS:\n";
        foreach ($chunks as $idx => $chunk) {
            $num = $idx + 1;
            $out .= "--- [Source {$num}: {$chunk['title']} | Standard: {$chunk['standard']} | Jurisdiction: {$chunk['jurisdiction']}] ---\n";
            $out .= trim($chunk['content']) . "\n\n";
        }

        return trim($out);
    }
}

