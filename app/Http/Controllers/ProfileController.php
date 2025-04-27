<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(User $user)
    {
        return view('profiles.index', compact('user'));
    }

    public function edit(User $user)
    {
        //check if the user is the current user
        if (Auth::id() !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        return view('profiles.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        //check if the user is the current user
        if (Auth::id() !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,',
            'bio' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            //delete the old profile image if it exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $imagePath = $request->file('profile_image')->store('uploads', 'public');
            $data['profile_image'] = $imagePath;
        }
        $user->update($data);
        return redirect('/profile/' . $user->id);
    }
}
