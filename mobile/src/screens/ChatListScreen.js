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
import SwipeableScreen from '../components/SwipeableScreen';
import { Skeleton, SkeletonText } from '../components/Skeleton';

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

  // Skeleton saat loading
  const ConvSkeleton = () => (
    <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#F8FAFC' }}>
      <Skeleton width={52} height={52} borderRadius={18} style={{ marginRight: 14, flexShrink: 0 }} />
      <View style={{ flex: 1 }}>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 }}>
          <SkeletonText width="45%" height={14} style={{ marginBottom: 0 }} />
          <Skeleton width={35} height={11} borderRadius={6} />
        </View>
        <SkeletonText width="70%" height={12} style={{ marginBottom: 0 }} />
      </View>
    </View>
  );

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />

      {/* Header Premium */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Pesan</Text>
          {totalUnread > 0 ? (
            <View style={styles.unreadPill}>
              <View style={styles.unreadDot} />
              <Text style={styles.headerSub}>{totalUnread} pesan baru</Text>
            </View>
          ) : (
            <Text style={styles.headerSubNormal}>Percakapan dengan muthowif</Text>
          )}
        </View>
        <View style={styles.headerIconWrap}>
          <Ionicons name="chatbubbles" size={26} color="#0984e3" />
        </View>
      </View>

      {loading ? (
        <View>
          {[1,2,3,4,5,6].map(i => <ConvSkeleton key={i} />)}
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
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },

  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingTop: 10,
    paddingBottom: 20,
    backgroundColor: '#F8FAFC',
  },
  headerTitle: { fontSize: 28, fontWeight: '800', color: '#0F172A', letterSpacing: -0.5 },
  headerSubNormal: { fontSize: 13, color: '#64748B', marginTop: 4, fontWeight: '500' },
  unreadPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#EFF6FF',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    marginTop: 6,
    alignSelf: 'flex-start',
    borderWidth: 1,
    borderColor: '#DBEAFE',
  },
  unreadDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#3B82F6',
    marginRight: 6,
  },
  headerSub: { fontSize: 12, color: '#1D4ED8', fontWeight: '700' },
  headerIconWrap: {
    width: 50,
    height: 50,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 10,
    elevation: 3,
  },

  listContent: { paddingHorizontal: 20, paddingBottom: 40 },
  separator: { height: 12 },

  convItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 2,
  },
  avatar: {
    width: 54,
    height: 54,
    borderRadius: 20,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
    position: 'relative',
    flexShrink: 0,
    borderWidth: 1,
    borderColor: '#E0F2FE',
  },
  avatarText: { color: '#0984e3', fontSize: 20, fontWeight: '800' },
  onlineDot: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    width: 14,
    height: 14,
    borderRadius: 7,
    backgroundColor: '#10B981',
    borderWidth: 2.5,
    borderColor: '#FFFFFF',
  },

  convContent: { flex: 1, minWidth: 0 },
  convTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  convName: { fontSize: 16, fontWeight: '700', color: '#1E293B', flex: 1, marginRight: 8, letterSpacing: -0.3 },
  convNameUnread: { fontWeight: '900', color: '#0F172A' },
  convTime: { fontSize: 11, color: '#94A3B8', fontWeight: '600', flexShrink: 0 },
  convTimeUnread: { color: '#0984e3', fontWeight: '800' },

  convBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  convLastMsg: { fontSize: 13, color: '#64748B', fontWeight: '500', flex: 1, marginRight: 8, lineHeight: 18 },
  convLastMsgUnread: { color: '#334155', fontWeight: '700' },

  bookingCode: { fontSize: 10, color: '#94A3B8', fontWeight: '700', letterSpacing: 0.5 },

  unreadBadge: {
    backgroundColor: '#EF4444',
    borderRadius: 12,
    minWidth: 24,
    height: 24,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 6,
    flexShrink: 0,
    shadowColor: '#EF4444',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 2,
  },
  unreadBadgeText: { color: '#FFFFFF', fontSize: 11, fontWeight: '800' },

  emptyContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  emptyState: { alignItems: 'center', gap: 14, paddingHorizontal: 40, marginTop: -50 },
  emptyTitle: { fontSize: 18, fontWeight: '800', color: '#0F172A', letterSpacing: -0.5 },
  emptyText: { fontSize: 14, color: '#64748B', textAlign: 'center', lineHeight: 22 },
});
