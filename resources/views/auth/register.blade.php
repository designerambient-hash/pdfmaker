@extends('layouts.app')

@section('title', 'Create Free Account - PDFCraft Studio')

@section('content')
<div class="min-h-[calc(100vh-140px)] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-indigo-200 mb-4">
            <i data-lucide="user-plus" class="w-7 h-7"></i>
        </div>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Create your personal workspace</h2>
        <p class="mt-1.5 text-xs text-slate-500">100% Free forever. All your documents and edits stay completely private to you.</p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-xl border border-slate-200/80 rounded-3xl sm:px-10">

            @if($errors->any())
            <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium space-y-1">
                @foreach($errors->all() as $err)
                    <div class="flex items-center space-x-1.5">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-500"></i>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-3 text-slate-400"></i>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="John Doe" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-3.5 top-3 text-slate-400"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="name@example.com" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <i data-lucide="key" class="w-4 h-4 absolute left-3.5 top-3 text-slate-400"></i>
                        <input type="password" id="password" name="password" required placeholder="Minimum 6 characters" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <i data-lucide="check" class="w-4 h-4 absolute left-3.5 top-3 text-slate-400"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat your password" class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md shadow-indigo-200 flex items-center justify-center space-x-2">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        <span>Create My Account</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <span class="text-xs text-slate-500">Already have an account?</span>
                <a href="{{ route('login') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 ml-1 hover:underline">
                    Sign in here
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

