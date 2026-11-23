<?php

namespace Tests\Feature;

use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    private array $inquiry = ['name' => 'Test Visitor', 'email' => 'visitor@example.org', 'type' => 'Other', 'message' => 'Test inquiry'];

    public function test_valid_request_addresses_the_official_mailbox(): void
    {
        $transport = new ArrayTransport;
        config(['mail.default' => 'smtp']);
        Mail::extend('smtp', fn () => $transport);
        $this->postJson('/api/contact', $this->inquiry)->assertOk();
        $message = $transport->messages()->sole()->getOriginalMessage();
        $this->assertSame(config('company.email'), $message->getTo()[0]->getAddress());
        $this->assertSame($this->inquiry['email'], $message->getReplyTo()[0]->getAddress());
    }

    public function test_form_validation_and_unconfigured_mail(): void
    {
        $this->postJson('/api/contact', ['website' => 'bot'])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'type', 'message', 'website']);
        $this->postJson('/api/contact', $this->inquiry)->assertStatus(503);
    }

    public function test_sixth_attempt_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/contact', [])->assertUnprocessable();
        }
        $this->postJson('/api/contact', [])->assertTooManyRequests();
    }
}
