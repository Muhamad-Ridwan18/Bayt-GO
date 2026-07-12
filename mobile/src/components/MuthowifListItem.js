import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { CircleCheckBig, ChevronRight, MapPin, Star } from 'lucide-react-native';
import AppImage from '../ui/AppImage';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

function MuthowifListItem({ item, onPress }) {
  const langs = (item.languages || []).join(', ');

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.press}>
      <Card style={styles.card} padding={spacing.md} elevated>
        <View style={styles.row}>
          <View style={styles.avatarWrap}>
            <AppImage uri={resolveMediaUrl(item.avatar)} size={64} rounded={radius.sm} />
            <View style={styles.verified}>
              <CircleCheckBig size={14} color={colors.success} strokeWidth={2.5} />
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
            {langs ? <Text style={styles.langs} numberOfLines={1}>{langs}</Text> : null}
            <View style={styles.metaRow}>
              <Star size={13} color={colors.warning} fill={colors.warning} strokeWidth={2} />
              <Text style={styles.rating}>{item.rating ?? '—'}</Text>
              <Text style={styles.reviews}>({item.reviews ?? 0})</Text>
            </View>
            <Text style={styles.price}>Mulai {formatIdr(item.start_price)} / hari</Text>
          </View>

          <ChevronRight size={20} color={colors.textMuted} strokeWidth={2} />
        </View>
      </Card>
    </PressableScale>
  );
}

export default memo(MuthowifListItem);

const styles = StyleSheet.create({
  press: { marginBottom: spacing.md },
  card: { borderRadius: radius.md },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  avatarWrap: { position: 'relative' },
  verified: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    backgroundColor: colors.white,
    borderRadius: radius.full,
    padding: 1,
  },
  body: { flex: 1 },
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
    marginTop: spacing.xs,
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
    fontSize: 10,
    color: colors.baytgo,
  },
  langs: {
    marginTop: spacing.xs,
    ...typography.label,
    color: colors.textSecondary,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  rating: {
    ...typography.small,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  reviews: {
    ...typography.label,
    color: colors.textSecondary,
  },
  price: {
    marginTop: spacing.sm,
    ...typography.small,
    color: colors.baytgo,
  },
});
