@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    @if (session('status'))
        <div class="alert alert-success mt-4 py-2 text-sm">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="input input w-full @error('email') input-error @enderror">
            @error('email')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="input input w-full @error('password') input-error @enderror">
            @error('password')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" class="checkbox checkbox-sm checkbox-primary">
            Ingat saya
        </label>

        <button type="submit" class="btn btn-primary w-full">Masuk</button>
    </form>

    <div class="mt-6 rounded-md border border-base-300 bg-base-200 p-3 text-xs leading-relaxed text-base-content/65">
        <p class="font-medium text-base-content/80">Akun demo</p>
        <ul class="mt-1 space-y-0.5 font-mono">
            <li>owner@farm.test / password</li>
            <li>manager@farm.test / password</li>
            <li>worker@farm.test / password</li>
            <li>vet@farm.test / password</li>
            <li>finance@farm.test / password</li>
        </ul>
    </div>
@endsection
