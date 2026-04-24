import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  FlatList,
  ActivityIndicator,
  RefreshControl,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Ionicons } from '@expo/vector-icons';
import { apiClient } from '../api/client';

const { width } = Dimensions.get('window');

export default function ChatListScreen({ user, navigation }) {
  const [conversations, setConversations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchConversations = useCallback(async () => {
    try {
      const data = await apiClient.getChatConversations(user.token);
      setConversations(data.conversations ?? []);
    } catch (err) {
      console.error('Fetch conversations error:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [user.token]);

  useEffect(() => {
    fetchConversations();
  }, [fetchConversations]);

  const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();
    if (isToday) {
      return `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
    }
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
  };

  const getInitial = (name = '') => name.charAt(0).toUpperCase() || '?';

  const renderItem = ({ item }) => {
    const hasUnread = item.unread_count > 0;
    return (
      <TouchableOpacity
        style={styles.convItem}
        onPress={() => navigation.navigate('Chat', {
          bookingId: item.booking_id,
          bookingCode: item.booking_code,
          partnerName: item.other_name,
        })}
        activeOpacity={0.7}
      >
        {/* Avatar */}
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{getInitial(item.other_name)}</Text>
          {item.is_open && <View style={styles.onlineDot} />}
        </View>

        {/* Content */}
        <View style={styles.convContent}>
          <View style={styles.convTop}>
            <Text style={[styles.convName, hasUnread && styles.convNameUnread]} numberOfLines={1}>
              {item.other_name}
            </Text>
            <Text style={[styles.convTime, hasUnread && styles.convTimeUnread]}>
              {formatTime(item.last_message_time)}
            </Text>
          </View>
          <View style={styles.convBottom}>
            <Text
              style={[styles.convLastMsg, hasUnread && styles.convLastMsgUnread]}
              numberOfLines={1}
            >
              {item.last_message}
            </Text>
            {hasUnread ? (
              <View style={styles.unreadBadge}>
                <Text style={styles.unreadBadgeText}>
                  {item.unread_count > 99 ? '99+' : item.unread_count}
                </Text>
              </View>
            ) : null}
          </View>
          <Text style={styles.bookingCode}>{item.booking_code}</Text>
        </View>
      </TouchableOpacity>
    );
  };

  const totalUnread = conversations.reduce((s, c) => s + (c.unread_count ?? 0), 0);

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />

      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Pesan</Text>
          {totalUnread > 0 && (
            <Text style={styles.headerSub}>{totalUnread} pesan belum dibaca</Text>
          )}
        </View>
        <View style={styles.headerIcon}>
          <Ionicons name="chatbubbles" size={24} color="#0984e3" />
        </View>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#0984e3" />
        </View>
      ) : (
        <FlatList
          data={conversations}
          keyExtractor={(item) => item.booking_id?.toString()}
          renderItem={renderItem}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchConversations(); }} color="#0984e3" />
          }
          contentContainerStyle={conversations.length === 0 ? styles.emptyContainer : styles.listContent}
          ItemSeparatorComponent={() => <View style={styles.separator} />}
          ListEmptyComponent={
            <View style={styles.emptyState}>
              <Ionicons name="chatbubbles-outline" size={64} color="#E2E8F0" />
              <Text style={styles.emptyTitle}>Belum ada percakapan</Text>
              <Text style={styles.emptyText}>
                Chat akan muncul setelah ada booking yang dikonfirmasi.
              </Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFFFFF' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },

  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
    backgroundColor: '#FFFFFF',
  },
  headerTitle: { fontSize: 26, fontWeight: '900', color: '#1E293B' },
  headerSub: { fontSize: 12, color: '#0984e3', fontWeight: '700', marginTop: 2 },
  headerIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
  },

  listContent: { paddingVertical: 8 },
  separator: { height: 1, backgroundColor: '#F8FAFC', marginLeft: 84 },

  convItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 14,
    backgroundColor: '#FFFFFF',
  },
  avatar: {
    width: 52,
    height: 52,
    borderRadius: 18,
    backgroundColor: '#0984e3',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
    position: 'relative',
    flexShrink: 0,
  },
  avatarText: { color: '#FFFFFF', fontSize: 20, fontWeight: '800' },
  onlineDot: {
    position: 'absolute',
    bottom: 2,
    right: 2,
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#10B981',
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },

  convContent: { flex: 1, minWidth: 0 },
  convTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 3,
  },
  convName: { fontSize: 15, fontWeight: '700', color: '#1E293B', flex: 1, marginRight: 8 },
  convNameUnread: { fontWeight: '900', color: '#0F172A' },
  convTime: { fontSize: 11, color: '#94A3B8', fontWeight: '600', flexShrink: 0 },
  convTimeUnread: { color: '#0984e3', fontWeight: '800' },

  convBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 2,
  },
  convLastMsg: { fontSize: 13, color: '#94A3B8', fontWeight: '500', flex: 1, marginRight: 8 },
  convLastMsgUnread: { color: '#475569', fontWeight: '700' },

  bookingCode: { fontSize: 10, color: '#CBD5E1', fontWeight: '700', letterSpacing: 0.5 },

  unreadBadge: {
    backgroundColor: '#0984e3',
    borderRadius: 10,
    minWidth: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 5,
    flexShrink: 0,
  },
  unreadBadgeText: { color: '#FFFFFF', fontSize: 10, fontWeight: '800' },

  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  emptyState: { alignItems: 'center', gap: 12, paddingHorizontal: 40 },
  emptyTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  emptyText: { fontSize: 13, color: '#94A3B8', textAlign: 'center', lineHeight: 20 },
});
