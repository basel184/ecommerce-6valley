<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppService
{
    protected Client $client;
    protected string $from;

    public function __construct()
    {
        $sid   = config('twilio.account_sid');
        $token = config('twilio.auth_token');
        $this->from = config('twilio.whatsapp_from', 'whatsapp:+14155238886');

        if (!$sid || !$token) {
            throw new \RuntimeException('Twilio credentials are not configured');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Send a WhatsApp message using either Twilio Content Template SID (preferred) or local body template.
     * - Preferred: set config('twilio.templates.{key}.content_sid') to an approved WhatsApp content template SID.
     * - Fallback: build the body from body_template/order or legacy body + interpolate.
     *
     * @param string $to E.164 without prefix, e.g. 9665xxxxxx (will be prefixed automatically)
     * @param string $templateKey config('twilio.templates') key
     * @param array  $vars ['customer_name'=>..., 'cart_value'=>..., 'cart_link'=>..., 'store_name'=>..., 'image_url'=>...]
     */
    public function sendTemplate(string $to, string $templateKey, array $vars = []): array
    {
        if (!config('twilio.enabled', false)) {
            return ['success' => false, 'message' => 'Twilio WhatsApp disabled'];
        }

        $tpl = config("twilio.templates.$templateKey");
        if (!$tpl || (empty($tpl['body_template']) && empty($tpl['body']))) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        $toFormatted = $this->formatToWhatsApp($to);

        try {
            // If Content Template SID is configured, use Content API
            $contentSid = $tpl['content_sid'] ?? null;
            $enforceContent = (bool) config('twilio.enforce_content_templates', false);
            if ($enforceContent && empty($contentSid)) {
                return ['success' => false, 'message' => 'Content template enforcement enabled but content_sid missing'];
            }
            if ($contentSid) {
                $contentVariables = $this->buildContentVariables($tpl, $vars);
                $contentParams = [
                    'from' => $this->from,
                    'contentSid' => $contentSid,
                    'to' => $toFormatted,
                    'contentVariables' => json_encode($contentVariables, JSON_UNESCAPED_UNICODE),
                    // For WhatsApp, channel override auto-detected from sender. Keep minimal.
                ];
                // Media in Content Template is defined in the template itself; no mediaUrl here.
                Log::info('Twilio Content send attempt', [
                    'to' => $toFormatted,
                    'from' => $this->from,
                    'content_sid' => $contentSid,
                    'content_variables' => $contentVariables,
                ]);
                $message = $this->client->messages->create($toFormatted, $contentParams);
                return [
                    'success' => true,
                    'sid' => $message->sid,
                    'status' => $message->status,
                    'to' => $toFormatted,
                    'from' => $this->from,
                    'body' => null,
                    'used_content_sid' => $contentSid,
                ];
            }

            // Fallback: build body text and send standard WhatsApp message
            // Note: only allowed when enforcement flag is OFF
            if ($enforceContent) {
                return ['success' => false, 'message' => 'Content template required'];
            }
            if (!empty($tpl['body_template']) && !empty($tpl['order'])) {
                $body = $this->fillOrdered($tpl['body_template'], (array) $tpl['order'], $vars);
            } else {
                // Legacy fallback
                $body = $this->interpolate($tpl['body'] ?? '', $vars);
            }

            $params = [
                'from' => $this->from,
                'body' => $body,
            ];

            // Optional media header if present in fallback path
            $mediaConf = $tpl['media'] ?? null;
            $mediaUrl = $mediaConf['url'] ?? null;
            if (!$mediaUrl && !empty($vars['image_url'])) {
                $mediaUrl = $vars['image_url'];
            }
            if ($mediaUrl) {
                $params['mediaUrl'] = [$mediaUrl];
            }

            $message = $this->client->messages->create($toFormatted, $params);

            return [
                'success' => true,
                'sid' => $message->sid,
                'status' => $message->status,
                'to' => $toFormatted,
                'from' => $this->from,
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp send failed', [
                'error' => $e->getMessage(),
                'to' => $toFormatted,
                'template' => $templateKey,
                'from' => $this->from,
                'used_content_sid' => $contentSid ?? null,
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function interpolate(string $body, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $k => $v) {
            $replacements['{'.$k.'}'] = (string) $v;
        }
        return strtr($body, $replacements);
    }

    protected function formatToWhatsApp(string $to): string
    {
        $to = preg_replace('/[^0-9+]/', '', $to);
        if (strpos($to, '+') !== 0) {
            $to = '+'.$to;
        }
        if (strpos($to, 'whatsapp:') === 0) {
            return $to;
        }
        return 'whatsapp:'.$to;
    }

    private function fillOrdered(string $template, array $order, array $vars): string
    {
        // Replace {{1}}, {{2}} in order; leave unchanged if missing
        foreach ($order as $i => $key) {
            $idx = $i + 1;
            $val = isset($vars[$key]) ? (string) $vars[$key] : '';
            $template = str_replace('{{' . $idx . '}}', $val, $template);
        }
        return $template;
    }

    private function buildContentVariables(array $tpl, array $vars): array
    {
        // If content template expects named variables, build by names exactly
        if (!empty($tpl['content_var_names']) && is_array($tpl['content_var_names'])) {
            $named = [];
            foreach ($tpl['content_var_names'] as $name) {
                // Support synonyms mapping (e.g., first_name <- customer_name)
                $val = null;
                if (isset($vars[$name]) && $vars[$name] !== '') {
                    $val = (string)$vars[$name];
                } else {
                    // basic synonyms
                    $synonyms = [
                        'first_name' => ['customer_name', 'name', 'full_name'],
                        'customer_name' => ['first_name', 'name', 'full_name'],
                        'store' => ['store_name'],
                    ];
                    if (isset($synonyms[$name])) {
                        foreach ($synonyms[$name] as $alt) {
                            if (isset($vars[$alt]) && $vars[$alt] !== '') { $val = (string)$vars[$alt]; break; }
                        }
                    }
                }
                // Avoid null/empty per Twilio guidance: substitute '-' when missing
                if ($val === null || $val === '') { $val = '-'; }
                $named[$name] = $val;
            }
            return $named; // {"customer_name":"...","store_name":"..."}
        }

        // Else: positional variables {{1}}, {{2}}, ...
        if (!empty($tpl['order']) && is_array($tpl['order'])) {
            $byIndex = [];
            $i = 1;
            foreach ($tpl['order'] as $key) {
                $val = isset($vars[$key]) && $vars[$key] !== '' ? (string)$vars[$key] : '-';
                $byIndex[(string)$i] = $val;
                $i++;
            }
            return $byIndex; // {"1":"val1","2":"val2"}
        }

        // Fallback: if template uses named variables, pass by names
        $named = [];
        foreach ($vars as $k => $v) {
            $named[$k] = $v !== '' ? (string)$v : '-';
        }
        return $named;
    }
}
