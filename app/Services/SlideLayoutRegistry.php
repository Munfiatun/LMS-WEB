<?php

namespace App\Services;

class SlideLayoutRegistry
{
    /**
     * Map of valid layouts and their human-readable labels.
     * 
     * @var array<string, string>
     */
    private const LAYOUTS = [
        'cover' => 'Cover',
        'section-divider' => 'Section Divider',
        'concept' => 'Concept',
        'definition' => 'Definition',
        'key-points' => 'Key Points',
        'process' => 'Process / Sequence',
        'timeline' => 'Timeline',
        'comparison' => 'Comparison',
        'example' => 'Example',
        'case-study' => 'Case Study',
        'image-focus' => 'Image Focus',
        'quote' => 'Quote',
        'code' => 'Code Block',
        'diagram' => 'Diagram',
        'checkpoint' => 'Checkpoint / Quiz',
        'summary' => 'Summary',
        'closing' => 'Closing',
        'reading' => 'Reading (Default)',
    ];

    /**
     * Legacy layouts and what they map to now.
     * 
     * @var array<string, string>
     */
    private const ALIASES = [
        'visual' => 'image-focus',
    ];

    /**
     * Resolve a layout identifier to a safe, valid layout.
     * If unknown, it falls back to 'reading'.
     */
    public function resolve(?string $layout): string
    {
        if (empty($layout)) {
            return 'reading';
        }

        $layout = strtolower(trim($layout));

        if (isset(self::ALIASES[$layout])) {
            $layout = self::ALIASES[$layout];
        }

        if (array_key_exists($layout, self::LAYOUTS)) {
            return $layout;
        }

        return 'reading';
    }

    /**
     * Get all available layouts for selection.
     * 
     * @return array<string, string>
     */
    public function getAvailableLayouts(): array
    {
        return self::LAYOUTS;
    }

    /**
     * Get array of all valid keys for validation (including aliases).
     *
     * @return array<string>
     */
    public static function getValidationKeys(): array
    {
        return array_merge(array_keys(self::LAYOUTS), array_keys(self::ALIASES));
    }
}
