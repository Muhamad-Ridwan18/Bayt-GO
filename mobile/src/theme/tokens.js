export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  '2xl': 24,
  '3xl': 32,
  '4xl': 40,
  '5xl': 48,
  '6xl': 56,
  '7xl': 64,
};

export const radius = {
  sm: 12,
  md: 20,
  lg: 28,
  xl: 36,
  hero: 36,
  full: 9999,
};

export const palette = {
  primary: '#059669',
  primaryDark: '#1A3D34',
  primaryLight: '#ECFDF5',
  gold: '#C5A059',
  goldMuted: '#B8954D',
  goldLight: '#FEF9E8',
  background: '#F8FAFC',
  card: '#FFFFFF',
  surface: '#F1F5F9',
  border: '#E2E8F0',
  textPrimary: '#0F172A',
  textSecondary: '#64748B',
  textMuted: '#94A3B8',
  error: '#EF4444',
  errorLight: '#FEF2F2',
  success: '#22C55E',
  successLight: '#F0FDF4',
  warning: '#F59E0B',
  warningLight: '#FFFBEB',
  overlay: 'rgba(15, 23, 42, 0.45)',
  white: '#FFFFFF',
};

export const colors = {
  ...palette,
  baytgo: palette.primaryDark,
  baytgoDark: '#0F221D',
  baytgoLight: '#E8EEEC',
  gold: palette.gold,
  goldMuted: palette.goldMuted,
  goldLight: palette.goldLight,
  canvas: palette.background,
  canvasSoft: '#FBFCFE',
  white: palette.white,
  slate900: palette.textPrimary,
  slate800: '#1E293B',
  slate700: '#334155',
  slate600: '#475569',
  slate500: palette.textSecondary,
  slate400: palette.textMuted,
  slate200: palette.border,
  slate100: palette.surface,
  emerald600: palette.success,
  emerald50: palette.successLight,
};

export const typography = {
  hero: { fontSize: 32, fontWeight: '800', lineHeight: 40, fontFamily: 'PlusJakartaSans_800ExtraBold' },
  title: { fontSize: 24, fontWeight: '700', lineHeight: 32, fontFamily: 'PlusJakartaSans_700Bold' },
  subtitle: { fontSize: 20, fontWeight: '600', lineHeight: 28, fontFamily: 'PlusJakartaSans_600SemiBold' },
  body: { fontSize: 16, fontWeight: '500', lineHeight: 24, fontFamily: 'PlusJakartaSans_500Medium' },
  caption: { fontSize: 14, fontWeight: '500', lineHeight: 20, fontFamily: 'PlusJakartaSans_500Medium' },
  small: { fontSize: 12, fontWeight: '600', lineHeight: 16, fontFamily: 'PlusJakartaSans_600SemiBold' },
  label: { fontSize: 11, fontWeight: '700', lineHeight: 14, fontFamily: 'PlusJakartaSans_700Bold', letterSpacing: 0.4 },
};

export const shadows = {
  sm: {
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.04,
    shadowRadius: 12,
    elevation: 2,
  },
  md: {
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.06,
    shadowRadius: 20,
    elevation: 4,
  },
  lg: {
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 16 },
    shadowOpacity: 0.1,
    shadowRadius: 28,
    elevation: 8,
  },
  float: {
    shadowColor: '#0F172A',
    shadowOffset: { width: 0, height: 14 },
    shadowOpacity: 0.14,
    shadowRadius: 28,
    elevation: 12,
  },
  navFloat: {
    shadowColor: '#0F172A',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.1,
    shadowRadius: 24,
    elevation: 10,
  },
};

export const motion = {
  fast: 150,
  normal: 250,
  slow: 400,
  spring: { damping: 18, stiffness: 220, mass: 0.8 },
};

export const layout = {
  minTouch: 48,
  screenPadding: 20,
  tabBarOffset: 110,
  navBar: {
    height: 64,
    marginHorizontal: 20,
    marginBottom: 24,
    floatGap: 10,
  },
};

export const gradients = {
  primary: ['#1A3D34', '#0F221D'],
  primarySoft: ['#059669', '#1A3D34'],
  gold: ['#D4AF6A', '#C5A059'],
  heroOverlay: ['transparent', 'rgba(15, 34, 29, 0.75)'],
  cardShine: ['rgba(255,255,255,0.12)', 'transparent'],
};
