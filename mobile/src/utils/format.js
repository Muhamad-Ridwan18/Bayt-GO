export function formatIdr(amount) {
  if (!amount || amount < 1) return '—';
  return `Rp ${Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
}
