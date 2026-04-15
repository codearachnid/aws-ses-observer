<div>
    <x-aws-ses-observer::source-layout :source="$source" activeTab="setup">

        <div class="max-w-3xl space-y-8">

            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Webhook URL</h3>
                <div class="flex items-center gap-2">
                    <code class="flex-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded px-3 py-2 text-sm font-mono text-zinc-800 dark:text-zinc-200 break-all">{{ $this->webhookUrl }}</code>
                </div>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Use this URL when configuring your SNS subscription.
                </p>
            </div>

            {{-- Step 1 --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">1. Create an SES Configuration Set</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    In the AWS Console, navigate to SES > Configuration sets > Create set. Name it something descriptive (e.g., <code class="bg-zinc-100 dark:bg-zinc-800 px-1 rounded">production-observer</code>).
                </p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    If you already have a configuration set, skip to step 2.
                </p>
            </div>

            {{-- Step 2 --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">2. Create an SNS Topic</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    In the AWS Console, navigate to SNS > Topics > Create topic. Choose <strong>Standard</strong> type. Name it (e.g., <code class="bg-zinc-100 dark:bg-zinc-800 px-1 rounded">ses-observer-events</code>).
                </p>
            </div>

            {{-- Step 3 --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">3. Add an Event Destination to Your Configuration Set</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Go to your Configuration Set > Event destinations > Add destination. Select the event types you want to track:
                </p>
                <ul class="list-disc list-inside text-sm text-zinc-600 dark:text-zinc-400 space-y-1 mb-3">
                    <li>Send, Delivery, Bounce, Complaint</li>
                    <li>Open, Click (requires enabling in SES)</li>
                    <li>Reject, Delivery Delay, Rendering Failure, Subscription</li>
                </ul>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Choose <strong>Amazon SNS</strong> as the destination and select the topic you created in step 2.
                </p>
            </div>

            {{-- Step 4 --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">4. Create an SNS Subscription</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Go to your SNS Topic > Create subscription:
                </p>
                <ul class="list-disc list-inside text-sm text-zinc-600 dark:text-zinc-400 space-y-1 mb-3">
                    <li>Protocol: <strong>HTTPS</strong></li>
                    <li>Endpoint: <code class="bg-zinc-100 dark:bg-zinc-800 px-1 rounded break-all">{{ $this->webhookUrl }}</code></li>
                    <li>Enable raw message delivery: <strong>No</strong> (keep the SNS envelope)</li>
                </ul>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    AWS will send a SubscriptionConfirmation request. SES Observer will automatically confirm it.
                </p>
            </div>

            {{-- Step 5 --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">5. Configure Laravel to Use the Configuration Set</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                    Add the configuration set name to your mail config or set it per message:
                </p>
                <pre class="bg-zinc-900 text-zinc-100 rounded-lg p-4 text-sm overflow-x-auto mb-3"><code>// In config/services.php
'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
],

// In .env
MAIL_MAILER=ses

// Or per-message with a configuration set header:
Mail::to($user)->send(
    (new WelcomeMail)->withSymfonyMessage(function ($message) {
        $message->getHeaders()->addTextHeader(
            'X-SES-CONFIGURATION-SET', 'production-observer'
        );
    })
);</code></pre>
            </div>

            {{-- Step 6 --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">6. Verify It Works</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Send a test email through your application. Within a few seconds, you should see events appear on the
                    <a href="{{ route('ses-observer.sources.events', $source) }}" class="text-blue-600 dark:text-blue-400 underline" wire:navigate>Activity</a> tab.
                </p>
            </div>

        </div>
    </x-aws-ses-observer::source-layout>
</div>
