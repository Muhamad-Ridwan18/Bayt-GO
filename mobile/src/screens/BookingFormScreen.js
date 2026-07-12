import React, { useEffect, useMemo, useState } from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import * as DocumentPicker from 'expo-document-picker';
import ScreenHeader from '../components/ScreenHeader';
import DocumentPickerField from '../components/DocumentPickerField';
import { createBooking } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { Button, Card } from '../ui';
import {
  AddOnToggle, BookingAddonChoices, BookingEstimateCard, FormError, PilgrimCounter, SectionTitle, ServiceOption, StepBadges,
} from '../features/booking/BookingFormParts';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { estimateBookingPricing } from '../utils/bookingEstimate';
import { navigateToSuccess } from '../navigation/rootNavigation';

async function pickDocument() {
  const result = await DocumentPicker.getDocumentAsync({
    type: ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'],
    copyToCacheDirectory: true,
  });
  if (result.canceled || !result.assets?.[0]) return null;
  const asset = result.assets[0];
  return { uri: asset.uri, name: asset.name, mimeType: asset.mimeType };
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
  const minPax = activeService?.min_pilgrims ?? 1;
  const maxPax = activeService?.max_pilgrims ?? 50;
  const canHotel = (activeService?.same_hotel_price_per_day || 0) > 0;
  const canTransport = (activeService?.transport_price_flat || 0) > 0;
  const addOns = serviceType === 'private' ? (privateService?.add_ons || []) : [];

  useEffect(() => {
    setSelectedAddOns([]);
    setWithSameHotel(false);
    setWithTransport(false);
  }, [serviceType]);

  const priceEstimate = useMemo(() => estimateBookingPricing({
    service: activeService,
    startDate,
    endDate: endDate || startDate,
    withSameHotel: canHotel && withSameHotel,
    withTransport: canTransport && withTransport,
    selectedAddOnIds: selectedAddOns,
    addOns,
  }), [
    activeService,
    startDate,
    endDate,
    canHotel,
    withSameHotel,
    canTransport,
    withTransport,
    selectedAddOns,
    addOns,
  ]);

  useEffect(() => {
    const unsubscribe = navigation.addListener('beforeRemove', (event) => {
      if (step === 1) return;
      event.preventDefault();
      setStep(1);
      setError('');
    });
    return unsubscribe;
  }, [navigation, step]);

  const toggleAddOn = (id, enabled) => {
    setSelectedAddOns((prev) => {
      if (enabled) return prev.includes(id) ? prev : [...prev, id];
      return prev.filter((x) => x !== id);
    });
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
    if (err) { setError(err); return; }
    setError('');
    setStep(2);
  };

  const handleSubmit = async () => {
    const err = validateStep2();
    if (err) { setError(err); return; }

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

      navigateToSuccess(navigation, {
        title: 'Pemesanan berhasil',
        description: `Kode pesanan ${data.booking_code}. Permintaan Anda telah dikirim ke muthowif untuk ditinjau. Anda bisa melihat statusnya di halaman Pesanan.`,
        primaryLabel: 'Lihat pesanan',
        primaryTarget: {
          root: true,
          name: 'Main',
          params: {
            screen: 'BookingsTab',
            params: { screen: 'BookingsList' },
          },
        },
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
        <Card padding={spacing.lg} elevated={false} style={styles.headerCard}>
          <Text style={styles.profileName}>{profileName}</Text>
          <Text style={styles.dates}>
            {startDate}{endDate && endDate !== startDate ? ` — ${endDate}` : ''}
          </Text>
        </Card>

        <StepBadges step={step} />
        <FormError message={error} />

        {step === 1 ? (
          <>
            <SectionTitle>Tipe layanan</SectionTitle>
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

            <SectionTitle>Jumlah jamaah</SectionTitle>
            <PilgrimCounter value={pilgrimCount} minPax={minPax} maxPax={maxPax} onChange={setPilgrimCount} />

            <BookingAddonChoices
              canHotel={canHotel}
              canTransport={canTransport}
              hotelPricePerDay={activeService?.same_hotel_price_per_day || 0}
              transportPriceFlat={activeService?.transport_price_flat || 0}
              withSameHotel={withSameHotel}
              withTransport={withTransport}
              onHotelChange={setWithSameHotel}
              onTransportChange={setWithTransport}
            />

            {addOns.length > 0 ? (
              <>
                <SectionTitle>Add-on</SectionTitle>
                {addOns.map((addon) => (
                  <AddOnToggle
                    key={addon.id}
                    addon={addon}
                    value={selectedAddOns.includes(addon.id)}
                    onValueChange={(enabled) => toggleAddOn(addon.id, enabled)}
                  />
                ))}
              </>
            ) : null}

            <BookingEstimateCard estimate={priceEstimate} />

            <Button label="Lanjut ke dokumen" onPress={handleNext} style={styles.cta} />
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

            <Button label="Buat pemesanan" onPress={handleSubmit} loading={loading} style={styles.cta} />
          </>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  headerCard: { marginBottom: spacing.sm },
  profileName: { ...typography.subtitle, color: colors.baytgo },
  dates: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  serviceRow: { flexDirection: 'row', gap: spacing.md },
  cta: { marginTop: spacing['2xl'] },
});
