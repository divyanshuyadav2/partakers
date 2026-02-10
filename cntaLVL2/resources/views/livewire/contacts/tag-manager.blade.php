<div>
    @if ($showModal)
        <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/75 transition-opacity" wire:click="$set('showModal', false)"></div>

                <!-- Modal Panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-slate-800 rounded-md text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">
                    <div class="bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-100 flex items-center gap-2">
                                <i class="bi bi-tags text-blue-400"></i>
                                Manage Tags
                            </h3>
                            <button wire:click="$set('showModal', false)"
                                class="text-gray-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                        </div>

                        <!-- Modal Body -->
                        <div class="space-y-4">
                            {{-- Session Messages --}}
                            @if (session()->has('tag_manager_message'))
                                <div
                                    class="bg-green-800/50 border border-green-600 text-green-300 px-4 py-2 rounded text-sm">
                                    {{ session('tag_manager_message') }}</div>
                            @endif
                            @if (session()->has('tag_manager_error'))
                                <div class="bg-red-800/50 border border-red-600 text-red-300 px-4 py-2 rounded text-sm">
                                    {{ session('tag_manager_error') }}</div>
                            @endif

                            {{-- Create Tag Form --}}
                            <form wire:submit.prevent="save">
                                <div class="flex space-x-2">
                                    <input type="text" wire:model.defer="name" placeholder="Create new tag..."
                                        class="flex-grow w-full bg-slate-700 border-slate-600 rounded-md text-sm text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                    <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition">Create</button>
                                </div>
                                @error('name')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </form>

                            {{-- List of Tags --}}
                            <div class="max-h-80 overflow-y-auto border border-slate-700 rounded-md">
                                <ul class="divide-y divide-slate-700">
                                    @forelse ($tags as $tag)
                                        <li class="p-3 flex justify-between items-center"
                                            wire:key="tag-{{ $tag->Admn_Tag_Mast_UIN }}">
                                            @if ($editingTagId === $tag->Admn_Tag_Mast_UIN)
                                                <input type="text"
                                                    class="flex-grow bg-slate-900 border-slate-600 text-sm rounded-md"
                                                    wire:model.defer="editingTagName" wire:keydown.enter="update">
                                                <div class="ml-2 flex-shrink-0 space-x-1">
                                                    <button wire:click="update"
                                                        class="text-green-400 hover:text-white text-sm">Save</button>
                                                    <button wire:click="cancelEdit"
                                                        class="text-gray-400 hover:text-white text-sm">Cancel</button>
                                                </div>
                                            @else
                                                <div class="flex items-center justify-between w-full">
                                                    <div>
                                                        <span class="text-gray-200">{{ $tag->Name }}</span>
                                                        @if ($tag->stau == 100201)
                                                            <span
                                                                class="ml-2 text-xs text-green-400">(Active)</span>
                                                        @else
                                                            <span
                                                                class="ml-2 text-xs text-red-400">(Inactive)</span>
                                                        @endif
                                                    </div>
                                                    <div class="space-x-2 flex-shrink-0">
                                                        <button wire:click="toggleStatus({{ $tag->Admn_Tag_Mast_UIN }})"
                                                            class="text-yellow-400 hover:text-white">
                                                            {{ $tag->stau == 100201 ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                        <button wire:click="edit({{ $tag->Admn_Tag_Mast_UIN }})"
                                                            class="text-blue-400 hover:text-white">Edit</button>
                                                        @if ($tag->contacts_count == 0)
                                                            <button
                                                                wire:click="delete({{ $tag->Admn_Tag_Mast_UIN }})"
                                                                wire:confirm="Are you sure you want to delete this tag?"
                                                                class="text-red-400 hover:text-white">Delete</button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </li>
                                    @empty
                                        <li class="p-4 text-center text-gray-500">No tags have been created for this
                                            organization.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>