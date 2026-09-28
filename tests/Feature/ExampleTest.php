<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** The health check Kubernetes uses must always answer 200. */
    public function test_health_check_is_up(): void
    {
        $this->get('/up')->assertOk();
    }
}
