<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $posts = Post::with('user')->latest()->get();
        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        return view('posts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'caption' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Store the image on the 's3' disk in the 'uploads' directory with public visibility
        $imagePath = $request->file('image')->store('uploads', 's3');

        Post::create([
            'caption' => $data['caption'],
            'image_path' => $imagePath,
            'user_id' => Auth::id()
        ]);

        return redirect('/profile/' . Auth::id());
    }

    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        //check if the post belongs to the user
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized action.');
        }
        return view('posts.edit', compact('post'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //check if the post belongs to the user
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized action.');
        }
        $data = $request->validate([
            'caption' => 'required|string|max:255',
        ]);
        $post->update($data);

        //update the image from the storage
        // if ($request->hasFile('image')) {
        //     $imagePath = $request->file('image')->store('uploads', 'public');
        //     $post->image_path = $imagePath;
        //     $post->save();
        // }
        return redirect('/posts/' . $post->id);
    }

    public function destroy(Post $post)
    {
        //check if the post belongs to the user
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized action.');
        }
        //delete the image from the storage
        Storage::disk('s3')->delete($post->image_path);

        //delete the post
        $post->delete();

        return redirect('/profile/' . Auth::id());
    }
}
