<template>
    <span aria-hidden="true" class="rolling-arrow"><i>↗</i><i>↗</i></span>
</template>
<style>
.rolling-arrow { position: relative; display: inline-block; flex: none; overflow: hidden; width: 1.15em; height: 1.15em; vertical-align: -.16em; }
.rolling-arrow i { position: absolute; inset: 0; display: grid; place-items: center; font-style: normal; line-height: 1; transition: transform .32s cubic-bezier(.22,.68,.2,1); }
.rolling-arrow i + i { color: #75adff; transform: translateY(110%); }
:is(a,button):focus-visible .rolling-arrow i:first-child { transform: translate(65%,-110%); }
:is(a,button):focus-visible .rolling-arrow i + i { transform: none; }
@media (hover: hover) {
    :is(a,button):hover .rolling-arrow i:first-child { transform: translate(65%,-110%); }
    :is(a,button):hover .rolling-arrow i + i { transform: none; }
}
@media (prefers-reduced-motion: reduce) { .rolling-arrow i { transition: none; } }
</style>

