import React, { memo } from 'react';
import { Linking, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { PressableScale } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';

export function formatTime(iso) {
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

export const MessageBubble = memo(function MessageBubble({ message }) {
  const isStaff = message.is_staff;
  const attachments = message.attachments || [];

  return (
    <View style={[styles.msgRow, isStaff ? styles.msgRowStaff : styles.msgRowMe]}>
      <View style={[styles.bubble, isStaff ? styles.bubbleStaff : styles.bubbleMe]}>
        <Text style={[styles.author, isStaff ? styles.authorStaff : styles.authorMe]}>
          {isStaff ? message.author_name || 'Tim Bayt-GO' : 'Anda'}
        </Text>
        <Text style={[styles.msgBody, isStaff ? styles.msgBodyStaff : styles.msgBodyMe]}>
          {message.body}
        </Text>
        {attachments.length > 0 ? (
          <View style={styles.attachments}>
            {attachments.map((att, index) => (
              <PressableScale
                key={`${att.url}-${index}`}
                onPress={() => att.url && Linking.openURL(att.url)}
                haptic="light"
              >
                {att.is_image ? (
                  <Image source={{ uri: att.url }} style={styles.attachmentImage} contentFit="cover" transition={200} />
                ) : (
                  <Text style={styles.attachmentName} numberOfLines={1}>
                    {att.original_name || 'Lampiran'}
                  </Text>
                )}
              </PressableScale>
            ))}
          </View>
        ) : null}
        <Text style={[styles.msgTime, isStaff ? styles.msgTimeStaff : styles.msgTimeMe]}>
          {formatTime(message.created_at)}
        </Text>
      </View>
    </View>
  );
});

export function TicketInfoCard({ ticket }) {
  if (!ticket) return null;
  return (
    <View style={styles.infoCard}>
      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Prioritas</Text>
        <Text style={styles.infoValue}>{ticket.priority_label}</Text>
      </View>
      <View style={styles.infoRow}>
        <Text style={styles.infoLabel}>Status</Text>
        <Text style={styles.infoValue}>{ticket.status_label}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  infoCard: {
    backgroundColor: colors.card,
    borderRadius: radius.md,
    padding: spacing.lg,
    marginBottom: spacing.lg,
    borderWidth: 1,
    borderColor: colors.border,
    gap: spacing.sm,
  },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  infoLabel: { ...typography.small, color: colors.textSecondary, fontWeight: '600' },
  infoValue: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  msgRow: { marginBottom: spacing.md, flexDirection: 'row' },
  msgRowMe: { justifyContent: 'flex-end' },
  msgRowStaff: { justifyContent: 'flex-start' },
  bubble: { maxWidth: '85%', borderRadius: radius.md, padding: spacing.md },
  bubbleMe: { backgroundColor: colors.baytgo, borderBottomRightRadius: 4 },
  bubbleStaff: { backgroundColor: colors.card, borderWidth: 1, borderColor: colors.border, borderBottomLeftRadius: 4 },
  author: { ...typography.small, fontSize: 11, marginBottom: 6 },
  authorMe: { color: 'rgba(255,255,255,0.85)' },
  authorStaff: { color: colors.baytgo },
  msgBody: { ...typography.caption, lineHeight: 20, fontWeight: '500' },
  msgBodyMe: { color: colors.white },
  msgBodyStaff: { color: colors.textPrimary },
  msgTime: { marginTop: 6, fontSize: 10, fontWeight: '600' },
  msgTimeMe: { color: 'rgba(255,255,255,0.75)', textAlign: 'right' },
  msgTimeStaff: { color: colors.textMuted },
  attachments: { marginTop: spacing.sm, gap: spacing.sm },
  attachmentImage: { width: 120, height: 90, borderRadius: radius.sm - 2 },
  attachmentName: { ...typography.small, fontSize: 11, color: colors.baytgo, maxWidth: 160 },
});
