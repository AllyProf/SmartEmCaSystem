<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use App\Models\SmsSchedule;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Services\SmsScheduleService;
use App\Services\SmsService;
use Illuminate\Console\Command;

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

    public function __construct(
        protected AttendanceSettingService $settings
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService, SmsScheduleService $scheduleService)
    {
        $now = now();
        $this->info("Checking for scheduled SMS messages at: " . $now->toDateTimeString());

        $added = $scheduleService->supplementAllPendingSchedules();
        if ($added > 0) {
            $this->info("Added {$added} newly registered customers to pending all-customer batches.");
        }

        $dueSchedules = SmsSchedule::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->get();

        $scheduledSms = SmsLog::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        if ($scheduledSms->isEmpty()) {
            $this->info('No pending scheduled SMS found.');
            return 0;
        }

        $this->info("Found {$scheduledSms->count()} scheduled SMS messages to send.");

        $batches = [];

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
                $result = ['success' => false];
            }

            $batchKey = $this->batchKey($sms);
            if (!isset($batches[$batchKey])) {
                $batches[$batchKey] = [
                    'sent_by' => $sms->sent_by,
                    'scheduled_at' => $sms->scheduled_at,
                    'total' => 0,
                    'sent' => 0,
                    'failed' => 0,
                ];
            }

            $batches[$batchKey]['total']++;
            if (($result['success'] ?? false) === true) {
                $batches[$batchKey]['sent']++;
            } else {
                $batches[$batchKey]['failed']++;
            }
        }

        if ($this->settings->scheduledSmsConfirmationEnabled()) {
            foreach ($batches as $batch) {
                $this->sendBatchConfirmation($smsService, $batch);
            }
        }

        // Mark schedules completed if they have no pending logs.
        foreach ($dueSchedules as $schedule) {
            $pending = SmsLog::where('schedule_id', $schedule->id)
                ->where('status', 'scheduled')
                ->exists();
            if (!$pending) {
                $schedule->update(['status' => 'completed']);
            }
        }

        $this->info('Scheduled SMS processing completed.');
        return 0;
    }

    private function batchKey(SmsLog $sms): string
    {
        $scheduledAt = $sms->scheduled_at?->format('Y-m-d H:i:s') ?? 'unknown';

        return ($sms->sent_by ?? '0') . '::' . $scheduledAt;
    }

    /**
     * @param array{sent_by: int|null, scheduled_at: \Carbon\Carbon|null, total: int, sent: int, failed: int} $batch
     */
    private function sendBatchConfirmation(SmsService $smsService, array $batch): void
    {
        if (!$batch['sent_by']) {
            $this->warn('Skipping confirmation SMS: batch has no scheduler.');
            return;
        }

        $staff = User::find($batch['sent_by']);
        if (!$staff || !$staff->phone) {
            $this->warn("Skipping confirmation SMS: staff #{$batch['sent_by']} has no phone number.");
            return;
        }

        $message = str_replace(
            ['{name}', '{total}', '{sent}', '{failed}', '{scheduled_time}', '{time}'],
            [
                $staff->name,
                (string) $batch['total'],
                (string) $batch['sent'],
                (string) $batch['failed'],
                $batch['scheduled_at']?->format('M d, Y H:i') ?? 'N/A',
                now()->format('M d, Y H:i'),
            ],
            $this->settings->scheduledSmsConfirmationTemplate()
        );

        $this->info("Sending scheduled SMS confirmation to {$staff->name} ({$staff->phone})...");

        try {
            $smsService->sendAndLog(
                $staff->phone,
                $message,
                'other',
                null,
                $staff->id
            );
        } catch (\Exception $e) {
            $this->error("Failed sending confirmation SMS to staff #{$staff->id}: " . $e->getMessage());
        }
    }
}
