import React, { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { Check, CheckCheck, Expand } from 'lucide-react-native';
import PressableScale from '../ui/PressableScale';
import { buildChatImageSource } from '../utils/chatImageSource';
import { colors, radius, spacing, typography } from '../theme/tokens';

function formatTime(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

function ChatImage({ uri, token, imageOnly }) {
  const source = buildChatImageSource(uri, token);
  if (!source) return null;

  return (
    <View style={styles.imageWrap}>
      <Image
        source={source}
        style={[styles.image, imageOnly && styles.imageOnly]}
        contentFit="cover"
        transition={200}
        cachePolicy="memory-disk"
      />
      <View style={styles.expandHint}>
        <Expand size={12} color={colors.white} strokeWidth={2.2} />
      </View>
    </View>
  );
}

export default memo(function ChatMessageBubble({ message, token, onImagePress }) {
  const isMe = message.is_me;
  const ReadIcon = message.is_read ? CheckCheck : Check;
  const imageOnly = Boolean(message.image_url && !message.body?.trim());

  return (
    <View style={[styles.row, isMe ? styles.rowMe : styles.rowOther]}>
      <View
        style={[
          styles.bubble,
          isMe ? styles.bubbleMe : styles.bubbleOther,
          imageOnly && styles.bubbleImageOnly,
          imageOnly && isMe && styles.bubbleImageOnlyMe,
        ]}
      >
        {!isMe && !imageOnly ? <Text style={styles.sender}>{message.sender_name}</Text> : null}

        {message.image_url ? (
          <PressableScale onPress={() => onImagePress?.(message.image_url)} haptic="light" scaleTo={0.98}>
            <ChatImage uri={message.image_url} token={token} imageOnly={imageOnly} />
          </PressableScale>
        ) : null}

        {message.body ? (
          <Text style={[styles.body, isMe ? styles.bodyMe : styles.bodyOther]}>{message.body}</Text>
        ) : null}

        {!imageOnly ? (
          <View style={styles.meta}>
            <Text style={[styles.time, isMe ? styles.timeMe : styles.timeOther]}>
              {formatTime(message.created_at)}
            </Text>
            {isMe ? (
              <ReadIcon
                size={12}
                color={message.is_read ? '#A7F3D0' : 'rgba(255,255,255,0.6)'}
                strokeWidth={2.5}
              />
            ) : null}
          </View>
        ) : (
          <View style={[styles.imageMeta, isMe ? styles.imageMetaMe : styles.imageMetaOther]}>
            <Text style={[styles.imageMetaTime, !isMe && styles.imageMetaTimeOther]}>
              {formatTime(message.created_at)}
            </Text>
            {isMe ? (
              <ReadIcon
                size={11}
                color={message.is_read ? '#A7F3D0' : 'rgba(255,255,255,0.85)'}
                strokeWidth={2.5}
              />
            ) : null}
          </View>
        )}
      </View>
    </View>
  );
});

const styles = StyleSheet.create({
  row: { marginBottom: spacing.md, flexDirection: 'row' },
  rowMe: { justifyContent: 'flex-end' },
  rowOther: { justifyContent: 'flex-start' },
  bubble: {
    maxWidth: '82%',
    borderRadius: radius.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  bubbleImageOnly: {
    paddingHorizontal: spacing.xs,
    paddingVertical: spacing.xs,
    overflow: 'hidden',
  },
  bubbleImageOnlyMe: {
    backgroundColor: 'transparent',
    shadowOpacity: 0,
    elevation: 0,
  },
  bubbleMe: {
    backgroundColor: colors.baytgo,
    borderBottomRightRadius: spacing.xs,
    shadowColor: colors.baytgo,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 3,
  },
  bubbleOther: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    borderBottomLeftRadius: spacing.xs,
  },
  sender: {
    ...typography.label,
    color: colors.baytgo,
    marginBottom: spacing.sm,
  },
  body: { ...typography.caption, lineHeight: 20 },
  bodyMe: { color: colors.white },
  bodyOther: { color: colors.slate800 },
  imageWrap: { position: 'relative' },
  image: {
    width: 228,
    height: 168,
    borderRadius: radius.sm,
    marginBottom: spacing.sm,
    backgroundColor: colors.surface,
  },
  imageOnly: {
    width: 248,
    height: 188,
    marginBottom: 0,
    borderRadius: radius.md,
  },
  expandHint: {
    position: 'absolute',
    top: spacing.sm,
    right: spacing.sm,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: 'rgba(15,23,42,0.45)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  meta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  time: { ...typography.label, fontSize: 10 },
  timeMe: { color: 'rgba(255,255,255,0.7)' },
  timeOther: { color: colors.textMuted },
  imageMeta: {
    position: 'absolute',
    right: spacing.sm,
    bottom: spacing.sm,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    borderRadius: radius.full,
  },
  imageMetaMe: { backgroundColor: 'rgba(15,23,42,0.42)' },
  imageMetaOther: { backgroundColor: 'rgba(255,255,255,0.82)' },
  imageMetaTime: { ...typography.label, fontSize: 10, color: colors.white },
  imageMetaTimeOther: { color: colors.slate700 },
});
