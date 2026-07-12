import React from 'react';
import { Dimensions, Modal, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  Bed,
  Camera,
  Car,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  Map,
  Star,
  Users,
  UtensilsCrossed,
  X,
} from 'lucide-react-native';
import { AppImage, Card, PressableScale } from '../../ui';
import { colors, gradients, radius, shadows, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';
import { resolveMediaUrl } from '../../utils/mediaUrl';

const { width: SCREEN_W } = Dimensions.get('window');

export const ADDON_ICONS = [Star, Car, Bed, UtensilsCrossed, Camera, Map];

export function AddOnListItem({ addon, index }) {
  const Icon = ADDON_ICONS[index % ADDON_ICONS.length] || Star;

  return (
    <View style={styles.addonRow}>
      <LinearGradient colors={gradients.gold} style={styles.addonIconWrap}>
        <Icon size={20} color={colors.gold} strokeWidth={2} />
      </LinearGradient>
      <View style={styles.addonRowBody}>
        <Text style={styles.addonRowName}>{addon.name}</Text>
        <Text style={styles.addonRowHint}>Opsional - dipilih saat booking</Text>
      </View>
      <View style={styles.addonPricePill}>
        <Text style={styles.addonPricePillText}>{formatIdr(addon.price)}</Text>
      </View>
    </View>
  );
}

export function StatCell({ icon: Icon, label, value }) {
  const CellIcon = Icon || Star;
  return (
    <View style={styles.statCell}>
      <CellIcon size={15} color={colors.baytgo} strokeWidth={2} />
      <Text style={styles.statCellLabel}>{label}</Text>
      <Text style={styles.statCellValue} numberOfLines={3}>{value}</Text>
    </View>
  );
}

export function Stars({ rating, size = 14 }) {
  const rounded = Math.round(Number(rating) || 0);
  return (
    <View style={styles.starsRow}>
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={`star-${i}`}
          size={size}
          color={colors.gold}
          strokeWidth={1.9}
          fill={i < rounded ? colors.gold : 'transparent'}
        />
      ))}
    </View>
  );
}

export function SectionCard({ title, subtitle, icon: Icon, iconBg, children }) {
  return (
    <Card style={styles.sectionCard} padding={spacing.lg} elevated={false}>
      <View style={styles.sectionHeader}>
        {Icon ? (
          <View style={[styles.sectionIcon, { backgroundColor: iconBg || colors.baytgoLight }]}>
            <Icon size={20} color={colors.baytgo} strokeWidth={2} />
          </View>
        ) : null}
        <View style={styles.sectionHeaderText}>
          <Text style={styles.sectionTitle}>{title}</Text>
          {subtitle ? <Text style={styles.sectionSubtitle}>{subtitle}</Text> : null}
        </View>
      </View>
      {children}
    </Card>
  );
}

export function PackageCard({ service }) {
  const isPrivate = service.type === 'private';
  const accent = isPrivate ? colors.gold : colors.baytgo;
  const gradient = isPrivate ? [colors.warningLight, colors.white] : [colors.primaryLight, colors.white];
  const serviceAddOns = service.add_ons || [];

  return (
    <Card style={[styles.packageCard, isPrivate ? styles.packagePrivate : styles.packageGroup]} padding={0} elevated={false}>
      <LinearGradient colors={gradient} style={styles.packageGradient}>
        <View style={styles.packageTopRow}>
          <View style={[styles.packageBadge, { backgroundColor: accent }]}>
            <Text style={styles.packageTypeLabel}>{service.type_label || service.type}</Text>
          </View>
          {service.has_hotel_addon ? (
            <View style={styles.packageMiniBadge}>
              <Bed size={12} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.packageMiniText}>Hotel</Text>
            </View>
          ) : null}
          {service.has_transport_addon ? (
            <View style={styles.packageMiniBadge}>
              <Car size={12} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.packageMiniText}>Transport</Text>
            </View>
          ) : null}
        </View>

        <Text style={[styles.packagePrice, { color: accent }]}>
          {service.price ? formatIdr(service.price) : 'Hubungi kami'}
          {service.price ? <Text style={styles.packagePerDay}> / hari</Text> : null}
        </Text>

        {service.min_pilgrims && service.max_pilgrims ? (
          <View style={styles.packagePaxRow}>
            <Users size={14} color={colors.slate500} strokeWidth={2} />
            <Text style={styles.packagePax}>{service.min_pilgrims}-{service.max_pilgrims} jamaah</Text>
          </View>
        ) : null}

        {service.description ? <Text style={styles.packageDesc}>{service.description}</Text> : null}

        <View style={styles.featureList}>
          {(service.features || []).map((feature) => (
            <View key={feature} style={styles.featureRow}>
              <CheckCircle2 size={16} color={accent} strokeWidth={2} />
              <Text style={styles.featureText}>{feature}</Text>
            </View>
          ))}
        </View>

        {serviceAddOns.length > 0 ? (
          <View style={styles.packageAddonBlock}>
            <Text style={styles.packageAddonTitle}>Add-on paket ini</Text>
            {serviceAddOns.map((addon) => (
              <View key={addon.id} style={styles.packageAddonRow}>
                <Text style={styles.packageAddonName} numberOfLines={1}>{addon.name}</Text>
                <Text style={styles.packageAddonPrice}>{formatIdr(addon.price)}</Text>
              </View>
            ))}
          </View>
        ) : null}
      </LinearGradient>
    </Card>
  );
}

export function ReviewItem({ review }) {
  return (
    <Card style={styles.reviewCard} padding={spacing.md} elevated={false}>
      <View style={styles.reviewHeader}>
        <AppImage uri={resolveMediaUrl(review.customer_avatar)} size={36} rounded={radius.sm} style={styles.reviewAvatar} />
        <View style={styles.reviewMeta}>
          <Text style={styles.reviewName}>{review.customer_name}</Text>
          <Stars rating={review.rating} size={12} />
        </View>
      </View>
      {review.comment ? <Text style={styles.reviewComment}>{review.comment}</Text> : null}
      <Text style={styles.reviewTime}>{review.created_at}</Text>
    </Card>
  );
}

export function PortfolioLightbox({ visible, images, index, title, onClose, onChangeIndex }) {
  if (!visible || !images?.length) return null;

  const imageCount = images.length;
  const active = resolveMediaUrl(images[index]);

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.lightboxOverlay}>
        <PressableScale style={styles.lightboxClose} onPress={onClose} haptic="light">
          <X size={24} color={colors.white} strokeWidth={2.4} />
        </PressableScale>
        {imageCount > 1 ? (
          <>
            <PressableScale
              style={[styles.lightboxNav, styles.lightboxNavLeft]}
              onPress={() => onChangeIndex((index - 1 + imageCount) % imageCount)}
              haptic="light"
            >
              <ChevronLeft size={28} color={colors.white} strokeWidth={2.2} />
            </PressableScale>
            <PressableScale
              style={[styles.lightboxNav, styles.lightboxNavRight]}
              onPress={() => onChangeIndex((index + 1) % imageCount)}
              haptic="light"
            >
              <ChevronRight size={28} color={colors.white} strokeWidth={2.2} />
            </PressableScale>
          </>
        ) : null}
        <AppImage uri={active} style={styles.lightboxImage} rounded={radius.sm} contentFit="contain" />
        {title ? <Text style={styles.lightboxTitle}>{title}</Text> : null}
        {imageCount > 1 ? <Text style={styles.lightboxCounter}>{index + 1} / {imageCount}</Text> : null}
      </View>
    </Modal>
  );
}

export const styles = StyleSheet.create({
  sectionCard: {
    borderRadius: radius.md + 2,
    marginTop: spacing.lg - 2,
    borderColor: colors.slate100,
    ...shadows.sm,
  },
  sectionHeader: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, marginBottom: spacing.lg - 2 },
  sectionIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionHeaderText: { flex: 1 },
  sectionTitle: {
    ...typography.subtitle,
    fontSize: 17,
    lineHeight: 24,
    color: colors.slate900,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  sectionSubtitle: { marginTop: 3, ...typography.small, color: colors.slate500, fontWeight: '500', lineHeight: 17 },

  addonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.canvas,
    borderRadius: radius.sm + 4,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  addonIconWrap: {
    width: 44,
    height: 44,
    borderRadius: radius.sm + 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addonRowBody: { flex: 1 },
  addonRowName: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.slate900 },
  addonRowHint: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  addonPricePill: {
    backgroundColor: colors.baytgo,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: spacing.sm - 2,
  },
  addonPricePillText: { ...typography.small, fontSize: 11, color: colors.white, fontWeight: '800' },

  statCell: { flex: 1, alignItems: 'center', paddingHorizontal: spacing.xs },
  statCellLabel: { marginTop: 6, ...typography.label, fontSize: 10, color: colors.slate500, textTransform: 'uppercase' },
  statCellValue: { marginTop: 4, ...typography.small, color: colors.slate900, textAlign: 'center', lineHeight: 16, fontWeight: '800' },
  starsRow: { flexDirection: 'row', gap: 2, marginTop: 4 },

  packageCard: {
    borderRadius: radius.sm + 6,
    overflow: 'hidden',
    marginBottom: spacing.md,
    borderWidth: 1,
    ...shadows.sm,
  },
  packageGroup: { borderColor: '#A7C4BC' },
  packagePrivate: { borderColor: colors.goldLight },
  packageGradient: { padding: spacing.lg },
  packageTopRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.md },
  packageBadge: { borderRadius: radius.full, paddingHorizontal: spacing.md, paddingVertical: spacing.xs + 1 },
  packageTypeLabel: { ...typography.label, fontSize: 10, color: colors.white, textTransform: 'uppercase', letterSpacing: 0.8 },
  packageMiniBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.white,
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  packageMiniText: { ...typography.small, fontSize: 10, color: colors.baytgo, fontWeight: '700' },
  packagePrice: { ...typography.title, fontSize: 26, lineHeight: 34, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '900' },
  packagePerDay: { ...typography.caption, fontSize: 14, color: colors.slate500, fontWeight: '600' },
  packagePaxRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm - 2, marginTop: 6 },
  packagePax: { ...typography.caption, fontSize: 13, color: colors.slate600, fontWeight: '700' },
  packageDesc: { marginTop: spacing.sm + 2, ...typography.caption, fontSize: 13, lineHeight: 21, color: colors.slate600, fontWeight: '500' },
  featureList: { marginTop: spacing.md },
  featureRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, marginTop: spacing.sm },
  featureText: { flex: 1, ...typography.caption, fontSize: 13, color: colors.slate700, fontWeight: '600', lineHeight: 19 },
  packageAddonBlock: {
    marginTop: spacing.lg - 2,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.06)',
  },
  packageAddonTitle: { ...typography.label, fontSize: 10, color: colors.slate500, textTransform: 'uppercase', marginBottom: spacing.sm },
  packageAddonRow: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm, paddingVertical: spacing.sm - 2 },
  packageAddonName: { flex: 1, ...typography.small, fontSize: 12, color: colors.slate800, fontWeight: '700' },
  packageAddonPrice: { ...typography.small, fontSize: 12, color: colors.baytgo, fontWeight: '800' },

  reviewCard: {
    backgroundColor: colors.slate100,
    borderRadius: radius.sm + 2,
    marginBottom: spacing.sm + 2,
    borderColor: 'transparent',
  },
  reviewHeader: { flexDirection: 'row', gap: spacing.sm + 2 },
  reviewAvatar: { backgroundColor: colors.slate200 },
  reviewMeta: { flex: 1 },
  reviewName: { ...typography.caption, fontSize: 13, color: colors.slate900, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800' },
  reviewComment: { marginTop: spacing.sm + 2, ...typography.caption, fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '500' },
  reviewTime: { marginTop: spacing.sm, ...typography.small, fontSize: 11, color: colors.slate400, fontWeight: '600' },

  lightboxOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15,23,42,0.95)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: spacing.lg,
  },
  lightboxClose: {
    position: 'absolute',
    top: spacing['5xl'],
    right: spacing.xl,
    zIndex: 10,
    padding: spacing.sm,
  },
  lightboxNav: {
    position: 'absolute',
    top: '45%',
    zIndex: 10,
    padding: spacing.md,
  },
  lightboxNavLeft: { left: spacing.sm },
  lightboxNavRight: { right: spacing.sm },
  lightboxImage: { width: SCREEN_W - 32, height: SCREEN_W * 0.85, backgroundColor: 'transparent' },
  lightboxTitle: {
    marginTop: spacing.lg,
    ...typography.caption,
    fontSize: 15,
    color: colors.white,
    textAlign: 'center',
    fontFamily: 'PlusJakartaSans_700Bold',
    fontWeight: '700',
  },
  lightboxCounter: { marginTop: spacing.sm, ...typography.small, fontSize: 12, color: colors.slate400, fontWeight: '600' },
});
