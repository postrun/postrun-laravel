# PostRun Laravel Mail Driver

Laravel Mail Driver for PostRun email delivery.

## Installation

```bash
composer require postrun/postrun-laravel
```

The package will auto-register its service provider.

## Configuration

### 1. Add PostRun to your mail configuration

Add the PostRun mailer to your `config/mail.php`:

```php
'mailers' => [
    // ... other mailers

    'postrun' => [
        'transport' => 'postrun',
        'api_key' => env('POSTRUN_API_KEY'),
        'endpoint' => env('POSTRUN_ENDPOINT', 'https://postrun.io'),
    ],

    // Optional: Define multiple PostRun mailers, if you are sending from different domains
    'postrun-marketing' => [
        'transport' => 'postrun',
        'api_key' => env('POSTRUN_MARKETING_KEY'),
        'endpoint' => env('POSTRUN_ENDPOINT', 'https://postrun.io'),
    ],
],
```

If you're using multiple mailers, you can use them like this:

```php
Mail::mailer('postrun')->send($mailable);
Mail::mailer('postrun-marketing')->send($mailable);
```

### 2. Set environment variables

Add these to your `.env` file:

```env
MAIL_MAILER=postrun
POSTRUN_API_KEY=your-domain-api-key
POSTRUN_ENDPOINT=https://your-postrun-instance.com
```

### 3. (Optional) Publish configuration

```bash
php artisan vendor:publish --tag=postrun-config
```

## Usage

Once configured, Laravel's mail system will use PostRun automatically:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

// Using a Mailable
Mail::to('user@example.com')->send(new WelcomeEmail($user));

// Using the Mail facade directly
Mail::raw('Hello!', function ($message) {
    $message->to('user@example.com')
            ->subject('Test Email');
});
```

## Adding Tags and Metadata

### Using Laravel's Native Tag Support (Recommended)

Laravel 9+ has built-in support for tags and metadata on Mailables. Simply define `tags()` and/or `metadata()` methods on your Mailable class:

```php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class WelcomeEmail extends Mailable
{
    public function __construct(
        protected User $user
    ) {}

    public function build()
    {
        return $this->subject('Welcome!')
            ->view('emails.welcome');
    }

    /**
     * Define tags for this email.
     */
    public function tags(): array
    {
        return ['welcome', 'onboarding'];
    }

    /**
     * Define metadata for this email.
     */
    public function metadata(): array
    {
        return [
            'user_id' => $this->user->id,
            'campaign' => 'welcome-series',
        ];
    }
}
```

You can also use the fluent `tag()` and `metadata()` methods:

```php
public function build()
{
    return $this->subject('Welcome!')
        ->view('emails.welcome')
        ->tag('welcome')
        ->tag('onboarding')
        ->metadata('user_id', $this->user->id)
        ->metadata('campaign', 'welcome-series');
}
```

### Using Custom Headers (Alternative)

You can also add tags and metadata using custom headers directly:

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Hello!', function ($message) {
    $message->to('user@example.com')
            ->subject('Test Email');

    // Add tags (comma-separated)
    $message->getHeaders()->addTextHeader('X-PostRun-Tags', 'welcome,onboarding');

    // Add metadata (JSON encoded)
    $message->getHeaders()->addTextHeader('X-PostRun-Meta', json_encode([
        'user_id' => 123,
        'campaign' => 'welcome-series',
    ]));
});
```

Or with `withSymfonyMessage` in a Mailable:

```php
public function build()
{
    return $this->subject('Welcome!')
        ->view('emails.welcome')
        ->withSymfonyMessage(function ($message) {
            $message->getHeaders()->addTextHeader('X-PostRun-Tags', 'welcome');
            $message->getHeaders()->addTextHeader('X-PostRun-Meta', json_encode([
                'user_id' => $this->user->id,
            ]));
        });
}
```

## Retrieving Message ID

After sending, the PostRun message ID is available in the message headers:

```php
$mailable = new WelcomeEmail($user);
$sentMessage = Mail::to('user@example.com')->send($mailable);

// Access via the SentMessage
$messageId = $sentMessage->getOriginalMessage()
    ->getHeaders()
    ->get('X-PostRun-Message-Id')
    ?->getBodyAsString();
```

## Using Multiple Mailers

You can use PostRun alongside other mailers:

```php
// Send via PostRun
Mail::mailer('postrun')->to('user@example.com')->send(new WelcomeEmail());

// Send via default mailer
Mail::to('user@example.com')->send(new WelcomeEmail());
```

## Error Handling

The driver will throw a `RuntimeException` if the PostRun API returns an error:

```php
try {
    Mail::to('user@example.com')->send(new WelcomeEmail());
} catch (\RuntimeException $e) {
    // Handle the error
    Log::error('Failed to send email via PostRun: ' . $e->getMessage());
}
```

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x

## License

MIT
