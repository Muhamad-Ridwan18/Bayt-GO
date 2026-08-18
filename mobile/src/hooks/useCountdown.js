import { useEffect, useState } from 'react';

function pad(n) {
  return String(n).padStart(2, '0');
}

export function formatDueDatetime(isoDate) {
  const date = new Date(isoDate);
  if (Number.isNaN(date.getTime())) return '';
  return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function formatRemaining(ms) {
  if (ms <= 0) return 'Waktu habis';
  const total = Math.floor(ms / 1000);
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  return `${pad(h)}:${pad(m)}:${pad(s)}`;
}

export function useCountdown(isoDate) {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (!isoDate) return undefined;
    const id = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(id);
  }, [isoDate]);

  if (!isoDate) {
    return { remainingMs: null, expired: false, label: null };
  }

  const due = new Date(isoDate).getTime();
  if (Number.isNaN(due)) {
    return { remainingMs: null, expired: false, label: null };
  }

  const remainingMs = due - now;
  return {
    remainingMs,
    expired: remainingMs <= 0,
    label: formatRemaining(remainingMs),
  };
}
