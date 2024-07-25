<?php

namespace App\Console\Commands;

use App\Events\MessageCreated;
use App\Models\User;
use Illuminate\Console\Command;

class ReverbTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        MessageCreated::dispatch(User::findOrFail(1), 'test');

        //MessageCreated::broadcast(User::findOrFail(1), 'test');

        return 0;
    }
}
