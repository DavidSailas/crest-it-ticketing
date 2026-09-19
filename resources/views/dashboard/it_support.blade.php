<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Support Dashboard</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        {{-- Live region: the whole dashboard body (headline, stats, open queue, assigned tickets) refreshes
             automatically, so approvals, new tickets, and hand-offs show up without a reload. --}}
        @php $liveHtml = view('dashboard.partials.it-live', compact('stats', 'openQueue', 'assignedTickets', 'agentWorkload'))->render(); @endphp
        <div id="it-live" data-hash="{{ md5($liveHtml) }}">{!! $liveHtml !!}</div>
    </div>

    <script>
        (function () {
            const url = @json(route('dashboard.live'));
            const region = document.getElementById('it-live');
            const INTERVAL = 5000;
            let busy = false;

            async function refresh() {
                if (document.hidden || busy) return;
                busy = true;

                try {
                    // Keep whichever page of the assigned list is open.
                    const params = new URLSearchParams(window.location.search);
                    params.set('hash', region.dataset.hash);

                    const res = await fetch(`${url}?${params}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;

                    const data = await res.json();
                    if (data.changed) {
                        region.innerHTML = data.html;
                        region.dataset.hash = data.hash;
                    }
                } catch (e) {
                    // A missed poll just retries on the next tick.
                } finally {
                    busy = false;
                }
            }

            setInterval(refresh, INTERVAL);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
        })();
    </script>
</x-app-layout>
