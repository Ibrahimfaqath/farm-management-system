@extends('layouts.guest', ['title' => __('Log in')])

@section('content')
    @if (session('status'))
        <div role="status" class="alert alert-success alert-soft mt-4 py-2 text-sm">
            <x-icon name="success" :size="16" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="email" class="field-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="input w-full bg-base-100 @error('email') input-error @enderror">
            @error('email')
                <p class="mt-1 flex items-start gap-1 text-xs text-tone-error">
                    <x-icon name="error" :size="13" class="mt-px" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="input w-full bg-base-100 @error('password') input-error @enderror">
            @error('password')
                <p class="mt-1 flex items-start gap-1 text-xs text-tone-error">
                    <x-icon name="error" :size="13" class="mt-px" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" class="checkbox checkbox-sm checkbox-primary">
            {{ __('Remember me') }}
        </label>

        <x-form.submit size="md" class="w-full">{{ __('Log in') }}</x-form.submit>
    </form>

    {{-- Demo credentials are a development convenience; they are never shown on
         a deployed environment. --}}
    @if (app()->environment(['local', 'testing']))
        <div class="mt-6 rounded-field border border-base-300 bg-base-200 p-3 text-xs leading-relaxed text-base-content/65">
            <p class="font-medium text-base-content/80">{{ __('Demo accounts') }}</p>
            <ul class="mt-1 space-y-0.5 font-mono">
                <li>owner@farm.test / password</li>
                <li>manager@farm.test / password</li>
                <li>worker@farm.test / password</li>
                <li>vet@farm.test / password</li>
                <li>finance@farm.test / password</li>
            </ul>
        </div>
    @endif
@endsection