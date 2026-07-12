import React, { useCallback, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import { Plus } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import SupportTicketListItem from '../components/SupportTicketListItem';
import { fetchSupportTickets } from '../api/support';
import { useAuth } from '../context/AuthContext';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import { colors, gradients, layout, radius, shadows, spacing } from '../theme/tokens';

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

  const renderItem = useCallback(({ item }) => (
    <SupportTicketListItem
      item={item}
      onPress={() => navigation.navigate('SupportDetail', { ticketId: item.id })}
    />
  ), [navigation]);

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader title="Bantuan" subtitle="Tiket dukungan Anda" />
        <SkeletonList count={4} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader title="Bantuan" subtitle="Tiket dukungan Anda" />

      {error && items.length === 0 ? (
        <ErrorState description={error} onRetry={() => load()} />
      ) : (
        <FlashList
          data={items}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          estimatedItemSize={100}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshing={refreshing}
          onRefresh={() => load(true)}
          ListEmptyComponent={
            error ? (
              <ErrorState description={error} onRetry={() => load()} />
            ) : (
              <EmptyState
                variant="default"
                title="Belum ada tiket bantuan"
                description="Buat tiket baru jika Anda membutuhkan bantuan."
                actionLabel="Buat tiket"
                onAction={() => navigation.navigate('SupportCreate')}
              />
            )
          }
        />
      )}

      <PressableScale
        onPress={() => navigation.navigate('SupportCreate')}
        haptic="medium"
        style={styles.fabWrap}
      >
        <LinearGradient colors={gradients.primarySoft} style={styles.fab}>
          <Plus size={28} color={colors.white} strokeWidth={2} />
        </LinearGradient>
      </PressableScale>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  skeleton: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
  },
  list: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing['4xl'],
  },
  fabWrap: {
    position: 'absolute',
    right: layout.screenPadding,
    bottom: spacing['2xl'],
    ...shadows.float,
  },
  fab: {
    width: 56,
    height: 56,
    borderRadius: radius.full,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
