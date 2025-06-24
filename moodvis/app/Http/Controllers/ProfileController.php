<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {

        $user = User::find(auth()->id());

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }



        $rules = [
            'name' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];


        if ($user->role === 'murid') {
            $rules['class_id'] = 'required|integer|exists:classes,id';
        }


        $messages = [
            'class_id.required' => 'Sebagai murid, Anda wajib memilih kelas.',
        ];


        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }





        $validatedData = $validator->validated();

        if (isset($validatedData['name'])) {
            $user->name = $validatedData['name'];
        }
        if (isset($validatedData['email'])) {
            $user->email = $validatedData['email'];
        }
        if (isset($validatedData['class_id'])) {
            $user->class_id = $validatedData['class_id'];
        }

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();


            $publicProfilePath = public_path('profile');
            if (!file_exists($publicProfilePath)) {
                mkdir($publicProfilePath, 0777, true);
            }


            if ($user->photo && file_exists($publicProfilePath . '/' . $user->photo)) {
                unlink($publicProfilePath . '/' . $user->photo);
            }


            $photo->move($publicProfilePath, $filename);
            $user->photo = $filename;
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',

            'user' => $user->fresh()->only(['id', 'name', 'email', 'photo_url', 'role', 'class_id']),
        ]);
    }
}


if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        return app()->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}