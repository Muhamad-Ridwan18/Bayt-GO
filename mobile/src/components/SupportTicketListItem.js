import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { ChevronRight, LifeBuoy } from 'lucide-react-native';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

function formatTime(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch {
    return '';
  }
}

function statusStyle(status) {
  switch (status) {
    case 'open':
      return { bg: colors.successLight, text: colors.success };
    case 'in_progress':
      return { bg: colors.primaryLight, text: colors.primary };
    case 'awaiting_customer':
      return { bg: colors.warningLight, text: colors.warning };
    case 'resolved':
      return { bg: colors.surface, text: colors.textSecondary };
    case 'closed':
      return { bg: colors.surface, text: colors.textMuted };
    default:
      return { bg: colors.surface, text: colors.textSecondary };
  }
}

function SupportTicketListItem({ item, onPress }) {
  const badge = statusStyle(item.status);

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.press}>
      <Card style={styles.card} padding={spacing.lg} elevated>
        <View style={styles.row}>
          <View style={styles.iconWrap}>
            <LifeBuoy size={20} color={colors.baytgo} strokeWidth={2} />
          </View>

          <View style={styles.body}>
            <Text style={styles.subject} numberOfLines={1}>{item.subject}</Text>
            <View style={styles.metaRow}>
              <Text style={styles.meta}>{item.category_label}</Text>
              <Text style={styles.dot}>·</Text>
              <Text style={styles.meta}>{item.priority_label}</Text>
            </View>
            <Text style={styles.time}>
              {formatTime(item.last_activity_at || item.created_at)}
            </Text>
          </View>

          <View style={[styles.badge, { backgroundColor: badge.bg }]}>
            <Text style={[styles.badgeText, { color: badge.text }]} numberOfLines={1}>
              {item.status_label}
            </Text>
          </View>

          <ChevronRight size={18} color={colors.textMuted} strokeWidth={2} />
        </View>
      </Card>
    </PressableScale>
  );
}

export default memo(SupportTicketListItem);

const styles = StyleSheet.create({
  press: { marginBottom: spacing.lg },
  card: { borderRadius: radius.md, minHeight: layout.minTouch },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  iconWrap: {
    width: 48,
    height: 48,
    borderRadius: radius.sm,
    backgroundColor: colors.successLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: { flex: 1 },
  subject: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: spacing.xs,
    gap: spacing.xs,
  },
  meta: {
    ...typography.label,
    color: colors.textSecondary,
  },
  dot: {
    ...typography.label,
    color: colors.textMuted,
  },
  time: {
    ...typography.label,
    color: colors.textMuted,
    marginTop: spacing.xs,
  },
  badge: {
    borderRadius: radius.full,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    maxWidth: 90,
  },
  badgeText: {
    ...typography.label,
    fontSize: 10,
    textAlign: 'center',
  },
});
