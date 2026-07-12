import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { ChevronRight, MessageCircle } from 'lucide-react-native';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

function formatTime(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '';
  }
}

function ConversationListItem({ item, onPress }) {
  const hasUnread = item.unread_count > 0;

  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.press}>
      <Card style={styles.card} padding={spacing.lg} elevated>
        <View style={styles.row}>
          <View style={[styles.avatar, hasUnread && styles.avatarUnread]}>
            <MessageCircle size={22} color={colors.baytgo} strokeWidth={2} />
            {hasUnread ? <View style={styles.unreadDot} /> : null}
          </View>

          <View style={styles.body}>
            <View style={styles.topRow}>
              <Text style={styles.name} numberOfLines={1}>{item.other_name}</Text>
              <Text style={styles.time}>{formatTime(item.last_message_time)}</Text>
            </View>
            <Text style={styles.code}>{item.booking_code}</Text>
            <Text
              style={[styles.preview, hasUnread && styles.previewUnread]}
              numberOfLines={1}
            >
              {item.last_message}
            </Text>
          </View>

          {hasUnread ? (
            <View style={styles.unread}>
              <Text style={styles.unreadText}>
                {item.unread_count > 9 ? '9+' : item.unread_count}
              </Text>
            </View>
          ) : (
            <ChevronRight size={20} color={colors.textMuted} strokeWidth={2} />
          )}
        </View>
      </Card>
    </PressableScale>
  );
}

export default memo(ConversationListItem);

const styles = StyleSheet.create({
  press: { marginBottom: spacing.lg },
  card: { borderRadius: radius.md, minHeight: layout.minTouch },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: radius.sm,
    backgroundColor: colors.successLight,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  avatarUnread: { backgroundColor: colors.baytgoLight },
  unreadDot: {
    position: 'absolute',
    top: -2,
    right: -2,
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: colors.baytgo,
    borderWidth: 2,
    borderColor: colors.white,
  },
  body: { flex: 1 },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  name: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
    flex: 1,
  },
  time: {
    ...typography.label,
    color: colors.textMuted,
  },
  code: {
    ...typography.label,
    color: colors.baytgo,
    marginTop: spacing.xs,
  },
  preview: {
    ...typography.caption,
    color: colors.textSecondary,
    marginTop: spacing.xs,
  },
  previewUnread: {
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.slate800,
  },
  unread: {
    minWidth: 24,
    height: 24,
    borderRadius: radius.full,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.sm,
  },
  unreadText: {
    ...typography.label,
    color: colors.white,
    fontSize: 10,
  },
});
