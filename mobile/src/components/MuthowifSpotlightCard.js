import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Heart, Star } from 'lucide-react-native';
import AppImage from '../ui/AppImage';
import PressableScale from '../ui/PressableScale';
import { colors, radius, shadows, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

const CARD_W = 232;

function MuthowifSpotlightCard({ item, onPress }) {
  const avatarUri = resolveMediaUrl(item.avatar);
  const rating = item.rating ?? '—';
  const langs = (item.languages || []).slice(0, 3);
  const isTop = Number(item.rating) >= 4.5;

  return (
    <PressableScale onPress={onPress} haptic="light" scaleTo={0.98}>
      <View style={styles.card}>
        <View style={styles.coverWrap}>
          <AppImage
            uri={avatarUri}
            name={item.name}
            style={styles.cover}
            rounded={0}
            contentFit="cover"
            contentPosition="center"
          />
          <View style={styles.coverTop}>
            <View style={[styles.badge, isTop ? styles.badgeTop : styles.badgeVerified]}>
              <Text style={styles.badgeText}>{isTop ? 'Top Rated' : 'Terverifikasi'}</Text>
            </View>
            <View style={styles.heartBtn}>
              <Heart size={14} color={colors.white} strokeWidth={2.2} />
            </View>
          </View>
        </View>
        <View style={styles.body}>
          <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
          <View style={styles.ratingRow}>
            <Star size={12} color={colors.warning} fill={colors.warning} strokeWidth={2} />
            <Text style={styles.ratingText}>{rating}</Text>
            <Text style={styles.reviewText}>({item.reviews ?? 0} ulasan)</Text>
          </View>
          {langs.length > 0 ? (
            <View style={styles.langRow}>
              {langs.map((lang) => (
                <View key={lang} style={styles.langTag}>
                  <Text style={styles.langText}>{lang}</Text>
                </View>
              ))}
            </View>
          ) : null}
          {item.experience ? (
            <Text style={styles.exp} numberOfLines={1}>{item.experience}</Text>
          ) : null}
          <View style={styles.footer}>
            <Text style={styles.price}>
              {formatIdr(item.start_price)}
              <Text style={styles.priceUnit}> /hari</Text>
            </Text>
            <View style={styles.avail}>
              <View style={styles.availDot} />
              <Text style={styles.availText}>Tersedia</Text>
            </View>
          </View>
        </View>
      </View>
    </PressableScale>
  );
}

export default memo(MuthowifSpotlightCard);

const styles = StyleSheet.create({
  card: {
    width: CARD_W,
    backgroundColor: colors.white,
    borderRadius: radius.md - 2,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    ...shadows.md,
  },
  coverWrap: {
    aspectRatio: 4 / 5,
    backgroundColor: colors.surface,
    overflow: 'hidden',
    position: 'relative',
  },
  cover: { width: '100%', height: '100%' },
  coverTop: {
    ...StyleSheet.absoluteFillObject,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    padding: spacing.sm + 2,
  },
  badge: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
  },
  badgeTop: { backgroundColor: colors.baytgo },
  badgeVerified: { backgroundColor: 'rgba(26,61,52,0.82)' },
  badgeText: {
    fontSize: 9,
    fontWeight: '700',
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.white,
  },
  heartBtn: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(15,23,42,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: { padding: spacing.md + 2 },
  name: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.textPrimary,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs - 1,
    marginTop: spacing.xs,
  },
  ratingText: {
    fontSize: 11,
    fontWeight: '800',
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  reviewText: {
    fontSize: 10,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
    color: colors.textSecondary,
  },
  langRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs, marginTop: spacing.sm },
  langTag: {
    backgroundColor: colors.surface,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
    borderRadius: radius.sm - 6,
  },
  langText: {
    fontSize: 9,
    fontWeight: '700',
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.slate600,
  },
  exp: {
    marginTop: spacing.xs,
    fontSize: 10,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
    color: colors.textSecondary,
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.sm + 2,
    gap: spacing.sm,
  },
  price: {
    flex: 1,
    fontSize: 13,
    fontWeight: '800',
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.primary,
  },
  priceUnit: {
    fontSize: 10,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
  },
  avail: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  availDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: colors.success },
  availText: {
    fontSize: 9,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.success,
  },
});
