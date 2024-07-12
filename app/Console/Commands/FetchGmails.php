<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Yantrana\Components\GmailToWeb\GmailToWebEngine;
use Illuminate\Support\Facades\Log;

class FetchGmails extends Command
{
    protected $signature = 'fetch:gmails';
    protected $description = 'Continuously fetch gmails every 2 seconds and save them to the database';

    
    protected $gmailToWebEngine;

    public function __construct(GmailToWebEngine $gmailToWebEngine)
    {
        parent::__construct();
        $this->gmailToWebEngine = $gmailToWebEngine;
    }

    public function handle()
    {
        Log::info('Starting continuous fetch gmails job');

        while (true) {
            try {
                $this->gmailToWebEngine->fetchGmails();
                Log::info('Fetch gmails iteration completed successfully');
            } catch (\Exception $e) {
                Log::error('Error occurred while fetching gmails: ' . $e->getMessage());
            }

            sleep(2); // Wait for 2 seconds before the next iteration
        }
    }
}
