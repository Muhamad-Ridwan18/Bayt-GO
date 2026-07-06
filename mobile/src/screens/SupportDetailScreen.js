import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Alert,
  Image,
  Linking,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import { fetchSupportTicket, replySupportTicket } from '../api/support';
import AttachmentPicker from '../components/AttachmentPicker';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

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

function MessageBubble({ message }) {
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
              <TouchableOpacity
                key={`${att.url}-${index}`}
                style={styles.attachmentChip}
                onPress={() => att.url && Linking.openURL(att.url)}
              >
                {att.is_image ? (
                  <Image source={{ uri: att.url }} style={styles.attachmentImage} />
                ) : (
                  <Text style={styles.attachmentName} numberOfLines={1}>
                    {att.original_name || 'Lampiran'}
                  </Text>
                )}
              </TouchableOpacity>
            ))}
          </View>
        ) : null}
        <Text style={[styles.msgTime, isStaff ? styles.msgTimeStaff : styles.msgTimeMe]}>
          {formatTime(message.created_at)}
        </Text>
      </View>
    </View>
  );
}

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

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

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

  const subtitle = ticket
    ? `${ticket.status_label} · ${ticket.category_label}`
    : '';

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
        <View style={styles.closedBanner}>
          <Ionicons name="lock-closed-outline" size={16} color={colors.baytgo} />
          <Text style={styles.closedText}>Tiket ini tidak dapat dibalas lagi.</Text>
        </View>
      ) : null}

      {loading ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : error ? (
        <View style={styles.errorBox}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={load}>
            <Text style={styles.retry}>Coba lagi</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          ref={listRef}
          data={messages}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => <MessageBubble message={item} />}
          contentContainerStyle={styles.list}
          onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: false })}
          ListHeaderComponent={
            ticket ? (
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
            ) : null
          }
          ListEmptyComponent={
            <Text style={styles.empty}>Belum ada pesan pada tiket ini.</Text>
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
              placeholderTextColor={colors.slate400}
              editable={!sending}
              multiline
              maxLength={12000}
            />
            <TouchableOpacity
              style={[styles.sendBtn, sending && styles.sendBtnDisabled]}
              onPress={handleReply}
              disabled={sending}
            >
              {sending ? (
                <ActivityIndicator size="small" color={colors.white} />
              ) : (
                <Ionicons name="send" size={18} color={colors.white} />
              )}
            </TouchableOpacity>
          </View>
        </View>
      ) : null}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  closedBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.emerald50,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  closedText: { fontSize: 12, fontWeight: '700', color: colors.baytgo },
  loader: { marginTop: 40 },
  errorBox: { padding: 24, alignItems: 'center' },
  errorText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  list: { padding: 16, paddingBottom: 8, flexGrow: 1 },
  infoCard: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    gap: 8,
  },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  infoLabel: { fontSize: 12, fontWeight: '700', color: colors.slate500 },
  infoValue: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  empty: { textAlign: 'center', color: colors.slate500, fontSize: 14, fontWeight: '600', marginTop: 40, lineHeight: 20 },
  msgRow: { marginBottom: 10, flexDirection: 'row' },
  msgRowMe: { justifyContent: 'flex-end' },
  msgRowStaff: { justifyContent: 'flex-start' },
  bubble: { maxWidth: '85%', borderRadius: 18, padding: 12 },
  bubbleMe: { backgroundColor: colors.baytgo, borderBottomRightRadius: 4 },
  bubbleStaff: { backgroundColor: colors.white, borderWidth: 1, borderColor: colors.slate100, borderBottomLeftRadius: 4 },
  author: { fontSize: 11, fontWeight: '800', marginBottom: 6 },
  authorMe: { color: 'rgba(255,255,255,0.85)' },
  authorStaff: { color: colors.baytgo },
  msgBody: { fontSize: 14, lineHeight: 20, fontWeight: '500' },
  msgBodyMe: { color: colors.white },
  msgBodyStaff: { color: colors.slate900 },
  msgTime: { marginTop: 6, fontSize: 10, fontWeight: '600' },
  msgTimeMe: { color: 'rgba(255,255,255,0.75)', textAlign: 'right' },
  msgTimeStaff: { color: colors.slate400 },
  attachments: { marginTop: 8, gap: 8 },
  attachmentChip: { borderRadius: 10, overflow: 'hidden' },
  attachmentImage: { width: 120, height: 90, borderRadius: 10 },
  attachmentName: { fontSize: 11, fontWeight: '700', color: colors.baytgo, maxWidth: 160 },
  composer: {
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
    backgroundColor: colors.white,
    paddingHorizontal: 12,
    paddingTop: 8,
    paddingBottom: Platform.OS === 'ios' ? 24 : 12,
  },
  inputRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 8 },
  input: {
    flex: 1,
    minHeight: 40,
    maxHeight: 120,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: colors.canvas,
    fontSize: 14,
    fontWeight: '500',
    color: colors.slate900,
  },
  sendBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendBtnDisabled: { opacity: 0.5 },
});
