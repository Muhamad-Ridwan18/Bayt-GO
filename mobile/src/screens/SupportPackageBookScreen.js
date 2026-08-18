import React, { useMemo, useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import DatePickerField from '../components/DatePickerField';
import { createSupportBooking } from '../api/supportCatalog';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import { TermsConsentSheet } from '../features/booking/BookingFormParts';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { notifyError, notifySuccess } from '../utils/feedback';
import { clearAffiliateCode, useAffiliateReferralCode } from '../utils/affiliateReferral';
import { useLocale } from '../utils/locale';

export default function SupportPackageBookScreen({ navigation, route }) {
  const locale = useLocale(); const isEn = locale === 'en';
  const { token } = useAuth();
  const { id, startsAt: initialStartsAt, startsAtDate, packagePreview, affiliateCode: routeAffiliateCode } = route.params || {};
  const pkg = packagePreview || {};

  const [startsAt, setStartsAt] = useState(initialStartsAt || startsAtDate || '');
  const [pilgrimCount, setPilgrimCount] = useState(String(pkg.min_pilgrims || 1));
  const [affiliateCode, setAffiliateCode] = useAffiliateReferralCode(routeAffiliateCode);
  const [submitting, setSubmitting] = useState(false);
  const [tcOpen, setTcOpen] = useState(false);
  const [tcAgree, setTcAgree] = useState(false);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const handleSubmit = async (agreed = false) => {
    const count = Number(pilgrimCount);
    const min = Number(pkg.min_pilgrims || 1);
    const max = Number(pkg.max_pilgrims || 500);

    if (!startsAt) {
      Alert.alert(isEn ? 'Validation' : 'Validasi', isEn ? 'Select a service date & time' : 'Pilih tanggal & jam layanan');
      return;
    }
    if (!count || count < min || count > max) {
      Alert.alert(isEn ? 'Validation' : 'Validasi', isEn ? `Pilgrim count must be ${min}–${max}` : `Jumlah jamaah harus ${min}–${max}`);
      return;
    }

    if (!agreed) {
      setTcAgree(false);
      setTcOpen(true);
      return;
    }

    setTcOpen(false);
    setSubmitting(true);
    try {
      const result = await createSupportBooking(token, {
        support_package_id: id,
        starts_at: startsAt,
        pilgrim_count: count,
        affiliate_code: affiliateCode.trim() || null,
      });
      notifySuccess(result.message || (isEn ? 'Request sent' : 'Permintaan terkirim'));
      await clearAffiliateCode();
      navigation.getParent()?.navigate('BookingsTab', {
        screen: 'BookingDetail',
        params: { bookingId: result.booking_id },
      });
    } catch (err) {
      notifyError(err.message || (isEn ? 'Failed to create booking' : 'Gagal membuat booking'));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.flex}>
      <ScreenHeader title={isEn ? 'Book package' : 'Pesan paket'} subtitle={pkg.name} onBack={() => navigation.goBack()} />
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Card style={styles.card}>
          <Text style={styles.pkgName}>{pkg.name}</Text>
          <Text style={styles.muted}>{pkg.muthowif?.name}</Text>
          <Text style={styles.price}>{formatIdr(pkg.price)}</Text>
        </Card>

        <Card style={styles.card}>
          <DatePickerField
            label={isEn ? 'Start date & time' : 'Tanggal & jam mulai'}
            mode="datetime"
            value={startsAt}
            onChange={setStartsAt}
            placeholder={isEn ? 'Select date & time' : 'Pilih tanggal & jam'}
            minimumDate={today}
          />
          <Text style={styles.hint}>{isEn ? 'Service starts at this time.' : 'Layanan dimulai pada waktu ini.'}</Text>
          <Text style={styles.label}>{isEn ? 'Pilgrim count' : 'Jumlah jamaah'} ({pkg.min_pilgrims}–{pkg.max_pilgrims})</Text>
          <TextInput
            value={pilgrimCount}
            onChangeText={setPilgrimCount}
            keyboardType="number-pad"
            style={styles.input}
            placeholderTextColor={colors.textMuted}
          />
          <Text style={styles.label}>{isEn ? 'Affiliate code (optional)' : 'Kode affiliate (opsional)'}</Text>
          <TextInput
            value={affiliateCode}
            onChangeText={setAffiliateCode}
            autoCapitalize="characters"
            style={styles.input}
            placeholder={isEn ? 'Referral code' : 'Kode referral'}
            placeholderTextColor={colors.textMuted}
          />
        </Card>

        <Button label={isEn ? 'Submit request' : 'Kirim permintaan'} onPress={handleSubmit} loading={submitting} />
        <Text style={styles.consentHint}>
          {isEn ? 'By submitting, you agree that the muthowif will review your request (Pending status).' : 'Dengan mengajukan, Anda setuju muthowif akan meninjau permintaan (status Menunggu).'}
        </Text>
      </ScrollView>

      <TermsConsentSheet
        visible={tcOpen}
        agreed={tcAgree}
        loading={submitting}
        onClose={() => setTcOpen(false)}
        onAgreeChange={setTcAgree}
        onConfirm={() => {
          if (!tcAgree) return;
          handleSubmit(true);
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  content: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.xxl,
    gap: spacing.md,
  },
  card: { gap: spacing.sm },
  pkgName: {
    ...typography.subtitle,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  muted: { ...typography.caption, color: colors.textMuted },
  price: {
    ...typography.subtitle,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  hint: {
    ...typography.small,
    color: colors.textMuted,
    marginTop: -spacing.xs,
  },
  label: {
    ...typography.caption,
    color: colors.textSecondary,
    fontWeight: '600',
    marginTop: spacing.sm,
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    backgroundColor: colors.card,
    color: colors.textPrimary,
    ...typography.body,
  },
  consentHint: {
    ...typography.small,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 18,
  },
});
