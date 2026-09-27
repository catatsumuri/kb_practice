<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Bedrock)]
class TranslatorAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $locale = config('app.locale');

        return <<<INSTRUCTIONS
            You are a professional translator. Identify the language of the
            text the user sends yourself; it is not given to you. Translate
            it into the language identified by the locale code "{$locale}".
            If the text is already in that language, return it unchanged.

            Preserve the original Markdown formatting exactly: headings,
            lists, links, tables, and code blocks. Do not translate code,
            URLs, or proper nouns that should stay untranslated.

            Respond with only the translated text. Do not add a preamble,
            explanation, or any note about the source language.
            INSTRUCTIONS;
    }
}
