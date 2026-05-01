<?php

namespace Opscale\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class DbAccessJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        DB::table('audit_log')->insert(['source' => 'job']);
    }
}
