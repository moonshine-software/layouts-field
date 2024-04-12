<div x-data="layouts(
    `{{ $element->getAddRoute() }}`,
    `{{ $element->column() }}`
)"
    {{ $element->attributes() }}
>
    <div class="_layouts-blocks">
        @foreach($element->getFilledLayouts() as $layout)
            {!! $layout !!}
        @endforeach
    </div>

    <div>
        {!! $element->getDropdown() !!}
    </div>

    <br />
</div>
