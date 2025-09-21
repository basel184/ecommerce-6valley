<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Rest\Client;

class TwilioMessageStatus extends Command
{
    protected $signature = 'twilio:message-status {sid}';

    protected $description = 'Fetch Twilio Message status, error code, and details by SID';

    public function handle(): int
    {
        $sid = (string) $this->argument('sid');
        $accountSid = config('twilio.account_sid');
        $authToken  = config('twilio.auth_token');

        if (!$accountSid || !$authToken) {
            $this->error('Twilio credentials are not configured.');
            return Command::FAILURE;
        }

        try {
            $client = new Client($accountSid, $authToken);
            $msg = $client->messages($sid)->fetch();
            $fmt = function($dt) {
                if ($dt instanceof \DateTimeInterface) {
                    return $dt->format(DATE_ATOM);
                }
                return $dt !== null ? (string)$dt : null;
            };

            $data = [
                'sid' => $msg->sid,
                'status' => $msg->status,
                'errorCode' => $msg->errorCode,
                'errorMessage' => $msg->errorMessage,
                'to' => $msg->to,
                'from' => $msg->from,
                'dateCreated' => $fmt($msg->dateCreated),
                'dateUpdated' => $fmt($msg->dateUpdated),
                'numSegments' => $msg->numSegments,
                'messagingServiceSid' => $msg->messagingServiceSid,
                'body' => $msg->body,
            ];
            $this->info(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to fetch message: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
