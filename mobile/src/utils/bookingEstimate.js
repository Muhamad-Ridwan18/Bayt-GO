const PLATFORM_FEE_RATE = 0.075;
const PLATFORM_FEE_PERCENT = 7.5;

function parseDay(iso) {
  const d = new Date(iso);
  d.setHours(0, 0, 0, 0);
  return d;
}

function round2(value) {
  return Math.round(value * 100) / 100;
}

export function billingNightsInclusive(startDate, endDate) {
  const start = parseDay(startDate);
  const end = parseDay(endDate || startDate);
  const diff = Math.round((end.getTime() - start.getTime()) / 86400000);
  return Math.max(1, diff + 1);
}

export function estimateBookingPricing({
  service,
  startDate,
  endDate,
  withSameHotel = false,
  withTransport = false,
  selectedAddOnIds = [],
  addOns = [],
}) {
  if (!service) return null;

  const nights = billingNightsInclusive(startDate, endDate);
  const dailyPrice = Number(service.price || 0);
  const lines = [];

  if (dailyPrice > 0 && nights > 0) {
    lines.push({
      key: 'daily',
      label: `Tarif harian × ${nights} hari`,
      amount: round2(nights * dailyPrice),
    });
  }

  addOns
    .filter((addon) => selectedAddOnIds.includes(addon.id))
    .forEach((addon) => {
      lines.push({
        key: `addon-${addon.id}`,
        label: addon.name,
        amount: round2(Number(addon.price || 0)),
      });
    });

  const hotelPerDay = Number(service.same_hotel_price_per_day || 0);
  if (withSameHotel && hotelPerDay > 0) {
    const amount = round2(nights * hotelPerDay);
    if (amount > 0) {
      lines.push({ key: 'same_hotel', label: 'Hotel sama', amount });
    }
  }

  const transportFlat = Number(service.transport_price_flat || 0);
  if (withTransport && transportFlat > 0) {
    lines.push({ key: 'transport', label: 'Transport', amount: round2(transportFlat) });
  }

  const base = round2(lines.reduce((sum, line) => sum + line.amount, 0));
  const platformFee = round2(base * PLATFORM_FEE_RATE);

  return {
    base,
    platform_fee: platformFee,
    platform_fee_percent: PLATFORM_FEE_PERCENT,
    total_payable: round2(base + platformFee),
    lines,
    nights,
  };
}
