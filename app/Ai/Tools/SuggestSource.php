<?php

namespace App\Ai\Tools;

use App\Ai\SourceDiscovery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[Strict]
class SuggestSource implements Tool
{
    // One row per suggestion: [status, name, message].
    public array $results = [];

    public function __construct(private SourceDiscovery $discovery, private int $max) {}

    public function name(): string
    {
        return 'suggest_source';
    }

    public function description(): string
    {
        return 'Save one job board or sitemap as a suggested source. Returns whether it was saved or why it was rejected.';
    }

    public function handle(Request $request): string
    {
        if ($this->added() >= $this->max) { return "Enough: {$this->max} sources are already saved. Stop now."; }
        $result = $this->discovery->store($request->all());
        $this->results[] = $result;
        return $this->added() >= $this->max ? $result[2].' That was the last one needed, so stop now.' : $result[2];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'provider' => $schema->string()->enum(['greenhouse', 'lever', 'ashby', 'workable', 'recruitee', 'sitemap'])->required(),
            'board' => $schema->string()->description('Board token for Greenhouse, Lever, Ashby, Workable or Recruitee; full https sitemap URL for a sitemap.')->required(),
            'company' => $schema->string()->description('Employer or site name.')->required(),
            'job_path_prefix' => $schema->string()->description('For a sitemap, the path every job page starts with, such as /jobs/. Empty for job boards.')->required(),
            'why' => $schema->string()->description('One sentence: where they hire and what developer roles they list.')->required(),
        ];
    }

    private function added(): int
    {
        return collect($this->results)->where(0, 'added')->count();
    }
}
