@php
    $page = (int) floor($offset / $limit) + 1;
    $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;
    $sep = !empty($baseUrl) && str_contains($baseUrl, '?') ? '&' : '?';
@endphp
@if($totalPages > 1 && !empty($baseUrl))
<div class="flex items-center gap-2 font-mono text-xs">
    @if($page > 1)
    <a href="{{ $baseUrl }}{{ $sep }}limit={{ $limit }}&offset={{ max(0, $offset - $limit) }}" class="btn-secondary text-xs">prev</a>
    @endif
    <span class="text-term-text-dim">page {{ $page }} / {{ $totalPages }}</span>
    @if($page < $totalPages)
    <a href="{{ $baseUrl }}{{ $sep }}limit={{ $limit }}&offset={{ $offset + $limit }}" class="btn-secondary text-xs">next</a>
    @endif
</div>
@endif
