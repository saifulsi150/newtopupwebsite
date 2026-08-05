@extends('layout.master')
@section('title')
Register
@endsection
@section('content')
<div class="login">
  <div class="secondary-section">
    <div class="login-form mx-auto">
      <div class="w-auto px-0 md:px-3 pt-5 pb-1">
        <h1 class="text-2xl font-bold"> Register</h1>
        <div class="text-center my-3">
          <div class="flex justify-between items-center pt-5">
            <hr class="w-1/5 px-2">
            <h1 class="text-gray-500 w-3/5 font-primary px-2 text-sm"> Or sign up with credentials</h1>
            <hr class="w-1/5 px-2">
          </div>
        </div>
        <form method="POST" action="{{ route('signup') }}">
          @csrf
          <div class="relative py-1">
        <label class="label-title">Full Name</label>
        <input type="text" placeholder="Name" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" id="username" value="" name="name">
        @error('name')<p style='color: red;'>{{ $message }}</p>@enderror
                        <span id="username-alert"></span>
                              </div>
      <!---->
      <div class="relative py-1">
        <label class="label-title">Phone</label>
        <input type="text" placeholder="Phone" class="form-input py-1 block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" id="phone" value="" name="phone">
        @error('phone')<p style='color: red;'>{{ $message }}</p>@enderror
                        <span id="phone-alert"></span>
                              </div>
      <!---->
      <div class="relative py-1">
        <label class="label-title">Email</label>
        <input type="text" placeholder="Email" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" id="email" value="" name="email">
        @error('email')<p style='color: red;'>{{ $message }}</p>@enderror
                        <span id="email-alert"></span>
                              </div>
      <!---->
      <div class="relative py-1">
        <label class="label-title">Password</label>
        <input type="password" autocomplete="off" placeholder="Password" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-black-900 focus:ring-2 focus:ring-black-900 dark:focus:ring-black-900" id="password" value="" name="password">
        @error('password')<p style='color: red;'>{{ $message }}</p>@enderror
                        <span id="password-alert"></span>
                              </div>
      <!---->
      <div class="relative py-1">
        <label class="label-title">Confirm Password</label>
        <input type="password" autocomplete="off" placeholder="Password" class="form-input relative block w-full disabled:cursor-not-allowed disabled:opacity-75 focus:outline-none border-0 rounded-md placeholder-gray-400 dark:placeholder-gray-500 text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 dark:text-white ring-1 ring-inset dark:ring-red-400 focus:ring-2 focus:ring-black-500 dark:focus:ring-black-400" id="confirm_password" value="" name="password_confirmation">
        @error('password_confirmation')<p style='color: red;'>{{ $message }}</p>@enderror
                        <span id="confirm_password-alert"></span>
                              </div>
      <!---->
      <div class="text-center">
        <input type="hidden" name="terms" value="1" id="terms" checked required>
        <button type="submit" class="justify-center focus:outline-none disabled:cursor-not-allowed disabled:opacity-75 flex-shrink-0 font-medium rounded-md text-sm gap-x-1.5 px-2.5 py-2.5 shadow-sm text-white dark:text-gray-900 bg-pink-500 hover:bg-pink-600 disabled:bg-primary-500 dark:bg-primary-400 dark:hover:bg-primary-500 dark:disabled:bg-primary-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:focus-visible:outline-primary-400 inline-flex items-center my-2 w-full text-center">Register</button>
      </div>
        </form>
      </div>
      <!---->
        <div class="mb-5 text-center subtitle-4 font-primary font-normal game-name"> Already member? <a href="{{ url('login') }}" class="text-pink-500 font-primary font-normal">Login</a> Now </div>
    </div>
  </div>
</div>
@endsection