<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Ai\Ai;
use Laravel\Ai\Enums\Lab;

use function Laravel\Ai\agent;

class TestBedrockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test-bedrock
        {prompt? : The prompt to send}
        {--tier=cheap : Which model tier to use: cheap, expensive, or both}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test prompt to AWS Bedrock to verify credentials and connectivity';

    public function handle(): int
    {
        $tier = $this->option('tier');
        $tiers = match ($tier) {
            'cheap', 'expensive' => [$tier],
            'both' => ['cheap', 'expensive'],
            default => null,
        };

        if ($tiers === null) {
            $this->components->error('The --tier option must be one of: cheap, expensive, both.');

            return self::FAILURE;
        }

        $prompt = $this->argument('prompt') ?? 'Bedrock経由で接続できているか一言で教えてください。';

        $provider = Ai::textProvider(Lab::Bedrock->value);

        foreach ($tiers as $currentTier) {
            $model = $currentTier === 'cheap'
                ? $provider->cheapestTextModel()
                : $provider->smartestTextModel();

            $this->components->info("[{$currentTier}] {$model}");

            $startedAt = microtime(true);

            try {
                $response = agent(instructions: 'You are a helpful assistant for a quick connectivity test.')
                    ->prompt($prompt, provider: Lab::Bedrock, model: $model);
            } catch (\Throwable $e) {
                $this->components->error($e->getMessage());

                return self::FAILURE;
            }

            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

            $this->line($response->text);
            $this->components->twoColumnDetail('Elapsed', "{$elapsedMs} ms");
            $this->components->twoColumnDetail('Input tokens', (string) $response->usage->inputTokens);
            $this->components->twoColumnDetail('Output tokens', (string) $response->usage->outputTokens);
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
