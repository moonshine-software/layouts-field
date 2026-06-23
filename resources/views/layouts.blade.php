<div x-data="layouts(
    `{{ $addRoute }}`,
    `{{ $column }}`
)"
    {{ $attributes->class('space-y-2') }}
    data-top-level="true"
    data-r-item-selector=":scope > ._layouts-blocks > ._layouts-block"
>
    <div class="_layouts-blocks space-y-2">
        @foreach($fields as $layout)
            {!! $layout !!}
        @endforeach
    </div>

    <div>
        {!! $dropdown !!}
    </div>
</div>
