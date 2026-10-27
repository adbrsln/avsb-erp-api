@props(['url'])
<tr>
<td class="header">
<a href="{{ config('app.frontend_url') }}" style="display: inline-block;">
<img src="{{ rtrim((string) config('app.url'), '/') }}/mail-logo.png"
     alt="{{ config('app.name') }}"
     width="48" height="48"
     style="display: block; border: 0; max-height: 48px; width: auto;">
</a>
</td>
</tr>
