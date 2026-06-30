import React, { useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Switch,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as DocumentPicker from 'expo-document-picker';
import ScreenHeader from '../components/ScreenHeader';
import DocumentPickerField from '../components/DocumentPickerField';
import { createBooking } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

async function pickDocument() {
  const result = await DocumentPicker.getDocumentAsync({
    type: ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'],
    copyToCacheDirectory: true,
  });
  if (result.canceled || !result.assets?.[0]) return null;
  const asset = result.assets[0];
  return { uri: asset.uri, name: asset.name, mimeType: asset.mimeType };
}

function ServiceOption({ label, active, price, onPress }) {
  return (
    <TouchableOpacity style={[styles.serviceOption, active && styles.serviceOptionActive]} onPress={onPress}>
      <Text style={[styles.serviceOptionText, active && styles.serviceOptionTextActive]}>{label}</Text>
      <Text style={styles.serviceOptionPrice}>{formatIdr(price)} / hari</Text>
    </TouchableOpacity>
  );
}

export default function BookingFormScreen({ navigation, route }) {
  const { token } = useAuth();
  const { profileId, profileName, startDate, endDate, services = [] } = route.params;

  const groupService = useMemo(() => services.find((s) => s.type === 'group'), [services]);
  const privateService = useMemo(() => services.find((s) => s.type === 'private'), [services]);

  const defaultType = groupService ? 'group' : 'private';
  const [step, setStep] = useState(1);
  const [serviceType, setServiceType] = useState(defaultType);
  const [pilgrimCount, setPilgrimCount] = useState('1');
  const [selectedAddOns, setSelectedAddOns] = useState([]);
  const [withSameHotel, setWithSameHotel] = useState(false);
  const [withTransport, setWithTransport] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [ticketOutbound, setTicketOutbound] = useState(null);
  const [ticketReturn, setTicketReturn] = useState(null);
  const [passport, setPassport] = useState(null);
  const [itinerary, setItinerary] = useState(null);
  const [visa, setVisa] = useState(null);

  const activeService = serviceType === 'private' ? privateService : groupService;
  const maxPax = activeService?.max_pax || 50;
  const minPax = 1;
  const canHotel = (activeService?.same_hotel_price_per_day || 0) > 0;
  const canTransport = (activeService?.transport_price_flat || 0) > 0;
  const addOns = serviceType === 'private' ? (privateService?.add_ons || []) : [];

  useEffect(() => {
    const unsubscribe = navigation.addListener('beforeRemove', (event) => {
      if (step === 1) return;
      event.preventDefault();
      setStep(1);
      setError('');
    });
    return unsubscribe;
  }, [navigation, step]);

  const toggleAddOn = (id) => {
    setSelectedAddOns((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
    );
  };

  const validateStep1 = () => {
    if (!activeService) return 'Layanan tidak tersedia.';
    const count = parseInt(pilgrimCount, 10);
    if (!count || count < minPax || count > maxPax) {
      return `Jumlah jamaah harus ${minPax}–${maxPax}.`;
    }
    return null;
  };

  const validateStep2 = () => {
    if (!ticketOutbound || !ticketReturn || !passport) {
      return 'Tiket berangkat, tiket pulang, dan paspor wajib diupload.';
    }
    if (serviceType === 'group' && !itinerary) {
      return 'Itinerary wajib untuk layanan grup.';
    }
    return null;
  };

  const handleNext = () => {
    const err = validateStep1();
    if (err) {
      setError(err);
      return;
    }
    setError('');
    setStep(2);
  };

  const handleSubmit = async () => {
    const err = validateStep2();
    if (err) {
      setError(err);
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await createBooking(token, {
        profileId,
        startDate,
        endDate: endDate || startDate,
        serviceType,
        pilgrimCount: parseInt(pilgrimCount, 10),
        withSameHotel: canHotel && withSameHotel,
        withTransport: canTransport && withTransport,
        addOnIds: selectedAddOns,
        ticketOutbound,
        ticketReturn,
        passport,
        itinerary: serviceType === 'group' ? itinerary : null,
        visa,
      });

      navigation.replace('BookingPayment', {
        bookingId: data.booking_id,
        bookingCode: data.booking_code,
      });
    } catch (err) {
      setError(err.message || 'Gagal membuat pemesanan');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Pesan Muthowif" onBack={() => (step === 2 ? setStep(1) : navigation.goBack())} />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Text style={styles.profileName}>{profileName}</Text>
        <Text style={styles.dates}>
          {startDate}{endDate && endDate !== startDate ? ` — ${endDate}` : ''}
        </Text>

        <View style={styles.stepRow}>
          <Text style={[styles.stepBadge, step === 1 && styles.stepBadgeActive]}>1 Layanan</Text>
          <Text style={[styles.stepBadge, step === 2 && styles.stepBadgeActive]}>2 Dokumen</Text>
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        {step === 1 ? (
          <>
            <Text style={styles.sectionTitle}>Tipe layanan</Text>
            <View style={styles.serviceRow}>
              {groupService ? (
                <ServiceOption
                  label="Grup"
                  price={groupService.price}
                  active={serviceType === 'group'}
                  onPress={() => setServiceType('group')}
                />
              ) : null}
              {privateService ? (
                <ServiceOption
                  label="Privat"
                  price={privateService.price}
                  active={serviceType === 'private'}
                  onPress={() => setServiceType('private')}
                />
              ) : null}
            </View>

            <Text style={styles.sectionTitle}>Jumlah jamaah</Text>
            <View style={styles.counterRow}>
              <TouchableOpacity
                style={styles.counterBtn}
                onPress={() => setPilgrimCount(String(Math.max(minPax, parseInt(pilgrimCount, 10) - 1 || minPax)))}
              >
                <Text style={styles.counterBtnText}>−</Text>
              </TouchableOpacity>
              <Text style={styles.counterValue}>{pilgrimCount}</Text>
              <TouchableOpacity
                style={styles.counterBtn}
                onPress={() => setPilgrimCount(String(Math.min(maxPax, (parseInt(pilgrimCount, 10) || minPax) + 1)))}
              >
                <Text style={styles.counterBtnText}>+</Text>
              </TouchableOpacity>
            </View>
            <Text style={styles.hint}>Min {minPax}, max {maxPax} jamaah</Text>

            {canHotel ? (
              <View style={styles.switchRow}>
                <Text style={styles.switchLabel}>Hotel sama (+{formatIdr(activeService.same_hotel_price_per_day)}/hari)</Text>
                <Switch value={withSameHotel} onValueChange={setWithSameHotel} trackColor={{ true: colors.baytgo }} />
              </View>
            ) : null}

            {canTransport ? (
              <View style={styles.switchRow}>
                <Text style={styles.switchLabel}>Transport (+{formatIdr(activeService.transport_price_flat)})</Text>
                <Switch value={withTransport} onValueChange={setWithTransport} trackColor={{ true: colors.baytgo }} />
              </View>
            ) : null}

            {addOns.length > 0 ? (
              <>
                <Text style={styles.sectionTitle}>Add-on</Text>
                {addOns.map((addon) => (
                  <TouchableOpacity
                    key={addon.id}
                    style={[styles.addOnRow, selectedAddOns.includes(addon.id) && styles.addOnRowActive]}
                    onPress={() => toggleAddOn(addon.id)}
                  >
                    <Text style={styles.addOnName}>{addon.name}</Text>
                    <Text style={styles.addOnPrice}>{formatIdr(addon.price)}</Text>
                  </TouchableOpacity>
                ))}
              </>
            ) : null}

            <TouchableOpacity style={styles.primaryBtn} onPress={handleNext} activeOpacity={0.9}>
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
                <Text style={styles.primaryText}>Lanjut ke dokumen</Text>
              </LinearGradient>
            </TouchableOpacity>
          </>
        ) : (
          <>
            <DocumentPickerField
              label="Tiket berangkat"
              required
              file={ticketOutbound}
              onPick={async () => setTicketOutbound(await pickDocument())}
              onClear={() => setTicketOutbound(null)}
            />
            <DocumentPickerField
              label="Tiket pulang"
              required
              file={ticketReturn}
              onPick={async () => setTicketReturn(await pickDocument())}
              onClear={() => setTicketReturn(null)}
            />
            <DocumentPickerField
              label="Paspor"
              required
              file={passport}
              onPick={async () => setPassport(await pickDocument())}
              onClear={() => setPassport(null)}
            />
            {serviceType === 'group' ? (
              <DocumentPickerField
                label="Itinerary"
                required
                file={itinerary}
                onPick={async () => setItinerary(await pickDocument())}
                onClear={() => setItinerary(null)}
              />
            ) : null}
            <DocumentPickerField
              label="Visa"
              file={visa}
              onPick={async () => setVisa(await pickDocument())}
              onClear={() => setVisa(null)}
            />

            <TouchableOpacity
              style={styles.primaryBtn}
              onPress={handleSubmit}
              disabled={loading}
              activeOpacity={0.9}
            >
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
                {loading ? (
                  <ActivityIndicator color={colors.white} />
                ) : (
                  <Text style={styles.primaryText}>Buat pemesanan</Text>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  profileName: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  dates: { marginTop: 4, fontSize: 13, fontWeight: '600', color: colors.slate500 },
  stepRow: { flexDirection: 'row', gap: 8, marginTop: 16, marginBottom: 12 },
  stepBadge: {
    fontSize: 11,
    fontWeight: '800',
    color: colors.slate400,
    backgroundColor: colors.white,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    overflow: 'hidden',
  },
  stepBadgeActive: { color: colors.baytgo, backgroundColor: colors.emerald50 },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 12,
  },
  sectionTitle: { fontSize: 15, fontWeight: '900', color: colors.baytgo, marginTop: 16, marginBottom: 10 },
  serviceRow: { flexDirection: 'row', gap: 10 },
  serviceOption: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  serviceOptionActive: { borderColor: colors.baytgo, backgroundColor: colors.emerald50 },
  serviceOptionText: { fontSize: 14, fontWeight: '800', color: colors.slate700 },
  serviceOptionTextActive: { color: colors.baytgo },
  serviceOptionPrice: { marginTop: 4, fontSize: 12, fontWeight: '700', color: colors.slate500 },
  counterRow: { flexDirection: 'row', alignItems: 'center', gap: 16 },
  counterBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  counterBtnText: { fontSize: 22, fontWeight: '700', color: colors.baytgo },
  counterValue: { fontSize: 24, fontWeight: '900', color: colors.slate900, minWidth: 40, textAlign: 'center' },
  hint: { marginTop: 8, fontSize: 12, color: colors.slate500, fontWeight: '600' },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginTop: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  switchLabel: { flex: 1, fontSize: 13, fontWeight: '700', color: colors.slate700, paddingRight: 12 },
  addOnRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  addOnRowActive: { borderColor: colors.baytgo, backgroundColor: colors.emerald50 },
  addOnName: { fontSize: 13, fontWeight: '700', color: colors.slate800 },
  addOnPrice: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  primaryBtn: { marginTop: 24, borderRadius: 16, overflow: 'hidden' },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
