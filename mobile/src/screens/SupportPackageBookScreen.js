import React, { useMemo, useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import DatePickerField from '../components/DatePickerField';
import { createSupportBooking } from '../api/supportCatalog';
import { useAuth } from '../context/AuthContext';
import Button from '../ui/Button';
import Card from '../ui/Card';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { notifyError, notifySuccess } from '../utils/feedback';

export default function SupportPackageBookScreen({ navigation, route }) {
  const { token } = useAuth();
  const { id, startsAt: initialStartsAt, startsAtDate, packagePreview } = route.params || {};
  const pkg = packagePreview || {};

  const [startsAt, setStartsAt] = useState(initialStartsAt || startsAtDate || '');
  const [pilgrimCount, setPilgrimCount] = useState(String(pkg.min_pilgrims || 1));
  const [affiliateCode, setAffiliateCode] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const handleSubmit = async () => {
    const count = Number(pilgrimCount);
    const min = Number(pkg.min_pilgrims || 1);
    const max = Number(pkg.max_pilgrims || 500);

    if (!startsAt) {
      Alert.alert('Validasi', 'Pilih tanggal & jam layanan');
      return;
    }
    if (!count || count < min || count > max) {
      Alert.alert('Validasi', `Jumlah jamaah harus ${min}–${max}`);
      return;
    }

    setSubmitting(true);
    try {
      const result = await createSupportBooking(token, {
        support_package_id: id,
        starts_at: startsAt,
        pilgrim_count: count,
        affiliate_code: affiliateCode.trim() || null,
      });
      notifySuccess(result.message || 'Permintaan terkirim');
      navigation.getParent()?.navigate('BookingsTab', {
        screen: 'BookingDetail',
        params: { bookingId: result.booking_id },
      });
    } catch (err) {
      notifyError(err.message || 'Gagal membuat booking');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.flex}>
      <ScreenHeader title="Pesan paket" subtitle={pkg.name} onBack={() => navigation.goBack()} />
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Card style={styles.card}>
          <Text style={styles.pkgName}>{pkg.name}</Text>
          <Text style={styles.muted}>{pkg.muthowif?.name}</Text>
          <Text style={styles.price}>{formatIdr(pkg.price)}</Text>
        </Card>

        <Card style={styles.card}>
          <DatePickerField
            label="Tanggal & jam mulai"
            mode="datetime"
            value={startsAt}
            onChange={setStartsAt}
            placeholder="Pilih tanggal & jam"
            minimumDate={today}
          />
          <Text style={styles.hint}>Layanan dimulai pada waktu ini.</Text>
          <Text style={styles.label}>Jumlah jamaah ({pkg.min_pilgrims}–{pkg.max_pilgrims})</Text>
          <TextInput
            value={pilgrimCount}
            onChangeText={setPilgrimCount}
            keyboardType="number-pad"
            style={styles.input}
            placeholderTextColor={colors.textMuted}
          />
          <Text style={styles.label}>Kode affiliate (opsional)</Text>
          <TextInput
            value={affiliateCode}
            onChangeText={setAffiliateCode}
            autoCapitalize="characters"
            style={styles.input}
            placeholder="Kode referral"
            placeholderTextColor={colors.textMuted}
          />
        </Card>

        <Button label="Kirim permintaan" onPress={handleSubmit} loading={submitting} />
      </ScrollView>
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
});
