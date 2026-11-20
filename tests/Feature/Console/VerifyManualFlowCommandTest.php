<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Command;
use Tests\TestCase;

class VerifyManualFlowCommandTest extends TestCase
{
    public function test_verify_manual_flow_command_fails_outside_local_environment(): void
    {
        // Set environment ke selain local (misal production)
        $this->app['env'] = 'production';

        $this->artisan('ems:verify-flow')
            ->expectsOutputToContain('Perintah ems:verify-flow hanya diizinkan berjalan di environment local')
            ->assertExitCode(Command::FAILURE);
    }
}
