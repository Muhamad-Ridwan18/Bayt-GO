import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { useChatInbox } from '../context/ChatInboxContext';
import { colors } from '../theme/colors';

function formatTime(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

function ConversationItem({ item, onPress }) {
  return (
    <TouchableOpacity style={styles.row} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.avatar}>
        <Ionicons name="chatbubbles-outline" size={20} color={colors.baytgo} />
      </View>
      <View style={styles.body}>
        <View style={styles.topRow}>
          <Text style={styles.name} numberOfLines={1}>{item.other_name}</Text>
          <Text style={styles.time}>{formatTime(item.last_message_time)}</Text>
        </View>
        <Text style={styles.code}>{item.booking_code}</Text>
        <Text style={[styles.preview, item.unread_count > 0 && styles.previewUnread]} numberOfLines={1}>
          {item.last_message}
        </Text>
      </View>
      {item.unread_count > 0 ? (
        <View style={styles.unread}>
          <Text style={styles.unreadText}>{item.unread_count > 9 ? '9+' : item.unread_count}</Text>
        </View>
      ) : null}
    </TouchableOpacity>
  );
}

export default function ChatListScreen({ navigation }) {
  const {
    conversations,
    loading,
    refreshing,
    error,
    refresh,
    pullToRefresh,
  } = useChatInbox();

  useFocusEffect(
    React.useCallback(() => {
      refresh(true);
    }, [refresh]),
  );

  const openConversation = (item) => {
    navigation.navigate('ChatRoom', {
      bookingId: item.booking_id,
      bookingCode: item.booking_code,
      otherName: item.other_name,
    });
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Chat" subtitle="Percakapan booking Anda" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <FlatList
          data={conversations}
          keyExtractor={(item) => String(item.booking_id)}
          renderItem={({ item }) => (
            <ConversationItem item={item} onPress={() => openConversation(item)} />
          )}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={pullToRefresh} tintColor={colors.baytgo} />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                {error || 'Belum ada percakapan. Chat tersedia setelah booking dikonfirmasi.'}
              </Text>
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  list: { padding: 16, paddingBottom: 24 },
  loader: { marginTop: 40 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.emerald50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: { flex: 1 },
  topRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 8 },
  name: { flex: 1, fontSize: 15, fontWeight: '800', color: colors.slate900 },
  time: { fontSize: 11, fontWeight: '600', color: colors.slate400 },
  code: { marginTop: 2, fontSize: 11, fontWeight: '700', color: colors.baytgo },
  preview: { marginTop: 4, fontSize: 13, fontWeight: '500', color: colors.slate500 },
  previewUnread: { fontWeight: '700', color: colors.slate800 },
  unread: {
    minWidth: 22,
    height: 22,
    borderRadius: 999,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
  },
  unreadText: { fontSize: 11, fontWeight: '800', color: colors.white },
  empty: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 24,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center', lineHeight: 20 },
});
