import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import TabPageHeader from '../components/TabPageHeader';
import {
  fetchEmergencyOffers,
  acceptEmergencyOffer,
  declineEmergencyOffer,
} from '../api/emergencyOffers';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

const STATUS_LABELS = {
  offered: 'Menunggu respons',
  accepted: 'Diterima',
};

function formatDateRange(startsOn, endsOn) {
  if (!startsOn) return '—';
  if (!endsOn || endsOn === startsOn) return startsOn;
  return `${startsOn} – ${endsOn}`;
}

function OfferCard({ offer, onAccept, onDecline, busy }) {
  const pending = offer.status === 'offered';

  return (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.bookingCode}>{offer.booking_code || 'Booking'}</Text>
        <View style={[styles.badge, pending ? styles.badgePending : styles.badgeAccepted]}>
          <Text style={[styles.badgeText, pending ? styles.badgeTextPending : styles.badgeTextAccepted]}>
            {STATUS_LABELS[offer.status] || offer.status}
          </Text>
        </View>
      </View>

      <Text style={styles.customer}>{offer.customer_name || 'Jamaah'}</Text>
      <Text style={styles.dates}>{formatDateRange(offer.starts_on, offer.ends_on)}</Text>
      {offer.original_muthowif ? (
        <Text style={styles.original}>Menggantikan: {offer.original_muthowif}</Text>
      ) : null}

      {pending ? (
        <View style={styles.actions}>
          <TouchableOpacity
            style={[styles.declineBtn, busy && styles.btnDisabled]}
            onPress={() => onDecline(offer)}
            disabled={busy}
          >
            <Text style={styles.declineText}>Tolak</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.acceptBtn, busy && styles.btnDisabled]}
            onPress={() => onAccept(offer)}
            disabled={busy}
          >
            <Text style={styles.acceptText}>Terima</Text>
          </TouchableOpacity>
        </View>
      ) : null}
    </View>
  );
}

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

  const handleAccept = (offer) => {
    Alert.alert('Terima penawaran?', 'Anda akan ditugaskan menggantikan muthowif pada booking ini.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Terima',
        onPress: async () => {
          setBusyId(offer.id);
          try {
            await acceptEmergencyOffer(token, offer.id);
            Alert.alert('Berhasil', 'Penawaran darurat diterima.');
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menerima penawaran');
          } finally {
            setBusyId(null);
          }
        },
      },
    ]);
  };

  const handleDecline = (offer) => {
    Alert.alert('Tolak penawaran?', 'Penawaran ini akan dilewati.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Tolak',
        style: 'destructive',
        onPress: async () => {
          setBusyId(offer.id);
          try {
            await declineEmergencyOffer(token, offer.id);
            Alert.alert('Berhasil', 'Penawaran darurat ditolak.');
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menolak penawaran');
          } finally {
            setBusyId(null);
          }
        },
      },
    ]);
  };

  const pendingCount = offers.filter((o) => o.status === 'offered').length;

  return (
    <View style={styles.container}>
      <TabPageHeader
        title="Penawaran darurat"
        subtitle={pendingCount > 0 ? `${pendingCount} menunggu respons` : 'Ganti muthowif darurat'}
      />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <FlatList
          data={offers}
          keyExtractor={(item) => String(item.id)}
          renderItem={({ item }) => (
            <OfferCard
              offer={item}
              onAccept={handleAccept}
              onDecline={handleDecline}
              busy={busyId === item.id}
            />
          )}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyText}>{error || 'Belum ada penawaran darurat.'}</Text>
              {error ? (
                <TouchableOpacity onPress={() => load()}>
                  <Text style={styles.retry}>Coba lagi</Text>
                </TouchableOpacity>
              ) : null}
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  list: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 8,
  },
  bookingCode: { fontSize: 14, fontWeight: '900', color: colors.baytgo },
  badge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 4 },
  badgePending: { backgroundColor: '#FEF3C7' },
  badgeAccepted: { backgroundColor: colors.emerald50 },
  badgeText: { fontSize: 10, fontWeight: '800' },
  badgeTextPending: { color: '#B45309' },
  badgeTextAccepted: { color: colors.emerald600 },
  customer: { marginTop: 10, fontSize: 15, fontWeight: '800', color: colors.slate900 },
  dates: { marginTop: 4, fontSize: 12, fontWeight: '700', color: colors.slate600 },
  original: { marginTop: 6, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  actions: { flexDirection: 'row', gap: 10, marginTop: 14 },
  declineBtn: {
    flex: 1,
    borderRadius: 12,
    paddingVertical: 12,
    alignItems: 'center',
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  declineText: { fontSize: 13, fontWeight: '800', color: '#B91C1C' },
  acceptBtn: {
    flex: 1,
    borderRadius: 12,
    paddingVertical: 12,
    alignItems: 'center',
    backgroundColor: colors.baytgo,
  },
  acceptText: { fontSize: 13, fontWeight: '800', color: colors.white },
  btnDisabled: { opacity: 0.6 },
  empty: { paddingTop: 48, alignItems: 'center' },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retry: { marginTop: 12, fontSize: 14, fontWeight: '800', color: colors.baytgo },
});
