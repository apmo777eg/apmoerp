<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $avatar;

    // C2 FIX: Lock currentAvatar to prevent client-side tampering
    #[Locked]
    public ?string $currentAvatar = null;

    /**
     * Validate that a path is a safe avatar path
     * Prevents directory traversal and ensures path is within avatars/ directory
     */
    private function isValidAvatarPath(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true; // null/empty is valid (no avatar)
        }

        // Reject null bytes and other dangerous characters
        if (str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        // Reject path traversal attempts
        if (str_contains($path, '..')) {
            return false;
        }

        // Ensure path starts with avatars/
        if (! str_starts_with($path, 'avatars/')) {
            return false;
        }

        return true;
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->currentAvatar = $user->avatar ?? null;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);

        session()->flash('success', __('Profile updated successfully'));

        $this->redirectRoute('profile.edit', navigate: true);
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('success', __('Password updated successfully'));

        $this->redirectRoute('profile.edit', navigate: true);
    }

    #[On('file-uploaded')]
    public function handleFileUploaded(string $fieldId, string $path, array $fileInfo): void
    {
        if ($fieldId === 'profile-avatar') {
            $user = Auth::user();

            // C2 FIX: Validate the new path is a valid avatar path
            if (! $this->isValidAvatarPath($path)) {
                session()->flash('error', __('Invalid avatar path'));

                return;
            }

            // C2 FIX: Verify the file actually exists on disk
            if (! Storage::disk('public')->exists($path)) {
                session()->flash('error', __('Avatar file not found'));

                return;
            }

            // Delete old avatar if exists (already validated through #[Locked])
            if ($this->currentAvatar && $this->isValidAvatarPath($this->currentAvatar)) {
                Storage::disk('public')->delete($this->currentAvatar);
            }

            // Update user with new avatar path
            $user->update([
                'avatar' => $path,
            ]);

            $this->currentAvatar = $path;
            session()->flash('success', __('Avatar updated successfully'));

            $this->redirectRoute('profile.edit', navigate: true);
        }
    }

    #[On('file-cleared')]
    public function handleFileCleared(string $fieldId): void
    {
        if ($fieldId === 'profile-avatar') {
            $this->removeAvatar();
        }
    }

    public function updateAvatar(): void
    {
        $user = Auth::user();

        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        // C2 FIX: Only delete if path is valid
        if ($this->currentAvatar && $this->isValidAvatarPath($this->currentAvatar)) {
            Storage::disk('public')->delete($this->currentAvatar);
        }

        $path = $this->avatar->store('avatars', 'public');

        $user->update([
            'avatar' => $path,
        ]);

        $this->currentAvatar = $path;
        $this->reset('avatar');

        session()->flash('success', __('Avatar updated successfully'));

        $this->redirectRoute('profile.edit', navigate: true);
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        // C2 FIX: Only delete if path is valid
        if ($this->currentAvatar && $this->isValidAvatarPath($this->currentAvatar)) {
            Storage::disk('public')->delete($this->currentAvatar);
        }

        $user->update([
            'avatar' => null,
        ]);

        $this->currentAvatar = null;

        session()->flash('success', __('Avatar removed successfully'));

        $this->redirectRoute('profile.edit', navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.edit');
    }
}
