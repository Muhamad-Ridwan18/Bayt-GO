import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  TextInput,
  FlatList,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Alert,
  Image,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { apiClient } from '../api/client';
import { connectPusher, subscribeToBookingChat, disconnectPusher } from '../services/echoService';

const { width } = Dimensions.get('window');

export default function ChatScreen({ user, route, navigation }) {
  const { bookingId, bookingCode, partnerName } = route?.params ?? {};

  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [chatOpen, setChatOpen] = useState(true);
  const [connected, setConnected] = useState(false);
  const [body, setBody] = useState('');
  const [imageUri, setImageUri] = useState(null);

  const flatListRef = useRef(null);

  // ─── Load initial messages ────────────────────────────────────────────────
  const loadMessages = useCallback(async () => {
    try {
      const data = await apiClient.getChatMessages(user.token, bookingId);
      setMessages(data.messages ?? []);
      setChatOpen(data.chat_open ?? false);
    } catch {
      Alert.alert('Error', 'Gagal memuat pesan chat.');
    } finally {
      setLoading(false);
    }
  }, [bookingId, user.token]);

  // ─── Setup Reverb WebSocket ───────────────────────────────────────────────
  useEffect(() => {
    loadMessages();

    const pusher = connectPusher(user.token);

    // Track connection state
    pusher.connection.bind('connected',    () => setConnected(true));
    pusher.connection.bind('disconnected', () => setConnected(false));
    pusher.connection.bind('error',        () => setConnected(false));

    const subscription = subscribeToBookingChat(user.token, bookingId, () => {
      // Reload semua pesan ketika ada event baru dari Reverb
      apiClient.getChatMessages(user.token, bookingId).then((data) => {
        setMessages(data.messages ?? []);
        setChatOpen(data.chat_open ?? false);
      }).catch(() => {});
    });

    return () => {
      subscription.unsubscribe();
      disconnectPusher();
    };
  }, [bookingId, user.token]);

  // ─── Auto-scroll on new message ──────────────────────────────────────────
  useEffect(() => {
    if (messages.length > 0) {
      setTimeout(() => flatListRef.current?.scrollToEnd({ animated: true }), 100);
    }
  }, [messages.length]);

  // ─── Send message ─────────────────────────────────────────────────────────
  const handleSend = async () => {
    if (!body.trim() && !imageUri) return;
    setSending(true);
    const sentBody = body;
    const sentImage = imageUri;
    setBody('');
    setImageUri(null);

    try {
      const data = await apiClient.sendChatMessage(user.token, bookingId, sentBody, sentImage);
      setMessages((prev) => [...prev, data.message]);
      setChatOpen(data.chat_open ?? true);
    } catch (err) {
      Alert.alert('Gagal', err.message);
    } finally {
      setSending(false);
    }
  };

  // ─── Image Picker ─────────────────────────────────────────────────────────
  const pickImage = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.7,
      allowsEditing: false,
    });
    if (!result.canceled) setImageUri(result.assets[0].uri);
  };

  // ─── Helpers ──────────────────────────────────────────────────────────────
  const formatTime = (iso) => {
    const d = new Date(iso);
    return `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
  };

  const formatDateSeparator = (iso) => {
    const d = new Date(iso);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return 'Hari ini';
    if (d.toDateString() === yesterday.toDateString()) return 'Kemarin';
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  };

  const buildRenderList = () => {
    const result = [];
    let lastDate = null;
    messages.forEach((msg) => {
      const date = msg.created_at ? new Date(msg.created_at).toDateString() : null;
      if (date && date !== lastDate) {
        result.push({ type: 'separator', id: `sep-${date}`, label: formatDateSeparator(msg.created_at) });
        lastDate = date;
      }
      result.push({ ...msg, type: 'message' });
    });
    return result;
  };

  // ─── Render ──────────────────────────────────────────────────────────────
  const renderItem = ({ item }) => {
    if (item.type === 'separator') {
      return (
        <View style={styles.separator}>
          <View style={styles.separatorLine} />
          <Text style={styles.separatorLabel}>{item.label}</Text>
          <View style={styles.separatorLine} />
        </View>
      );
    }

    const isMe = item.is_me;
    return (
      <View style={[styles.messageRow, isMe ? styles.messageRowMe : styles.messageRowOther]}>
        {!isMe && (
          <View style={styles.avatarSmall}>
            <Text style={styles.avatarSmallText}>{item.sender_name?.charAt(0) ?? '?'}</Text>
          </View>
        )}
        <View style={[styles.bubble, isMe ? styles.bubbleMe : styles.bubbleOther]}>
          {!isMe && <Text style={styles.senderName}>{item.sender_name}</Text>}
          {item.image_url ? (
            <Image 
              source={{ 
                uri: item.image_url,
                headers: {
                  Authorization: `Bearer ${user.token}`
                }
              }} 
              style={styles.chatImage} 
              resizeMode="cover" 
            />
          ) : null}
          {item.body ? (
            <Text style={[styles.messageText, isMe ? styles.messageTextMe : styles.messageTextOther]}>
              {item.body}
            </Text>
          ) : null}
          <View style={[styles.metaRow, isMe && { justifyContent: 'flex-end' }]}>
            <Text style={[styles.timeText, isMe && { color: 'rgba(255,255,255,0.65)' }]}>
              {item.created_at ? formatTime(item.created_at) : ''}
            </Text>
            {isMe && (
              <Ionicons
                name={item.is_read ? 'checkmark-done' : 'checkmark'}
                size={12}
                color={item.is_read ? '#93C5FD' : 'rgba(255,255,255,0.55)'}
                style={{ marginLeft: 4 }}
              />
            )}
          </View>
        </View>
      </View>
    );
  };

  const renderList = buildRenderList();

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />

      {/* ── Header ── */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation?.goBack()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1E293B" />
        </TouchableOpacity>
        <View style={styles.headerInfo}>
          <View style={styles.headerAvatar}>
            <Text style={styles.headerAvatarText}>{partnerName?.charAt(0) ?? '?'}</Text>
          </View>
          <View>
            <Text style={styles.headerName} numberOfLines={1}>{partnerName ?? 'Chat'}</Text>
            <View style={styles.statusRow}>
              <View style={[styles.statusDot, { backgroundColor: connected ? '#10B981' : '#94A3B8' }]} />
              <Text style={styles.headerSub}>
                {connected ? 'Terhubung' : 'Menghubungkan...'}
              </Text>
            </View>
          </View>
        </View>
        <Text style={styles.bookingCodeBadge}>{bookingCode}</Text>
      </View>

      {/* ── Chat closed banner ── */}
      {!chatOpen && (
        <View style={styles.closedBanner}>
          <Ionicons name="lock-closed-outline" size={14} color="#92400E" />
          <Text style={styles.closedBannerText}>Chat ditutup. Hanya dapat dibaca.</Text>
        </View>
      )}

      {/* ── Messages ── */}
      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#0984e3" />
        </View>
      ) : (
        <FlatList
          ref={flatListRef}
          data={renderList}
          keyExtractor={(item) => item.id?.toString() ?? item.label}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          ListEmptyComponent={
            <View style={styles.emptyState}>
              <Ionicons name="chatbubbles-outline" size={56} color="#E2E8F0" />
              <Text style={styles.emptyText}>Belum ada pesan. Mulai percakapan!</Text>
            </View>
          }
        />
      )}

      {/* ── Input area ── */}
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={10}
      >
        {imageUri && (
          <View style={styles.imagePreview}>
            <Image source={{ uri: imageUri }} style={styles.previewThumb} resizeMode="cover" />
            <TouchableOpacity style={styles.removeImageBtn} onPress={() => setImageUri(null)}>
              <Ionicons name="close-circle" size={20} color="#EF4444" />
            </TouchableOpacity>
          </View>
        )}
        <View style={[styles.inputBar, !chatOpen && styles.inputBarDisabled]}>
          <TouchableOpacity style={styles.iconBtn} onPress={pickImage} disabled={!chatOpen || sending}>
            <Ionicons name="image-outline" size={22} color={chatOpen ? '#64748B' : '#CBD5E1'} />
          </TouchableOpacity>
          <TextInput
            style={styles.input}
            placeholder={chatOpen ? 'Ketik pesan...' : 'Chat ditutup'}
            placeholderTextColor="#94A3B8"
            value={body}
            onChangeText={setBody}
            multiline
            editable={chatOpen && !sending}
            maxLength={4000}
          />
          <TouchableOpacity
            style={[styles.sendBtn, (!body.trim() && !imageUri) && styles.sendBtnDisabled]}
            onPress={handleSend}
            disabled={!chatOpen || sending || (!body.trim() && !imageUri)}
          >
            {sending ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <Ionicons name="send" size={18} color="#FFF" />
            )}
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
    gap: 10,
  },
  backBtn: { padding: 4 },
  headerInfo: { flexDirection: 'row', alignItems: 'center', flex: 1, gap: 10 },
  headerAvatar: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: '#0984e3',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerAvatarText: { color: '#FFF', fontWeight: '800', fontSize: 15 },
  headerName: { fontSize: 15, fontWeight: '800', color: '#1E293B', maxWidth: width * 0.4 },
  statusRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 2 },
  statusDot: { width: 7, height: 7, borderRadius: 4 },
  headerSub: { fontSize: 10, color: '#94A3B8', fontWeight: '600' },
  bookingCodeBadge: { fontSize: 10, color: '#94A3B8', fontWeight: '700', letterSpacing: 0.5, flexShrink: 0 },

  closedBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#FDE68A',
  },
  closedBannerText: { fontSize: 12, color: '#92400E', fontWeight: '600' },

  listContent: { paddingHorizontal: 14, paddingVertical: 16 },

  separator: { flexDirection: 'row', alignItems: 'center', marginVertical: 14, gap: 8 },
  separatorLine: { flex: 1, height: 1, backgroundColor: '#E2E8F0' },
  separatorLabel: { fontSize: 11, color: '#94A3B8', fontWeight: '700' },

  messageRow: { flexDirection: 'row', marginBottom: 10, alignItems: 'flex-end' },
  messageRowMe: { justifyContent: 'flex-end' },
  messageRowOther: { justifyContent: 'flex-start' },

  avatarSmall: {
    width: 28,
    height: 28,
    borderRadius: 9,
    backgroundColor: '#E2E8F0',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 8,
    flexShrink: 0,
  },
  avatarSmallText: { fontSize: 11, fontWeight: '800', color: '#64748B' },

  bubble: {
    maxWidth: width * 0.72,
    borderRadius: 18,
    paddingHorizontal: 13,
    paddingVertical: 9,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 1,
  },
  bubbleMe: { backgroundColor: '#0984e3', borderBottomRightRadius: 4 },
  bubbleOther: {
    backgroundColor: '#FFFFFF',
    borderBottomLeftRadius: 4,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  senderName: { fontSize: 10, fontWeight: '800', color: '#0984e3', marginBottom: 3 },
  messageText: { fontSize: 14, lineHeight: 20 },
  messageTextMe: { color: '#FFFFFF' },
  messageTextOther: { color: '#1E293B' },
  metaRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4 },
  timeText: { fontSize: 9, color: '#94A3B8', fontWeight: '600' },
  chatImage: { width: width * 0.55, height: width * 0.45, borderRadius: 10, marginBottom: 4 },

  emptyState: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingTop: 100, gap: 12 },
  emptyText: { color: '#94A3B8', fontSize: 13, fontWeight: '600' },

  inputBar: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    gap: 10,
  },
  inputBarDisabled: { backgroundColor: '#F8FAFC' },
  iconBtn: { padding: 6, justifyContent: 'center' },
  input: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    borderRadius: 20,
    paddingHorizontal: 14,
    paddingVertical: Platform.OS === 'ios' ? 10 : 8,
    fontSize: 14,
    color: '#1E293B',
    maxHeight: 110,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  sendBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: '#0984e3',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 5,
    elevation: 4,
  },
  sendBtnDisabled: { backgroundColor: '#CBD5E1', shadowOpacity: 0 },

  imagePreview: {
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 4,
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    gap: 8,
  },
  previewThumb: { width: 56, height: 56, borderRadius: 10 },
  removeImageBtn: { padding: 4 },
});
