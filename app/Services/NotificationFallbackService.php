<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\NotificationLog;

/**
 * Implements the spec's resolveGuestNotificationChannels() logic exactly:
 *
 *   if guest.phone        -> SMS to guest.phone
 *   if guest.email        -> EMAIL to guest.email
 *   if neither            -> SMS to hotel.phone (always present)
 *                            + EMAIL to hotel.email (if hotel has one)
 *                            -> flagged as a fallback so the receptionist
 *                               knows they must inform the guest manually.
 *
 * Every send (or skip) is logged to notifications_log with the channel,
 * recipient, and whether it was a fallback — this method is the ONLY place
 * guest-facing notifications should be sent from, so that rule can never be
 * accidentally skipped by a controller.
 */
class NotificationFallbackService
{
    public function __construct(
        protected SmsService $sms,
        protected EmailService $email,
    ) {}

    /**
     * @param string $event e.g. 'booking_confirmed', 'check_in', 'payment_received', 'check_out'
     * @param string $smsMessage message used for SMS (guest or hotel fallback)
     * @param string|null $emailSubject required if you want an email sent
     * @param string|null $emailHtml required if you want an email sent
     * @return array{channels: string[], fallback: bool}
     */
    public function notify(
        Hotel $hotel,
        Guest $guest,
        string $event,
        string $smsMessage,
        ?string $emailSubject = null,
        ?string $emailHtml = null,
    ): array {
        $channelsUsed = [];
        $isFallback = false;

        if (filled($guest->phone)) {
            $sent = $this->sms->send($guest->phone, $smsMessage);
            $this->log($hotel, $guest, 'sms', $guest->phone, $smsMessage, $event, $sent, false);
            $channelsUsed[] = 'sms_guest';
        }

        if (filled($guest->email) && $emailSubject && $emailHtml) {
            $sent = $this->email->send($guest->email, $emailSubject, $emailHtml);
            $this->log($hotel, $guest, 'email', $guest->email, $emailSubject, $event, $sent, false);
            $channelsUsed[] = 'email_guest';
        }

        if (empty($channelsUsed)) {
            // Guest has no contact info at all — fall back to the hotel's own
            // contacts so the receptionist can relay the information manually.
            $isFallback = true;

            $sent = $this->sms->send($hotel->phone, "[Guest has no contact info] {$smsMessage}");
            $this->log($hotel, $guest, 'sms', $hotel->phone, $smsMessage, $event, $sent, true);
            $channelsUsed[] = 'sms_hotel_fallback';

            if (filled($hotel->email) && $emailSubject && $emailHtml) {
                $sent = $this->email->send($hotel->email, "[Guest has no contact info] {$emailSubject}", $emailHtml);
                $this->log($hotel, $guest, 'email', $hotel->email, $emailSubject, $event, $sent, true);
                $channelsUsed[] = 'email_hotel_fallback';
            }
        }

        return ['channels' => $channelsUsed, 'fallback' => $isFallback];
    }

    /** For hotel-level notifications (new online booking, withdrawal completed) — always SMS, email only if hotel has one. */
    public function notifyHotel(Hotel $hotel, string $event, string $smsMessage, ?string $emailSubject = null, ?string $emailHtml = null): void
    {
        $sent = $this->sms->send($hotel->phone, $smsMessage);
        NotificationLog::create([
            'hotel_id' => $hotel->id,
            'guest_id' => null,
            'type' => 'sms',
            'recipient' => $hotel->phone,
            'message' => $smsMessage,
            'status' => $sent ? 'sent' : 'failed',
            'was_fallback' => false,
            'event' => $event,
            'created_at' => now(),
        ]);

        if (filled($hotel->email) && $emailSubject && $emailHtml) {
            $sent = $this->email->send($hotel->email, $emailSubject, $emailHtml);
            NotificationLog::create([
                'hotel_id' => $hotel->id,
                'guest_id' => null,
                'type' => 'email',
                'recipient' => $hotel->email,
                'message' => $emailSubject,
                'status' => $sent ? 'sent' : 'failed',
                'was_fallback' => false,
                'event' => $event,
                'created_at' => now(),
            ]);
        }
    }

    protected function log(Hotel $hotel, Guest $guest, string $type, string $recipient, string $message, string $event, bool $sent, bool $fallback): void
    {
        NotificationLog::create([
            'hotel_id' => $hotel->id,
            'guest_id' => $guest->id,
            'type' => $type,
            'recipient' => $recipient,
            'message' => $message,
            'status' => $sent ? 'sent' : 'failed',
            'was_fallback' => $fallback,
            'event' => $event,
            'created_at' => now(),
        ]);
    }
}
