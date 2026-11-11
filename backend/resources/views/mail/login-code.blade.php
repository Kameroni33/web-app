<x-mail::message>
# Your sign-in code

<x-mail::panel>
<span style="font-size: 28px; letter-spacing: 6px; font-weight: bold;">{{ $code }}</span>
</x-mail::panel>

Type this code into the app. It expires in {{ $expiresInMinutes }} minutes.

Or sign in on this device:

<x-mail::button :url="$url">
Sign in
</x-mail::button>

If you didn't try to sign in, you can ignore this email.
</x-mail::message>
