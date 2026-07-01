<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsLog;
use App\Services\SmsService;

class SendScheduledSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled SMS messages that are due';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService)
    {
        $now = now();
        $this->info("Checking for scheduled SMS messages at: " . $now->toDateTimeString());

        $scheduledSms = SmsLog::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        if ($scheduledSms->isEmpty()) {
            $this->info('No pending scheduled SMS found.');
            return 0;
        }

        $this->info("Found {$scheduledSms->count()} scheduled SMS messages to send.");

        foreach ($scheduledSms as $sms) {
            $this->info("Sending SMS ID {$sms->id} to {$sms->phone_number}...");
            try {
                $result = $smsService->sendSms($sms->phone_number, $sms->message);

                $sms->update([
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'api_response' => json_encode($result),
                    'sent_at' => now(),
                ]);

                $this->info("Result for SMS ID {$sms->id}: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));
            } catch (\Exception $e) {
                $sms->update([
                    'status' => 'failed',
                    'api_response' => json_encode(['success' => false, 'error' => $e->getMessage()]),
                    'sent_at' => now(),
                ]);
                $this->error("Failed sending SMS ID {$sms->id}: " . $e->getMessage());
            }
        }

        $this->info('Scheduled SMS processing completed.');
        return 0;
    }
}
