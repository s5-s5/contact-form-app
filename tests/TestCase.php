<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * テストでは Vite のビルド結果がなくても画面を表示できるようにする
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
