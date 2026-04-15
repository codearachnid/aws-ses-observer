<div>
    <x-aws-ses-observer::source-layout :source="$source" activeTab="overview">

        {{-- Metric cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            @php $stats = $this->stats; @endphp
            @foreach([
                ['label' => 'Sent', 'key' => 'sent', 'color' => 'text-zinc-900 dark:text-white'],
                ['label' => 'Delivered', 'key' => 'delivered', 'color' => 'text-green-600 dark:text-green-400'],
                ['label' => 'Bounced', 'key' => 'bounced', 'color' => 'text-red-600 dark:text-red-400'],
                ['label' => 'Complaints', 'key' => 'complaints', 'color' => 'text-orange-600 dark:text-orange-400'],
                ['label' => 'Opens', 'key' => 'opens', 'color' => 'text-blue-600 dark:text-blue-400'],
                ['label' => 'Clicks', 'key' => 'clicks', 'color' => 'text-cyan-600 dark:text-cyan-400'],
            ] as $metric)
                <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $metric['label'] }}</p>
                    <p class="text-2xl font-bold {{ $metric['color'] }} mt-1">{{ number_format($stats[$metric['key']]) }}</p>
                </div>
            @endforeach
        </div>

        {{-- 30-day chart --}}
        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-8">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-4">30-Day Trend</h3>
            <div wire:ignore x-data="sesObserverChart(@js($this->chartData))" class="h-64">
                <canvas x-ref="chart"></canvas>
            </div>
        </div>

        {{-- Bounce breakdown --}}
        @if(!empty($this->bounceBreakdown))
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-4">Bounce Breakdown (30d)</h3>
                <div class="space-y-2">
                    @foreach($this->bounceBreakdown as $type => $count)
                        @php $bounceEnum = \codearachnid\AwsSesObserver\Enums\BounceType::tryFrom($type); @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $bounceEnum?->label() ?? $type }}</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-aws-ses-observer::source-layout>
</div>

@script
<script>
    Alpine.data('sesObserverChart', (chartData) => ({
        chart: null,
        init() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js is not loaded. Skipping chart rendering.');
                return;
            }

            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.06)';

            this.chart = new Chart(this.$refs.chart, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        { label: 'Sent', data: chartData.sent, borderColor: '#71717a', backgroundColor: 'rgba(113,113,122,0.1)', tension: 0.3, fill: false },
                        { label: 'Delivered', data: chartData.delivered, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', tension: 0.3, fill: false },
                        { label: 'Bounced', data: chartData.bounced, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', tension: 0.3, fill: false },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: isDark ? '#d4d4d8' : '#3f3f46' } } },
                    scales: {
                        x: { grid: { color: gridColor }, ticks: { color: isDark ? '#a1a1aa' : '#71717a' } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: isDark ? '#a1a1aa' : '#71717a' } },
                    },
                },
            });
        },
        destroy() {
            this.chart?.destroy();
        },
    }));
</script>
@endscript
