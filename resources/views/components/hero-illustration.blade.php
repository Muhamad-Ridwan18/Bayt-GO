{{-- Line-art mosque + kaaba illustration for the welcome hero. --}}
<svg {{ $attributes->merge(['class' => 'w-full h-auto']) }} viewBox="0 0 460 380" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi masjid dan Ka'bah">
    {{-- Soft backdrop circle --}}
    <circle cx="235" cy="185" r="168" fill="#EAF2ED" />
    <circle cx="235" cy="185" r="168" stroke="#1A3D34" stroke-opacity="0.06" />
    <circle cx="235" cy="185" r="132" stroke="#1A3D34" stroke-opacity="0.05" stroke-dasharray="2 8" />

    {{-- Decorative dots --}}
    <g fill="#C5A059" opacity="0.55">
        <circle cx="372" cy="86" r="3" />
        <circle cx="388" cy="104" r="3" />
        <circle cx="356" cy="104" r="3" />
        <circle cx="98" cy="250" r="3" />
        <circle cx="82" cy="268" r="3" />
        <circle cx="114" cy="268" r="3" />
    </g>

    <g stroke="#1A3D34" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" fill="#FDFBF6">
        {{-- Left minaret --}}
        <rect x="118" y="150" width="26" height="118" rx="4" />
        <path d="M118 176h26M118 210h26" />
        <path d="M115 150c0-14 7-22 16-22s16 8 16 22z" fill="#EAF2ED" />
        <path d="M131 128v-14" stroke="#C5A059" />
        <path d="M131 106a7 7 0 100 12 5 5 0 110-12z" fill="#C5A059" stroke="#C5A059" stroke-width="2" />

        {{-- Right minaret --}}
        <rect x="316" y="150" width="26" height="118" rx="4" />
        <path d="M316 176h26M316 210h26" />
        <path d="M313 150c0-14 7-22 16-22s16 8 16 22z" fill="#EAF2ED" />
        <path d="M329 128v-14" stroke="#C5A059" />
        <path d="M329 106a7 7 0 100 12 5 5 0 110-12z" fill="#C5A059" stroke="#C5A059" stroke-width="2" />

        {{-- Main building --}}
        <path d="M158 268V176h144v92z" />

        {{-- Central onion dome --}}
        <path d="M170 176c-18-24 6-84 60-84s78 60 60 84z" fill="#EAF2ED" />
        <path d="M230 92V72" stroke="#C5A059" />
        <path d="M230 60a9 9 0 100 14 6.5 6.5 0 110-14z" fill="#C5A059" stroke="#C5A059" stroke-width="2" />

        {{-- Arched main gate --}}
        <path d="M210 268v-40a20 20 0 0140 0v40" fill="#EAF2ED" />
        {{-- Side arch windows --}}
        <path d="M176 268v-30a12 12 0 0124 0v30" fill="#FFFFFF" />
        <path d="M260 268v-30a12 12 0 0124 0v30" fill="#FFFFFF" />
    </g>

    {{-- Kaaba (foreground) --}}
    <g stroke-linejoin="round">
        <path d="M196 300l38-16 38 16v40l-38 16-38-16z" fill="#17352D" stroke="#0F221D" stroke-width="2.5" />
        <path d="M196 300l38 16 38-16" fill="none" stroke="#0F221D" stroke-width="2.5" />
        <path d="M234 316v56" stroke="#0F221D" stroke-width="2.5" />
        {{-- Gold band --}}
        <path d="M196 312l38 16 38-16" stroke="#C5A059" stroke-width="5" fill="none" />
        <path d="M234 300v16" stroke="#C5A059" stroke-width="4" />
    </g>

    {{-- Ground line --}}
    <path d="M96 356h278" stroke="#1A3D34" stroke-opacity="0.12" stroke-width="3" stroke-linecap="round" />
</svg>
