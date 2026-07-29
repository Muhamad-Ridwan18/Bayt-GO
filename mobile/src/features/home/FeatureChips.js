import React from 'react';
import { Dimensions, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Accessibility, Camera, MapPin, Moon, User } from 'lucide-react-native';
import { PressableScale } from '../../ui';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';

const { width: SCREEN_W } = Dimensions.get('window');

/** Mirror web WelcomePageData::buildCategories() */
export const HOME_CATEGORIES = [
  {
    key: 'umroh',
    type: 'layanan',
    title: 'Pendamping Umrah',
    sub: 'Private & group',
    Icon: User,
    bg: '#ECFDF5',
    color: '#059669',
  },
  {
    key: 'mobility',
    type: 'support',
    category: 'mobility',
    title: 'Kursi Roda',
    sub: 'Mobilitas di Haram',
    Icon: Accessibility,
    bg: '#EFF6FF',
    color: '#2563EB',
  },
  {
    key: 'umrah',
    type: 'support',
    category: 'umrah',
    title: 'Pendamping Salat',
    sub: 'Bimbingan ibadah',
    Icon: Moon,
    bg: '#F0FDF4',
    color: '#16A34A',
  },
  {
    key: 'other',
    type: 'support',
    category: 'other',
    title: 'Fotografer & Videografer',
    sub: 'Dokumentasi perjalanan',
    Icon: Camera,
    bg: '#F5F3FF',
    color: '#7C3AED',
  },
  {
    key: 'ziarah',
    type: 'support',
    category: 'ziarah',
    title: 'Raudhah',
    sub: 'Pendamping ziarah',
    Icon: MapPin,
    bg: '#FFF7ED',
    color: '#EA580C',
  },
];

export default function FeatureChips({ onCategoryPress, onSeeAll }) {
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
        {HOME_CATEGORIES.map((feat) => (
          <PressableScale
            key={feat.key}
            onPress={() => onCategoryPress?.(feat)}
            haptic="light"
            scaleTo={0.97}
          >
            <View style={[styles.card, { backgroundColor: feat.bg }]}>
              <View style={[styles.iconWrap, { backgroundColor: `${feat.color}18` }]}>
                <feat.Icon size={20} color={feat.color} strokeWidth={2} />
              </View>
              <Text style={styles.title} numberOfLines={2}>{feat.title}</Text>
              <Text style={styles.sub} numberOfLines={2}>{feat.sub}</Text>
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
    width: SCREEN_W * 0.36,
    borderRadius: radius.md - 2,
    padding: spacing.lg,
    minHeight: 118,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.04)',
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
