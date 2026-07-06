export const DEFAULT_PHONE_COUNTRY = { d: '62', iso: 'ID', flag: '🇮🇩', name: 'Indonesia' };

export const PHONE_COUNTRIES = [
  DEFAULT_PHONE_COUNTRY,
  { d: '966', iso: 'SA', flag: '🇸🇦', name: 'Saudi Arabia' },
  { d: '971', iso: 'AE', flag: '🇦🇪', name: 'United Arab Emirates' },
  { d: '60', iso: 'MY', flag: '🇲🇾', name: 'Malaysia' },
  { d: '65', iso: 'SG', flag: '🇸🇬', name: 'Singapore' },
  { d: '673', iso: 'BN', flag: '🇧🇳', name: 'Brunei' },
  { d: '1', iso: 'US', flag: '🇺🇸', name: 'United States' },
  { d: '44', iso: 'GB', flag: '🇬🇧', name: 'United Kingdom' },
  { d: '31', iso: 'NL', flag: '🇳🇱', name: 'Netherlands' },
  { d: '49', iso: 'DE', flag: '🇩🇪', name: 'Germany' },
  { d: '33', iso: 'FR', flag: '🇫🇷', name: 'France' },
  { d: '39', iso: 'IT', flag: '🇮🇹', name: 'Italy' },
  { d: '34', iso: 'ES', flag: '🇪🇸', name: 'Spain' },
  { d: '90', iso: 'TR', flag: '🇹🇷', name: 'Türkiye' },
  { d: '20', iso: 'EG', flag: '🇪🇬', name: 'Egypt' },
  { d: '212', iso: 'MA', flag: '🇲🇦', name: 'Morocco' },
  { d: '213', iso: 'DZ', flag: '🇩🇿', name: 'Algeria' },
  { d: '216', iso: 'TN', flag: '🇹🇳', name: 'Tunisia' },
  { d: '91', iso: 'IN', flag: '🇮🇳', name: 'India' },
  { d: '92', iso: 'PK', flag: '🇵🇰', name: 'Pakistan' },
  { d: '880', iso: 'BD', flag: '🇧🇩', name: 'Bangladesh' },
  { d: '94', iso: 'LK', flag: '🇱🇰', name: 'Sri Lanka' },
  { d: '63', iso: 'PH', flag: '🇵🇭', name: 'Philippines' },
  { d: '66', iso: 'TH', flag: '🇹🇭', name: 'Thailand' },
  { d: '84', iso: 'VN', flag: '🇻🇳', name: 'Vietnam' },
  { d: '81', iso: 'JP', flag: '🇯🇵', name: 'Japan' },
  { d: '82', iso: 'KR', flag: '🇰🇷', name: 'South Korea' },
  { d: '86', iso: 'CN', flag: '🇨🇳', name: 'China' },
  { d: '852', iso: 'HK', flag: '🇭🇰', name: 'Hong Kong' },
  { d: '886', iso: 'TW', flag: '🇹🇼', name: 'Taiwan' },
  { d: '61', iso: 'AU', flag: '🇦🇺', name: 'Australia' },
  { d: '64', iso: 'NZ', flag: '🇳🇿', name: 'New Zealand' },
  { d: '27', iso: 'ZA', flag: '🇿🇦', name: 'South Africa' },
  { d: '234', iso: 'NG', flag: '🇳🇬', name: 'Nigeria' },
  { d: '254', iso: 'KE', flag: '🇰🇪', name: 'Kenya' },
  { d: '974', iso: 'QA', flag: '🇶🇦', name: 'Qatar' },
  { d: '965', iso: 'KW', flag: '🇰🇼', name: 'Kuwait' },
  { d: '973', iso: 'BH', flag: '🇧🇭', name: 'Bahrain' },
  { d: '968', iso: 'OM', flag: '🇴🇲', name: 'Oman' },
  { d: '962', iso: 'JO', flag: '🇯🇴', name: 'Jordan' },
  { d: '961', iso: 'LB', flag: '🇱🇧', name: 'Lebanon' },
];

export function buildFullPhone(dial, national) {
  const d = String(dial || '').replace(/\D/g, '');
  let l = String(national || '').replace(/\D/g, '');
  if (d === '62' && l.startsWith('0')) {
    l = l.slice(1);
  }
  return d && l ? `+${d}${l}` : '';
}

export function findCountryByIso(iso) {
  if (!iso) return null;
  return PHONE_COUNTRIES.find((c) => c.iso === iso.toUpperCase()) || null;
}

export function findCountryByDial(dial) {
  const d = String(dial || '').replace(/\D/g, '');
  return PHONE_COUNTRIES.find((c) => c.d === d) || null;
}

export function parsePhoneForInput(phone) {
  if (!phone?.trim()) {
    return { dial: DEFAULT_PHONE_COUNTRY.d, national: '', countryIso: DEFAULT_PHONE_COUNTRY.iso };
  }

  const cleaned = phone.trim().replace(/\s/g, '');
  if (cleaned.startsWith('+')) {
    const sorted = [...PHONE_COUNTRIES].sort((a, b) => b.d.length - a.d.length);
    for (const c of sorted) {
      if (cleaned.startsWith(`+${c.d}`)) {
        return { dial: c.d, national: cleaned.slice(c.d.length + 1), countryIso: c.iso };
      }
    }
  }

  const digits = cleaned.replace(/\D/g, '');
  if (digits.startsWith('62') && digits.length > 2) {
    return { dial: '62', national: digits.slice(2), countryIso: 'ID' };
  }
  if (digits.startsWith('0')) {
    return { dial: '62', national: digits.slice(1), countryIso: 'ID' };
  }

  return { dial: '62', national: digits, countryIso: 'ID' };
}
