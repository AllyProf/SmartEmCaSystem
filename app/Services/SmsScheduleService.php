<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\SmsSchedule;
use Illuminate\Support\Collection;

class SmsScheduleService
{
    public function personalizeMessage(string $template, ?Customer $customer): string
    {
        $message = str_replace('{year}', date('Y'), $template);

        if ($customer?->name) {
            $message = str_replace('{name}', $customer->name, $message);
        }

        return $message;
    }

    /**
     * Add any customers missing from all pending "send to all" schedules (modern + legacy bulk batches).
     */
    public function supplementAllPendingSchedules(): int
    {
        $added = 0;

        SmsSchedule::query()
            ->whereIn('status', ['scheduled', 'paused'])
            ->where('send_to', 'all')
            ->whereNotNull('scheduled_at')
            ->each(function (SmsSchedule $schedule) use (&$added) {
                $added += $this->addMissingCustomersToSchedule($schedule);
            });

        foreach ($this->legacyBulkBatchGroups() as $group) {
            $added += $this->addMissingCustomersToLegacyGroup($group);
        }

        return $added;
    }

    /**
     * Add a single new customer to every pending "send to all" schedule / legacy bulk batch.
     */
    public function addCustomerToAllPendingSchedules(Customer $customer): int
    {
        $added = 0;

        SmsSchedule::query()
            ->whereIn('status', ['scheduled', 'paused'])
            ->where('send_to', 'all')
            ->whereNotNull('scheduled_at')
            ->each(function (SmsSchedule $schedule) use ($customer, &$added) {
                $added += $this->createScheduledLogIfMissing($schedule, $customer, $schedule->created_by);
            });

        foreach ($this->legacyBulkBatchGroups() as $group) {
            $first = $group->first();
            if (!$first) {
                continue;
            }

            $alreadyIncluded = $group->contains(
                fn (SmsLog $log) => (int) $log->customer_id === (int) $customer->id
            );

            if ($alreadyIncluded) {
                continue;
            }

            $schedule = $this->ensureLegacyScheduleRecord($group);
            $added += $this->createScheduledLogIfMissing($schedule, $customer, $first->sent_by);
        }

        return $added;
    }

    public function addMissingCustomersToSchedule(SmsSchedule $schedule, ?int $sentBy = null): int
    {
        if ($schedule->send_to !== 'all') {
            return 0;
        }

        $existingCustomerIds = SmsLog::query()
            ->where('schedule_id', $schedule->id)
            ->where('status', 'scheduled')
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missingCustomers = Customer::query()
            ->when(!empty($existingCustomerIds), fn ($q) => $q->whereNotIn('id', $existingCustomerIds))
            ->get();

        $added = 0;

        foreach ($missingCustomers as $customer) {
            $added += $this->createScheduledLogIfMissing(
                $schedule,
                $customer,
                $sentBy ?? $schedule->created_by
            );
        }

        return $added;
    }

    /**
     * @return Collection<int, Collection<int, SmsLog>>
     */
    public function legacyBulkBatchGroups(): Collection
    {
        return SmsLog::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->whereNull('schedule_id')
            ->get()
            ->groupBy(fn (SmsLog $sms) => $this->legacyBatchKey($sms))
            ->filter(fn (Collection $group) => $this->isBulkAllCustomerBatch($group->count()))
            ->values();
    }

    public function isBulkAllCustomerBatch(int $batchCount): bool
    {
        $totalCustomers = Customer::count();

        if ($batchCount < 50 || $totalCustomers === 0) {
            return false;
        }

        return $batchCount >= (int) ceil($totalCustomers * 0.75);
    }

    /**
     * @param Collection<int, SmsLog> $group
     */
    private function addMissingCustomersToLegacyGroup(Collection $group): int
    {
        $schedule = $this->ensureLegacyScheduleRecord($group);

        return $this->addMissingCustomersToSchedule($schedule);
    }

    /**
     * @param Collection<int, SmsLog> $group
     */
    private function ensureLegacyScheduleRecord(Collection $group): SmsSchedule
    {
        $first = $group->first();

        $existing = SmsSchedule::query()
            ->where('send_to', 'all')
            ->where('scheduled_at', $first->scheduled_at)
            ->where('message_template', $first->message)
            ->whereIn('status', ['scheduled', 'paused'])
            ->first();

        if ($existing) {
            SmsLog::query()
                ->whereIn('id', $group->pluck('id'))
                ->whereNull('schedule_id')
                ->update(['schedule_id' => $existing->id]);

            return $existing;
        }

        $schedule = SmsSchedule::create([
            'send_to' => 'all',
            'message_template' => $first->message,
            'sms_type' => $first->sms_type,
            'status' => 'scheduled',
            'scheduled_at' => $first->scheduled_at,
            'created_by' => $first->sent_by,
            'meta' => ['migrated_from_legacy' => true],
        ]);

        SmsLog::query()
            ->whereIn('id', $group->pluck('id'))
            ->update(['schedule_id' => $schedule->id]);

        return $schedule;
    }

    private function createScheduledLogIfMissing(SmsSchedule $schedule, Customer $customer, ?int $sentBy): int
    {
        $exists = SmsLog::query()
            ->where('schedule_id', $schedule->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'scheduled')
            ->exists();

        if ($exists) {
            return 0;
        }

        SmsLog::create([
            'schedule_id' => $schedule->id,
            'customer_id' => $customer->id,
            'phone_number' => $customer->phone_number,
            'message' => $this->personalizeMessage($schedule->message_template, $customer),
            'sms_type' => $schedule->sms_type,
            'status' => 'scheduled',
            'scheduled_at' => $schedule->scheduled_at,
            'sent_by' => $sentBy,
        ]);

        return 1;
    }

    private function legacyBatchKey(SmsLog $sms): string
    {
        return $sms->scheduled_at->format('Y-m-d H:i:s') . '::' . md5($sms->message);
    }
}
