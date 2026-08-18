import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { ChevronDown } from 'lucide-react-native';
import PressableScale from '../../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';
import { useLocale } from '../../utils/locale';

const FALLBACK = {
  id: [
    {
      q: 'Bagaimana cara pesan?',
      a: 'Pilih tanggal perjalanan, jelajahi muthowif yang tersedia, buka profil, dan ikuti langkah pemesanan.',
    },
    {
      q: 'Apakah pembayaran aman?',
      a: 'Pembayaran diproses melalui mitra checkout terintegrasi; data kartu tidak disimpan di BaytGo.',
    },
    {
      q: 'Bisa batal atau jadwal ulang?',
      a: 'Kebijakan tergantung tahap pesanan dan layanan; cek ketentuan di tiket Anda atau hubungi support.',
    },
  ],
  en: [
    {
      q: 'How do I book?',
      a: 'Choose your travel dates, browse available muthowif, open a profile, and follow the booking steps.',
    },
    {
      q: 'Is payment secure?',
      a: 'Payments are processed through integrated checkout partners; card data is not stored in BaytGo.',
    },
    {
      q: 'Can I cancel or reschedule?',
      a: 'Policies depend on the order stage and service; check the terms on your ticket or contact support.',
    },
  ],
};

export default function FaqSection({ faq }) {
  const locale = useLocale();
  const [open, setOpen] = useState(0);
  const title = faq?.title || 'FAQ';
  const items = faq?.items?.length ? faq.items : (FALLBACK[locale] || FALLBACK.id);

  return (
    <View style={styles.wrap}>
      <Text style={styles.title}>{title}</Text>
      <View style={styles.list}>
        {items.map((item, i) => {
          const expanded = open === i;
          return (
            <View key={item.q || i} style={styles.item}>
              <PressableScale onPress={() => setOpen(expanded ? -1 : i)} haptic="light">
                <View style={styles.qRow}>
                  <Text style={styles.q}>{item.q}</Text>
                  <ChevronDown
                    size={16}
                    color={colors.textMuted}
                    strokeWidth={2.2}
                    style={expanded ? styles.chevronOpen : undefined}
                  />
                </View>
              </PressableScale>
              {expanded && item.a ? <Text style={styles.a}>{item.a}</Text> : null}
            </View>
          );
        })}
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
    marginBottom: spacing.md,
  },
  list: { gap: spacing.sm },
  item: {
    backgroundColor: colors.card,
    borderRadius: radius.md - 2,
    borderWidth: 1,
    borderColor: 'rgba(226, 232, 240, 0.8)',
    overflow: 'hidden',
  },
  qRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md + 2,
  },
  q: {
    flex: 1,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
  },
  chevronOpen: { transform: [{ rotate: '180deg' }] },
  a: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md + 2,
    ...typography.small,
    color: colors.textSecondary,
    lineHeight: 18,
  },
});
