@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php($brand = $color === 'primary' ? 'background-color:#ca2316;border-color:#ca2316;' : '')
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="{{ $brand }}">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
