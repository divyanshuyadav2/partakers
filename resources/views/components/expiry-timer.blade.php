@props(['expiresAt'])

<div class="mb-6 bg-blue-900/30 border border-blue-500/50 rounded-md p-4" 
    x-data="linkExpiryTimer('{{ $expiresAt }}')" x-init="init()">
    
    <div class="flex items-center justify-between">
        {{-- Left Side: Info --}}
        <div class="flex items-center gap-3 flex-1">
            <i class="bi bi-clock text-blue-400 text-lg flex-shrink-0"></i>
            <div>
                <p class="text-blue-300 font-medium text-sm">Link Expires In</p>
                <p class="text-blue-200 text-xs">This invitation link will expire automatically for security.</p>
            </div>
        </div>

        {{-- Right Side: Timer --}}
        <div class="text-right">
            <div class="flex items-center gap-2 text-blue-300">
                <div class="bg-blue-800/50 rounded px-2 py-1 text-sm font-mono" x-show="timeLeft.hours > 0">
                    <span x-text="timeLeft.hours.toString().padStart(2, '0')"></span>h
                </div>
                <div class="bg-blue-800/50 rounded px-2 py-1 text-sm font-mono">
                    <span x-text="timeLeft.minutes.toString().padStart(2, '0')"></span>m
                </div>
                <div class="bg-blue-800/50 rounded px-2 py-1 text-sm font-mono">
                    <span x-text="timeLeft.seconds.toString().padStart(2, '0')"></span>s
                </div>
            </div>
            <p class="text-blue-400 text-xs mt-1" x-text="expiryDate"></p>
        </div>
    </div>

    {{-- Warning: Less than 1 hour --}}
    <div x-show="timeLeft.total < 3600000 && timeLeft.total > 0" x-transition class="mt-3 bg-amber-900/30 border border-amber-600/50 rounded-md p-3">
        <div class="flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-amber-400"></i>
            <p class="text-amber-300 text-sm font-medium">Link expires in less than 1 hour!</p>
        </div>
        <p class="text-amber-200 text-xs mt-1">Please complete and submit the form before it expires.</p>
    </div>

    {{-- Critical Warning: Less than 15 minutes --}}
    <div x-show="timeLeft.total < 900000 && timeLeft.total > 0" x-transition class="mt-3 bg-red-900/30 border border-red-600/50 rounded-md p-3">
        <div class="flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-red-400 animate-pulse"></i>
            <p class="text-red-300 text-sm font-medium">Critical: Link expires in less than 15 minutes!</p>
        </div>
        <p class="text-red-200 text-xs mt-1">Submit the form immediately to avoid losing access.</p>
    </div>
</div>