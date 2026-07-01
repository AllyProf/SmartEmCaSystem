<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

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

        $otherUser = User::query()
            ->where($column, $deviceId)
            ->whereKeyNot($user->id)
            ->first();

        if ($otherUser) {
            return $platform === self::PLATFORM_WEB
                ? 'This browser is already registered to ' . $this->maskEmail($otherUser->email) . '. Contact admin to reset the device.'
                : 'This device is already registered to ' . $this->maskEmail($otherUser->email) . '. Contact admin to reset the device.';
        }

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

    public function findUserByDevice(?string $deviceId, string $platform = self::PLATFORM_MOBILE): ?User
    {
        if (empty($deviceId)) {
            return null;
        }

        $column = $this->columnForPlatform($platform);

        return User::query()->where($column, $deviceId)->first();
    }

    public function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (!str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 1);
        $maskedLocal = $visible . str_repeat('*', max(1, strlen($local) - 1));

        return "{$maskedLocal}@{$domain}";
    }

    /**
     * Staff sign on a phone browser should use the mobile device slot, not the desktop web slot.
     */
    public function resolveStaffSignPlatform(Request $request): string
    {
        return $this->isMobileUserAgent($request->userAgent()) ? self::PLATFORM_MOBILE : self::PLATFORM_WEB;
    }

    public function isMobileUserAgent(?string $userAgent): bool
    {
        $ua = strtolower((string) $userAgent);

        if ($ua === '') {
            return false;
        }

        $mobileHints = [
            'iphone', 'ipod', 'ipad', 'android', 'mobile', 'webos', 'blackberry',
            'opera mini', 'iemobile', 'windows phone', 'silk/',
        ];

        foreach ($mobileHints as $hint) {
            if (str_contains($ua, $hint)) {
                return true;
            }
        }

        return false;
    }

    private function columnForPlatform(string $platform): string
    {
        return $platform === self::PLATFORM_WEB ? 'web_device_id' : 'device_id';
    }
}
