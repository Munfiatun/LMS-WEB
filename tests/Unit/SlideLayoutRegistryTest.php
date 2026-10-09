<?php

namespace Tests\Unit;

use App\Services\SlideLayoutRegistry;
use PHPUnit\Framework\TestCase;

class SlideLayoutRegistryTest extends TestCase
{
    private SlideLayoutRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new SlideLayoutRegistry();
    }

    public function test_it_returns_all_available_layouts()
    {
        $layouts = $this->registry->getAvailableLayouts();

        $this->assertIsArray($layouts);
        $this->assertArrayHasKey('cover', $layouts);
        $this->assertArrayHasKey('concept', $layouts);
        $this->assertArrayHasKey('reading', $layouts);
    }

    public function test_it_resolves_valid_layout()
    {
        $this->assertSame('concept', $this->registry->resolve('concept'));
        $this->assertSame('process', $this->registry->resolve('process'));
    }

    public function test_it_resolves_aliased_layout()
    {
        $this->assertSame('image-focus', $this->registry->resolve('visual'));
    }

    public function test_it_falls_back_to_reading_for_unknown_layout()
    {
        $this->assertSame('reading', $this->registry->resolve('unknown-layout-x'));
        $this->assertSame('reading', $this->registry->resolve('123456'));
    }

    public function test_it_falls_back_to_reading_for_empty_layout()
    {
        $this->assertSame('reading', $this->registry->resolve(null));
        $this->assertSame('reading', $this->registry->resolve(''));
        $this->assertSame('reading', $this->registry->resolve('   '));
    }
}
