import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { fetchSupportTickets } from '../api/support';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

function formatTime(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch {
    return '';
  }
}

function statusStyle(status) {
  switch (status) {
    case 'open':
      return { bg: colors.emerald50, text: colors.emerald600 };
    case 'in_progress':
      return { bg: '#EFF6FF', text: '#2563EB' };
    case 'awaiting_customer':
      return { bg: '#FEF3C7', text: '#D97706' };
    case 'resolved':
      return { bg: colors.slate100, text: colors.slate600 };
    case 'closed':
      return { bg: colors.slate100, text: colors.slate400 };
    default:
      return { bg: colors.slate100, text: colors.slate500 };
  }
}

function TicketItem({ item, onPress }) {
  const badge = statusStyle(item.status);

  return (
    <TouchableOpacity style={styles.row} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.iconWrap}>
        <Ionicons name="help-buoy-outline" size={20} color={colors.baytgo} />
      </View>
      <View style={styles.body}>
        <Text style={styles.subject} numberOfLines={1}>{item.subject}</Text>
        <View style={styles.metaRow}>
          <Text style={styles.meta}>{item.category_label}</Text>
          <Text style={styles.dot}>·</Text>
          <Text style={styles.meta}>{item.priority_label}</Text>
        </View>
        <Text style={styles.time}>{formatTime(item.last_activity_at || item.created_at)}</Text>
      </View>
      <View style={[styles.badge, { backgroundColor: badge.bg }]}>
        <Text style={[styles.badgeText, { color: badge.text }]} numberOfLines={1}>
          {item.status_label}
        </Text>
      </View>
    </TouchableOpacity>
  );
}

export default function SupportListScreen({ navigation }) {
  const { token } = useAuth();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchSupportTickets(token);
      setItems(data.data || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat tiket bantuan');
      if (!refresh) setItems([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  return (
    <View style={styles.container}>
      <TabPageHeader title="Bantuan" subtitle="Tiket dukungan Anda" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <TicketItem
              item={item}
              onPress={() => navigation.navigate('SupportDetail', { ticketId: item.id })}
            />
          )}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                {error || 'Belum ada tiket bantuan. Buat tiket baru jika Anda membutuhkan bantuan.'}
              </Text>
              {error ? (
                <TouchableOpacity onPress={() => load()}>
                  <Text style={styles.retry}>Coba lagi</Text>
                </TouchableOpacity>
              ) : null}
            </View>
          }
        />
      )}

      <TouchableOpacity
        style={styles.fab}
        onPress={() => navigation.navigate('SupportCreate')}
        activeOpacity={0.9}
      >
        <Ionicons name="add" size={28} color={colors.white} />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  list: { padding: 16, paddingBottom: 96 },
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
  iconWrap: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.emerald50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  body: { flex: 1 },
  subject: { fontSize: 15, fontWeight: '800', color: colors.slate900 },
  metaRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4, gap: 4 },
  meta: { fontSize: 11, fontWeight: '600', color: colors.slate500 },
  dot: { fontSize: 11, color: colors.slate400 },
  time: { marginTop: 4, fontSize: 11, fontWeight: '600', color: colors.slate400 },
  badge: {
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 4,
    maxWidth: 90,
  },
  badgeText: { fontSize: 10, fontWeight: '800', textAlign: 'center' },
  empty: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 24,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    alignItems: 'center',
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center', lineHeight: 20 },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  fab: {
    position: 'absolute',
    right: 20,
    bottom: 24,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 4,
  },
});
