@php
    $tone = $tone ?? 'indigo';
    $iconColor = [
        'indigo' => 'text-[#6366F1]',
        'green' => 'text-green-500',
        'orange' => 'text-orange-500',
        'red' => 'text-[#EF4444]',
    ][$tone] ?? 'text-[#6366F1]';

    $valueTone = $valueTone ?? 'gray';
    $valueColor = [
        'gray' => 'text-gray-900',
        'orange' => 'text-orange-500',
        'green' => 'text-green-600',
        'red' => 'text-[#EF4444]',
        'indigo' => 'text-[#6366F1]',
    ][$valueTone] ?? 'text-gray-900';

    $hintTone = $hintTone ?? 'gray';
    $hintColor = [
        'gray' => 'text-gray-400',
        'green' => 'text-green-600',
        'orange' => 'text-orange-500',
        'red' => 'text-[#EF4444]',
    ][$hintTone] ?? 'text-gray-400';
    $href = $href ?? null;
    $tag = $href ? 'a' : 'div';
    $interactive = $href ? 'hover:shadow-md hover:border-indigo-400 transition-all group' : '';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 block {{ $interactive }}">
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
        <span class="{{ $iconColor }} shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                {!! $icon ?? '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>' !!}
            </svg>
        </span>
    </div>
    <p class="mt-3 text-3xl font-bold {{ $valueColor }}">{{ $value }}</p>
    @isset($hint)
        <p class="mt-1 text-xs {{ $hintColor }}">{{ $hint }}</p>
    @endisset
    @if ($href)
        <p class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-[#6366F1] opacity-0 group-hover:opacity-100 transition-opacity">
            Voir le détail
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
        </p>
    @endif
</{{ $tag }}>
