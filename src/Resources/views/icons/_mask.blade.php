@if ($vector ?? true)
    <span
        class="icon-mask shrink-0 {{ $class }}"
        style="--icon-mask-url: url('{{ $url }}')"
        aria-hidden="true"
    ></span>
@else
    <img
        src="{{ $url }}"
        class="inline-block shrink-0 object-contain {{ $class }}"
        alt=""
        aria-hidden="true"
        loading="lazy"
    >
@endif
