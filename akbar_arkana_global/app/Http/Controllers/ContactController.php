<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ContactController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'company' => ['nullable', 'string', 'max:160'],
            'type' => ['required', Rule::in(config('company.inquiry_types'))],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['prohibited'],
        ]);

        // Only hand personal information to a configured delivery transport.
        $mailer = config('mail.default');
        if (! in_array(config("mail.mailers.$mailer.transport"), ['smtp', 'sendmail', 'ses', 'postmark', 'resend', 'mailgun'], true)) {
            return response()->json(['message' => 'Email delivery is unavailable. Please contact us directly.'], 503);
        }

        $body = implode("\n", [$data['name'], $data['email'], $data['company'] ?? '', $data['type'], '', $data['message']]);
        try {
            Mail::raw($body, function (Message $mail) use ($data) {
                $mail->to(config('company.email'))->replyTo($data['email'])->subject('AAG inquiry — '.$data['type']);
            });
        } catch (TransportExceptionInterface) {
            return response()->json(['message' => 'Email delivery is unavailable. Please try again later.'], 503);
        }

        return response()->json(['message' => 'Thank you. Your inquiry has been sent.']);
    }
}
