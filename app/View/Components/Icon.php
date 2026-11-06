<?php

namespace App\View\Components;

use Illuminate\View\Component;

// <x-icon name="kanban" class="w-4 h-4" /> -> inline lucide SVG from resources/icons (no icon JS shipped)
class Icon extends Component
{
    private static array $cache = [];

    public function __construct(public string $name) {}

    public function render(): \Closure
    {
        return function (array $data) {
            $svg = self::$cache[$this->name] ??= file_get_contents(resource_path("icons/{$this->name}.svg"));
            $attrs = $data['attributes'];
            $class = trim('lucide '.$attrs->get('class', ''));
            return preg_replace('/ class="[^"]*"/', ' class="'.e($class).'" '.$attrs->except('class')->toHtml(), $svg, 1);
        };
    }
}
