import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  View, Text, StyleSheet, TextInput, ActivityIndicator,
  KeyboardAvoidingView, Platform, Alert,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import { Lock, Image as ImageIcon, Send } from 'lucide-react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import ChatMessageBubble from '../components/ChatMessageBubble';
import ImageLightbox from '../components/ImageLightbox';
import { fetchChatMessages, sendChatMessage } from '../api/chat';
import { useAuth } from '../context/AuthContext';
import { useChatInbox } from '../context/ChatInboxContext';
import { useBookingChatRealtime } from '../hooks/useBookingChatRealtime';
import Card from '../ui/Card';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import MessageList from '../ui/MessageList';
import SingleImagePreview from '../ui/SingleImagePreview';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';

export default function ChatRoomScreen({ navigation, route }) {
  const { token } = useAuth();
  const { setActiveBookingId, clearUnreadForBooking } = useChatInbox();
  const { bookingId, bookingCode, otherName } = route.params;

  const [messages, setMessages] = useState([]);
  const [chatOpen, setChatOpen] = useState(true);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [text, setText] = useState('');
  const [pendingImage, setPendingImage] = useState(null);
  const [error, setError] = useState(null);
  const [liveConnected, setLiveConnected] = useState(false);
  const [lightbox, setLightbox] = useState({ visible: false, images: [], index: 0, auth: true });

  const listRef = useRef(null);
  const pollRef = useRef(null);
  const safetyPollRef = useRef(null);
  const liveRef = useRef(false);
  const lastIdRef = useRef(null);

  const mergeMessages = useCallback((incoming, replace = false) => {
    if (!incoming?.length) return;
    setMessages((prev) => {
      const base = replace ? [] : prev;
      const map = new Map(base.map((m) => [m.id, m]));
      incoming.forEach((m) => map.set(m.id, m));
      const merged = Array.from(map.values()).sort(
        (a, b) => new Date(a.created_at) - new Date(b.created_at),
      );
      lastIdRef.current = merged[merged.length - 1]?.id ?? lastIdRef.current;
      return merged;
    });
  }, []);

  const loadInitial = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchChatMessages(token, bookingId);
      const list = data.messages || [];
      setMessages(list);
      setChatOpen(data.chat_open !== false);
      lastIdRef.current = list[list.length - 1]?.id ?? null;
    } catch (err) {
      setError(err.message || 'Gagal memuat chat');
    } finally {
      setLoading(false);
    }
  }, [token, bookingId]);

  const pollNew = useCallback(async () => {
    try {
      const data = await fetchChatMessages(token, bookingId, lastIdRef.current);
      if (data.messages?.length) mergeMessages(data.messages);
      if (typeof data.chat_open === 'boolean') setChatOpen(data.chat_open);
    } catch {
      // ignore poll errors
    }
  }, [token, bookingId, mergeMessages]);

  useBookingChatRealtime({
    token, bookingId,
    onConnected: () => { liveRef.current = true; setLiveConnected(true); },
    onError: () => { liveRef.current = false; setLiveConnected(false); },
    onEvent: (payload) => {
      const action = payload?.action || 'message';
      if (action === 'read') {
        setMessages((prev) => prev.map((m) => (m.is_me ? { ...m, is_read: true } : m)));
        return;
      }
      pollNew();
    },
  });

  useFocusEffect(
    useCallback(() => {
      setActiveBookingId(bookingId);
      clearUnreadForBooking(bookingId);
      loadInitial();
      pollRef.current = setInterval(() => { if (!liveRef.current) pollNew(); }, 5000);
      safetyPollRef.current = setInterval(() => { if (liveRef.current) pollNew(); }, 60000);
      return () => {
        setActiveBookingId(null);
        clearInterval(pollRef.current);
        clearInterval(safetyPollRef.current);
      };
    }, [bookingId, setActiveBookingId, clearUnreadForBooking, loadInitial, pollNew]),
  );

  useEffect(() => {
    if (messages.length > 0) {
      setTimeout(() => listRef.current?.scrollToEnd({ animated: true }), 100);
    }
  }, [messages.length]);

  const pickImage = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri untuk mengirim gambar.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8 });
    if (!result.canceled && result.assets[0]) setPendingImage(result.assets[0]);
  };

  const handleSend = async () => {
    const body = text.trim();
    if (!chatOpen) {
      Alert.alert('Chat ditutup', 'Percakapan tidak dapat dilanjutkan untuk booking ini.');
      return;
    }
    if (!body && !pendingImage) return;

    setSending(true);
    try {
      const data = await sendChatMessage(token, bookingId, {
        body,
        image: pendingImage ? { uri: pendingImage.uri, name: 'chat.jpg', mimeType: 'image/jpeg' } : null,
      });
      if (data.message) mergeMessages([data.message]);
      if (typeof data.chat_open === 'boolean') setChatOpen(data.chat_open);
      setText('');
      setPendingImage(null);
    } catch (err) {
      Alert.alert('Gagal kirim', err.message || 'Pesan tidak terkirim');
    } finally {
      setSending(false);
    }
  };

  const title = otherName || 'Chat';
  const subtitle = liveConnected ? `${bookingCode || bookingId} · Live` : (bookingCode || bookingId);
  const canSend = chatOpen && !sending && (text.trim().length > 0 || pendingImage);

  const chatImages = useMemo(
    () => messages.filter((m) => m.image_url).map((m) => m.image_url),
    [messages],
  );

  const openLightbox = useCallback((uri, { images = chatImages, auth = true } = {}) => {
    const list = images?.length ? images : [uri];
    const index = Math.max(0, list.indexOf(uri));
    setLightbox({ visible: true, images: list, index, auth });
  }, [chatImages]);

  const closeLightbox = useCallback(() => {
    setLightbox((prev) => ({ ...prev, visible: false }));
  }, []);

  const renderMessage = useCallback(
    ({ item }) => (
      <ChatMessageBubble
        message={item}
        token={token}
        onImagePress={(uri) => openLightbox(uri)}
      />
    ),
    [token, openLightbox],
  );

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
    >
      <ScreenHeader title={title} subtitle={subtitle} onBack={() => navigation.goBack()} />

      {!chatOpen ? (
        <View style={styles.closedBanner}>
          <Lock size={16} color={colors.baytgo} strokeWidth={2} />
          <Text style={styles.closedText}>Chat ditutup untuk booking ini.</Text>
        </View>
      ) : null}

      {loading ? (
        <SkeletonList count={4} style={styles.skeleton} />
      ) : error ? (
        <ErrorState description={error} onRetry={loadInitial} />
      ) : (
        <MessageList
          ref={listRef}
          data={messages}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderMessage}
          estimatedItemSize={88}
          contentContainerStyle={styles.list}
          ListEmptyComponent={(
            <EmptyState
              variant="chat"
              title="Belum ada pesan"
              description={`Mulai percakapan dengan ${otherName || 'lawans bicara'}.`}
            />
          )}
        />
      )}

      <Card style={styles.composer} padding={spacing.md} elevated={false}>
        {pendingImage ? (
          <SingleImagePreview
            uri={pendingImage.uri}
            onRemove={() => setPendingImage(null)}
            onPress={() => openLightbox(pendingImage.uri, { images: [pendingImage.uri], auth: false })}
            size={92}
            style={styles.pendingPreview}
          />
        ) : null}
        {sending ? <Text style={styles.sendingHint}>Mengirim pesan…</Text> : null}
        <View style={styles.inputRow}>
          <PressableScale
            onPress={pickImage}
            disabled={!chatOpen || sending}
            haptic="light"
            style={[styles.attachBtn, (!chatOpen || sending) && styles.attachBtnDisabled]}
          >
            <ImageIcon size={20} color={chatOpen ? colors.baytgo : colors.textMuted} strokeWidth={2} />
          </PressableScale>
          <TextInput
            style={styles.input}
            value={text}
            onChangeText={setText}
            placeholder={chatOpen ? 'Tulis pesan…' : 'Chat ditutup'}
            placeholderTextColor={colors.textMuted}
            editable={chatOpen && !sending}
            multiline
            maxLength={4000}
          />
          <PressableScale
            onPress={handleSend}
            disabled={!canSend}
            haptic="medium"
            style={styles.sendWrap}
          >
            {canSend ? (
              <LinearGradient colors={gradients.primarySoft} style={styles.sendBtn}>
                {sending ? (
                  <ActivityIndicator size="small" color={colors.white} />
                ) : (
                  <Send size={18} color={colors.white} strokeWidth={2} />
                )}
              </LinearGradient>
            ) : (
              <View style={[styles.sendBtn, styles.sendBtnDisabled]}>
                {sending ? (
                  <ActivityIndicator size="small" color={colors.textMuted} />
                ) : (
                  <Send size={18} color={colors.textMuted} strokeWidth={2} />
                )}
              </View>
            )}
          </PressableScale>
        </View>
      </Card>

      <ImageLightbox
        visible={lightbox.visible}
        images={lightbox.images}
        index={lightbox.index}
        token={lightbox.auth ? token : null}
        title={title}
        onClose={closeLightbox}
        onChangeIndex={(next) => setLightbox((prev) => ({
          ...prev,
          index: typeof next === 'function' ? next(prev.index) : next,
        }))}
      />
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  closedBanner: {
    flexDirection: 'row', alignItems: 'center', gap: spacing.sm,
    backgroundColor: colors.successLight, paddingHorizontal: layout.screenPadding, paddingVertical: spacing.md,
  },
  closedText: { ...typography.small, color: colors.baytgo },
  skeleton: { padding: layout.screenPadding, flex: 1 },
  list: { padding: layout.screenPadding, paddingBottom: spacing.sm, flexGrow: 1 },
  composer: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    borderRadius: 0,
    paddingBottom: Platform.OS === 'ios' ? spacing['2xl'] : spacing.md,
  },
  pendingPreview: { marginBottom: spacing.sm },
  sendingHint: { ...typography.small, color: colors.textMuted, marginBottom: spacing.sm, fontWeight: '600' },
  inputRow: { flexDirection: 'row', alignItems: 'flex-end', gap: spacing.sm },
  attachBtn: {
    width: 44, height: 44, borderRadius: radius.sm,
    alignItems: 'center', justifyContent: 'center', backgroundColor: colors.surface,
  },
  attachBtnDisabled: { opacity: 0.5 },
  input: {
    flex: 1, minHeight: 44, maxHeight: 120, borderRadius: radius.sm,
    paddingHorizontal: spacing.lg, paddingVertical: spacing.md,
    backgroundColor: colors.surface, ...typography.caption, color: colors.textPrimary,
  },
  sendWrap: { borderRadius: radius.sm, overflow: 'hidden' },
  sendBtn: {
    width: 44, height: 44, borderRadius: radius.sm,
    alignItems: 'center', justifyContent: 'center',
  },
  sendBtnDisabled: { backgroundColor: colors.surface },
});
