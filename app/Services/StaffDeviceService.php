<?php

namespace App\Services;

use App\Models\User;

class StaffDeviceService
{
    public const PLATFORM_MOBILE = 'mobile';
    public const PLATFORM_WEB = 'web';

    /**
     * Ensure the request comes from the staff member's registered device for the given platform.
     * Mobile app and web staff sign each get their own device slot.
     */
    public function assertDevice(
        User $user,
        ?string $deviceId,
        bool $bindIfEmpty = true,
        string $platform = self::PLATFORM_MOBILE
    ): ?string {
        if (empty($deviceId)) {
            return 'Device identification is required. Please allow storage in your browser and try again.';
        }

        $column = $this->columnForPlatform($platform);
        $stored = $user->{$column};

        if (!empty($stored) && $stored !== $deviceId) {
            return $platform === self::PLATFORM_WEB
                ? 'This account is locked to another browser. Contact admin to reset your web device.'
                : 'This account is locked to another phone. Contact admin to reset your mobile device.';
        }

        if ($bindIfEmpty && empty($stored)) {
            $user->update([$column => $deviceId]);
        }

        return null;
    }

    public function resetPlatform(User $user, ?string $platform = null): void
    {
        if ($platform === null || $platform === self::PLATFORM_MOBILE) {
            $user->device_id = null;
        }
        if ($platform === null || $platform === self::PLATFORM_WEB) {
            $user->web_device_id = null;
        }
        $user->save();
    }

    private function columnForPlatform(string $platform): string
    {
        return $platform === self::PLATFORM_WEB ? 'web_device_id' : 'device_id';
    }
}
