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
import { useLocale } from '../utils/locale';

export default function EmergencyOffersScreen() {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
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
      setError(err.message || (isEn ? 'Failed to load emergency offers' : 'Gagal memuat penawaran darurat'));
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
    Alert.alert(isEn ? 'Accept offer?' : 'Terima penawaran?', isEn ? 'You will be assigned to replace the muthowif on this booking.' : 'Anda akan ditugaskan menggantikan muthowif pada booking ini.', [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Accept' : 'Terima',
        onPress: async () => {
          setBusyId(offer.id);
          try {
            await acceptEmergencyOffer(token, offer.id);
            notifySuccess(isEn ? 'Emergency offer accepted.' : 'Penawaran darurat diterima.');
            await load(true);
          } catch (err) {
            notifyError(err.message || (isEn ? 'Cannot accept offer' : 'Tidak dapat menerima penawaran'));
          } finally {
            setBusyId(null);
          }
        },
      },
    ]);
  }, [load, token]);

  const handleDecline = useCallback((offer) => {
    Alert.alert(isEn ? 'Decline offer?' : 'Tolak penawaran?', isEn ? 'This offer will be skipped.' : 'Penawaran ini akan dilewati.', [
      { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
      {
        text: isEn ? 'Decline' : 'Tolak',
        style: 'destructive',
        onPress: async () => {
          setBusyId(offer.id);
          try {
            await declineEmergencyOffer(token, offer.id);
            notifySuccess(isEn ? 'Emergency offer declined.' : 'Penawaran darurat ditolak.');
            await load(true);
          } catch (err) {
            notifyError(err.message || (isEn ? 'Cannot decline offer' : 'Tidak dapat menolak penawaran'));
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
      <Text style={styles.alertText}>{pendingCount} {isEn ? 'offers waiting for your response' : 'penawaran menunggu respons Anda'}</Text>
    </View>
  ) : null;

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader
          title={isEn ? 'Emergency offers' : 'Penawaran darurat'}
          subtitle={isEn ? 'Emergency muthowif replacement' : 'Ganti muthowif darurat'}
        />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader
          title={isEn ? 'Emergency offers' : 'Penawaran darurat'}
          subtitle={pendingCount > 0 ? (isEn ? `${pendingCount} awaiting response` : `${pendingCount} menunggu respons`) : (isEn ? 'Emergency muthowif replacement' : 'Ganti muthowif darurat')}
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
                title={isEn ? 'No emergency offers yet' : 'Belum ada penawaran darurat'}
                description={isEn ? 'Muthowif replacement offers will appear here.' : 'Penawaran penggantian muthowif akan muncul di sini.'}
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
