@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm']) }}>
