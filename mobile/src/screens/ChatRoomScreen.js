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
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import ChatMessageBubble from '../components/ChatMessageBubble';
import { fetchChatMessages, sendChatMessage } from '../api/chat';
import { useAuth } from '../context/AuthContext';
import { useChatInbox } from '../context/ChatInboxContext';
import { useBookingChatRealtime } from '../hooks/useBookingChatRealtime';
import { colors } from '../theme/colors';

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
    token,
    bookingId,
    onConnected: () => {
      liveRef.current = true;
      setLiveConnected(true);
    },
    onError: () => {
      liveRef.current = false;
      setLiveConnected(false);
    },
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
      pollRef.current = setInterval(() => {
        if (!liveRef.current) pollNew();
      }, 5000);
      safetyPollRef.current = setInterval(() => {
        if (liveRef.current) pollNew();
      }, 60000);
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
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.8,
    });
    if (!result.canceled && result.assets[0]) {
      setPendingImage(result.assets[0]);
    }
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
        image: pendingImage
          ? { uri: pendingImage.uri, name: 'chat.jpg', mimeType: 'image/jpeg' }
          : null,
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
  const subtitle = bookingCode || bookingId;

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
    >
      <ScreenHeader
        title={title}
        subtitle={liveConnected ? `${subtitle} · Live` : subtitle}
        onBack={() => navigation.goBack()}
      />

      {!chatOpen ? (
        <View style={styles.closedBanner}>
          <Ionicons name="lock-closed-outline" size={16} color={colors.baytgo} />
          <Text style={styles.closedText}>Chat ditutup untuk booking ini.</Text>
        </View>
      ) : null}

      {loading ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : error ? (
        <View style={styles.errorBox}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={loadInitial}>
            <Text style={styles.retry}>Coba lagi</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          ref={listRef}
          data={messages}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => <ChatMessageBubble message={item} token={token} />}
          contentContainerStyle={styles.list}
          onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: false })}
          ListEmptyComponent={
            <Text style={styles.empty}>Belum ada pesan. Mulai percakapan dengan {otherName || 'lawans bicara'}.</Text>
          }
        />
      )}

      <View style={styles.composer}>
        {pendingImage ? (
          <View style={styles.pendingRow}>
            <Text style={styles.pendingText}>Gambar siap dikirim</Text>
            <TouchableOpacity onPress={() => setPendingImage(null)}>
              <Text style={styles.pendingClear}>Hapus</Text>
            </TouchableOpacity>
          </View>
        ) : null}
        <View style={styles.inputRow}>
          <TouchableOpacity style={styles.attachBtn} onPress={pickImage} disabled={!chatOpen || sending}>
            <Ionicons name="image-outline" size={22} color={chatOpen ? colors.baytgo : colors.slate400} />
          </TouchableOpacity>
          <TextInput
            style={styles.input}
            value={text}
            onChangeText={setText}
            placeholder={chatOpen ? 'Tulis pesan…' : 'Chat ditutup'}
            placeholderTextColor={colors.slate400}
            editable={chatOpen && !sending}
            multiline
            maxLength={4000}
          />
          <TouchableOpacity
            style={[styles.sendBtn, (!chatOpen || sending) && styles.sendBtnDisabled]}
            onPress={handleSend}
            disabled={!chatOpen || sending}
          >
            {sending ? (
              <ActivityIndicator size="small" color={colors.white} />
            ) : (
              <Ionicons name="send" size={18} color={colors.white} />
            )}
          </TouchableOpacity>
        </View>
      </View>
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
  empty: { textAlign: 'center', color: colors.slate500, fontSize: 14, fontWeight: '600', marginTop: 40, lineHeight: 20 },
  composer: {
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
    backgroundColor: colors.white,
    paddingHorizontal: 12,
    paddingTop: 8,
    paddingBottom: Platform.OS === 'ios' ? 24 : 12,
  },
  pendingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
    paddingHorizontal: 4,
  },
  pendingText: { fontSize: 12, fontWeight: '600', color: colors.slate600 },
  pendingClear: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  inputRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 8 },
  attachBtn: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.canvas,
  },
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
