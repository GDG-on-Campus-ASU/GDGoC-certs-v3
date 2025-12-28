<?php

namespace App\Services;

class TemplatePreviewService
{
    /**
     * Generate replacements for template preview.
     *
     * @return array
     */
    public function getReplacements(): array
    {
        return [
            'Recipient_Name' => 'John Doe',
            'Event_Title' => 'Certificate Award Ceremony',
            'Org_Name' => 'GDG on Campus',
            'state' => 'New York',
            'event_type' => 'Workshop',
            'issue_date' => now()->toFormattedDateString(),
            'issuer_name' => 'Jane Smith',
            'unique_id' => '123e4567-e89b-12d3-a456-426614174000',
        ];
    }

    /**
     * Apply replacements to the content.
     *
     * @param string $content
     * @return string
     */
    public function applyReplacements(string $content): string
    {
        $replacements = $this->getReplacements();

        foreach ($replacements as $key => $value) {
            $content = str_replace(['{{ $' . $key . ' }}', '{{$' . $key . '}}', '{{ ' . $key . ' }}', '{{' . $key . '}}'], $value, $content);
        }

        return $content;
    }
}
