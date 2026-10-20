<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;

// The CV fields the AI fills, shared by kits and resume translations (see App\Support\Cv).
trait CvSchema
{
    private function cvSchema(JsonSchema $schema, bool $withContact = false): mixed
    {
        $text = fn () => $schema->string()->required();
        $words = fn () => $schema->array()->items($schema->string())->required();
        $list = fn (array $properties) => $schema->array()->items($schema->object($properties))->required();

        return $schema->object([
            ...($withContact ? ['name' => $text(), 'contact' => $words()] : []),
            'headline' => $text(),
            'summary' => $text(),
            'skills' => $words(),
            'experience' => $list(['title' => $text(), 'company' => $text(), 'location' => $text(), 'start' => $text(), 'end' => $text(), 'current' => $schema->boolean()->required(), 'highlights' => $words()]),
            'education' => $list(['school' => $text(), 'degree' => $text(), 'field' => $text(), 'start' => $text(), 'end' => $text()]),
            'projects' => $list(['name' => $text(), 'summary' => $text(), 'url' => $text()]),
            'languages' => $words(),
        ])->required();
    }
}
