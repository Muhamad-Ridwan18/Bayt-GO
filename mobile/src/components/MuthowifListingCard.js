import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import {
  Check,
  Heart,
  Info,
  MapPin,
  MessageCircle,
  Quote,
  Star,
  User,
} from 'lucide-react-native';
import AppImage from '../ui/AppImage';
import Button from '../ui/Button';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

function AttrCell({ icon: Icon, label, value }) {
  return (
    <View style={styles.attrCell}>
      <Icon size={16} color={colors.baytgo} strokeWidth={2} />
      <Text style={styles.attrValue} numberOfLines={2}>{value || '—'}</Text>
      <Text style={styles.attrLabel}>{label}</Text>
    </View>
  );
}

function MuthowifListingCard({ item, onPressDetail, onPressBook }) {
  const langs = item.languages || [];
  const avatarUri = resolveMediaUrl(item.avatar);
  const rating = item.rating ?? null;
  const langTags = langs.slice(0, 2);
  const langDisplay = langs.join(', ') || '—';
  const specialty = item.specialty || 'Pendamping Ibadah';

  return (
    <Card style={styles.card} padding={spacing.lg} elevated>
      <View style={styles.topRow}>
        <View style={styles.avatarWrap}>
          <AppImage uri={avatarUri} size={72} rounded={radius.full} />
          <View style={styles.verifiedDot}>
            <Check size={12} color={colors.white} strokeWidth={2.5} />
          </View>
        </View>

        <View style={styles.topInfo}>
          <View style={styles.nameRow}>
            <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
            <Heart size={20} color={colors.textMuted} strokeWidth={2} />
          </View>

          <View style={styles.ratingRow}>
            <Star size={13} color={colors.warning} fill={colors.warning} strokeWidth={2} />
            <Text style={styles.ratingText}>{rating ?? '—'}</Text>
            <Text style={styles.reviewText}>({item.reviews ?? 0} ulasan)</Text>
          </View>

          {item.experience ? (
            <Text style={styles.experienceText} numberOfLines={1}>{item.experience}</Text>
          ) : null}

          {langTags.length > 0 ? (
            <View style={styles.tagRow}>
              {langTags.map((lang) => (
                <View key={lang} style={styles.tag}>
                  <Text style={styles.tagText}>{lang}</Text>
                </View>
              ))}
            </View>
          ) : null}
        </View>
      </View>

      <View style={styles.attrGrid}>
        <AttrCell icon={User} label="Spesialisasi" value={specialty} />
        <View style={styles.attrDivider} />
        <AttrCell icon={MapPin} label="Domisili" value={item.location} />
        <View style={styles.attrDivider} />
        <AttrCell icon={MessageCircle} label="Bahasa" value={langDisplay} />
      </View>

      {item.bio ? (
        <View style={styles.quoteBox}>
          <Quote size={16} color={colors.baytgo} strokeWidth={2} style={styles.quoteIcon} />
          <Text style={styles.quoteText}>{item.bio}</Text>
        </View>
      ) : null}

      <View style={styles.footer}>
        <View style={styles.priceBlock}>
          <Text style={styles.priceLabel}>Mulai dari</Text>
          <Text style={styles.price}>
            {formatIdr(item.start_price)}
            <Text style={styles.priceUnit}> /hari</Text>
          </Text>
        </View>

        <View style={styles.actions}>
          <PressableScale onPress={onPressDetail} haptic="light" style={styles.detailBtn}>
            <Info size={16} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.detailBtnText}>Detail</Text>
          </PressableScale>
          <View style={styles.bookBtn}>
            <Button
              label="Pesan"
              onPress={onPressBook}
              size="sm"
              fullWidth={false}
              icon={<MessageCircle size={15} color={colors.white} strokeWidth={2} />}
            />
          </View>
        </View>
      </View>
    </Card>
  );
}

export default memo(MuthowifListingCard);

const styles = StyleSheet.create({
  card: {
    marginBottom: spacing.lg,
    borderRadius: radius.md,
  },
  topRow: { flexDirection: 'row', gap: spacing.md },
  avatarWrap: { position: 'relative' },
  verifiedDot: {
    position: 'absolute',
    right: -2,
    bottom: 2,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: colors.success,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: colors.white,
  },
  topInfo: { flex: 1, paddingTop: spacing.xs },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  name: {
    flex: 1,
    ...typography.body,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  ratingText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  reviewText: {
    ...typography.small,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
  },
  experienceText: {
    marginTop: spacing.xs,
    ...typography.small,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
  },
  tagRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  tag: {
    backgroundColor: colors.surface,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: radius.full,
  },
  tagText: {
    ...typography.label,
    color: colors.slate600,
  },
  attrGrid: {
    flexDirection: 'row',
    alignItems: 'stretch',
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  attrCell: { flex: 1, alignItems: 'center', paddingHorizontal: spacing.xs },
  attrValue: {
    marginTop: spacing.sm,
    ...typography.label,
    color: colors.textPrimary,
    textAlign: 'center',
    lineHeight: 15,
  },
  attrLabel: {
    marginTop: spacing.xs,
    ...typography.label,
    fontSize: 10,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
  },
  attrDivider: {
    width: 1,
    backgroundColor: colors.border,
    marginVertical: spacing.xs,
  },
  quoteBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  quoteIcon: { marginTop: 1 },
  quoteText: {
    flex: 1,
    ...typography.small,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.slate600,
    lineHeight: 18,
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    gap: spacing.md,
  },
  priceBlock: { flex: 1 },
  priceLabel: {
    ...typography.label,
    color: colors.textSecondary,
    textTransform: 'uppercase',
  },
  price: {
    marginTop: spacing.xs,
    ...typography.body,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  priceUnit: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textSecondary,
  },
  actions: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  detailBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    borderRadius: radius.sm,
    backgroundColor: colors.white,
    borderWidth: 1.5,
    borderColor: colors.baytgo,
  },
  detailBtnText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
  bookBtn: { minWidth: 96 },
});
