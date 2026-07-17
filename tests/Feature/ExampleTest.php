<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_root_path_redirects_to_dashboard()
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }
}
