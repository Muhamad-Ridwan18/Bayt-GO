import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View, Text, StyleSheet, TextInput, Alert,
  KeyboardAvoidingView, Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Lock, Send } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchSupportTicket, replySupportTicket } from '../api/support';
import AttachmentPicker from '../components/AttachmentPicker';
import { useAuth } from '../context/AuthContext';
import { Card, EmptyState, ErrorState, PressableScale, SkeletonList } from '../ui';
import MessageList from '../ui/MessageList';
import { MessageBubble, TicketInfoCard } from '../features/support/SupportDetailParts';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function SupportDetailScreen({ navigation, route }) {
  const { token } = useAuth();
  const { ticketId } = route.params;

  const [ticket, setTicket] = useState(null);
  const [messages, setMessages] = useState([]);
  const [canReply, setCanReply] = useState(false);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [text, setText] = useState('');
  const [replyAttachments, setReplyAttachments] = useState([]);
  const [error, setError] = useState(null);

  const listRef = useRef(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchSupportTicket(token, ticketId);
      setTicket(data.ticket);
      setMessages(data.ticket?.messages || []);
      setCanReply(data.can_reply === true);
    } catch (err) {
      setError(err.message || 'Gagal memuat tiket');
    } finally {
      setLoading(false);
    }
  }, [token, ticketId]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  useEffect(() => {
    if (messages.length > 0) {
      setTimeout(() => listRef.current?.scrollToEnd({ animated: true }), 100);
    }
  }, [messages.length]);

  const handleReply = async () => {
    const body = text.trim();
    if (!body) return;

    setSending(true);
    try {
      await replySupportTicket(token, ticketId, { body, attachments: replyAttachments });
      setText('');
      setReplyAttachments([]);
      await load();
    } catch (err) {
      Alert.alert('Gagal kirim', err.message || 'Balasan tidak terkirim');
    } finally {
      setSending(false);
    }
  };

  const subtitle = ticket ? `${ticket.status_label} · ${ticket.category_label}` : '';

  const renderMessage = useCallback(
    ({ item }) => <MessageBubble message={item} />,
    [],
  );

  const listHeader = useCallback(
    () => <TicketInfoCard ticket={ticket} />,
    [ticket],
  );

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
    >
      <ScreenHeader
        title={ticket?.subject || 'Detail Tiket'}
        subtitle={subtitle}
        onBack={() => navigation.goBack()}
      />

      {!canReply && ticket ? (
        <Card style={styles.closedBanner} padding={spacing.md} elevated={false}>
          <Lock size={16} color={colors.baytgo} strokeWidth={2} />
          <Text style={styles.closedText}>Tiket ini tidak dapat dibalas lagi.</Text>
        </Card>
      ) : null}

      {loading ? (
        <SkeletonList count={3} style={styles.skeleton} />
      ) : error ? (
        <ErrorState description={error} onRetry={load} />
      ) : (
        <MessageList
          ref={listRef}
          data={messages}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderMessage}
          estimatedItemSize={96}
          contentContainerStyle={styles.list}
          ListHeaderComponent={listHeader}
          ListEmptyComponent={
            <EmptyState variant="chat" title="Belum ada pesan" description="Belum ada pesan pada tiket ini." />
          }
        />
      )}

      {canReply ? (
        <View style={styles.composer}>
          <AttachmentPicker
            files={replyAttachments}
            onChange={setReplyAttachments}
            disabled={sending}
            hint="Lampiran balasan (opsional)"
          />
          <View style={styles.inputRow}>
            <TextInput
              style={styles.input}
              value={text}
              onChangeText={setText}
              placeholder="Tulis balasan…"
              placeholderTextColor={colors.textMuted}
              editable={!sending}
              multiline
              maxLength={12000}
            />
            <PressableScale
              onPress={handleReply}
              disabled={sending}
              haptic="medium"
              style={[styles.sendBtn, sending && styles.sendBtnDisabled]}
            >
              <Send size={18} color={colors.white} strokeWidth={2} />
            </PressableScale>
          </View>
        </View>
      ) : null}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  closedBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.successLight,
    borderRadius: 0,
    borderWidth: 0,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  closedText: { ...typography.small, color: colors.baytgo, fontWeight: '700' },
  skeleton: { padding: layout.screenPadding, paddingTop: spacing.lg },
  list: { padding: layout.screenPadding, paddingBottom: spacing.sm, flexGrow: 1 },
  composer: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
    paddingBottom: Platform.OS === 'ios' ? spacing['2xl'] : spacing.md,
  },
  inputRow: { flexDirection: 'row', alignItems: 'flex-end', gap: spacing.sm },
  input: {
    flex: 1,
    minHeight: 40,
    maxHeight: 120,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    backgroundColor: colors.background,
    ...typography.caption,
    color: colors.textPrimary,
  },
  sendBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendBtnDisabled: { opacity: 0.5 },
});
