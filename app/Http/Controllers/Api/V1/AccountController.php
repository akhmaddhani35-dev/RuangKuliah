<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return response()->json(['data' => $this->present($user)]);
    }

    public function updatePassword(Request $request): Response
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->noContent();
    }

    public function students(Request $request): JsonResponse
    {
        $this->requireAdmin($request);

        $students = User::query()
            ->where('role', 'user')
            ->orderBy('name')
            ->paginate(max(1, min($request->integer('per_page', 20), 100)));
        $students->through(fn (User $student) => $this->present($student));

        return response()->json($students);
    }

    public function updateStudent(Request $request, string $id): JsonResponse
    {
        $this->requireAdmin($request);
        $student = User::query()->where('role', 'user')->findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($student->id)],
            'password' => ['nullable', Password::defaults(), 'confirmed'],
        ]);

        $student->fill(collect($validated)->except('password')->all());
        if ($student->isDirty('email')) {
            $student->email_verified_at = null;
        }
        if (! empty($validated['password'])) {
            $student->password = Hash::make($validated['password']);
        }
        $student->save();

        return response()->json(['data' => $this->present($student)]);
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
