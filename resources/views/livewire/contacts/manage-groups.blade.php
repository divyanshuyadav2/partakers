<div>
    <!-- MANAGE GROUPS MODAL -->
    @if ($showManageGroupsModal)
        <div class="fixed inset-0 z-30 flex items-center justify-center p-4 sm:p-6" x-data="{ show: @entangle('showManageGroupsModal') }"
            @keydown.escape.window="show = false">

            <!-- Backdrop -->
            <div wire:click="closeAllModals" x-show="show" x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm"></div>

            <!-- Modal Window -->
            <div x-show="show" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 flex flex-col w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-4xl bg-slate-800 sm:rounded-xl rounded-none shadow-2xl border border-slate-700/60 overflow-hidden">

                <!-- Header -->
                <div
                    class="shrink-0 bg-slate-900/70 backdrop-blur-sm px-6 py-5 border-b border-slate-700/60 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <i class="bi bi-diagram-3-fill text-blue-400 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white tracking-tight">Groups</h2>
                    </div>
                    <button wire:click="closeAllModals"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-800/50">
                    <button wire:click="openCreateModal"
                        class="mb-6 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-5 rounded-lg shadow-lg shadow-blue-900/30 transition-all transform hover:scale-105 active:scale-100">
                        <i class="bi bi-plus-circle"></i> Add
                    </button>

                    {{-- Accessing the Computed Property $this->allGroups --}}
                    @if ($this->allGroups->count() > 0)
                        <div class="overflow-x-auto rounded-lg border border-slate-700/60 bg-slate-900/40">
                            <table class="w-full text-sm">
                                <thead
                                    class="bg-slate-800/50 border-b border-slate-700/60 text-xs uppercase text-slate-400">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold">Group Name</th>
                                        <th class="px-6 py-3 text-left font-semibold">Parent</th>
                                        <th class="px-6 py-3 text-center font-semibold">Contacts</th>
                                        <th class="px-6 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/40">
                                    @foreach ($this->allGroups as $group)
                                        <tr wire:key="group-{{ $group->Admn_Grup_Mast_UIN }}"
                                            class="hover:bg-slate-800/60 transition-colors duration-150">
                                            <td class="px-6 py-4 font-medium text-slate-100">{{ $group->Name }}</td>
                                            <td class="px-6 py-4">
                                                @if ($group->parent)
                                                    <span class="text-slate-400">{{ $group->parent->Name }}</span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-700/50 rounded-md text-xs font-medium text-slate-300">
                                                        <i class="bi bi-dash"></i> Root
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Contact Count Button --}}
                                            <td class="px-6 py-4 text-center">
                                                <button
                                                    wire:click="openViewAssignedModal({{ $group->Admn_Grup_Mast_UIN }})"
                                                    class="inline-flex items-center gap-2 px-3 py-1 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-full text-sm transition-colors">
                                                    <i class="bi bi-people text-blue-400"></i>
                                                    {{-- This users_count is now protected by the Computed Property in the Class --}}
                                                    <span
                                                        class="font-semibold text-blue-300">{{ $group->users_count ?? 0 }}</span>
                                                </button>
                                            </td>

                                            <td class="px-6 py-4 text-right">
                                                {{-- Action Dropdown --}}
                                                <div x-data="{ open: false }" class="relative inline-flex items-center">

                                                    <!-- The action menu -->
                                                    <div x-show="open" @click.away="open = false"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-90"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-75"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-90"
                                                        class="absolute right-full top-1/2 -translate-y-1/2 mr-2 z-10 flex items-center gap-1 p-1 bg-slate-700 rounded-md shadow-lg border border-slate-600"
                                                        style="display: none;">

                                                        <button
                                                            wire:click="openAssignModal({{ $group->Admn_Grup_Mast_UIN }})"
                                                            title="Assign"
                                                            class="p-2 rounded text-slate-300 hover:bg-blue-600/30 hover:text-blue-300 transition-colors">
                                                            <i class="bi bi-person-plus"></i>
                                                        </button>
                                                        <button
                                                            wire:click="openEditModal({{ $group->Admn_Grup_Mast_UIN }})"
                                                            title="Edit"
                                                            class="p-2 rounded text-slate-300 hover:bg-amber-600/30 hover:text-amber-300 transition-colors">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        @if ($this->canDeleteGroup($group->Admn_Grup_Mast_UIN))
                                                            <div class="h-5 w-px bg-slate-600 mx-1"></div>
                                                            <button
                                                                wire:click="confirmDelete({{ $group->Admn_Grup_Mast_UIN }})"
                                                                wire:confirm="Delete '{{ $group->Name }}'? Once Deleted this Group cannot be recovered."
                                                                title="Delete"
                                                                class="p-2 rounded text-slate-300 hover:bg-red-600/30 hover:text-red-300 transition-colors">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <!-- The 3-dot toggle button -->
                                                    <button @click="open = !open"
                                                        :class="{ 'bg-slate-700/80 text-white': open }"
                                                        class="p-2 rounded-full text-slate-400 hover:bg-slate-700/80 hover:text-white transition-colors">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div
                            class="rounded-lg border-2 border-dashed border-slate-700 py-20 text-center bg-slate-800/30">

                            <h3 class="text-lg font-semibold text-slate-200 mb-2">No Groups Yet</h3>
                            <p class="text-sm text-slate-500 mb-6">Create your first group to organize your contacts.
                            </p>
                            <button wire:click="openCreateModal"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-6 rounded-lg shadow-lg transition-colors">
                                <i class="bi bi-plus-circle"></i> Create Group
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- CREATE/EDIT MODAL -->
    @if ($showCreateEditModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
            <div wire:click="closeModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <form wire:submit.prevent="saveGroup" x-data="{ show: @entangle('showCreateEditModal') }" x-show="show"
                x-transition.scale.origin.center
                class="relative z-10 bg-slate-800 rounded-xl shadow-2xl w-full max-w-md border border-slate-700/60 overflow-hidden">

                <div class="bg-slate-900/70 px-6 py-4 border-b border-slate-700/60 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">
                        {{ $editingGroupId ? ' Edit Group' : ' Add Group' }}
                    </h3>
                    <button type="button" wire:click="closeModal"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Parent
                            Group</label>
                        <select wire:model.live="parent_grup_uin" class="w-full form-select-figma">
                            {{-- Accessing the Computed Property for Parent Options --}}
                            @foreach ($this->parentGroupOptions as $option)
                                @if ($editingGroupId && $option->Admn_Grup_Mast_UIN == $editingGroupId)
                                    @continue
                                @endif
                                <option value="{{ $option->Admn_Grup_Mast_UIN }}">{{ $option->Name }}</option>
                            @endforeach
                        </select>
                        @error('parent_grup_uin')
                            <div class="mt-1.5 text-red-400 text-xs flex items-center gap-1"><i
                                    class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-baseline">
                            <label
                                class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Group
                                Name</label>
                            <p class="text-[11px] text-slate-500">{{ strlen($name) }}/15</p>
                        </div>
                        <input type="text" wire:model.live="name" placeholder="e.g., 'Clients'"
                            class="w-full bg-slate-900/70 border border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 py-2.5 px-3 transition"
                            maxlength="15">
                        @error('name')
                            <div class="mt-1.5 text-red-400 text-xs flex items-center gap-1"><i
                                    class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="bg-slate-900/50 px-6 py-4 border-t border-slate-700/60 flex gap-3 justify-end">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-5 rounded-lg transition-colors w-28">
                        <span wire:loading.remove
                            wire:target="saveGroup">{{ $editingGroupId ? 'Save' : 'Create' }}</span>
                        <span wire:loading wire:target="saveGroup"><i
                                class="bi bi-arrow-repeat animate-spin"></i></span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- ASSIGN CONTACTS MODAL -->
    @if ($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div wire:click="closeModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div x-data="{ show: @entangle('showAssignModal') }" x-show="show" x-transition.scale.origin.center
                class="relative z-10 flex flex-col w-full h-full sm:h-auto sm:max-h-[70vh] sm:max-w-2xl bg-slate-800 sm:rounded-xl rounded-none shadow-2xl border border-slate-700/60 overflow-hidden">

                <div class="bg-slate-900/70 px-6 py-4 border-b border-slate-700/60 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2.5">
                        Assign to: {{ $groupForAssignment?->Name }}
                    </h3>
                    <button type="button" wire:click="closeModal"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-1.5">
                    {{-- Accessing the Computed Property for Unassigned Contacts --}}
                    @forelse ($this->unassignedContacts as $contact)
                        <label wire:key="contact-{{ $contact->Admn_User_Mast_UIN }}"
                            class="flex items-center gap-4 p-3 rounded-lg hover:bg-slate-700/50 cursor-pointer border border-transparent has-[:checked]:bg-blue-500/10 has-[:checked]:border-blue-500/20 transition-all">
                            <input type="checkbox" wire:model.live="selectedContacts"
                                value="{{ $contact->Admn_User_Mast_UIN }}"
                                class="w-4 h-4 rounded text-blue-500 bg-slate-700 border-slate-600 focus:ring-blue-500">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-200 truncate">
                                    {{ trim($contact->FaNm . ' ' . $contact->LaNm) }}</p>
                                @if ($contact->Comp_Name)
                                    <p class="text-xs text-slate-500 truncate">{{ $contact->Comp_Name }}</p>
                                @endif
                            </div>
                            @if ($contact->phones->first())
                                <p class="text-xs text-slate-500 flex-shrink-0">
                                    {{ $contact->phones->first()->Phon_Numb }}</p>
                            @endif
                        </label>
                    @empty
                        <div class="text-center py-16 text-slate-500">
                            <p class="font-medium">No unassigned contacts</p>
                        </div>
                    @endforelse
                </div>

                <div class="bg-slate-900/50 px-6 py-4 border-t border-slate-700/60">
                    <div class="flex items-center justify-between border-slate-700/30">
                        <span class="text-sm text-slate-400">Selected: <span
                                class="font-semibold text-blue-300">{{ count($selectedContacts) }}</span></span>
                        <div class="flex gap-3 justify-end">
                            <button type="button" wire:click="$set('selectedContacts', [])"
                                class="bg-slate-700/50 hover:bg-slate-700 text-slate-300 font-semibold py-2 px-5 rounded-lg transition-colors">Clear</button>
                            <button wire:click="assignContacts" @disabled(empty($selectedContacts))
                                class="bg-blue-600 hover:bg-blue-500 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-2 px-5 rounded-lg transition-colors">
                                Assign {{ count($selectedContacts) > 0 ? '(' . count($selectedContacts) . ')' : '' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- VIEW ASSIGNED CONTACTS MODAL -->
    @if ($showViewAssignedModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div wire:click="closeModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div x-data="{ show: @entangle('showViewAssignedModal') }" x-show="show" x-transition.scale.origin.center
                class="relative z-10 flex flex-col w-full h-full sm:h-auto sm:max-h-[70vh] sm:max-w-2xl bg-slate-800 sm:rounded-xl rounded-none shadow-2xl border border-slate-700/60 overflow-hidden">

                <div class="bg-slate-900/70 px-6 py-4 border-b border-slate-700/60 flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center gap-2.5">
                            Unassign Contacts From: {{ $groupForViewing?->Name }}
                        </h3>
                    </div>
                    <button type="button" wire:click="closeModal"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-1.5">
                    {{-- Accessing the Computed Property for Assigned Contacts --}}
                    @forelse ($this->assignedContacts as $contact)
                        <label wire:key="assigned-contact-{{ $contact->Admn_User_Mast_UIN }}"
                            class="flex items-center gap-4 p-3 rounded-lg hover:bg-slate-700/50 cursor-pointer border border-transparent has-[:checked]:bg-red-500/10 has-[:checked]:border-red-500/20 transition-all">
                            <input type="checkbox" wire:model.live="contactsToUnassign"
                                value="{{ $contact->Admn_User_Mast_UIN }}"
                                class="w-4 h-4 rounded text-red-500 bg-slate-700 border-slate-600 focus:ring-red-500">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-slate-200 truncate">
                                        {{ trim($contact->FaNm . ' ' . $contact->MiNm . ' ' . $contact->LaNm) }}</p>
                                    @if ($contact->Is_Vf == 100206)
                                        <span class="h-2 w-2 bg-yellow-400 rounded-full" title="Verified"></span>
                                    @endif
                                </div>
                                @if ($contact->Comp_Name)
                                    <p class="text-xs text-slate-500 truncate"><i
                                            class="bi bi-briefcase mr-1.5"></i>{{ $contact->Comp_Name }}</p>
                                @endif
                            </div>
                            <div class="flex-shrink-0 text-right text-xs">
                                @if ($contact->phones->first())
                                    <p class="text-slate-500">{{ $contact->phones->first()->Phon_Numb }}</p>
                                @endif
                                @if ($contact->emails->first())
                                    <p class="text-slate-600">{{ $contact->emails->first()->Emai_Addr }}</p>
                                @endif
                            </div>
                        </label>
                    @empty
                        <div class="text-center py-16 text-slate-500">
                            <p class="font-medium">No assigned contacts</p>
                        </div>
                    @endforelse
                </div>

                <div class="bg-slate-900/50 px-6 py-4 border-t border-slate-700/60">
                    <div class="flex items-center justify-between border-slate-700/30">
                        <span class="text-sm text-slate-400">
                            Total: <span
                                class="font-semibold text-green-300">{{ $this->assignedContacts->count() }}</span>
                            @if (count($contactsToUnassign) > 0)
                                <span class="mx-2">|</span> Selected: <span
                                    class="font-semibold text-red-300">{{ count($contactsToUnassign) }}</span>
                            @endif
                        </span>
                        <div class="flex gap-3 justify-end">
                            @if (count($contactsToUnassign) > 0)
                                <button wire:click="unassignContacts"
                                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-500 text-white font-semibold py-2 px-5 rounded-lg transition-colors">
                                    <i class="bi bi-trash"></i> Unassign
                                    {{ count($contactsToUnassign) > 0 ? '(' . count($contactsToUnassign) . ')' : '' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
