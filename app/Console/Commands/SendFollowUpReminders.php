<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminders extends Command
{
    protected $signature   = 'followups:send-reminders';
    protected $description = 'Send SMS reminders for follow-ups that are due today';

    public function handle(SmsService $smsService): int
    {
        $today = Carbon::today()->toDateString();

        // Find all follow-ups whose reminder_date is today and were not yet sent
        $followUps = FollowUp::with(['customer', 'assignedUser'])
            ->where('reminder_date', $today)
            ->whereNull('reminder_sent_at')
            ->where('status', 'pending')
            ->get();

        if ($followUps->isEmpty()) {
            $this->info("No reminders to send today ({$today}).");
            return 0;
        }

        $this->info("Found {$followUps->count()} reminder(s) to send.");

        foreach ($followUps as $followUp) {
            $customerName  = $followUp->customer->name ?? $followUp->customer->phone_number ?? 'Unknown';
            $followUpDate  = $followUp->next_follow_up_date
                ? Carbon::parse($followUp->next_follow_up_date)->format('d M Y')
                : Carbon::parse($followUp->visit_date)->format('d M Y');
            $purpose       = $followUp->visit_purpose ?? 'General Follow-up';
            $assignedName  = $followUp->assignedUser->name ?? 'Staff';

            $message = "SmartEmCa Reminder: Follow-up with {$customerName} is due on {$followUpDate}. Purpose: {$purpose}. Assigned to: {$assignedName}. - EmCa Tech";

            $reminderSent = false;

            // Send to assigned user
            if (in_array($followUp->remind_via, ['assigned_user', 'both'])) {
                $assignedPhone = $followUp->assignedUser->phone ?? null;
                if ($assignedPhone) {
                    $result = $smsService->sendAndLog($assignedPhone, $message, 'follow_up_reminder');
                    if ($result->status === 'sent') {
                        $reminderSent = true;
                        $this->info("  ✓ Sent to assigned user: {$assignedPhone}");
                    } else {
                        $this->warn("  ✗ Failed to send to assigned user: {$assignedPhone}");
                    }
                } else {
                    $this->warn("  ✗ Assigned user has no phone number. Skipping.");
                }
            }

            // Send to customer
            if (in_array($followUp->remind_via, ['customer', 'both'])) {
                $customerPhone = $followUp->customer->phone_number ?? null;
                if ($customerPhone) {
                    $customerMsg = "SmartEmCa: A follow-up visit from EmCa Tech is scheduled for you on {$followUpDate}. Purpose: {$purpose}.";
                    $result = $smsService->sendAndLog($customerPhone, $customerMsg, 'follow_up_reminder');
                    if ($result->status === 'sent') {
                        $reminderSent = true;
                        $this->info("  ✓ Sent to customer: {$customerPhone}");
                    } else {
                        $this->warn("  ✗ Failed to send to customer: {$customerPhone}");
                    }
                } else {
                    $this->warn("  ✗ Customer has no phone number. Skipping.");
                }
            }

            // Mark as sent regardless — to prevent re-sending even if it failed
            $followUp->update(['reminder_sent_at' => Carbon::now()]);
            Log::info("Follow-up reminder processed", [
                'follow_up_id' => $followUp->id,
                'customer'     => $customerName,
                'sent'         => $reminderSent,
            ]);
        }

        $this->info("Done. Processed {$followUps->count()} reminder(s).");
        return 0;
    }
}
