@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<table align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="vertical-align: middle;"><img src="{{ rtrim(config('app.url'), '/') }}/brand/nextward/symbol-192.png" class="logo" width="36" height="36" alt=""></td>
<td class="brand-name" style="vertical-align: middle;">{{ config('app.name') }}</td>
</tr>
</table>
</a>
</td>
</tr>
