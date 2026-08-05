@extends('layout.master')
@section('title')
Login
@endsection
@section('content')
<div class="login">
  <div class="secondary-section">
    <div class="login-form mx-auto">
      <div class="w-auto px-0 md:px-3 pt-5 pb-1">

        <h1 class="text-2xl font-bold"> Login</h1>
        <div class="text-center my-3">
          <div class="flex justify-between items-center pt-5">
            <hr class="w-1/5 px-2">
            <h1 class="text-gray-500 w-3/5 font-primary px-2 text-sm"> Or sign in with credentials</h1>
            <hr class="w-1/5 px-2">
          </div>
        </div>

        <form method="POST" action="{{ route('signin') }}">
          @csrf
          @error('credential')<p style='color: red;'>{{ $message }}</p>@enderror
          <div class="my-2 relative">
            <div class="relative">
              <label class="font-primary font-normal">Email</label>
              <input type="text" placeholder="Email" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" name="email" value="">
              @error('email')<p style='color: red;'>{{ $message }}</p>@enderror
            </div>
          </div>
          <div class="my-2 relative">
            <div class="relative">
              <label class="font-primary font-normal">Password</label>
              <input autocomplete="off" type="password" placeholder="Password" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" name="password">
            </div>
          </div>
          <div class="text-center">
            <button type="submit" class="justify-center focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 flex-shrink-0 font-medium rounded-md text-sm gap-x-1.5 px-2.5 py-2.5 shadow-sm text-white dark:text-gray-900 bg-pink-500 hover:bg-primary-600 disabled:bg-primary-500 dark:bg-primary-400 dark:hover:bg-primary-500 dark:disabled:bg-primary-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:focus-visible:outline-primary-400 inline-flex items-center my-2 w-full text-center"name="signin">Login</button>
          </div>
        </form>
      </div>

      <div class="text-center subtitle-4 font-primary font-normal game-name">
        <a href="{{ route('forget') }}" class="text-pink-500 font-primary font-normal">Forget Password?</a>
      </div>
      <div class="mb-5 text-center subtitle-4 font-primary font-normal game-name">
        New user to {{ $settings->site_name }} ? <a href="/register" class="text-pink-500 font-primary font-normal">Register</a> Now
      </div>
    </div>
  </div>
</div>
@endsection