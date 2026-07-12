import React, { memo } from 'react';
import { Dimensions, StyleSheet, Text, View } from 'react-native';
import { CircleCheckBig, MapPin, Star } from 'lucide-react-native';
import AppImage from '../ui/AppImage';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

const CARD_WIDTH = Dimensions.get('window').width * 0.62;

function MuthowifCard({ item, onPress }) {
  const langs = (item.languages || []).join(', ');

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.press}>
      <Card style={styles.card} padding={0} elevated>
        <View style={styles.photoWrap}>
          <AppImage
            uri={resolveMediaUrl(item.avatar)}
            style={styles.photo}
            rounded={0}
            contentFit="cover"
          />
          <View style={styles.verified}>
            <CircleCheckBig size={16} color={colors.success} strokeWidth={2.5} />
          </View>
        </View>
        <View style={styles.body}>
          <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
          {item.location ? (
            <View style={styles.locationBadge}>
              <MapPin size={11} color={colors.primary} strokeWidth={2} />
              <Text style={styles.location} numberOfLines={1}>{item.location}</Text>
            </View>
          ) : null}
          {langs ? <Text style={styles.langs} numberOfLines={2}>{langs}</Text> : null}
          <View style={styles.ratingRow}>
            <Star size={14} color={colors.warning} fill={colors.warning} strokeWidth={2} />
            <Text style={styles.rating}>{item.rating ?? '—'}</Text>
            <Text style={styles.reviews}>({item.reviews ?? 0})</Text>
          </View>
          <Text style={styles.price}>Mulai dari {formatIdr(item.start_price)} / hari</Text>
        </View>
      </Card>
    </PressableScale>
  );
}

export default memo(MuthowifCard);
export { CARD_WIDTH };

const styles = StyleSheet.create({
  press: { marginRight: spacing.lg },
  card: {
    width: CARD_WIDTH,
    borderRadius: radius.md,
    overflow: 'hidden',
  },
  photoWrap: {
    aspectRatio: 4 / 5,
    backgroundColor: colors.surface,
    position: 'relative',
  },
  photo: { width: '100%', height: '100%' },
  verified: {
    position: 'absolute',
    top: spacing.md,
    right: spacing.md,
    backgroundColor: colors.white,
    borderRadius: radius.full,
    padding: spacing.xs,
  },
  body: { padding: spacing.lg },
  name: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  locationBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: spacing.xs,
    marginTop: spacing.sm,
    maxWidth: '100%',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
    backgroundColor: colors.primaryLight,
    borderWidth: 1,
    borderColor: colors.border,
  },
  location: {
    flexShrink: 1,
    ...typography.label,
    color: colors.baytgo,
  },
  langs: {
    marginTop: spacing.xs,
    ...typography.label,
    color: colors.textSecondary,
    lineHeight: 16,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  rating: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  reviews: {
    ...typography.label,
    color: colors.textSecondary,
  },
  price: {
    marginTop: spacing.md,
    ...typography.small,
    color: colors.baytgo,
  },
});
