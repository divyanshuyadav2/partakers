@if($errors->any())
    <div class="mt-4 bg-red-900/50 border border-red-500/50 rounded-md p-4">
        <div class="flex gap-3">
            <i class="bi bi-exclamation-triangle-fill text-red-400 text-lg flex-shrink-0"></i>
            <div>
                <h3 class="text-sm font-medium text-red-400 mb-2">Errors with your submission:</h3>
                <ul class="list-disc list-inside space-y-1 text-sm text-red-300">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif