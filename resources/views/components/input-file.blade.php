@props(['disabled' => false])

<input type="file" @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100']) }} />
