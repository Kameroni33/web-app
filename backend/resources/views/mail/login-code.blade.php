<x-mail::message>
# Sign in to {{ config('app.name') }}

Here's your sign-in code:

<x-mail::code>
{{ $code }}
</x-mail::code>

Type it into the app. It expires in {{ $expiresInMinutes }} minutes.

**Reading this on the device you're signing in on?** Skip the code:

<x-mail::button :url="$url">
Sign in
</x-mail::button>

<x-slot:subcopy>
Didn't try to sign in? You can ignore this email. Nobody can get in without the code or the button above.
</x-slot:subcopy>
</x-mail::message>
