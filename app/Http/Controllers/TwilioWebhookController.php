<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TwilioWebhookController extends Controller
{
    // Incoming WhatsApp messages from Twilio
    public function incoming(Request $request): Response
    {
        $payload = $request->all();
        Log::info('Twilio WA incoming webhook', [
            'headers' => $this->safeHeaders($request),
            'payload' => $payload,
        ]);
        return response('OK', 200);
    }

    // Fallback for incoming messages when primary webhook fails
    public function fallback(Request $request): Response
    {
        $payload = $request->all();
        Log::warning('Twilio WA fallback webhook triggered', [
            'headers' => $this->safeHeaders($request),
            'payload' => $payload,
        ]);
        return response('OK', 200);
    }

    // Delivery status callbacks for outbound messages
    public function status(Request $request): Response
    {
        // Twilio sends form-encoded fields like MessageSid, MessageStatus, ErrorCode, ErrorMessage
        $messageSid = $request->input('MessageSid');
        $status     = $request->input('MessageStatus');
        $errorCode  = $request->input('ErrorCode');
        $errorMsg   = $request->input('ErrorMessage');

        Log::info('Twilio WA status callback', [
            'MessageSid' => $messageSid,
            'MessageStatus' => $status,
            'ErrorCode' => $errorCode,
            'ErrorMessage' => $errorMsg,
            'to' => $request->input('To'),
            'from' => $request->input('From'),
        ]);
        return response('OK', 200);
    }

    private function safeHeaders(Request $request): array
    {
        // Avoid logging auth tokens; include Twilio signature and content type only
        return [
            'X-Twilio-Signature' => $request->header('X-Twilio-Signature'),
            'Content-Type' => $request->header('Content-Type'),
            'User-Agent' => $request->header('User-Agent'),
        ];
    }
}
