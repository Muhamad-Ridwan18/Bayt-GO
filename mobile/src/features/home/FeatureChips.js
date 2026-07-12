import React from 'react';
import { Dimensions, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Crown, Heart, User, Users } from 'lucide-react-native';
import { PressableScale } from '../../ui';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';

const { width: SCREEN_W } = Dimensions.get('window');

const SERVICES = [
  {
    id: 'private',
    title: 'Private Muthowif',
    sub: 'Pendamping eksklusif',
    Icon: User,
    bg: '#ECFDF5',
    color: '#059669',
  },
  {
    id: 'group',
    title: 'Group Jamaah',
    sub: 'Untuk rombongan',
    Icon: Users,
    bg: '#EFF6FF',
    color: '#2563EB',
  },
  {
    id: 'vip',
    title: 'VIP Experience',
    sub: 'Layanan premium',
    Icon: Crown,
    bg: '#FEF9E8',
    color: '#B8954D',
  },
  {
    id: 'women',
    title: 'Wanita Only',
    sub: 'Khusus jamaah wanita',
    Icon: Heart,
    bg: '#FDF2F8',
    color: '#DB2777',
    badge: 'New',
  },
];

export default function FeatureChips({ onFeaturePress, onSeeAll }) {
  return (
    <View style={styles.wrap}>
      <View style={styles.head}>
        <Text style={styles.sectionTitle}>Pilih Layanan yang Kamu Butuhkan</Text>
        {onSeeAll ? (
          <PressableScale onPress={onSeeAll} haptic="light">
            <Text style={styles.seeAll}>Lihat semua</Text>
          </PressableScale>
        ) : null}
      </View>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.row}
        nestedScrollEnabled
      >
        {SERVICES.map((feat) => (
          <PressableScale
            key={feat.id}
            onPress={() => onFeaturePress?.({ sort: feat.id })}
            haptic="light"
            scaleTo={0.97}
          >
            <View style={[styles.card, { backgroundColor: feat.bg }]}>
              {feat.badge ? (
                <View style={styles.newBadge}>
                  <Text style={styles.newBadgeText}>{feat.badge}</Text>
                </View>
              ) : null}
              <View style={[styles.iconWrap, { backgroundColor: `${feat.color}18` }]}>
                <feat.Icon size={20} color={feat.color} strokeWidth={2} />
              </View>
              <Text style={styles.title}>{feat.title}</Text>
              <Text style={styles.sub}>{feat.sub}</Text>
            </View>
          </PressableScale>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginTop: spacing.xl },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: layout.screenPadding,
    marginBottom: spacing.md,
  },
  sectionTitle: {
    flex: 1,
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  seeAll: {
    ...typography.caption,
    fontWeight: '700',
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.goldMuted,
  },
  row: {
    paddingHorizontal: layout.screenPadding,
    gap: spacing.md,
    paddingBottom: spacing.xs,
  },
  card: {
    width: SCREEN_W * 0.38,
    borderRadius: radius.md - 2,
    padding: spacing.lg,
    minHeight: 118,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.04)',
    position: 'relative',
  },
  newBadge: {
    position: 'absolute',
    top: spacing.sm,
    right: spacing.sm,
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
    borderRadius: radius.full,
  },
  newBadgeText: {
    fontSize: 8,
    fontWeight: '800',
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.white,
  },
  iconWrap: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    marginTop: spacing.md,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.textPrimary,
  },
  sub: {
    marginTop: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
    lineHeight: 15,
  },
});
