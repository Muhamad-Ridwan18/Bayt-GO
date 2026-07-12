import React, { useCallback, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useFocusEffect } from '@react-navigation/native';
import { AlertTriangle } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import EmergencyOfferListItem from '../components/EmergencyOfferListItem';
import {
  fetchEmergencyOffers,
  acceptEmergencyOffer,
  declineEmergencyOffer,
} from '../api/emergencyOffers';
import { useAuth } from '../context/AuthContext';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import { SkeletonList } from '../ui/Skeleton';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess } from '../utils/feedback';

export default function EmergencyOffersScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [offers, setOffers] = useState([]);
  const [busyId, setBusyId] = useState(null);
  const [error, setError] = useState(null);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchEmergencyOffers(token);
      setOffers(data.data || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat penawaran darurat');
      if (!refresh) setOffers([]);
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

  const handleAccept = useCallback((offer) => {
    Alert.alert('Terima penawaran?', 'Anda akan ditugaskan menggantikan muthowif pada booking ini.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Terima',
        onPress: async () => {
          setBusyId(offer.id);
          try {
            await acceptEmergencyOffer(token, offer.id);
            notifySuccess('Penawaran darurat diterima.');
            await load(true);
          } catch (err) {
            notifyError(err.message || 'Tidak dapat menerima penawaran');
          } finally {
            setBusyId(null);
          }
        },
      },
    ]);
  }, [load, token]);

  const handleDecline = useCallback((offer) => {
    Alert.alert('Tolak penawaran?', 'Penawaran ini akan dilewati.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Tolak',
        style: 'destructive',
        onPress: async () => {
          setBusyId(offer.id);
          try {
            await declineEmergencyOffer(token, offer.id);
            notifySuccess('Penawaran darurat ditolak.');
            await load(true);
          } catch (err) {
            notifyError(err.message || 'Tidak dapat menolak penawaran');
          } finally {
            setBusyId(null);
          }
        },
      },
    ]);
  }, [load, token]);

  const renderItem = useCallback(({ item }) => (
    <EmergencyOfferListItem
      offer={item}
      onAccept={handleAccept}
      onDecline={handleDecline}
      busy={busyId === item.id}
    />
  ), [busyId, handleAccept, handleDecline]);

  const pendingCount = offers.filter((o) => o.status === 'offered').length;

  const listHeader = pendingCount > 0 ? (
    <View style={styles.alert}>
      <AlertTriangle size={18} color={colors.warning} strokeWidth={2} />
      <Text style={styles.alertText}>{pendingCount} penawaran menunggu respons Anda</Text>
    </View>
  ) : null;

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader
          title="Penawaran darurat"
          subtitle="Ganti muthowif darurat"
        />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader
        title="Penawaran darurat"
        subtitle={pendingCount > 0 ? `${pendingCount} menunggu respons` : 'Ganti muthowif darurat'}
      />

      {error && offers.length === 0 ? (
        <ErrorState description={error} onRetry={() => load()} />
      ) : (
        <FlashList
          data={offers}
          keyExtractor={(item) => String(item.id)}
          renderItem={renderItem}
          estimatedItemSize={180}
          ListHeaderComponent={listHeader}
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
                title="Belum ada penawaran darurat"
                description="Penawaran penggantian muthowif akan muncul di sini."
              />
            )
          }
        />
      )}
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
    paddingBottom: spacing.lg,
  },
  alert: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.warningLight,
    borderRadius: 20,
    padding: spacing.lg,
    marginBottom: spacing.lg,
    borderWidth: 1,
    borderColor: `${colors.warning}30`,
  },
  alertText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.warning,
    flex: 1,
  },
});
