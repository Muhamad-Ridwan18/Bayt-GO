import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Calendar, Headphones, ShieldCheck, Tag } from 'lucide-react-native';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';

const TRUST_USPS = [
  { Icon: ShieldCheck, title: 'Muthowif Terverifikasi' },
  { Icon: Tag, title: 'Harga Transparan' },
  { Icon: Calendar, title: 'Real-time Update' },
  { Icon: Headphones, title: 'Bantuan 24/7' },
];

export default function TrustSection() {
  return (
    <View style={styles.wrap}>
      {TRUST_USPS.map((usp) => (
        <View key={usp.title} style={styles.item}>
          <View style={styles.icon}>
            <usp.Icon size={18} color={colors.baytgo} strokeWidth={2} />
          </View>
          <Text style={styles.title} numberOfLines={2}>{usp.title}</Text>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    paddingHorizontal: layout.screenPadding,
    marginTop: spacing.xl,
    gap: spacing.sm,
  },
  item: {
    flex: 1,
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: radius.sm,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.xs,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.07)',
  },
  icon: {
    width: 36,
    height: 36,
    borderRadius: radius.sm - 4,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm - 1,
  },
  title: {
    fontSize: 9,
    lineHeight: 12,
    textAlign: 'center',
    fontFamily: 'PlusJakartaSans_700Bold',
    fontWeight: '700',
    color: colors.baytgo,
  },
});
