import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  Bed, Bus, Calendar, CheckCircle2, ChevronRight, Clock, MapPin, MessageCircle, Star, Users,
} from 'lucide-react-native';
import AppImage from '../../ui/AppImage';
import Card from '../../ui/Card';
import PressableScale from '../../ui/PressableScale';
import StatusPill from './StatusPill';
import { colors, gradients, layout, radius, shadows, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';
import { formatDateRange, serviceTypeLabel } from '../../utils/bookingLabels';

const STEPS = [
  { key: 'pending', label: 'Menunggu' },
  { key: 'confirmed', label: 'Dikonfirmasi' },
  { key: 'in_progress', label: 'Berlangsung' },
  { key: 'completed', label: 'Selesai' },
];

function stepIndex(status) {
  if (status === 'cancelled') return -1;
  const idx = STEPS.findIndex((s) => s.key === status);
  return idx >= 0 ? idx : 0;
}

export function BookingDetailHero({
  bookingCode,
  muthowifName,
  avatarUri,
  bookingMeta,
  paymentMeta,
  amount,
  feeHint,
  onPressMuthowif,
}) {
  return (
    <View style={styles.heroWrap}>
      <LinearGradient colors={gradients.primary} style={styles.heroGradient}>
        <View style={styles.heroTop}>
          <Text style={styles.heroEyebrow}>Kode pesanan</Text>
          <Text style={styles.heroCode}>{bookingCode}</Text>
        </View>

        <PressableScale onPress={onPressMuthowif} disabled={!onPressMuthowif} haptic="light">
          <View style={styles.heroProfile}>
            <View style={styles.avatarRing}>
              <AppImage uri={avatarUri} name={muthowifName} size={64} rounded={radius.md} />
            </View>
            <View style={styles.heroProfileCopy}>
              <Text style={styles.heroLabel}>Muthowif</Text>
              <Text style={styles.heroName} numberOfLines={2}>{muthowifName}</Text>
              {onPressMuthowif ? (
                <View style={styles.chatHint}>
                  <MessageCircle size={12} color="rgba(255,255,255,0.85)" strokeWidth={2} />
                  <Text style={styles.chatHintText}>Ketuk untuk chat</Text>
                </View>
              ) : null}
            </View>
          </View>
        </PressableScale>

        <View style={styles.heroPills}>
          <StatusPill label={bookingMeta.label} color={bookingMeta.color} />
          <StatusPill label={paymentMeta.label} color={paymentMeta.color} />
        </View>

        <View style={styles.heroAmountBox}>
          <Text style={styles.heroAmountLabel}>Total pesanan</Text>
          <Text style={styles.heroAmount}>{formatIdr(amount)}</Text>
          {feeHint ? <Text style={styles.heroFeeHint}>{feeHint}</Text> : null}
        </View>
      </LinearGradient>
    </View>
  );
}

export function BookingProgressBar({ status }) {
  if (status === 'cancelled') {
    return (
      <Card style={styles.progressCard} padding={spacing.lg} elevated={false}>
        <Text style={styles.progressCancelled}>Pesanan dibatalkan</Text>
      </Card>
    );
  }

  const active = stepIndex(status);

  return (
    <Card style={styles.progressCard} padding={spacing.lg} elevated={false}>
      <View style={styles.progressRow}>
        {STEPS.map((step, index) => {
          const done = index <= active;
          const current = index === active;
          return (
            <View key={step.key} style={styles.progressStep}>
              <View style={[styles.progressDot, done && styles.progressDotDone, current && styles.progressDotCurrent]}>
                {done ? <CheckCircle2 size={12} color={colors.white} strokeWidth={2.5} /> : null}
              </View>
              <Text style={[styles.progressLabel, done && styles.progressLabelDone]} numberOfLines={1}>
                {step.label}
              </Text>
            </View>
          );
        })}
      </View>
      <View style={styles.progressTrack}>
        <View style={[styles.progressFill, { width: `${Math.max(12, (active / (STEPS.length - 1)) * 100)}%` }]} />
      </View>
    </Card>
  );
}

function TripTile({ icon: Icon, label, value }) {
  return (
    <View style={styles.tripTile}>
      <View style={styles.tripIcon}>
        <Icon size={16} color={colors.baytgo} strokeWidth={2} />
      </View>
      <Text style={styles.tripLabel}>{label}</Text>
      <Text style={styles.tripValue} numberOfLines={2}>{value}</Text>
    </View>
  );
}

export function TripSummaryGrid({ booking, nights }) {
  return (
    <Card style={styles.tripCard} padding={spacing.lg} elevated>
      <Text style={styles.tripTitle}>Ringkasan perjalanan</Text>
      <View style={styles.tripGrid}>
        <TripTile icon={Calendar} label="Tanggal" value={formatDateRange(booking.starts_on, booking.ends_on)} />
        <TripTile icon={Clock} label="Durasi" value={`${nights} hari`} />
        <TripTile icon={MapPin} label="Layanan" value={serviceTypeLabel(booking.service_type)} />
        <TripTile icon={Users} label="Jamaah" value={`${booking.pilgrim_count} orang`} />
        {booking.with_same_hotel ? <TripTile icon={Bed} label="Hotel" value="Sama dengan muthowif" /> : null}
        {booking.with_transport ? <TripTile icon={Bus} label="Transport" value="Termasuk" /> : null}
      </View>
    </Card>
  );
}

const ACTION_THEMES = {
  default: { bg: colors.baytgoLight, color: colors.baytgo },
  success: { bg: '#ECFDF5', color: colors.success },
  warning: { bg: colors.warningLight, color: '#B45309' },
  danger: { bg: colors.errorLight, color: colors.error },
};

function ActionRow({ action, showDivider }) {
  const Icon = action.icon;
  const theme = ACTION_THEMES[action.tone || 'default'];

  return (
    <PressableScale onPress={action.onPress} haptic="light" disabled={action.disabled}>
      <View style={[styles.actionRow, showDivider && styles.actionRowDivider]}>
        <View style={[styles.actionRowIcon, { backgroundColor: theme.bg }]}>
          <Icon size={20} color={theme.color} strokeWidth={2} />
        </View>
        <View style={styles.actionRowCopy}>
          <Text style={[styles.actionRowTitle, action.tone === 'danger' && styles.actionRowTitleDanger]}>
            {action.label}
          </Text>
          {action.hint ? <Text style={styles.actionRowHint}>{action.hint}</Text> : null}
        </View>
        <ChevronRight size={18} color={colors.textMuted} strokeWidth={2} />
      </View>
    </PressableScale>
  );
}

export function BookingActionList({ actions }) {
  if (!actions.length) return null;

  const primary = actions.filter((a) => a.tone !== 'danger');
  const danger = actions.filter((a) => a.tone === 'danger');

  return (
    <Card style={styles.actionCard} padding={0} elevated>
      <View style={styles.actionHeader}>
        <Text style={styles.actionTitle}>Layanan & tindakan</Text>
      </View>
      {primary.map((action, index) => (
        <ActionRow
          key={action.key}
          action={action}
          showDivider={index < primary.length - 1 || danger.length > 0}
        />
      ))}
      {danger.map((action, index) => (
        <ActionRow
          key={action.key}
          action={action}
          showDivider={index < danger.length - 1}
        />
      ))}
    </Card>
  );
}

export function ReviewCard({ review, onEdit }) {
  return (
    <Card style={styles.reviewCard} padding={spacing.lg} elevated={false}>
      <View style={styles.reviewHeader}>
        <Text style={styles.reviewTitle}>Ulasan Anda</Text>
        {onEdit ? (
          <PressableScale onPress={onEdit} haptic="light">
            <Text style={styles.reviewEdit}>Edit</Text>
          </PressableScale>
        ) : null}
      </View>
      <View style={styles.reviewStars}>
        {Array.from({ length: 5 }).map((_, i) => (
          <Star
            key={i}
            size={18}
            color="#F59E0B"
            fill={i < review.rating ? '#F59E0B' : 'transparent'}
            strokeWidth={2}
          />
        ))}
      </View>
      {review.comment ? <Text style={styles.reviewComment}>{review.comment}</Text> : null}
    </Card>
  );
}

export function HistoryItemCard({ title, lines = [], date }) {
  return (
    <View style={styles.historyCard}>
      <Text style={styles.historyTitle}>{title}</Text>
      {lines.map((line) => (
        <Text key={line} style={styles.historyLine}>{line}</Text>
      ))}
      {date ? <Text style={styles.historyDate}>{date}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  heroWrap: { marginBottom: spacing.lg, borderRadius: radius.lg, overflow: 'hidden', ...shadows.md },
  heroGradient: { padding: spacing.xl },
  heroTop: { marginBottom: spacing.lg },
  heroEyebrow: { ...typography.label, color: 'rgba(255,255,255,0.72)', textTransform: 'uppercase' },
  heroCode: { marginTop: spacing.xs, ...typography.title, color: colors.white, letterSpacing: 0.5 },
  heroProfile: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  avatarRing: {
    padding: 3,
    borderRadius: radius.md + 4,
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.35)',
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  heroProfileCopy: { flex: 1 },
  heroLabel: { ...typography.label, color: 'rgba(255,255,255,0.7)' },
  heroName: { marginTop: 2, ...typography.subtitle, color: colors.white },
  chatHint: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: spacing.sm },
  chatHintText: { ...typography.small, color: 'rgba(255,255,255,0.85)', fontWeight: '600' },
  heroPills: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.lg },
  heroAmountBox: {
    marginTop: spacing.xl,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.15)',
  },
  heroAmountLabel: { ...typography.label, color: 'rgba(255,255,255,0.72)' },
  heroAmount: { marginTop: spacing.xs, ...typography.hero, fontSize: 28, color: colors.goldLight },
  heroFeeHint: { marginTop: spacing.xs, ...typography.small, color: 'rgba(255,255,255,0.72)', fontWeight: '500' },
  progressCard: { marginBottom: spacing.md },
  progressCancelled: { ...typography.caption, color: colors.error, fontFamily: 'PlusJakartaSans_700Bold', textAlign: 'center' },
  progressRow: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.xs },
  progressStep: { flex: 1, alignItems: 'center' },
  progressDot: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    borderColor: colors.border,
    backgroundColor: colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  progressDotDone: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  progressDotCurrent: { ...shadows.sm },
  progressLabel: { marginTop: spacing.sm, ...typography.label, color: colors.textMuted, textAlign: 'center' },
  progressLabelDone: { color: colors.baytgo },
  progressTrack: {
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.surface,
    marginTop: spacing.md,
    overflow: 'hidden',
  },
  progressFill: { height: '100%', backgroundColor: colors.baytgo, borderRadius: 2 },
  tripCard: { marginBottom: spacing.md },
  tripTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginBottom: spacing.md },
  tripGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  tripTile: {
    width: '48%',
    backgroundColor: colors.surface,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  tripIcon: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  tripLabel: { ...typography.label, color: colors.textMuted },
  tripValue: { marginTop: 2, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  actionCard: { marginBottom: spacing.md, overflow: 'hidden' },
  actionHeader: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    paddingBottom: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  actionTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  actionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
    minHeight: layout.minTouch + 8,
    backgroundColor: colors.card,
  },
  actionRowDivider: {
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  actionRowIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionRowCopy: { flex: 1, gap: 2 },
  actionRowTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
  },
  actionRowTitleDanger: { color: colors.error },
  actionRowHint: { ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  reviewCard: { marginBottom: spacing.md, backgroundColor: '#FFFBEB', borderColor: '#FDE68A' },
  reviewHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  reviewTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  reviewEdit: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  reviewStars: { flexDirection: 'row', gap: spacing.xs, marginTop: spacing.md },
  reviewComment: { marginTop: spacing.md, ...typography.caption, color: colors.textSecondary, lineHeight: 22 },
  historyCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.sm,
    padding: spacing.md,
    marginBottom: spacing.sm,
    borderWidth: 1,
    borderColor: colors.border,
  },
  historyTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  historyLine: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  historyDate: { marginTop: spacing.sm, ...typography.label, color: colors.textMuted },
});
