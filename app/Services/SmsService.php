<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $username;
    private $password;
    private $from;
    private $baseUrl;

    public function __construct()
    {
        $this->username = config('services.sms.username', 'emcatechn');
        $this->password = config('services.sms.password', 'Emca@%2312');
        $this->from = config('services.sms.from', 'ASA');
        $this->baseUrl = config('services.sms.base_url', 'https://messaging-service.co.tz');
    }

    /**
     * Send SMS using the link-based API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Ensure phone number starts with 255 and is valid
            try {
                $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            } catch (\Exception $e) {
                Log::error('SMS Phone Number Format Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'response' => null,
                    'http_code' => 0,
                ];
            }
            
            $text = urlencode($message);
            $url = "{$this->baseUrl}/link/sms/v1/text/single?username={$this->username}&password={$this->password}&from={$this->from}&to={$phoneNumber}&text={$text}";

            // Log the request (without password for security)
            Log::info('SMS API Request', [
                'url' => str_replace($this->password, '***', $url),
                'phone' => $phoneNumber,
                'from' => $this->from
            ]);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                Log::error('SMS cURL Error: ' . $error);
                return [
                    'success' => false,
                    'error' => "cURL Error: " . $error,
                    'response' => null,
                    'http_code' => $httpCode,
                ];
            }

            // Log the response for debugging
            Log::info('SMS API Response', [
                'http_code' => $httpCode,
                'response' => $response ? substr($response, 0, 500) : '(empty)',
                'response_length' => strlen($response ?? ''),
                'phone' => $phoneNumber
            ]);

            // Parse JSON response if available
            $responseData = null;
            $isSuccess = false;
            $errorMessage = null;

            if ($response && strlen($response) > 0) {
                // Try to parse as JSON
                $responseData = json_decode($response, true);
                
                if ($responseData && isset($responseData['messages'])) {
                    // Check the status of the first message
                    $firstMessage = $responseData['messages'][0] ?? null;
                    if ($firstMessage) {
                        $status = $firstMessage['status'] ?? null;
                        if ($status) {
                            $groupName = $status['groupName'] ?? '';
                            $statusName = $status['name'] ?? '';
                            $statusDesc = $status['description'] ?? '';
                            
                            // Check if it's a success status
                            if ($groupName === 'PENDING' || $groupName === 'DELIVERED') {
                                $isSuccess = true;
                            } else {
                                // It's an error/rejection
                                $isSuccess = false;
                                $errorMessage = $statusDesc ?: $statusName ?: $groupName;
                            }
                        }
                    }
                } else {
                    // Not JSON or different format, check for error keywords
                    $responseLower = strtolower($response);
                    if (stripos($responseLower, 'error') !== false || 
                        stripos($responseLower, 'failed') !== false ||
                        stripos($responseLower, 'invalid') !== false ||
                        stripos($responseLower, 'rejected') !== false) {
                        $isSuccess = false;
                        $errorMessage = $response;
                    } else {
                        // Empty response or success indicator
                        $isSuccess = ($httpCode >= 200 && $httpCode < 300);
                    }
                }
            } else {
                // No response body - link-based API might return empty on success
                $isSuccess = ($httpCode >= 200 && $httpCode < 300);
            }

            // If still no error message and not successful, create one
            if (!$isSuccess && !$errorMessage) {
                if ($httpCode >= 400) {
                    $errorMessage = "HTTP Error {$httpCode}: " . ($response ?: 'No response from server');
                } elseif ($response) {
                    $errorMessage = $response;
                } else {
                    $errorMessage = "Unknown error (HTTP {$httpCode})";
                }
            }

            return [
                'success' => $isSuccess,
                'error' => $errorMessage,
                'response' => $response,
                'response_data' => $responseData,
                'http_code' => $httpCode,
            ];

        } catch (\Exception $e) {
            Log::error('SMS Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    /**
     * Send SMS and log it
     *
     * @param string $phoneNumber
     * @param string $message
     * @param string $smsType
     * @param int|null $customerId
     * @param int|null $sentBy
     * @return SmsLog
     */
    public function sendAndLog(string $phoneNumber, string $message, string $smsType = 'custom', ?int $customerId = null, ?int $sentBy = null): SmsLog
    {
        $result = $this->sendSms($phoneNumber, $message);

        return SmsLog::create([
            'customer_id' => $customerId,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'sms_type' => $smsType,
            'status' => $result['success'] ? 'sent' : 'failed',
            'api_response' => json_encode($result),
            'sent_by' => $sentBy ?? auth()->id(),
            'sent_at' => now(),
        ]);
    }

    /**
     * Format phone number to ensure it starts with 255 and has correct length
     * Tanzanian numbers: 255 + 9 digits = 12 digits total
     *
     * @param string $phoneNumber
     * @return string
     * @throws \Exception if phone number format is invalid
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any spaces, dashes, or other characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        $originalNumber = $phoneNumber;
        $length = strlen($cleaned);

        // Log for debugging
        Log::info('Formatting phone number', [
            'original' => $originalNumber,
            'cleaned' => $cleaned,
            'length' => $length
        ]);

        // If it starts with 0, replace with 255 (e.g., 0612345678 -> 255612345678)
        if (substr($cleaned, 0, 1) === '0') {
            if ($length !== 10) {
                throw new \Exception("Phone number starting with 0 must be 10 digits (e.g., 0612345678). You entered: {$originalNumber} ({$length} digits)");
            }
            $formatted = '255' . substr($cleaned, 1);
        }
        // If it already starts with 255
        elseif (substr($cleaned, 0, 3) === '255') {
            if ($length === 11) {
                // Common case: missing one digit, might be missing the first digit after 255
                // Tanzanian numbers typically start with 6, 7, or 8 after 255
                // Try adding a common prefix digit
                $after255 = substr($cleaned, 3);
                if (strlen($after255) === 8) {
                    // Missing first digit, try common prefixes
                    $suggestions = [];
                    foreach (['6', '7', '8'] as $prefix) {
                        $suggestions[] = '255' . $prefix . $after255;
                    }
                    throw new \Exception("Phone number starting with 255 must be 12 digits. You entered: {$originalNumber} (11 digits - missing 1 digit).\n\nPossible corrections:\n- " . implode("\n- ", $suggestions) . "\n\nPlease verify the correct phone number.");
                } else {
                    throw new \Exception("Phone number starting with 255 must be 12 digits. You entered: {$originalNumber} (11 digits - missing 1 digit). Please check if you're missing a digit.");
                }
            } elseif ($length < 12) {
                // Missing multiple digits
                throw new \Exception("Phone number starting with 255 must be 12 digits (e.g., 255612345678). You entered: {$originalNumber} ({$length} digits - missing " . (12 - $length) . " digit(s)). Please check if you're missing digits.");
            } elseif ($length > 12) {
                // Too many digits - take first 12
                $formatted = substr($cleaned, 0, 12);
                Log::warning('Phone number had extra digits, truncated', ['original' => $cleaned, 'formatted' => $formatted]);
            } else {
                $formatted = $cleaned;
            }
        }
        // If it doesn't start with 255 or 0, add 255
        else {
            // Assume it's a 9-digit local number
            if ($length === 9) {
                $formatted = '255' . $cleaned;
            } elseif ($length === 10) {
                // Might be missing the leading 0, try adding 255 to last 9 digits
                $formatted = '255' . substr($cleaned, 1);
            } else {
                throw new \Exception("Invalid phone number format. Expected formats:\n- 0612345678 (10 digits starting with 0)\n- 255612345678 (12 digits starting with 255)\n- 612345678 (9 digits)\n\nYou entered: {$originalNumber} ({$length} digits)");
            }
        }

        // Final validation - must be exactly 12 digits starting with 255
        if (strlen($formatted) !== 12 || substr($formatted, 0, 3) !== '255') {
            throw new \Exception("Invalid phone number format after formatting. Result: {$formatted} (should be 12 digits starting with 255)");
        }

        return $formatted;
    }
}
