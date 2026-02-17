<div>
    <!-- MANAGE ISSUES MODAL -->
    @if ($showIssueModal)
        <div class="fixed inset-0 z-30 flex items-center justify-center p-4" 
             x-data="{ show: @entangle('showIssueModal') }"
             @keydown.escape.window="show = false">

            <!-- Backdrop -->
            <div wire:click="closeAllModals" 
                 x-show="show" 
                 x-transition.opacity.duration.300ms
                 class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm"></div>

            <!-- Modal Window - ADJUSTED WIDTH AND POSITIONING -->
            <div x-show="show" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200" 
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative z-10 flex flex-col w-full max-w-6xl bg-slate-800 rounded-xl shadow-2xl border border-slate-700/60 overflow-hidden"
                 style="max-height: 85vh;">

                <!-- Header -->
                <div class="shrink-0 bg-slate-900/70 backdrop-blur-sm px-6 py-4 border-b border-slate-700/60 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <i class="bi bi-card-text text-blue-400 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white tracking-tight">Notes</h2>
                    </div>
                    <button wire:click="closeAllModals"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Flash Messages -->
                @if (session()->has('message'))
                    <div class="mx-6 mt-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg flex items-center gap-3">
                        <i class="bi bi-check-circle text-green-400 text-lg"></i>
                        <span class="text-green-300 text-sm">{{ session('message') }}</span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mx-6 mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg flex items-center gap-3">
                        <i class="bi bi-exclamation-circle text-red-400 text-lg"></i>
                        <span class="text-red-300 text-sm">{{ session('error') }}</span>
                    </div>
                @endif

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-800/50">
                    <div class="mb-4 flex gap-3">
                        <button wire:click="openCreate"
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-5 rounded-lg shadow-lg shadow-blue-900/30 transition-all transform hover:scale-105 active:scale-100">
                            <i class="bi bi-plus-circle"></i> Add Note
                        </button>
                        <button wire:click="openEditCategory"
                            class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold py-2 px-5 rounded-lg shadow-lg shadow-purple-900/30 transition-all transform hover:scale-105 active:scale-100">
                            <i class="bi bi-pencil-square"></i> Edit Parent
                        </button>
                    </div>

                    @if ($this->allIssues->count() > 0)
                        <div class="overflow-x-auto rounded-lg border border-slate-700/60 bg-slate-900/40">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-800/50 border-b border-slate-700/60 text-xs uppercase text-slate-400">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold">Category (Parent)</th>
                                        <th class="px-6 py-3 text-left font-semibold">Note (Comment)</th>
                                        <th class="px-6 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/40">
                                    @foreach ($this->allIssues as $issue)
                                        <tr wire:key="issue-{{ $issue->Admn_Cnta_Note_Comnt_UIN }}"
                                            class="hover:bg-slate-800/60 transition-colors duration-150">
                                            
                                            <!-- Category -->
                                            <td class="px-6 py-4 font-medium text-slate-100">
                                                <div class="flex items-center gap-2">
                                                    <i class="bi bi-folder text-blue-400"></i>
                                                    {{ $issue->Category }}
                                                </div>
                                            </td>
                                            
                                            <!-- Comment -->
                                            <td class="px-6 py-4 text-slate-300">
                                                {{ Str::limit($issue->Comnt_Text, 80) }}
                                            </td>
                                            
                                            <!-- Actions -->
                                            <td class="px-6 py-4 text-right">
                                                <div class="inline-flex gap-2">
                                                    <button wire:click="openEdit({{ $issue->Admn_Cnta_Note_Comnt_UIN }})"
                                                        title="Edit"
                                                        class="p-2 rounded text-slate-300 hover:bg-amber-600/30 hover:text-amber-300 transition-colors">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    
                                                    @if($this->canDelete($issue))
                                                        <button wire:click="delete({{ $issue->Admn_Cnta_Note_Comnt_UIN }})"
                                                            wire:confirm="Delete this note? This action cannot be undone."
                                                            title="Delete"
                                                            class="p-2 rounded text-slate-300 hover:bg-red-600/30 hover:text-red-300 transition-colors">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    @else
                                                        <span class="text-slate-500 text-xs px-2 py-1 italic" title="Note is in use">
                                                            <i class="bi bi-lock"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-lg border-2 border-dashed border-slate-700 py-16 text-center bg-slate-800/30">
                            <i class="bi bi-inbox text-5xl text-slate-600 mb-3"></i>
                            <h3 class="text-lg font-semibold text-slate-200 mb-2">No Notes Yet</h3>
                            <button wire:click="openCreate"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-6 rounded-lg shadow-lg transition-colors">
                                <i class="bi bi-plus-circle"></i> Create Note
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- CREATE/EDIT MODAL -->
    @if ($showCreateEditModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <!-- Backdrop - DON'T CLOSE MAIN MODAL -->
            <div wire:click="closeModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <form wire:submit.prevent="save" 
                  x-data="{ show: @entangle('showCreateEditModal') }" 
                  x-show="show"
                  x-transition.scale.origin.center
                  @click.stop
                  class="relative z-10 bg-slate-800 rounded-xl shadow-2xl w-full max-w-lg border border-slate-700/60 overflow-hidden">

                <div class="bg-slate-900/70 px-6 py-4 border-b border-slate-700/60 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">
                        {{ $editingId ? 'Edit Note' : 'Add Note' }}
                    </h3>
                    <button type="button" wire:click="closeModal"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Flash Messages in Modal -->
                @if (session()->has('error'))
                    <div class="mx-6 mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg flex items-start gap-3">
                        <i class="bi bi-exclamation-triangle text-red-400 text-lg mt-0.5"></i>
                        <span class="text-red-300 text-sm">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="p-6 space-y-5">
                    <!-- Parent/Category Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ $editingId ? 'Edit Parent' : 'Select Parent' }}
                        </label>
                        <select wire:model.live="parent" 
                                class="w-full bg-slate-900/70 border border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 py-2.5 px-3 transition">
                            <option value="new">Add Parent Note</option>
                            @foreach ($this->parents as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                      <!--  <p class="mt-1.5 text-xs text-slate-500">
                            <i class="bi bi-info-circle"></i> Showing system and organization categories
                        </p> -->
                    </div>

                    <!-- New Category Input (shown only if 'new' is selected) -->
                    @if($parent === 'new')
                        <div x-data="{ focused: false }">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                New Parent Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   wire:model.live="newParent" 
                                   @focus="focused = true"
                                   @blur="focused = false"
                                   placeholder="Enter new Parent name"
                                   maxlength="100"
                                   class="w-full bg-slate-900/70 border border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 py-2.5 px-3 transition"
                                   :class="{ 'border-blue-500': focused }">
                            <div class="flex justify-between items-center mt-1">
                               
                                <p class="text-xs text-slate-500">{{ strlen($newParent) }}/100</p>
                            </div>
                        </div>
                    @endif

                    <!-- Note/Comment Text -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            {{ $editingId ? 'Edit Note' : 'Enter Note' }} <span class="text-red-400">*</span>
                        </label>
                        <textarea wire:model.live="note" 
                                  rows="5"
                                  placeholder="Enter your note/comment here..."
                                  class="w-full bg-slate-900/70 border border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 py-2.5 px-3 transition resize-none"></textarea>
                        <p class="mt-1 text-xs text-slate-500">{{ strlen($note) }} characters</p>
                    </div>
                </div>

                <div class="bg-slate-900/50 px-6 py-4 border-t border-slate-700/60 flex gap-3 justify-end">
                    <button type="button" 
                            wire:click="closeModal"
                            class="bg-slate-700/50 hover:bg-slate-700 text-slate-300 font-semibold py-2 px-5 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-2 px-5 rounded-lg transition-colors min-w-[100px]">
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-circle"></i> {{ $editingId ? 'Save' : 'Create' }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <i class="bi bi-arrow-repeat animate-spin"></i> Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- EDIT CATEGORY MODAL -->
    @if ($showEditCategoryModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div wire:click="closeCategoryModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <form wire:submit.prevent="saveCategory" 
                  x-data="{ show: @entangle('showEditCategoryModal') }" 
                  x-show="show"
                  x-transition.scale.origin.center
                  @click.stop
                  class="relative z-10 bg-slate-800 rounded-xl shadow-2xl w-full max-w-lg border border-slate-700/60 overflow-hidden">

                <div class="bg-slate-900/70 px-6 py-4 border-b border-slate-700/60 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-500/10 rounded-lg border border-purple-500/20">
                            <i class="bi bi-pencil-square text-blue-400"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">Edit Parent Name</h3>
                    </div>
                    <button type="button" wire:click="closeCategoryModal"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Flash Messages in Modal -->
                @if (session()->has('category_error'))
                    <div class="mx-6 mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg flex items-start gap-3">
                        <i class="bi bi-exclamation-triangle text-red-400 text-lg mt-0.5"></i>
                        <span class="text-red-300 text-sm">{{ session('category_error') }}</span>
                    </div>
                @endif

                <div class="p-6 space-y-5">
                    <!-- Select Category to Edit -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            Select Parent to Rename <span class="text-red-400">*</span>
                        </label>
                        <select wire:model.live="selectedCategory" 
                                class="w-full bg-slate-900/70 border border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 py-2.5 px-3 transition">
                            <option value="">-- Select Parent Note --</option>
                            @foreach ($this->organizationCategories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">
                            <i class="bi bi-info-circle"></i> Only parents created by your organization can be renamed
                        </p>
                    </div>

                    <!-- New Category Name Input -->
                    @if($selectedCategory)
                        <div x-data="{ focused: false }">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                New Parent Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   wire:model.live="newCategoryName" 
                                   @focus="focused = true"
                                   @blur="focused = false"
                                   placeholder="Enter new Parent name"
                                   maxlength="100"
                                   class="w-full bg-slate-900/70 border border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 py-2.5 px-3 transition"
                                   :class="{ 'border-purple-500': focused }">
                            <div class="flex justify-between items-center mt-1">
                                                       <!--  <p class="text-xs text-slate-500">
                                    <i class="bi bi-lightbulb"></i> This will rename the category for all notes
                                </p> -->
                                <p class="text-xs text-slate-500">{{ strlen($newCategoryName) }}/100</p>
                            </div>
                        </div>

                        <!-- Preview of affected notes -->
                        @php
                            $affectedCount = $this->allIssues->where('Category', $selectedCategory)->count();
                        @endphp
                        @if($affectedCount > 0)
                            <div class="p-4 bg-purple-500/10 border border-purple-500/20 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <i class="bi bi-info-circle text-purple-400 text-lg mt-0.5"></i>
                                    <div>
                                        <p class="text-purple-300 text-sm font-semibold">
                                            {{ $affectedCount }} note{{ $affectedCount > 1 ? 's' : '' }} will be updated
                                        </p>
                                        <p class="text-purple-400 text-xs mt-1">
                                            All notes in parent "{{ $selectedCategory }}" will be renamed to the new parent name.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="bg-slate-900/50 px-6 py-4 border-t border-slate-700/60 flex gap-3 justify-end">
                    <button type="button" 
                            wire:click="closeCategoryModal"
                            class="bg-slate-700/50 hover:bg-slate-700 text-slate-300 font-semibold py-2 px-5 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            @if(!$selectedCategory || !$newCategoryName) disabled @endif
                            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-2 px-5 rounded-lg transition-colors min-w-[120px]">
                        <span wire:loading.remove wire:target="saveCategory">
                            <i class="bi bi-check-circle"></i> Rename
                        </span>
                        <span wire:loading wire:target="saveCategory" class="flex items-center gap-2">
                            <i class="bi bi-arrow-repeat animate-spin"></i> Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>