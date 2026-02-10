<?php

namespace App\Livewire\Contacts;

use App\Models\Admn_Tag_Mast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

use Livewire\Component;

class TagManager extends Component
{
    public bool $showModal = false;
    public string $name = '';
    public $tags;
    public $editingTagId;
    public $editingTagName;

    #[On('openTagManager')]
    public function open()
    {
        $this->resetErrorBag();
        $this->reset('name', 'editingTagId', 'editingTagName');
        $this->loadTags();
        $this->showModal = true;
    }

    public function loadTags()
    {
        $organizationUIN = session('selected_Orga_UIN');
        if (!$organizationUIN) {
            $this->tags = collect();
            return;
        }

        $this->tags = Admn_Tag_Mast::where('Admn_Orga_Mast_UIN', $organizationUIN)
            ->withCount('contacts')
            ->orderBy('Name')
            ->get();
    }

    public function save()
    {
        $organizationUIN = session('selected_Orga_UIN');
        $userUIN = session('authenticated_user_uin');

        $this->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('admn_tag_mast', 'Name')->where(function ($query) use ($organizationUIN) {
                    return $query->where('Admn_Orga_Mast_UIN', $organizationUIN);
                }),
            ],
        ]);

        Admn_Tag_Mast::create([
            'Name' => $this->name,
            'Admn_Orga_Mast_UIN' => $organizationUIN,
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
            'stau' => 100201, // Active by default
        ]);

        $this->reset('name');
        $this->loadTags();
        session()->flash('tag_manager_message', 'Tag created successfully.');
        $this->dispatch('tags-updated');
    }

    public function edit($tagId)
    {
        $tag = $this->tags->firstWhere('Admn_Tag_Mast_UIN', $tagId);
        if ($tag) {
            $this->editingTagId = $tag->Admn_Tag_Mast_UIN;
            $this->editingTagName = $tag->Name;
        }
    }

    public function cancelEdit()
    {
        $this->reset('editingTagId', 'editingTagName');
    }

    public function update()
    {
        $this->validate([
            'editingTagName' => 'required|string|min:2|max:50'
        ]);

        $tag = Admn_Tag_Mast::where('Admn_Tag_Mast_UIN', $this->editingTagId)->first();
        if ($tag && $tag->Admn_Orga_Mast_UIN == session('selected_Orga_UIN')) {
            $tag->update(['Name' => $this->editingTagName]);
            $this->cancelEdit();
            $this->loadTags();
            session()->flash('tag_manager_message', 'Tag updated successfully.');
            $this->dispatch('tags-updated');
        }
    }

    public function delete($tagId)
    {
        $tag = $this->tags->firstWhere('Admn_Tag_Mast_UIN', $tagId);
        if (!$tag || $tag->Admn_Orga_Mast_UIN != session('selected_Orga_UIN')) {
            session()->flash('tag_manager_error', 'Tag not found or permission denied.');
            return;
        }

        if ($tag->contacts_count > 0) {
            session()->flash('tag_manager_error', 'This tag is in use and cannot be deleted.');
            return;
        }

        $tag->delete();
        $this->loadTags();
        session()->flash('tag_manager_message', 'Tag deleted successfully.');
        $this->dispatch('tags-updated');
    }

    public function toggleStatus($tagId)
    {
        $tag = Admn_Tag_Mast::find($tagId);

        if (!$tag || $tag->Admn_Orga_Mast_UIN != session('selected_Orga_UIN')) {
            session()->flash('tag_manager_error', 'Tag not found or permission denied.');
            return;
        }

        $tag->stau = $tag->stau == 100201 ? 100202 : 100201;
        $tag->save();

        $this->loadTags();
        session()->flash('tag_manager_message', 'Tag status updated successfully.');
    }

    public function render()
    {
        return view('livewire.contacts.tag-manager');
    }
}