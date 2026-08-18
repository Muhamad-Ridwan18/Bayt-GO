import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';
import { useLocale } from '../../utils/locale';

const FALLBACK = {
  id: {
    title: 'Cara kerja BaytGo',
    subtitle: 'Tiga langkah mudah menemukan pendamping ibadah terbaik.',
    steps: [
      { title: 'Cari & Pilih', desc: 'Pilih jenis layanan, tanggal, dan pendamping yang sesuai kebutuhan Anda.' },
      { title: 'Booking & Konfirmasi', desc: 'Selesaikan pemesanan dengan mudah dan dapatkan konfirmasi instan.' },
      { title: 'Berangkat dengan Tenang', desc: 'Pendamping terverifikasi siap mendampingi perjalanan ibadah Anda.' },
    ],
  },
  en: {
    title: 'How BaytGo works',
    subtitle: 'Three easy steps to find the best worship companion.',
    steps: [
      { title: 'Search & Choose', desc: 'Choose the service type, dates, and companion that fit your needs.' },
      { title: 'Book & Confirm', desc: 'Complete your booking easily and get instant confirmation.' },
      { title: 'Travel with Peace of Mind', desc: 'Verified companions are ready to assist your pilgrimage.' },
    ],
  },
};

export default function HowItWorks({ work }) {
  const locale = useLocale();
  const fallback = FALLBACK[locale] || FALLBACK.id;
  const title = work?.title || fallback.title;
  const subtitle = work?.subtitle || fallback.subtitle;
  const steps = (work?.steps?.length ? work.steps : fallback.steps).slice(0, 3);

  return (
    <View style={styles.wrap}>
      <Text style={styles.title}>{title}</Text>
      {subtitle ? <Text style={styles.sub}>{subtitle}</Text> : null}
      <View style={styles.steps}>
        {steps.map((step, i) => (
          <View key={step.title || i} style={styles.card}>
            <View style={styles.num}>
              <Text style={styles.numText}>{i + 1}</Text>
            </View>
            <Text style={styles.stepTitle}>{step.title}</Text>
            <Text style={styles.stepDesc}>{step.desc}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginTop: spacing.xl,
    paddingHorizontal: layout.screenPadding,
  },
  title: {
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    textAlign: 'center',
  },
  sub: {
    marginTop: spacing.xs,
    ...typography.caption,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  steps: { marginTop: spacing.lg, gap: spacing.md },
  card: {
    backgroundColor: colors.card,
    borderRadius: radius.md - 2,
    padding: spacing.lg,
    borderWidth: 1,
    borderColor: 'rgba(226, 232, 240, 0.8)',
    alignItems: 'center',
  },
  num: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  numText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.white,
  },
  stepTitle: {
    marginTop: spacing.md,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  stepDesc: {
    marginTop: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 18,
  },
});
