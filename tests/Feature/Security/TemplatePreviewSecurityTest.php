<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TemplatePreviewSecurityTest extends TestCase
{
    /**
     * Test that the template preview modal uses a sandboxed iframe.
     */
    public function test_template_preview_uses_sandboxed_iframe()
    {
        $filePath = resource_path('views/components/template-preview-modal.blade.php');
        $this->assertTrue(File::exists($filePath), 'Template preview modal file not found.');

        $content = File::get($filePath);

        // Check for sandboxed iframe
        $this->assertStringContainsString('<iframe', $content, 'Iframe tag missing.');
        $this->assertStringContainsString('sandbox="allow-same-origin"', $content, 'Sandbox attribute with allow-same-origin missing.');

        // Ensure allow-scripts is NOT present in the sandbox attribute
        // This is a rough check, ideally we parse HTML but string check is good enough for this specific requirement
        // We match sandbox="..." and ensure 'allow-scripts' is not inside the quotes
        preg_match('/sandbox="([^"]*)"/', $content, $matches);
        $this->assertNotEmpty($matches, 'Sandbox attribute not found in regex check.');
        $sandboxValue = $matches[1];

        $this->assertStringNotContainsString('allow-scripts', $sandboxValue, 'Sandbox attribute MUST NOT contain allow-scripts.');

        // Check for removal of vulnerable innerHTML assignment
        // The original code had: previewContainer.innerHTML = data.content;
        // The new code writes to iframe document
        $this->assertStringNotContainsString('previewContainer.innerHTML = data.content', $content, 'Vulnerable innerHTML assignment still present.');

        // Check that we are writing to the iframe document
        $this->assertStringContainsString('previewFrame.contentWindow.document', $content, 'Not writing to iframe document.');
        $this->assertStringContainsString('doc.write(data.content)', $content, 'Not writing content to iframe.');
    }
}
