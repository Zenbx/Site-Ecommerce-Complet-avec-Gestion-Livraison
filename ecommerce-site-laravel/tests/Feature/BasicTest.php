<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicTest extends TestCase
{
    public function test_basic_application_works()
    {
        $response = $this->get('/api/test');
        $response->assertStatus(200);
    }
}
